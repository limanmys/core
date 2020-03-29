<?php

namespace App\Http\Controllers\Server;

use App\Classes\Connector\SSHConnector;
use App\Classes\Connector\WinRMConnector;
use App\ConnectorToken;
use App\Extension;
use App\Http\Controllers\Controller;
use App\Notification;
use App\ServerLog;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use View;
use GuzzleHttp\Client;
use App\UserSettings;

class OneController extends Controller
{
    public function one()
    {
        if (!\server()) {
            abort(504, "Sunucu Bulunamadı.");
        }
        if(server()->type == "linux_ssh" || server()->type == "windows_powershell"){
          View::share('hostname', server()->run("hostname"));
        }
        return view('server.one', [
            "server" => server(),
            "installed_extensions" => $this->installedExtensions(),
            "available_extensions" => $this->availableExtensions(),
        ]);
    }

    public function remove()
    {
        // Check if authenticated user is owner or admin.
        if (server()->user_id != auth()->id() && !auth()->user()->isAdmin()) {
            // Throw error
            return respond("Yalnızca kendi sunucunuzu silebilirsiniz.", 202);
        }
        $server = server();
        // Delete the Server Object.
        server()->delete();
        Notification::new(
            __("Bir sunucu silindi."),
            "notify",
            __(":server (:ip) isimli sunucu silindi.", ["server" => $server->name, "ip" => $server->ip_address])
        );
        // Redirect user to servers home page.
        return respond(route('servers'), 300);
    }

    public function serviceCheck()
    {
        if(is_numeric(extension()->service)){
            $status = @fsockopen(server()->ip_address,extension()->service,$errno,$errstr,(intval(env('SERVER_CONNECTION_TIMEOUT')) / 1000));
            $flag = is_resource($status);            
        }else{
            $flag = server()->isRunning(extension()->service);
        }
        // Return the button class name ~ color to update client.
        if ($flag) {
            return respond('btn-success');
        } else {
            return respond('btn-danger');
        }
    }

    public function service()
    {
        // Retrieve Service name from extension.
        $service = Extension::where('name', 'like', request('extension'))->first()->service;

        $output = server()->run(sudo()."systemctl " . request('action') . ' ' . $service);
        return [
            "result" => 200,
            "data" => $output
        ];
    }

    public function enableExtension()
    {
        if(!auth()->user()->id == server()->user_id && !auth()->user()->isAdmin()){
            return respond("Bu islemi yalnizca sunucu sahibi ya da bir yonetici yapabilir.");
        }
        $extensions = json_decode(request('extensions'));

        foreach($extensions as $extension){
            $data = [
                "server_id" => server()->id,
                "extension_id" => $extension
            ];
            if(DB::table("server_extensions")->where($data)->doesntExist()){
                $data["id"] = Str::uuid();
                DB::table("server_extensions")->insert($data);
            }
        }
        return respond('Eklenti başarıyla eklendi.');
   }

    public function update()
    {
        if(server()->name !== request('name') ){
            Notification::new(
                __("Server Adı Güncellemesi"),
                "notify",
                __(":old isimli sunucu adı :new olarak değiştirildi.", ["old" => server()->name, "new" => request('name')])
            );
        }

        $output = server()->update([
            "name" => request('name'),
            "control_port" => request('control_port'),
            "ip_address" => request('ip_address'),
            "city" => request('city')
        ]);

        ConnectorToken::clear();
        
        return [
            "result" => 200,
            "data" => $output
        ];
    }

    public function terminal()
    {
        $client = new Client(['verify' => false]);

        $xsrfRequest = $client->request('GET','http://127.0.0.1:8888/');
        $re = '/<input(.*?)name=\"_xsrf\"(.*)value=\"(.*?)\"/i';
        preg_match($re, $xsrfRequest->getBody(), $matches, PREG_OFFSET_CAPTURE, 0);
        $token = $matches[3][0];

        $r = $client->request('POST', 'http://127.0.0.1:8888/', [
            'form_params' => [
                "hostname" => server()->ip_address,
                "username" => extensionDb("clientUsername"),
                "password" => extensionDb("clientPassword"),
                "term" => "xterm-256color",
                "_xsrf" => $token
            ],
            "cookies" => CookieJar::fromArray(["_xsrf" => $token], '127.0.0.1')
        ]);
        if(json_decode($r->getBody()) && json_last_error() == JSON_ERROR_NONE){
            $json = json_decode($r->getBody());
            return response()->view('terminal.index',["id" => $json->id, "token" => $token])->withCookie('_xsrf',$token);
        }else{
            return respond("Bilinmeyen bir hata oluştu!",201);
        }
    }

