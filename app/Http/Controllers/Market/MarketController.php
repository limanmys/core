<?php

namespace App\Http\Controllers\Market;

use App\Http\Controllers\Controller;
use App\Jobs\ExtensionUpdaterJob;
use App\Jobs\LimanUpdaterJob;
use App\Models\Extension;
use GuzzleHttp\Client;
use Illuminate\Contracts\Bus\Dispatcher;

/**
 * Class Market
 */
class MarketController extends Controller
{
    /**
     * @api {post} /market/kontrol Check Market Access
     * @apiName Check Market Access
     * @apiGroup Updates
     *
     * @apiSuccess {JSON} message Status of the connection.
     */
    public function verifyMarketConnection()
    {
        if (! env('MARKET_ACCESS_TOKEN')) {
            return respond("Market'e bağlanmak için bir anahtarınız yok!", 201);
        }
        $client = self::getClient();
        try {
            $response = $client->post(env('MARKET_URL').'/api/users/me');
        } catch (\Exception $e) {
            return respond("Anahtarınız ile Market'e bağlanılamadı!", 201);
        }

        return respond('Market Bağlantısı Başarıyla Sağlandı.');
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

    /**
     * @api {post} /market/guncellemeKontrol Check Liman Updates
     * @apiName Check Liman Updates
     * @apiGroup Updates
     *
     * @apiSuccess {Array} array all components of the liman with update statuses.
     */
    public function checkMarketUpdates($returnRaw = false)
    {
        $client = self::getClient();

        $params = [];
        $limanCode = getVersionCode();

        array_push($params, [
            'packageName' => 'Liman.Core',
            'versionCode' => intval($limanCode),
            'currentVersion' => getVersion(),
            'extension_id' => null,
        ]);

        $extensions = Extension::all();
        foreach ($extensions as $extension) {
            $obj = json_decode(
                file_get_contents(
                    '/liman/extensions/'.
                        strtolower($extension->name).
                        DIRECTORY_SEPARATOR.
                        'db.json'
                ),
                true
            );
            array_push($params, [
                'packageName' => 'Liman.'.$obj['name'],
                'versionCode' => array_key_exists('version_code', $obj)
                    ? $obj['version_code']
                    : 0,
                'currentVersion' => $obj['version'],
                'extension_id' => $extension->id,
            ]);
        }

        try {
            $response = $client->get(
                env('MARKET_URL').'/api/application/check_version',
                [
                    'json' => $params,
                ]
            );
        } catch (\Exception $e) {
            return respond($e->getMessage(), 201);
        }
        $json = json_decode((string) $response->getBody());
        $collection = collect($json);
        $fileToWrite = [];
        for ($i = 0; $i < count($params); $i++) {
            $obj = $collection
                ->where('packageName', $params[$i]['packageName'])
                ->first();
            if (! $obj) {
                $params[$i]['status'] = __('Güncel');
                $params[$i]['updateAvailable'] = 0;
            } else {
                $obj = json_decode(json_encode($obj), true);
                $params[$i]['status'] =
                    $obj['version']['versionName'].__(' sürümü mevcut');
                $params[$i]['updateAvailable'] = 1;
                if (
                    $params[$i]['extension_id'] != null &&
                    count($obj['platforms'])
                ) {
                    $job = (new ExtensionUpdaterJob(
                        $params[$i]['extension_id'],
                        $obj['version']['versionCode'],
                        $obj['platforms'][0]['downloadLink'],
                        $obj['platforms'][0]['hashSHA512']
                    ))->onQueue('system_updater');

                    // Dispatch job right away.
                    $job_id = app(Dispatcher::class)->dispatch($job);

                    array_push($fileToWrite, [
                        'name' => substr($params[$i]['packageName'], 6),
                        'currentVersion' => $params[$i]['currentVersion'],
                        'newVersion' => $obj['version']['versionName'],
                        'downloadLink' => $obj['platforms'][0]['downloadLink'],
                        'hashSHA512' => $obj['platforms'][0]['hashSHA512'],
                        'versionCode' => $obj['version']['versionCode'],
                        'changeLog' => $obj['version']['versionDescription'],
                        'extension_id' => $params[$i]['extension_id'],
                    ]);
                } else {
                    $job = (new LimanUpdaterJob(
                        $obj['version']['versionName'],
                        $obj['platforms'][0]['downloadLink']
                    ))->onQueue('system_updater');

                    // Dispatch job right away.
                    $job_id = app(Dispatcher::class)->dispatch($job);
                }
            }
        }
        if (count($fileToWrite)) {
            file_put_contents(
                storage_path('extension_updates'),
                json_encode($fileToWrite),
                JSON_PRETTY_PRINT
            );
        }

        if ($returnRaw) {
            return $params;
        }

        return respond($params);
    }

    public static function getClient()
    {
        if (! self::checkAccess(parse_url(env('MARKET_URL'))['host'])) {
            if (env('MARKET_URL') == null) {
                abort(504, 'Market bağlantısı ayarlanmamış.');
            }
            abort(
                504,
                env('MARKET_URL').' adresindeki markete bağlanılamadı!'
            );
        }

        return new Client([
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer '.env('MARKET_ACCESS_TOKEN'),
            ],
            'verify' => false,
        ]);
    }
}
