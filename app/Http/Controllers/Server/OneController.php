<?php

namespace App\Http\Controllers\Server;

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
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Str;

class OneController extends Controller
{
    public function one()
    {
        if (!\server()) {
            abort(504, "Sunucu Bulunamadı.");
        }

        // Determine if request should be considered authorized or unauthorized.
        return (server()->type == "linux_ssh" || server()->type == "windows_powershell")
            ? $this->authorized() : $this->unauthorized();
    }

    public function remove()
    {
        // Obtain Server Object
        $server = Server::where('_id', request('server_id'))->first();

        // Check if authenticated user is owner or admin.
        if ($server->user_id != auth()->id() && !auth()->user()->isAdmin()) {
            // Throw error
            return respond("Yalnızca kendi sunucunuzu silebilirsiniz.", 202);
        }

        if ($server->type == "windows_powershell") {
            if (is_file(env('KEYS_PATH') . 'windows/' . auth()->id() . server()->id)) {
                unlink(env('KEYS_PATH') . 'windows/' . auth()->id() . server()->id);
            }

            Key::where('server_id', $server->_id)->delete();
        }

        // If server has key, simply delete it.
        if ($server->type == "linux_ssh") {
            Key::where('server_id', $server->_id)->delete();
        }

        Widget::where([
            "server_id" => $server->_id
        ])->delete();

        // Delete the Server Object.
        $server->delete();

        // Redirect user to servers home page.
        return respond(route('servers'), 300);
    }