    public function upload()
    {

        // Store file in /tmp directory.
        request()->file('file')->move('/tmp/', request()->file('file')->getClientOriginalName());

        // Send file to the server.
        server()->putFile('/tmp/' . request()->file('file')->getClientOriginalName(), \request('path'));

        // Build query to check if file exists in server to validate.
        $query = "(ls " . request('path') . " >> /dev/null 2>&1 && echo 1) || echo 0";
        $flag = server()->run($query, false);

        // Respond according to the flag.
        if ($flag == "1") {
//            ServerLog::new("Dosya Yükleme " . request('path'), "Sunucuya dosya yüklendi\n" . request('path') . " ", server()->id, auth()->id());
            return respond("Dosya başarıyla yüklendi.");
        }
//        ServerLog::new("Dosya Yükleme " . request('path'), "Sunucuya dosya yüklenemedi\n" . request('path') . " ", server()->id, auth()->id());
        return respond('Dosya yüklenemedi.', 201);
    }

    public function download()
    {
        // Generate random file name
        $file = Str::random();
        server()->getFile(request('path'), '/tmp/' . $file);

        // Extract file name from path.
        $file_name = explode("/", request('path'));

        // Send file to the user then delete it.
        return response()->download('/tmp/' . $file, $file_name[count($file_name) - 1])->deleteFileAfterSend();
    }


    private function availableExtensions()
    {
        return Extension::getAll()->whereNotIn("id",DB::table("server_extensions")->where([
            "server_id" => server()->id
        ])->pluck("extension_id")->toArray());
    }

    private function installedExtensions()
    {
        return server()->extensions();
    }

    public function favorite()
    {
        $current = DB::table("user_favorites")->where([
            "user_id" => auth()->user()->id,
            "server_id" => server()->id
        ])->first();

        if($current && request("action") != "true"){
            DB::table("user_favorites")->where([
                "user_id" => auth()->user()->id,
                "server_id" => server()->id
            ])->delete();
        }else if(!$current){
            DB::table("user_favorites")->insert([
                "id" => Str::uuid(),
                "server_id" => server()->id,
                "user_id" => auth()->user()->id,
            ]);
        }

        return respond("Düzenlendi.", 200);
    }

    public function stats()
    {
        if(server()->type == "linux_ssh"){
            $disk = server()->run('df -h / | grep /',false);
            preg_match("/(\d+)%/",$disk,$test);
            $disk = $test[1];
            $ram = server()->run("free -t | awk 'NR == 2 {printf($3/$2*100)}'", false);
            $cpu = server()->run("vmstat 1 1|tail -1|awk '{print $15}'", false);
            $cpu = 100 - intval(substr($cpu,0,-1));
        }elseif (server()->type == "windows_powershell"){
            $cpu = substr(server()->run("Get-WmiObject win32_processor | Measure-Object -property LoadPercentage -Average | Select Average"),23,-3);
            $disk = round(floatval(server()->run("(1 - (Get-WmiObject -Class Win32_logicalDisk | ? {\$_.DriveType -eq '3'}).FreeSpace / (Get-WmiObject -Class Win32_logicalDisk | ? {\$_.DriveType -eq '3'}).Size) * 100")),2);
            try{
                $usedRam = intval(substr(server()->run("Get-Counter '\Memory\Available MBytes'"),390,-335));
                $totalRam = intval(server()->run("[math]::Round((Get-WmiObject Win32_ComputerSystem).totalphysicalmemory / (1024 * 1024))"));
                $ram = round($usedRam / $totalRam * 100,2);
            }catch (\Exception $exception){
                $ram = "0";
            }
        }
        return [
            "disk" => $disk,
            "ram" => $ram,
            "cpu" => $cpu,
            "time" => Carbon::now()->format("H:i:s")
        ];
    }

