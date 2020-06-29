<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use App\Jobs\ExtensionUpdaterJob;
use GuzzleHttp\Exception\BadResponseException;
use Illuminate\Contracts\Bus\Dispatcher;

class MarketController extends Controller
{
    public function verifyMarketConnection()
    {
        if(!env('MARKET_ACCESS_TOKEN')){
            return respond("Market'e bağlanmak için bir anahtarınız yok!",201);
        }
        $client = self::getClient();
        try{
            $response = $client->post(env("MARKET_URL") . '/api/users/me');
        }catch(\Exception $e){
            return respond("Anahtarınız ile Market'e bağlanılamadı!",201);
        }
        
        return respond("Market Bağlantısı Başarıyla Sağlandı.");
    }

    private function checkAccess($hostname, $port = 443)
    {
        return is_resource(
            @fsockopen(
                $hostname,
                $port,
                $errno,
                $errstr,
                intval(config('liman.server_connection_timeout'))
            )
        );
    }

    public function checkMarketUpdates()
    {
        $client = self::getClient();

        $params = [];
        $limanCode = trim(file_get_contents(storage_path('VERSION_CODE')));

        array_push($params,[
            "packageName" => "Liman.Core",
            "versionCode" => intval($limanCode),
            "currentVersion" => getVersion(),
            "extension_id" => null
        ]);

        
        foreach(extensions() as $extension){
            $obj = json_decode(
                file_get_contents(
                    "/liman/extensions/" .
                        strtolower($extension->name) .
                        DIRECTORY_SEPARATOR .
                        "db.json"
                ),
                true
            );
            array_push($params,[
                "packageName" => "Liman." . $obj["name"],
                "versionCode" => array_key_exists("version_code",$obj) ? $obj["version_code"] : 0,
                "currentVersion" => $obj["version"],
                "extension_id" => $extension->id
            ]);
        }

        try{
            $response = $client->get(env("MARKET_URL") . '/api/application/check_version',[
                "json" => $params
            ]);
        }catch(\Exception $e){
            return respond($e->getMessage(),201);
        }
        $json = json_decode((string) $response->getBody());
        $collection = collect($json);
        $fileToWrite = [];
        for($i=0; $i < count($params); $i++){
            $obj = $collection->where('packageName',$params[$i]["packageName"])->first();
            if(!$obj){
                $params[$i]["status"] = "Güncel";
            }else{
                $obj = json_decode(json_encode($obj),true);
                $params[$i]["status"] = $obj["version"]["versionName"] . " sürümü mevcut";
                if($params[$i]["extension_id"] != null && count($obj["platforms"])){
                    $job = (new ExtensionUpdaterJob(
                        $params[$i]["extension_id"],
                        $obj["version"]["versionCode"],
                        $obj["platforms"][0]["downloadLink"]
                    ))->onQueue('system_updater');
            
                    // Dispatch job right away.
                    $job_id = app(Dispatcher::class)->dispatch($job);

                    array_push($fileToWrite,[
                        "name" => substr($params[$i]["packageName"],6),
                        "currentVersion" => $params[$i]["currentVersion"],
                        "newVersion" => $obj["version"]["versionName"],
                        "downloadLink" => $obj["platforms"][0]["downloadLink"],
                        "versionCode" => $obj["version"]["versionCode"],
                        "changeLog" => $obj["version"]["versionDescription"],
                        "extension_id" => $params[$i]["extension_id"]
                    ]);
                }
            }
        }
        if(count($fileToWrite)){
            file_put_contents(storage_path("extension_updates"),json_encode($fileToWrite),JSON_PRETTY_PRINT);
        }
        return respond($params);
    }


    private function getClient()
    {
        if(!self::checkAccess(parse_url(env("MARKET_URL"))["host"])){
            if(env("MARKET_URL") == null){
                abort(504,"Market bağlantısı ayarlanmamış.");
            }
            abort(504,env("MARKET_URL") . " adresindeki markete bağlanılamadı!");
        }

        return new Client([
            "headers" => [
                "Accept" => "application/json",
                "Authorization" => "Bearer " . env("MARKET_ACCESS_TOKEN")
            ],
            "verify" => false
        ]);
    }
}
