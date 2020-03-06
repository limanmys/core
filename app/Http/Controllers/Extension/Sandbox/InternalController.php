<?php

namespace App\Http\Controllers\Extension\Sandbox;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class InternalController extends Controller
{
    public function __construct()
    {
        checkPermissions();
    }

    /**
        * @api {post} /lmn/private/dispatchJob Dispatch Background Job
        * @apiName SandboxDispatchJob
        * @apiGroup Sandbox
        *
        * @apiParam {String} function_name Target function name to run
        * @apiParam {Array} parameters Parameters to use in function.
        * @apiParam {String} server_id Target Server Id
        * @apiParam {String} extension_id Target Extension Id
        * @apiParam {String} token Authenticated User Token
        *
        * @apiSuccess {String} result Simply returns ok string.
    */
    public function dispatchJob()
    {
        // Create a new object
        $history = new JobHistory([
            "status" => "0",
            "user_id" => user()->id,
            "server_id" => server()->id,
            "extension_id" => extension()->id,
            "job" => request('function_name')
        ]);

        // Save it into database
        $history->save();

        // Create job to work on.
        $job = (new ExtensionJob($history,server(),extension(),user(),request('function_name'),request('parameters')))->onQueue('extension_queue');
        
        // Dispatch job right away.
        $job_id = app(Dispatcher::class)->dispatch($job);

        // Update job with it's id.
        $history->job_id = $job_id;
        $history->save();
        
        return "ok";
    }

    /**
        * @api {post} /lmn/private/getJobList Get List and Status of Background Processes
        * @apiName SandboxGetJobList
        * @apiGroup Sandbox
        *
        * @apiParam {String} function_name Target function to check
        * @apiParam {String} server_id Target Server Id
        * @apiParam {String} extension_id Target Extension Id
        * @apiParam {String} token Authenticated User Token
        *
        * @apiSuccess {Array} json hold, success, fail and total counts.
    */
    public function getJobList()
    {
        // Retrieve Objects by function_name
        $all = JobHistory::where([
            "user_id" => user()->id,
            "extension_id" => extension()->id,
            "server_id" => server()->id,
            "job" => request('function_name')
        ])->get('status');

        // Simply sum up the counts
        $holdCount = $all->where('status',0)->count();
        $successCount = $all->where('status',1)->count();
        $failCount = $all->where('status',2)->count();

        // Return everything.
        return json_encode([
            "hold" => $holdCount,
            "success" => $successCount,
            "fail" => $failCount,
            "total" => $all->count()
        ]);
    }

    public function internalExtensions()
    {

    }

    /**
        * @api {post} /lmn/private/runCommandApi Run command on the server
        * @apiName SandboxRunCommand
        * @apiGroup Sandbox
        *
        * @apiParam {String} command Command to run.
        * @apiParam {String} server_id Target Server Id
        * @apiParam {String} extension_id Target Extension Id
        * @apiParam {String} token Authenticated User Token
        *
        * @apiSuccess {String} output Output of the command.
    */
    public function runCommand()
    {
        // Execute the command
        request()->request->add(['server' => server()]);
        $output = server()->run(request('command'));

        system_log(7,"EXTENSION_INTERNAL_RUN_COMMAND",[
            "extension_id" => extension()->id,
            "server_id" => server()->id
        ]);
        ServerLog::new("Komut Çalıştırma",$output);
        system_log(6,server()->id . ":" . "Komut Çalıştırma");
        
        return $output;
    }

    /**
        * @api {post} /lmn/private/runScriptApi Run script on the server
        * @apiName SandboxRunScript
        * @apiGroup Sandbox
        *
        * @apiParam {String} scriptName Script to run (inside scripts folder)
        * @apiParam {String} parameters Parameters as string -cli style-
        * @apiParam {String} runAsRoot If you wish to run script as root, simply send yes
        * @apiParam {String} server_id Target Server Id
        * @apiParam {String} extension_id Target Extension Id
        * @apiParam {String} token Authenticated User Token
        *
        * @apiSuccess {String} output Output of the script.
    */
    public function runScript()
    {
        $filePath = env("EXTENSIONS_PATH") . strtolower(extension()->name) . "/scripts/" . request("scriptName");
        if( !is_file($filePath)){
            system_log(7,"EXTENSION_INTERNAL_RUN_SCRIPT_FAILED_NOT_FOUND",[
                "extension_id" => extension()->id,
                "server_id" => request('server_id')
            ]);
            return "Betik Bulunamadi";
        }

        if (server()->type != "linux_ssh" && server()->type != "windows_powershell") {
            system_log(7,"EXTENSION_INTERNAL_RUN_COMMAND_FAILED",[
                "extension_id" => extension()->id,
                "server_id" => request('server_id')
            ]);
            return "Bu sunucuda komut çalıştıramazsınız.";
        }

        $output = server()->runScript($filePath,request("parameters"),request("runAsRoot"));

        system_log(7,"EXTENSION_INTERNAL_RUN_COMMAND",[
            "extension_id" => extension()->id,
            "server_id" => server()->id
        ]);

        return $output;
    }

