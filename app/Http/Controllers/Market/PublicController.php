<?php

namespace App\Http\Controllers\Market;

use App\Http\Controllers\Controller;
use App\System\Command;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Class Public
 */
class PublicController extends Controller
{
    private $apiUrls;

    public function __construct()
    {
        $this->apiUrls = [
            'getApplications' => env('MARKET_URL') . '/api/public/applications',
            'applicationDownload' => env('MARKET_URL') . '/api/public/download_application/',
            'categories' => env('MARKET_URL') . '/api/public/categories',
            'download' => env('MARKET_URL') . '/api/public/download_application/',
        ];
    }

    /**
     * Get application list from remote API
     *
     * @param Request $request
     * @return Application|Factory|View|JsonResponse|Response
     */
    public function getApplications(Request $request)
    {
        $client = self::httpClient();

        try {
            if (! $request->pageNumber) {
                $response = $client->get($this->apiUrls['getApplications']);
            } else {
                $response = $client->get($this->apiUrls['getApplications'] . '?pageNumber=' . $request->pageNumber);
            }
        } catch (\Throwable $e) {
            return respond($e->getMessage(), 201);
        }

        $json = json_decode((string) $response->getBody());

        $items = $this->updateFilter($json->items);
        $paginate = [
            'pageSize' => $json->pageSize,
            'pageIndex' => $json->pageIndex,
            'totalPages' => $json->totalPages,
            'hasPreviousPage' => $json->hasPreviousPage,
            'hasNextPage' => $json->hasNextPage,
        ];

        if ($json->hasNextPage) {
            $paginate['nextPage'] = $json->pageIndex + 1;
        }

        if ($json->hasPreviousPage) {
            $paginate['previousPage'] = $json->pageIndex - 1;
        }

        return view('market.list', ['apps' => $items, 'paginate' => (object) $paginate, 'categories' => $this->getCategories()]);
    }

    /**
     * HTTP Client instance that reachs to market API
     *
     * @return Client
     */
    private static function httpClient()
    {
        if (! self::checkAccess(parse_url((string) env('MARKET_URL'))['host'])) {
            if (env('MARKET_URL') == null) {
                abort(504, 'Market bağlantısı ayarlanmamış.');
            }
            abort(
                504,
                env('MARKET_URL') . ' adresindeki markete bağlanılamadı!'
            );
        }

        return new Client([
            'headers' => [
                'Accept' => 'application/json',
            ],
            'verify' => false,
        ]);
    }

    /**
     * Check access
     *
     * @param $hostname
     * @param $port
     * @return bool
     */
    private static function checkAccess($hostname, $port = 443): bool
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
     * Update filters
     *
     * @param $myArray
     * @return mixed
     */
    private function updateFilter($myArray)
    {
        $json = $myArray;

        $getExtensions = \App\Models\Extension::select('id', 'name')->get();

        $extensions = [];
        foreach ($getExtensions as $extension) {
            $extension_json = '/liman/extensions/' .
                strtolower((string) $extension->name) .
                DIRECTORY_SEPARATOR .
                'db.json';

            if (file_exists($extension_json)) {
                $obj = json_decode(
                    file_get_contents(
                        $extension_json
                    ),
                    true
                );
            } else {
                continue;
            }

            array_push($extensions, [
                'id' => $extension->id,
                'name' => 'Liman.' . $extension->name,
                'versionCode' => array_key_exists('version_code', $obj)
                    ? $obj['version_code']
                    : 0,
            ]);
        }

        foreach ($json as $extension) {
            [$search, $indice] = $this->searchForName($extension->packageName, $extensions);

            if ($extension->publicVersion) {
                if ($search && $extension->publicVersion->versionCode > $extensions[$indice]['versionCode']) {
                    $extension->publicVersion->needsToBeUpdated = true;
                } else {
                    $extension->publicVersion->needsToBeUpdated = false;
                }
            }

            if ($search && $extension->packageName == $extensions[$indice]['name']) {
                $extension->isInstalled = true;
            } else {
                $extension->isInstalled = false;
            }
        }

        return $json;
    }

    /**
     * Search name field in array
     *
     * @param $id
     * @param $array
     * @return array
     */
    private function searchForName($id, $array)
    {
        foreach ($array as $key => $val) {
            if ($val['name'] == $id) {
                return [true, $key];
            }
        }

        return [false, null];
    }

    /**
     * Get categories
     *
     * @return JsonResponse|Response|mixed
     */
    private function getCategories()
    {
        $client = self::httpClient();

        try {
            $response = $client->get($this->apiUrls['categories']);
        } catch (\Throwable $e) {
            return respond($e->getMessage(), 201);
        }

        $json = json_decode((string) $response->getBody());

        return $json;
    }