    public function serviceCheck()
    {
        // Check if requested service is running on server.
        $output = server()->isRunning(request('service'));

        // Return the button class name ~ color to update client.
        if ($output == "active\n") {
            return respond('btn-success');
        } else if ($output === "inactive\n") {
            return respond('btn-secondary');
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

        if (!Permission::can(auth()->id(), 'script', $script->_id)) {
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
        $permissions = Permission::where('user_id', $user->_id)->first();
        $user_servers = (Array)$permissions->server;
        array_push($user_servers, server()->_id);
        $permissions->server = $user_servers;

        // Lastly, save all information.
        $permissions->save();

        if (server()->type == "linux_ssh") {
            // Generate key for user.
            $flag = Key::initWithKey(server()->key->username, server()->key->_id, server()->ip_address,
                server()->port, auth()->id(), $user->_id);

            // Check if key initialized successfully.
            if (!$flag) {
                return respond("SSH anahtar hatası, lütfen yönetinizle iletişime geçin.", 201);
            }

            // Built key object for user.
            $key = new Key([
                "name" => server()->key->name,
                "username" => server()->key->username,
                "server_id" => server()->_id
            ]);

            $key->user_id = $user->_id;

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
        Permission::revoke(request('user_id'), 'server', server()->user_id);

        return respond("Yetki başarıyla alındı.", 200);
    }

    public function terminal()
    {
        $server = server();
        $client = new Client([
            'verify' => false,
            'cookies' => true
        ]);
        try {
            $response = $client->request('GET', 'https://localhost:4433');
            preg_match_all('/(?<=<input type=\"hidden\" name=\"_xsrf\" value=\")(.*)(?=\")/', $response->getBody()->getContents(), $output_array);
            $limanKey = Str::random();
            $response = $client->request('POST', 'https://localhost:4433', [
                "multipart" => [
                    [
                        "name" => "hostname",
                        "contents" => $server->ip_address,
                    ],
                    [
                        "name" => "port",
                        "contents" => "$server->port"
                    ],
                    [
                        "name" => "username",
                        "contents" => serverKey()->username
                    ],
                    [
                        "name" => "_xsrf",
                        "contents" => $output_array[0][0]
                    ],
                    [
                        "name" => "password",
                        "contents" => env('APP_KEY') . auth()->id()
                    ],
                    [
                        "name" => "privatekey",
                        "contents" => fopen(env('KEYS_PATH') . DIRECTORY_SEPARATOR . "linux" . DIRECTORY_SEPARATOR . auth()->id(), 'r')
                    ],
                    [
                        "name" => "limanKey",
                        "contents" => $limanKey
                    ]
                ],
            ]);
        } catch (GuzzleException $e) {
            return respond('Web SSH Servisi Çalışmıyor.', 201);
        }
        // First, request page to get _xsrf value.

        $json = json_decode($response->getBody()->getContents());
        return response()->view('terminal.index', [
            "id" => $json->id,
            "limanKey" => $limanKey
        ])->withCookie(cookie('_xsrf', $client->getConfig('cookies')->toArray()[0]["Value"]));
    }

    public function service()
    {
        // Retrieve Service name from extension.
        $service = Extension::where('name', 'like', request('extension'))->first()->service;

        $output = server()->run("sudo systemctl " . request('action') . ' ' . $service);
        return [
            "result" => 200,
            "data" => $output
        ];
    }

    public function enableExtension()
    {
        // Retrieve extension object.
        $extension = Extension::where('_id', \request('extension_id'))->first();

        $extensions_array = server()->extensions;
        $extensions_array[$extension->_id] = [];
        server()->extensions = $extensions_array;
        server()->save();
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
        $server = Server::where('_id', request('server_id'))->first();
        Notification::new(
            __("Server Adı Güncellemesi"),
            "notify",
            __(":old isimli sunucu adı :new olarak değiştirildi.", ["old" => $server->name, "new" => request('name')])
        );

        $output = $server->update([
            "name" => request('name'),
            "control_port" => request('control_port'),
            "city" => request('city')
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
            ServerLog::new("Dosya Yükleme " . request('path'), "Sunucuya dosya yüklendi\n" . request('path') . " ", server()->_id, auth()->id());
            return respond("Dosya başarıyla yüklendi.");
        }
        ServerLog::new("Dosya Yükleme " . request('path'), "Sunucuya dosya yüklenemedi\n" . request('path') . " ", server()->_id, auth()->id());
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

    private function authorized()
    {
        return view('server.one_auth', [
            "hostname" => server()->run("hostname"),
            "server" => server(),
            "installed_extensions" => $this->installedExtensions(),
            "available_extensions" => $this->availableExtensions(),
        ]);
    }

    private function unauthorized()
    {
        return view('server.one', [
            "installed_extensions" => $this->installedExtensions(),
            "available_extensions" => $this->availableExtensions(),
            "server" => server()
        ]);
    }

    private function availableExtensions()
    {
        if (auth()->user()->isAdmin()) {
            return Extension::all()->whereNotIn('_id', array_keys(server()->extensions));
        }
        return Extension::find(Permission::get(auth()->id(), 'extension'))->whereNotIn('_id', array_keys(server()->extensions));
    }

    private function installedExtensions()
    {
        if (auth()->user()->isAdmin()) {
            return Extension::find(array_keys(server()->extensions));
        }
        return Extension::find(Permission::get(auth()->id(), 'extension'))->whereIn('_id', array_keys(server()->extensions));
    }

    public function favorite()
    {
        $favorites = auth()->user()->favorites;
        if (!$favorites) {
            $favorites = [];
        }
        $favorites = array_unique($favorites);
        if (request('action') == "true") {
            array_push($favorites, server()->_id);
        } else {
            $key = array_search(server()->_id, $favorites);
            unset($favorites[$key]);
        }
        auth()->user()->favorites = array_values($favorites);
        auth()->user()->save();

        return respond("Düzenlendi.", 200);
    }

    public function stats()
    {
        if(server()->type == "linux_ssh"){
            $disk = substr(server()->run('df -h | grep ^/dev ', false), 0, -1);
            $ram = server()->run("free -m | awk '/Mem:/ { total=($6/$2)*100 } END { printf(\"%3.1f\", total) }'", false);
            $cpu = substr(server()->run("grep 'cpu ' /proc/stat | awk '{usage=($2+$4)*100/($2+$4+$5)} END {print usage}'", false), 0, -1);
        }elseif (server()->type == "windows_powershell"){
            $cpu = substr(server()->run("Get-WmiObject win32_processor | Measure-Object -property LoadPercentage -Average | Select Average"),23,-3);
            $disk = round(floatval(server()->run("(Get-WmiObject -Class Win32_logicalDisk | ? {\\\$_.DriveType -eq '3'}).FreeSpace / (Get-WmiObject -Class Win32_logicalDisk | ? {\\\$_.DriveType -eq '3'}).Size * 100")),2);
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
            "cpu" => $cpu
        ];
    }
}
