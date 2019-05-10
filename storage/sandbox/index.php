<?php 
require_once("vendor/autoload.php"); 
use Jenssegers\Blade\Blade;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

$tempExt = json_decode(str_replace('*m*','"', $argv[5]));
$tempSrv = json_decode(str_replace('*m*','"', $argv[4]));
$tempDb = json_decode(str_replace('*m*','"', $argv[6]),true);
$data = json_decode(str_replace('*m*','"', $argv[7]),true);
$tempRequest = json_decode(str_replace('*m*','"', $argv[8]),true);

function extensionDb($target){
    global $tempDb;
    return $tempDb[$target];
}

function extension(){
    global $tempExt;
    return $tempExt;
}

function server(){
    global $tempSrv;
    return $tempSrv;
}

// Translation disabled for now.
function __($str){
    return $str;
}

function request($target = null){
    global $tempRequest;
    if($target){
        return $tempRequest[$target];
    }
    return $tempRequest;    
}

function API($target){
    global $argv;
    return $argv[10] . "/" . $target;
}

function respond($message,$code = 200){
    echo [
        "message" => json_encode($message,true),
        "code" => $code
    ];
}

function navigate($name,$params = []){
    global $argv;
    $args = '';
    if($params != []){
    $args = '?&';
        foreach($params as $key=>$param){
            $args = $args . "&$key=$param";
        }
    }
    return $argv[11] . '/' . $name . $args;
}

function view($name,$params = []){
    global $argv;
    $blade = new Blade(dirname(dirname(dirname($argv[1]))),"/tmp");
    return $blade->render("l." . $name,$params);
}

function externalAPI($target, $extension_id, $server_id = null){
    global $argv;
    $client = new Client([
        'verify' => false,
        'cookies' => true
    ]);
    try{
        $response = $client->request('POST','https://127.0.0.1/lmn/private/extensionApi',[
            "multipart" => [
                [
                    "name" => "server_id",
                    "contents" => ($server_id) ? $server_id : server()->_id,
                ],
                [
                    "name" => "extension_id",
                    "contents" => $extension_id
                ],
                [
                    "name" => "target",
                    "contents" => $target
                ],
                [
                    "name" => "token",
                    "contents" => $argv[12]
                ]
            ],
        ]);
        return $response->getBody()->getContents();
    }catch(GuzzleException $exception){
        return $exception->getResponse()->getBody()->getContents();
    }
}

function runCommand($command){
    global $argv;
    $client = new Client([
        'verify' => false,
        'cookies' => true
    ]);
    try{
        $response = $client->request('POST','https://127.0.0.1/lmn/private/runCommandApi',[
            "multipart" => [
                [
                    "name" => "server_id",
                    "contents" => server()->_id,
                ],
                [
                    "name" => "command",
                    "contents" => $command
                ],
                [
                    "name" => "token",
                    "contents" => $argv[12]
                ]
            ],
        ]);
        return $response->getBody()->getContents();
    }catch(GuzzleException $exception){
        return $exception->getResponse()->getBody()->getContents();
    }
}

function putFile($localPath,$remotePath){
    global $argv;
    $client = new Client([
        'verify' => false,
        'cookies' => true
    ]);
    try{
        $response = $client->request('POST','https://127.0.0.1/lmn/private/putFileApi',[
            "multipart" => [
                [
                    "name" => "server_id",
                    "contents" => server()->_id,
                ],
                [
                    "name" => "localPath",
                    "contents" => $localPath
                ],
                [
                    "name" => "remotePath",
                    "contents" => $remotePath
                ],
                [
                    "name" => "token",
                    "contents" => $argv[12]
                ]
            ],
        ]);
        return "ok";
    }catch(GuzzleException $exception){
        return $exception->getResponse()->getBody()->getContents();
    }
}

function getFile($localPath,$remotePath){
    global $argv;
    $client = new Client([
        'verify' => false,
        'cookies' => true
    ]);
    try{
        $response = $client->request('POST','https://127.0.0.1/lmn/private/getFileApi',[
            "multipart" => [
                [
                    "name" => "server_id",
                    "contents" => server()->_id,
                ],
                [
                    "name" => "localPath",
                    "contents" => $localPath
                ],
                [
                    "name" => "remotePath",
                    "contents" => $remotePath
                ],
                [
                    "name" => "token",
                    "contents" => $argv[12]
                ]
            ],
        ]);
        return $response->getBody()->getContents();
    }catch(GuzzleException $exception){
        return $exception->getResponse()->getBody()->getContents();
    }
}

function getPath($filename = null){
    global $argv;
    return dirname($argv[1]) . "/" . $filename;
}

// Functions PHP
if(is_file($argv[1])){
    include($argv[1]);
}

if($argv[3] == "null"){
    echo json_encode(call_user_func($argv[9]),true);
}else{
    $blade = new Blade(dirname(dirname(dirname($argv[1]))),"/tmp");
    echo $blade->render("extensions." . $argv[2] . "." . $argv[3],[
        "data" => $data
    ]);
}