    /**
     * Get category items
     *
     * @param Request $request
     * @return Application|Factory|View|JsonResponse|Response
     */
    public function getCategoryItems(Request $request)
    {
        $client = self::httpClient();

        try {
            if (! $request->pageNumber) {
                $response = $client->get($this->apiUrls['getApplications'] . '?categoryId=' . $request->category_id);
            } else {
                $response = $client->get($this->apiUrls['getApplications'] . '?categoryId=' . $request->category_id . '&pageNumber=' . $request->pageNumber);
            }
        } catch (\Throwable $e) {
            return respond($e->getMessage(), 201);
        }

        $json = json_decode((string) $response->getBody());

        $items = $this->updateFilter($json->items);
        $paginate = [
            'pageSize' => $json->pageSize,
            'pageIndex' => $json->pageIndex,
            'totalPages' => $json->totalPages,
            'hasPreviousPage' => $json->hasPreviousPage,
            'hasNextPage' => $json->hasNextPage,
        ];

        if ($json->hasNextPage) {
            $paginate['nextPage'] = $json->pageIndex + 1;
        }

        if ($json->hasPreviousPage) {
            $paginate['previousPage'] = $json->pageIndex - 1;
        }

        return view('market.list', ['apps' => $items, 'paginate' => (object) $paginate, 'categories' => $this->getCategories()]);
    }

    /**
     * Search on applications
     *
     * @param Request $request
     * @return Application|Factory|View|JsonResponse|Response
     */
    public function search(Request $request)
    {
        $client = self::httpClient();

        try {
            $response = $client->get($this->apiUrls['getApplications'] . '?search=' . $request->search_query);
        } catch (\Throwable $e) {
            return respond($e->getMessage(), 201);
        }

        $json = json_decode((string) $response->getBody());

        $items = $this->updateFilter($json->items);
        $paginate = [
            'pageSize' => $json->pageSize,
            'pageIndex' => $json->pageIndex,
            'totalPages' => $json->totalPages,
            'hasPreviousPage' => $json->hasPreviousPage,
            'hasNextPage' => $json->hasNextPage,
        ];

        if ($json->hasNextPage) {
            $paginate['nextPage'] = $json->pageIndex + 1;
        }

        if ($json->hasPreviousPage) {
            $paginate['previousPage'] = $json->pageIndex - 1;
        }

        return view('market.list', ['apps' => $items, 'paginate' => (object) $paginate, 'categories' => $this->getCategories()]);
    }

    /**
     * Install application from market
     *
     * @param Request $request
     * @return JsonResponse|Response|mixed
     * @throws GuzzleException
     * @throws GuzzleException
     */
    public function installPackage(Request $request)
    {
        $client = self::httpClient();
        $file = fopen('/tmp/' . $request->package_name, 'w');
        try {
            $response = $client->get(
                $this->apiUrls['download'] . $request->package_name,
                ['sink' => $file]
            );

            $headers = $response->getHeaders();
            $headers = array_change_key_case($headers, CASE_LOWER);

            $str = $headers['content-disposition'][0];
            $arr = explode(';', (string) $str);
            $extension = substr($arr[1], -7) == '.signed' ? '.signed' : '.zip';
        } catch (\Throwable $e) {
            return respond($e->getMessage(), 201);
        }

        hook('extension_upload_attempt', [
            'request' => request()->all(),
        ]);

        $verify = false;
        $zipFile = '/tmp/' . $request->package_name;
        if (
            $extension == '.signed'
        ) {
            $verify = Command::runLiman(
                'gpg --verify --status-fd 1 @{:extension} | grep GOODSIG || echo 0',
                ['extension' => $zipFile]
            );
            if (! (bool) $verify) {
                return respond('Eklenti dosyanız doğrulanamadı.', 201);
            }
            $decrypt = Command::runLiman(
                "gpg --status-fd 1 -d -o '/tmp/{:originalName}' @{:extension} | grep FAILURE > /dev/null && echo 0 || echo 1",
                [
                    'originalName' => $request->package_name . '.zip',
                    'extension' => $zipFile,
                ]
            );
            if (! (bool) $decrypt) {
                return respond(
                    'Eklenti dosyası doğrulanırken bir hata oluştu!.',
                    201
                );
            }
            $zipFile =
                '/tmp/' . $request->package_name . '.zip';
        } else {
            if (! request()->has('force')) {
                return respond(
                    'Bu eklenti imzalanmamış bir eklenti, yine de kurmak istediğinize emin misiniz?',
                    203
                );
            }
        }
        $controller = new \App\Http\Controllers\Extension\MainController();
        [$error, $new] = $controller->setupNewExtension($zipFile, $verify);

        if ($error) {
            return $error;
        }

        system_log(3, 'EXTENSION_UPLOAD_SUCCESS', [
            'extension_id' => $new->id,
        ]);

        return respond('Eklenti başarıyla yüklendi.', 200);
    }

    /**
     * Get random items from market
     *
     * @return array|JsonResponse|Response
     */
    public function getHomepageApps()
    {
        $client = self::httpClient();

        try {
            $response = $client->get($this->apiUrls['getApplications']);
        } catch (\Throwable $e) {
            return respond($e->getMessage(), 201);
        }

        $json = json_decode((string) $response->getBody());

        $items = $this->updateFilter($json->items);

        $final = [];
        $keys = array_rand($items, count($items));
        $keys = array_rand($keys, count($items) < 4 ? count($items) : 4);
        shuffle($keys);
        shuffle($keys);
        shuffle($keys);
        foreach ($keys as $key) {
            array_push($final, $items[$key]);
        }

        return $final;
    }
}