    public function getLocalUsers()
    {
        if(server()->type == "linux_ssh"){
            $output = server()->run("cut -d: -f1,3 /etc/passwd | egrep ':[0-9]{4}$' | cut -d: -f1");
            $output = trim($output);
            if(empty($output)){
                $users = [];
            }else{
                $output = explode("\n", $output);
                foreach($output as $user){
                    $users[] = [
                        "user" => $user
                    ];
                }
            }
        }
        return view('table', [
            "value" => $users,
            "title" => [
                "Kullanıcı Adı"
            ],
            "display" => [
                "user"
            ]
        ]);
    }

    public function addLocalUser()
    {
        if(server()->type == "linux_ssh"){
            $user_name = request("user_name");
            $user_password = request("user_password");
            $user_password_confirmation = request("user_password_confirmation");
            if($user_password !== $user_password_confirmation){
                return respond("Şifreler uyuşmuyor!", 201);
            }
            $output = trim(server()->run(sudo()."bash -c 'useradd --no-user-group -p $(openssl passwd -1 $user_password) $user_name -s \"/bin/bash\"' &> /dev/null && echo 1 || echo 0"));
            if($output == "0"){
                return respond("Kullanıcı eklenemedi!", 201);
            }
            return respond("Kullanıcı başarıyla eklendi!", 200);
        }
        
    }

    public function getLocalGroups()
    {
        if(server()->type == "linux_ssh"){
            $output = server()->run("getent group | cut -d ':' -f1");
            $output = trim($output);
            if(empty($output)){
                $groups = [];
            }else{
                $output = explode("\n", $output);
                foreach($output as $group){
                    $groups[] = [
                        "group" => $group
                    ];
                }
                $groups = array_reverse($groups);
            }
        }
        return view('table', [
            "value" => $groups,
            "title" => [
                "Grup Adı"
            ],
            "display" => [
                "group"
            ],
            "onclick" => "localGroupDetails"
        ]);
    }

    public function getLocalGroupDetails()
    {
        if(server()->type == "linux_ssh"){
            $group = request("group");
            $output = trim(server()->run(sudo()."getent group $group | cut -d ':' -f4"));
        
            $users = [];
            if(!empty($output)){
                $users = array_map(function($value){
                    return(["name" => $value]);
                }, explode(",", $output));
            }
        }
        return view('table', [
            "value" => $users,
            "title" => [
                "Kullanıcı Adı"
            ],
            "display" => [
                "name"
            ]
        ]);
    }

    public function addLocalGroup()
    {
        if(server()->type == "linux_ssh"){
            $group_name = request("group_name");
            $output = trim(server()->run(sudo()."groupadd $group_name &> /dev/null && echo 1 || echo 0"));
            if($output == "0"){
                return respond("Grup eklenemedi!", 201);
            }
            return respond("Grup başarıyla eklendi!", 200);
        }
    }

    public function addLocalGroupUser()
    {
        if(server()->type == "linux_ssh"){
            $group = request("group");
            $user = request("user");
            $output = trim(server()->run(sudo()."usermod -a -G $group $user &> /dev/null && echo 1 || echo 0"));
            if($output != "1"){
                return respond("Kullanıcı gruba eklenemedi!", 201);
            }
            return respond("Kullanıcı gruba başarıyla eklendi!");
        }
    }

    public function getSudoers()
    {
        if(server()->type == "linux_ssh"){
            $output = trim(server()->run(sudo()."cat /etc/sudoers /etc/sudoers.d/* | grep -v '^#\|^Defaults' | sed '/^$/d' | awk '{ print $1 \"*-*\" $2 \" \" $3 }'"));
        
            $sudoers = [];
            if(!empty($output)){
                $sudoers = array_map(function($value){
                    $fetch = explode("*-*", $value);
                    return(["name" => $fetch[0], "access" => $fetch[1]]);
                }, explode("\n", $output));
            }
        }
        return view('table', [
            "value" => $sudoers,
            "title" => [
                "İsim", "Yetki"
            ],
            "display" => [
                "name", "access"
            ],
            "menu" => [
                "Sil" => [
                    "target" => "deleteSudoers",
                    "icon" => "fa-trash"
                ],
            ]
        ]);
    }

