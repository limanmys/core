<?php

namespace App\Http\Controllers\Server;

use App\Extension;
use App\Http\Controllers\Controller;
use App\Http\Middleware\Server;
use App\Jobs\RunScript;
use App\Key;
use App\Notification;
use App\Permission;
use App\Script;
use App\User;
use Auth;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class OneController extends Controller
{
    public function one(){
        return (request('server')->type == "linux_ssh" || request('server')->type == "windows_powershell")
            ? $this->authorized() : $this->unauthorized();
    }

    public function remove()
    {
        // Check if authenticated user is owner or admin.
        if(server()->user_id != Auth::id() && !Auth::user()->isAdmin()){
            // Throw error
            return respond("Yalnızca kendi sunucunuzu silebilirsiniz.",202);
        }
        // If server has key, simply delete it.
        if(request('server')->type == "linux_ssh"){
            request('server')->key->delete();
        }

        // Delete the Server Object.
        request('server')->delete();

        // Redirect user to servers home page.
        return respond(route('servers'),300);
    }

    public function serviceCheck()
    {
        $output = request('server')->isRunning(request('service'));
        if ($output == "active\n") {
            return respond('btn-success');
        } else if ($output === "inactive\n") {
            return respond('btn-secondary');
        } else{
            return respond('btn-danger');
        }
    }


    public function network()
    {
        $server = \request('server');
        $parameters = \request('ip') . ' ' . \request('cidr') . ' ' . \request('gateway') . ' ' . \request('interface');
        $server->systemScript('network', $parameters);
        sleep(3);
        $output = shell_exec("echo exit | telnet " . \request('ip') . " " . $server->port);
        if (!strpos($output, "Connected to " . \request('ip'))) {
            return [
                "result" => 201,
                "data" => $output
            ];
        }
        $server->update([
            'ip_address' => \request('ip')
        ]);
        Key::init($server->key["username"], request('password'), \request('ip'),
            $server->port, Auth::id());
        return [
            "result" => 200,
            "data" => $output
        ];
    }

    public function hostname()
    {
        $server = \request('server');
        $output = $server->systemScript('hostname', \request('hostname'));
        return [
            "result" => 200,
            "data" => $output
        ];
    }

    public function grant(){

        // Find user from email.
        $user = User::where('email',request('email'))->first();

        // If user not exists, cancel.
        if($user == null){
            return respond("Kullanıcı bulunamadı.",404);
        }

        // If user somehow typed same email, warn user.
        if($user == Auth::user()){
            return response("Bu email adresi size ait.",201);
        }

        // Give User a permission to use this server.
        $permissions = Permission::where('user_id',$user->_id)->first();
        $user_servers = (Array) $permissions->server;
        array_push($user_servers, request('server')->_id);
        $permissions->server = $user_servers;

        // Lastly, save all information.
        $permissions->save();

        if(request('server')->type == "linux_ssh"){
            // Generate key for user.
            Key::initWithKey(request('server')->key->username, request('server')->key->_id, request('server')->ip_address,
                request('server')->port, Auth::id(), $user->_id);

            // Built key object for user.

            $key = new Key([
                "name" => request('server')->key->name,
                "username" => request('server')->key->username,
                "server_id" => request('server')->_id
            ]);

            $key->user_id = $user->_id;

            $key->save();
        }

        return respond("Yetki başarıyla verildi.");
    }

    public function revoke(){

    }

    public function terminal(){
        $server = request('server');
        $client = new Client([
            'verify' => false,
            'cookies' => true
        ]);
        try{
            $response = $client->request('GET','https://localhost:4433');
            preg_match_all('/(?<=<input type=\"hidden\" name=\"_xsrf\" value=\")(.*)(?=\")/', $response->getBody()->getContents(), $output_array);
            $response = $client->request('POST','https://localhost:4433',[
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
                        "contents" => $server->key->username
                    ],
                    [
                        "name" => "_xsrf",
                        "contents" => $output_array[0][0]
                    ],
                    [
                        "name" => "privatekey",
                        "contents" => fopen(storage_path('keys') .
                            DIRECTORY_SEPARATOR . Auth::id(),'r')
                    ]
                ],
            ]);
        }catch (GuzzleException $e){
            return respond('Web SSH Servisi Çalışmıyor.',201);
        }
        //First, request page to get _xsrf value.

        $json = json_decode($response->getBody()->getContents());
        return response()->view('terminal.index',[
            "id" => $json->id
        ])->withCookie(cookie('_xsrf',$client->getConfig('cookies')->toArray()[0]["Value"]));
    }

    public function service()
    {
        $server = \request('server');
        $service = Extension::where('name', 'like', \request('extension'))->first()->service;
        $output = $server->run("sudo systemctl " . \request('action') . ' ' . $service);
        return [
            "result" => 200,
            "data" => $output
        ];
    }

    public function enableExtension()
    {
        $extension = Extension::where('_id', \request('extension_id'))->first();

        if(request('server')->type == "linux" || request('server')->type == "windows"){
            $extensions_array = request('server')->extensions;
            $extensions_array[$extension->_id] = [];
            request('server')->extensions = $extensions_array;
            request('server')->save();
            return respond('Servis başarıyla eklendi.');
        }

        $script = Script::where('unique_code', $extension->setup)->first();
        $server = \request('server');
        $notification = Notification::new(
            __("Servis Yükleniyor."),
            "onhold",
            __(":server isimli sunucuda :new kuruluyor.",["server"=>request('server')->name,"new"=>$extension->name])
        );
        $job = new RunScript($script, $server,\request('domain') . " "
            . \request('interface'),\Auth::user(),$notification ,$extension);
        dispatch($job);
        return respond("Kurulum talebi başarıyla alındı. Gelişmeleri bildirim üzerinden takip edebilirsiniz.");
    }

    public function update()
    {
        $server = \App\Server::where('_id',request('server_id'))->first();
        Notification::new(
            __("Server Adı Güncellemesi"),
            "notify",
            __(":old isimli sunucu adı :new olarak değiştirildi.",["old"=>$server->name,"new"=>request('name')])
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

    public function upload(){

        // Store file in /tmp directory.
        request()->file('file')->move('/tmp/',request()->file('file')->getClientOriginalName());

        // Send file to the server.
        server()->putFile('/tmp/' .request()->file('file')->getClientOriginalName(), \request('path'));

        // Build query to check if file exists in server to validate.
        $query = '[[ -f ' . request('path') .
            request()->file('file')->getClientOriginalName()  . ' ]] && echo "1" || echo "0"';
        $flag = server()->run($query);

        // Respond according to the flag.
        if($flag == "1\n"){
            return respond("Dosya başarıyla yüklendi.");
        }
        return respond('Dosya yüklenemedi.');
    }

    public function download(){

        // Generate random file name
        $file = str_random('10');
        server()->getFile(request('path'),'/tmp/' . $file);

        // Extract file name from path.
        $file_name = explode("/",request('path'));

        // Send file to the user then delete it.
        return response()->download('/tmp/' . $file, $file_name[count($file_name) -1 ])->deleteFileAfterSend();
    }

    private function authorized(){
        return view('server.one_auth', [
            "stats" => \request('server')->run("df -h"),
            "hostname" => request('server')->run("hostname"),
            "server" => \request('server'),
            "installed_extensions" => $this->installedExtensions(),
            "available_extensions" => $this->availableExtensions(),
        ]);
    }

    private function unauthorized(){
        return view('server.one',[
            "installed_extensions" => $this->installedExtensions(),
            "available_extensions" => $this->availableExtensions(),
            "server" => request('server')
        ]);
    }

    private function availableExtensions(){
        return Extension::whereNotIn('_id',array_keys(request('server')->extensions))->get();
    }

    private function installedExtensions(){
        return Extension::whereIn('_id',array_keys(request('server')->extensions))->get();
    }
}