    /**
        * @api {post} /lmn/private/putFileApi Send file to the server.
        * @apiName SandboxPutFile
        * @apiGroup Sandbox
        *
        * @apiParam {String} localPath Local full file path.
        * @apiParam {String} remotePath Remote full file path you wish.
        * @apiParam {String} server_id Target Server Id
        * @apiParam {String} extension_id Target Extension Id
        * @apiParam {String} token Authenticated User Token
        *
        * @apiSuccess {String} output ok or no according to status.
    */
    public function putFile()
    {
        $output = server()->putFile(request('localPath'), request('remotePath'));

        system_log(7,"EXTENSION_INTERNAL_SEND_FILE",[
            "extension_id" => extension()->id,
            "server_id" => server()->id,
            "file_name" => request('remotePath'),
        ]);

        return ($output) ? "ok" : "no";
    }

    /**
        * @api {post} /lmn/private/getFileApi Receive file from the server.
        * @apiName SandboxGetFile
        * @apiGroup Sandbox
        *
        * @apiParam {String} localPath Local full file path.
        * @apiParam {String} remotePath Remote full file path.
        * @apiParam {String} server_id Target Server Id
        * @apiParam {String} extension_id Target Extension Id
        * @apiParam {String} token Authenticated User Token
        *
        * @apiSuccess {String} output ok or no according to status.
    */
    public function getFile()
    {
        $output = server()->getFile(request('remotePath'), request('localPath'));

        // Update Permissions
        shell_exec("sudo chmod 770 " . request('localPath'));
        shell_exec("sudo chown " . clean_score(extension()->id) . ":liman " . request('localPath'));

        system_log(7,"EXTENSION_INTERNAL_RECEIVE_FILE",[
            "extension_id" => extension()->id,
            "server_id" => server()->id,
            "file_name" => request('remotePath'),
        ]);

        return ($output) ? "ok" : "no";
    }

    /**
        * @api {post} /lmn/private/openTunnel OpenSSH Tunnel Request
        * @apiName SandboxOpenSSHTunnel
        * @apiGroup Sandbox
        *
        * @apiParam {String} remote_host server host you wish to tunnel.
        * @apiParam {String} remote_port server port you wish to tunnel.
        * @apiParam {String} username server username you wish to tunnel.
        * @apiParam {String} password server password you wish to tunnel.
        * @apiParam {String} server_id Target Server Id
        * @apiParam {String} extension_id Target Extension Id
        * @apiParam {String} token Authenticated User Token
        *
        * @apiSuccess {String} token Tunnel token to close later on.
    */
    public function openTunnel()
    {
        return SSHTunnelConnector::new(request('remote_host'), request('remote_port'), request('username'), request('password'));
    }

    /**
        * @api {post} /lmn/private/stopTunnel Close OpenSSH Tunnel
        * @apiName SandboxStopSSHTunnel
        * @apiGroup Sandbox
        *
        * @apiParam {String} remote_host server host you wish to tunnel.
        * @apiParam {String} remote_port server port you wish to tunnel.
        * @apiParam {String} server_id Target Server Id
        * @apiParam {String} extension_id Target Extension Id
        * @apiParam {String} token Authenticated User Token
    */
    public function stopTunnel()
    {
        return SSHTunnelConnector::stop(request('remote_host'), request('remote_port'));
    }

    /**
        * @api {post} /lmn/private/reverseProxyRequest Add Vnc Proxy Config
        * @apiName SandboxAddVncProxyConfig
        * @apiGroup Sandbox
        *
        * @apiParam {String} hostname server host you wish to use in vnc.
        * @apiParam {String} port server port you wish to use in vnc.
        * @apiParam {String} server_id Target Server Id
        * @apiParam {String} extension_id Target Extension Id
        * @apiParam {String} token Authenticated User Token
    */
    public function addProxyConfig()
    {
        if(!is_dir(env("KEYS_PATH") . "vnc")){
            mkdir(env("KEYS_PATH") . "vnc",0700);
        }
        $writer = fopen(env("KEYS_PATH") . "vnc/config","a+");
        $hostname = request('hostname');
        $port = request('port');
        $token = Str::uuid();
        $token = str_replace("-","",$token);
        fwrite($writer,$token . ": $hostname:$port" . "\n");
        return $token;
    }

    private function checkPermissions()
    {
        if ($_SERVER['SERVER_ADDR'] != $_SERVER['REMOTE_ADDR']) {
            system_log(5,"EXTENSION_INTERNAL_NO_PERMISSION",[
                "extension_id" => extension()->id,
            ]);
            abort(403,'Not Allowed');
        }
        $token = Token::where('token', request('token'))->first() or abort(403, "Token gecersiz");
        auth()->loginUsingId($token->user_id);
        
        $server = Server::find(request('server_id')) or abort(404, 'Sunucu Bulunamadi');
        if (!Permission::can($token->user_id, 'server','id', $server->id)) {
            system_log(7,"EXTENSION_NO_PERMISSION_SERVER",[
                "extension_id" => extension()->id,
                "server_id" => request('server_id')
            ]);
            abort(504,"Sunucu icin yetkiniz yok.");
        }

        $extension = Extension::find(request('extension_id')) or abort(404, 'Eklenti Bulunamadi');
        if (!Permission::can($token->user_id, 'extension','id', $extension->id)) {
            system_log(7,"EXTENSION_NO_PERMISSION_SERVER",[
                "extension_id" => extension()->id,
                "server_id" => request('server_id')
            ]);
            abort(504,"Eklenti için yetkiniz yok.");
        }

        request()->request->add(['server' => $server]);
        request()->request->add(['extension' => $extension]);
    }
}
