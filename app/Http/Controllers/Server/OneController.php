<?php

namespace App\Http\Controllers\Server;

use App\Classes\Connector\SSHConnector;
use App\Classes\Connector\WinRMConnector;
use App\Extension;
use App\Http\Controllers\Controller;
use App\Key;
use App\Notification;
use App\Permission;
use App\Script;
use App\Server;
use App\ServerLog;
use App\User;
use App\Widget;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use View;

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

        if (server()->type == "windows_powershell") {
            if (is_file(env('KEYS_PATH') . 'windows/' . auth()->id() . server()->id)) {
                unlink(env('KEYS_PATH') . 'windows/' . auth()->id() . server()->id);
            }

            Key::where('server_id', server()->id)->delete();
        }

        // If server has key, simply delete it.
        if (server()->type == "linux_ssh") {
            Key::where('server_id', server()->id)->delete();
        }

        // Delete the Server Object.
        server()->delete();
        Notification::new(
            __("Bir sunucu silindi."),
            "notify",
            __(":server (:ip) isimli sunucu silindi.", ["server" => server()->name, "ip" => server()->ip_address])
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

    public function hostname()
    {
        // Obtain Script from Database
        $script = Script::where('unique_code', 'server_set_hostname')->first();

        // Check If Script Exists
        if (!$script) {
            return respond("Hostname değiştirme betiği bulunamadı.", 201);
        }

        if (!Permission::can(auth()->id(), 'script', 'id',$script->id)) {
            abort(504, "'" . $script->name . "' betiğini çalıştırmak için yetkiniz yok.");
        }

        // Simply run that script on server.
        $output = server()->runScript($script, "'" . \request('hostname') . "'");

        // Forward request.
        return respond("Hostname guncellendi", 200);
    }

    public function grant()
    {

        // Find user from email.
        $user = User::where('email', request('email'))->first();

        // If user not exists, cancel.
        if ($user == null) {
            return respond("Kullanıcı bulunamadı.", 404);
        }

        // If user somehow typed same email, warn user.
        if ($user == auth()->user()) {
            return respond("Bu email adresi size ait.", 201);
        }

        // Give User a permission to use this server.
        $permissions = Permission::where('user_id', $user->id)->first();
        $user_servers = (Array)$permissions->server;
        array_push($user_servers, server()->id);
        $permissions->server = $user_servers;

        // Lastly, save all information.
        $permissions->save();

        if (server()->type == "linux_ssh") {
            // Generate key for user.
            $flag = Key::initWithKey(server()->key->username, server()->key->id, server()->ip_address,
                server()->port, auth()->id(), $user->id);

            // Check if key initialized successfully.
            if (!$flag) {
                return respond("SSH anahtar hatası, lütfen yönetinizle iletişime geçin.", 201);
            }

            // Built key object for user.
            $key = new Key([
                "name" => server()->key->name,
                "username" => server()->key->username,
                "server_id" => server()->id
            ]);

            $key->user_id = $user->id;

            $key->save();
        }

        // Forward request.
        return respond("Yetki başarıyla verildi.");
    }

    public function revoke()
    {
        // Check if user owns the server or admin. If not, abort.
        if (server()->user_id != auth()->id() && !auth()->user()->isAdmin()) {
            return respond("Bu işlem için yetkiniz yok.", 201);
        }

        // Find User
        Permission::revoke(request('user_id'), 'server','id', server()->user_id);

        return respond("Yetki başarıyla alındı.", 200);
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
        DB::table("server_extensions")->insert([
            "id" => Str::uuid(),
            "server_id" => server()->id,
            "extension_id" => extension()->id
        ]);

        return respond('Servis başarıyla eklendi.');

//        if (array_key_exists("install", $extension->views)) {
//            return respond("Kurulum betiği bulunamadığı için işlem iptal edildi.", 201);
//        }
//
//        // Get Install script from extension.
//        $script = Script::where('unique_code', $extension->views["install"])->first();
//
//        //Just a double check if script is not installed, warn user.
//        if (!$script) {
//            return respond("Kurulum betiği bulunamadığı için işlem iptal edildi.", 201);
//        }
//
//        // Create a notification to inform user.
//        $notification = Notification::new(
//            __("Servis Yükleniyor."),
//            "onhold",
//            __(":server isimli sunucuda :new kuruluyor.", ["server" => server()->name, "new" => $extension->name])
//        );
//
//        // Create and dispatch the job immediately.
//        $job = new InstallService($script, server(), \request('domain') . " "
//            . \request('interface'), auth()->id(), $notification, $extension);
//        dispatch($job);
//
//        // Forward request and inform user.
//        return respond("Kurulum talebi başarıyla alındı. Gelişmeleri bildirim üzerinden takip edebilirsiniz.");
    }

    public function update()
    {
        Notification::new(
            __("Server Adı Güncellemesi"),
            "notify",
            __(":old isimli sunucu adı :new olarak değiştirildi.", ["old" => server()->name, "new" => request('name')])
        );

        $output = server()->update([
            "name" => request('name'),
            "control_port" => request('control_port'),
            "city" => request('city'),
            "ip_address" => request('ip_address')
        ]);
        return [
            "result" => 200,
            "data" => $output
        ];
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
        if ($flag == "1\n") {
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
            $disk = round(floatval(server()->run("(1 - (Get-WmiObject -Class Win32_logicalDisk | ? {\\\$_.DriveType -eq '3'}).FreeSpace / (Get-WmiObject -Class Win32_logicalDisk | ? {\\\$_.DriveType -eq '3'}).Size) * 100")),2);
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
        return respond($services);
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

    public function updatePackage()
    {
        if(server()->type == "linux_ssh"){
            $raw = server()->run('DEBIAN_FRONTEND=noninteractive '.sudo().'apt-get -o Dpkg::Progress-Fancy="0" -o Dpkg::Use-Pty=0 --only-upgrade install '.request("package_name")." -y --fix-missing 2>&1 | sudo tee -a /tmp/".request("package_name").".txt > /dev/null 2>&1 & echo $!");
            \Session::put(server()->id.request("package_name"), intval(trim($raw)) + 1 );
            ServerLog::new(__('Paket Güncelleme: :package_name', ['package_name' => request("package_name")]), __(':package_name paketi için güncelleme isteği gönderildi.', ['package_name' => request("package_name")]));
        }elseif (server()->type == "windows_powershell"){
            $raw = "";
        }
        return $raw;
    }

    public function checkUpdate()
    {
        if(server()->type == "linux_ssh"){
            $pid = session(server()->id.request("package_name"));
            if(empty($pid)){
                ServerLog::new(__('Paket Güncelleme: :package_name', ['package_name' => request("package_name")]), __(':package_name paketi bir hata nedeniyle takip edilemedi.', ['package_name' => request("package_name")]));
                return respond([
                    "status" => __(":package_name paketi bir hata nedeniyle takip edilemiyor.", ['package_name' => request("package_name")]),
                    "output" => ""
                ]);
            }
            $output = trim(server()->run('[ -d "/proc/'.$pid.'" ] && echo "YES" || echo "NO"'));
            $command_output = server()->run(sudo().'cat /tmp/'.request("package_name"). '.txt 2> /dev/null');
            server()->run(sudo().'truncate -s 0 /tmp/'.request("package_name"). '.txt');
            if($output === "NO"){
                $output = server()->run(sudo().'apt list --upgradable 2>/dev/null | grep '.request("package_name"));
                if(empty($output)){
                    ServerLog::new(__('Paket Güncelleme: :package_name', ['package_name' => request("package_name")]), __(':package_name paketi paketi başarıyla kuruldu.', ['package_name' => request("package_name")]));
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
    
    public function updateList()
    {
        if(server()->type == "linux_ssh"){
            $updates = [];
            $raw = server()->run(sudo()."apt-get -qq update 2> /dev/null > /dev/null; ".sudo()."apt list --upgradable 2>/dev/null | sed '1,1d'");
            foreach (explode("\n", $raw) as $package) {
                if ($package == "") {
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
                            "icon" => "fa-refresh"
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

        $key = new Key([
           "username" => "liman",
            "user_id" => auth()->user()->id,
            "server_id" => server()->id

        ]);

        $key->save();

        // Init key with parameters.
        if(server()->type == "linux"){
            try{
                $flag = SSHConnector::create(server(),request('username'),request('password'),auth()->id(),$key);
            }catch (\Exception $exception){
                $flag = "Sunucuya bağlanılamadı.";
            }
        }
        if(server()->type == "windows"){
            try{
                $flag = WinRMConnector::create(server(),request('username'),request('password'),auth()->id(),$key);
            }catch (\Exception $exception){
                $flag = $exception->getMessage();
            }
        }

        if($flag != "OK"){
            $key->delete();
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
}
