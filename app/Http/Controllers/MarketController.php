<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;

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
            "currentVersion" => getVersion()
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
                "currentVersion" => $obj["version"]
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
        for($i=0; $i < count($params); $i++){
            $obj = $collection->where('packageName',$params[$i]["packageName"])->first();
            if(!$obj){
                $params[$i]["status"] = "Güncel";
            }else{
                $params[$i]["status"] = $obj->version->versionName . " sürümü mevcut";
            }
        }
        return respond($params);
    }


    private function getClient()
    {
        if(!self::checkAccess(env("MARKET_URL"))){
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