    public function addSudoers()
    {
        if(server()->type == "linux_ssh"){
            $name = request("name");
            $name = str_replace(" ", "\\x20", $name);
            $checkFile = server()->run("[ -f '/etc/sudoers.d/$name' ] && echo 1 || echo 0");
            if($checkFile == "1"){
                return respond("Bu isimde bir kullanıcı zaten ekli!", 201);
            }
            $output = trim(server()->run(sudo()."bash -c 'echo \"$name ALL=(ALL:ALL) ALL\" | tee /etc/sudoers.d/$name' &> /dev/null && echo 1 || echo 0"));
            if($output == "0"){
                return respond("Tam yetkili kullanıcı eklenemedi!", 201);
            }
            return respond("Tam yetkili kullanıcı başarıyla eklendi!", 200);
        }
    }

    public function deleteSudoers()
    {
        //TODO: check here for bugs
        if(server()->type == "linux_ssh"){
            $name = request("name");
            $name = str_replace(" ", "\\x20", $name);
            $output = trim(server()->run(sudo()."bash -c 'if [ -f \"/etc/sudoers.d/$name\" ]; then rm /etc/sudoers.d/$name && echo 1 || echo 0; else echo 0; fi'"));
            if($output == "0"){
                return respond("Tam yetkili kullanıcı silinemedi!", 201);
            }
            return respond("Tam yetkili kullanıcı başarıyla silindi!", 200);
        }
    }

    public function serviceList()
    {
        $services = [];
        if(server()->type == "linux_ssh"){
            $raw = server()->run("systemctl list-units | grep service | awk '{print $1 \":\"$2\" \"$3\" \"$4\":\"$5\" \"$6\" \"$7\" \"$8\" \"$9\" \"$10}'",false);
            foreach (explode("\n", $raw) as $package) {
                if ($package == "") {
                    continue;
                }
                $row = explode(":", trim($package));
                try {
                    array_push($services, [
                        "name" => $row[0],
                        "description" => $row[2],
                        "status" => $row[1]
                    ]);
                } catch (Exception $exception) {
                }
            }
        }elseif (server()->type == "windows_powershell"){
            $rawServices = server()->run("(Get-WmiObject win32_service | select Name, DisplayName, State, StartMode) -replace '\s\s+',':'");
            $services = [];
            foreach (explode('}',$rawServices) as $service){
                $row = explode(";",substr($service,2));
                if($row[0] == ""){
                    continue;
                }
                try{
                    array_push($services,[
                        "name" => trim(explode('=',$row[0])[1]),
                        "description" => trim(explode('=',$row[1])[1]),
                        "status" => trim(explode('=',$row[2])[1]),
                    ]);
                }catch (Exception $exception){
                }
            }
        }else{
            return respond("Bu sunucudaki servisleri goremezsiniz.",403);
        }
        return view('table',[
            "id" => "servicesTable",
            "value" => $services,
            "title" => [
                "Servis Adı" , "Aciklamasi" , "Durumu"
            ],
            "display" => [
                "name" , "description", "status"
            ],
            "menu" => [
                "Baslat" => [
                    "target" => "startService",
                    "icon" => "fa-play"
                ],
                "Durdur" => [
                    "target" => "stopService",
                    "icon" => "fa-stop"
                ],
                "Yeniden Baslat" => [
                    "target" => "restartService",
                    "icon" => "fa-sync-alt"
                ]

            ],
        ]);
    }

    public function getLogs()
    {
        return view('l.table',[
            "value" => ServerLog::retrieve(true),
            "title" => [
                "Başlık" , "Açıklama" , "Kullanıcı"
            ],
            "display" => [
                "command" , "output" , "username"
            ],
        ]);
    }

    public function installPackage()
    {
        if(server()->type == "linux_ssh"){
            $package = request("package_name");
            $raw = server()->run(sudo()."bash -c 'DEBIAN_FRONTEND=noninteractive apt install \"$package\" -qqy >\"/tmp/".basename($package).".txt\" 2>&1 & disown && echo \$!'");
            ServerLog::new(__('Paket Güncelleme: :package_name', ['package_name' => request("package_name")]), __(':package_name paketi için güncelleme isteği gönderildi.', ['package_name' => request("package_name")]));
        }elseif (server()->type == "windows_powershell"){
            $raw = "";
        }
        return $raw;
    }

