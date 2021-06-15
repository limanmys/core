<?php

namespace App\Http\Controllers\Market;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use App\Models\Extension;
use App\Jobs\ExtensionUpdaterJob;
use App\Jobs\LimanUpdaterJob;
use GuzzleHttp\Exception\BadResponseException;
use Illuminate\Contracts\Bus\Dispatcher;

/**
 * Class Public
 * @package App\Http\Controllers\Market
 */
class PublicController extends Controller
{
    private $apiUrls;

    function __construct()
    {
        $this->apiUrls = [
            "getApplications"     => env('MARKET_URL') . "/api/public/applications",
            "applicationDownload" => env('MARKET_URL') . "/api/public/download_application/",
            "categories"          => env('MARKET_URL') . "/api/public/categories",
        ];
    }

    private static function checkAccess($hostname, $port = 443)
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

    private static function httpClient()
    {
        if (!self::checkAccess(parse_url(env("MARKET_URL"))["host"])) {
            if (env("MARKET_URL") == null) {
                abort(504, "Market bağlantısı ayarlanmamış.");
            }
            abort(
                504,
                env("MARKET_URL") . " adresindeki markete bağlanılamadı!"
            );
        }

        return new Client([
            "headers" => [
                "Accept" => "application/json"
            ],
            "verify" => false,
        ]);
    }

    public function getCategories()
    {
        $client = self::httpClient();

        try {
            $response = $client->get($this->apiUrls["categories"]);
        } 
        catch (\Throwable $e) 
        {
            return respond($e->getMessage(), 201);
        }

        $json = json_decode((string) $response->getBody());
        return $json;
    }

    public function getApplications() 
    {
        $client = self::httpClient();

        try {
            $response = $client->get($this->apiUrls["getApplications"]);
        } 
        catch (\Throwable $e) 
        {
            return respond($e->getMessage(), 201);
        }

        $json = json_decode((string) $response->getBody());

        return view("market.list", ["apps" => $json, "categories" => $this->getCategories()]);
    }

    public function getCategoryItems(Request $request)
    {
        $client = self::httpClient();

        try {
            $response = $client->get($this->apiUrls["getApplications"] . "?categoryId=" . $request->category_id);
        } 
        catch (\Throwable $e) 
        {
            return respond($e->getMessage(), 201);
        }

        $json = json_decode((string) $response->getBody());

        return view("market.list", ["apps" => $json, "categories" => $this->getCategories()]);
    }

    public function search(Request $request)
    {
        $client = self::httpClient();

        try {
            $response = $client->get($this->apiUrls["getApplications"] . "?search=" . $request->search_query);
        } 
        catch (\Throwable $e) 
        {
            return respond($e->getMessage(), 201);
        }
        
        $json = json_decode((string) $response->getBody());

        return view("market.list", ["apps" => $json, "categories" => $this->getCategories()]);
    }

    public function test()
    {
        //
    }
}