    public function checkPackage()
    {
        if(server()->type == "linux_ssh"){
            $mode = request("mode") ? request("mode") : 'update';
            $output = trim(server()->run("ps aux | grep \"apt \|dpkg \" | grep -v grep 2>/dev/null 1>/dev/null && echo '1' || echo '0'"));
            $command_output = server()->run(sudo().'cat "/tmp/'.basename(request("package_name")). '.txt" 2> /dev/null | base64');
            $command_output = base64_decode($command_output);
            server()->run(sudo().'truncate -s 0 /tmp/'.basename(request("package_name")). '.txt');
            if($output === "0"){
                $list_method = $mode == "install" ? "--installed" : "--upgradable";
                $package = request("package_name");
                if(endsWith($package, ".deb")){
                    $package = server()->run(sudo().'dpkg -I '.$package.' | grep Package: | cut -d\':\' -f2 | tr -d \'[:space:]\'');
                }
                $output = server()->run(sudo().'apt list '.$list_method.' 2>/dev/null | grep '.$package.' && echo 1 || echo 0');
                if(($mode == "update" && $output == "0") || ($mode == "install" && $output != "0")){
                    ServerLog::new(__('Paket Güncelleme: :package_name', ['package_name' => request("package_name")]), __(':package_name paketi başarıyla kuruldu.', ['package_name' => request("package_name")]));
                    return respond([
                        "status" => __(":package_name paketi başarıyla kuruldu.", ['package_name' => request("package_name")]),
                        "output" => trim($command_output)
                    ]);
                }else{
                    ServerLog::new(__('Paket Güncelleme: :package_name', ['package_name' => request("package_name")]), __(':package_name paketi kurulamadı.', ['package_name' => request("package_name")]));
                    return respond([
                        "status" => __(":package_name paketi kurulamadı.", ['package_name' => request("package_name")]),
                        "output" => trim($command_output)
                    ]);
                }
            }else{
                return respond([
                    "status" => __(":package_name paketinin kurulum işlemi henüz bitmedi.", ['package_name' => request("package_name")]),
                    "output" => trim($command_output),
                ], 400);
            }
        }elseif (server()->type == "windows_powershell"){
            $output = "";
        }
        return $output;
    }

    public function uploadDebFile()
    {
        if(server()->type == "linux_ssh"){
            $filePath = request('filePath');
            if(!$filePath){
                return respond("Dosya yolu zorunludur.",403);
            }
            server()->putFile($filePath, "/tmp/".basename($filePath));
            unlink($filePath);
            return respond("/tmp/".basename($filePath), 200);
        }else{
            return respond("Bu sunucuya deb paketi kuramazsınız.",403);
        }
    }
    
    public function updateList()
    {
        if(server()->type == "linux_ssh"){
            $updates = [];
            $raw = server()->run(sudo()."apt-get -qq update 2> /dev/null > /dev/null; ".sudo()."apt list --upgradable 2>/dev/null | sed '1,1d'");
            foreach (explode("\n", $raw) as $package) {
                if ($package == "" || strpos($package, 'List') !== false) {
                    continue;
                }
                $row = explode(" ", $package, 4);
                try {
                    array_push($updates, [
                        "name" => $row[0],
                        "version" => $row[1],
                        "type" => $row[2],
                        "status" => $row[3]
                    ]);
                } catch (\Exception $exception) {}
            }
        }elseif (server()->type == "windows_powershell"){
            
        }else{
            return respond("Bu sunucudaki güncellemeleri goremezsiniz.",403);
        }
        return [
            "count" => count($updates),
            "list"  => $updates,
            "table" => view('l.table',[
                    "id"    => "updateListTable",
                    "value" => $updates,
                    "title" => [
                        "Paket Adı" , "Versiyon" , "Tip" , "Durumu"
                    ],
                    "display" => [
                        "name" , "version", "type" , "status"
                    ],
                    "menu" => [
                        "Güncelle" => [
                            "target" => "updateSinglePackage",
                            "icon" => "fa-sync"
                        ]
                    ]
                ])->render()
        ];
    }

    public function packageList()
    {
        if(server()->type == "linux_ssh"){
            $raw = server()->run(sudo()."apt list --installed 2>/dev/null | sed '1,1d'", false);
            $packages = [];
            foreach (explode("\n", $raw) as $package) {
                if ($package == "") {
                    continue;
                }
                $row = explode(" ", $package);
                try {
                    array_push($packages, [
                        "name" => $row[0],
                        "version" => $row[1],
                        "type" => $row[2],
                        "status" => $row[3]
                    ]);
                } catch (Exception $exception) {
                }
            }
        }else{
            return respond("Bu sunucudaki paketleri goremezsiniz.",403);
        }
        return view('l.table',[
            "value" => $packages,
            "title" => [
                "Paket Adı" , "Versiyon" , "Tip" , "Durumu"
            ],
            "display" => [
                "name" , "version", "type" , "status"
            ],
        ]);
    }

    public function upgradeServer()
    {
        if(server()->type == "linux_ssh" || server()->type == "windows_powershell"){
            return respond("Bu Sunucuda yukseltme yapilamaz.",201);
        }

        // Init key with parameters.
        if(server()->type == "linux"){
            try{
                $flag = SSHConnector::create(server(),request('username'),request('password'),auth()->id(),null);
            }catch (\Exception $exception){
                $flag = "Sunucuya bağlanılamadı.";
            }
        }

        if(server()->type == "windows"){
            try{
                $flag = WinRMConnector::create(server(),request('username'),request('password'),auth()->id(),null);
            }catch (\Exception $exception){
                $flag = "test";
            }
        }

        if($flag != "OK"){
            return respond($flag,201);
        }

        if(server()->type == "linux"){
            server()->update([
                "type" => "linux_ssh"
            ]);
        }else{
            server()->update([
                "type" => "windows_powershell"
            ]);
        }

        // Add credentials
        $encKey = env('APP_KEY') . user()->id  . server()->id;
        $encrypted = openssl_encrypt(Str::random(16) . base64_encode(request('username')),'aes-256-cfb8',$encKey,0,Str::random(16));
        UserSettings::updateOrCreate([
            "user_id" => user()->id,
            "server_id" => server()->id,
            "name" => "clientUsername"
        ],[
            "value" => $encrypted
        ]);

        $encrypted = openssl_encrypt(Str::random(16) . base64_encode(request('password')),'aes-256-cfb8',$encKey,0,Str::random(16));
        UserSettings::updateOrCreate([
            "user_id" => user()->id,
            "server_id" => server()->id,
            "name" => "clientPassword"
        ],[
            "value" => $encrypted
        ]);

        return respond("Sunucu Başarıyla Yükseltildi.");
    }

    public function removeExtension()
    {
        if(server()->user_id != auth()->user()->id && !auth()->user()->isAdmin()){
            return respond("Yalnızca sunucu sahibi ya da yönetici bir eklentiyi silebilir.",201);
        }
        foreach (json_decode(request('extensions')) as $key => $value) {
          DB::table("server_extensions")->where([
              "server_id" => server()->id,
              "extension_id" => $value
          ])->delete();
        }
        return respond("Eklentiler Başarıyla Silindi");
    }

    public function startService()
    {
        if(server()->type == "linux_ssh"){
            $command = sudo()."systemctl start " . request('name');
        }else{
            $command = "Start-Service " . request("name");   
        }
        server()->run($command);
        return respond("Servis Baslatildi",200);
    }

    public function stopService()
    {
        if(server()->type == "linux_ssh"){
            $command = sudo()."systemctl stop " . request('name');
        }else{
            $command = "Stop-Service " . request("name");   
        }
        server()->run($command);
        return respond("Servis Durduruldu",200);
    }

    public function restartService()
    {
        if(server()->type == "linux_ssh"){
            $command = sudo()."systemctl restart " . request('name');
        }else{
            $command = "Restart-Service " . request("name");   
        }
        server()->run($command);
        return respond("Servis Yeniden Baslatildi",200);
    }

    public function getOpenPorts()
    {
        if(server()->type != "linux_ssh"){
            return respond("Bu sunucuda portlari kontrol edemezsiniz!",201);
        }

        $output = trim(server()->run(sudo() . "lsof -i -P -n | grep LISTEN | awk -F' ' '{print $1,$3,$5,$8,$9}'"));
        $arr = [];
        foreach(explode("\n",$output) as $line){
            $row = explode(" ",$line);
            array_push($arr,[
                "name" => $row[0],
                "username" => $row[1],
                "ip_type" => $row[2],
                "packet_type" => $row[3],
                "port" => $row[4]
            ]);
        }
        
        return respond(view('l.table',[
            "id"    => "openPortsTable",
            "value" => $arr,
            "title" => [
                "Program Adı" , "Kullanıcı" , "İp Türü" , "Paket Türü", "Port"
            ],
            "display" => [
                "name" , "username", "ip_type" , "packet_type", "port"
            ]
        ])->render());
    }
}
