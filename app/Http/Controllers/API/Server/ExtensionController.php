<?php

namespace App\Http\Controllers\API\Server;

use App\Exceptions\JsonResponseException;
use App\Http\Controllers\Controller;
use App\Models\Permission;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use mervick\aesEverywhere\AES256;

/**
 * Server Extension Controller
 */
class ExtensionController extends Controller
{
    /**
     * Extension list
     *
     * @return Collection
     */
    public function index()
    {
        if (! Permission::can(auth('api')->user()->id, 'liman', 'id', 'server_details')) {
            throw new JsonResponseException([
                'message' => 'Bu işlemi yapmak için yetkiniz yok!'
            ], '', Response::HTTP_FORBIDDEN);
        }

        return server()->extensions()->filter(function ($extension) {
            return Permission::can(auth('api')->user()->id, 'extension', 'id', $extension->id);
        })->map(function ($item) {
            $item->updated = Carbon::parse($item->getRawOriginal('updated_at'))->getPreciseTimestamp(3);

            return $item;
        })->values();
    }

    /**
     * Get extension settings list
     *
     * @return JsonResponse|Response
     * @throws \Exception
     */
    public function serverSettings()
    {
        $extension = getExtensionJson(extension()->name);
        
        $similar = [];
        $globalVars = [];
        $flag = server()->key();
        foreach ($extension['database'] as $key => $item) {
            if (
                ($flag != null && $item['variable'] == 'clientUsername') ||
                ($flag != null && $item['variable'] == 'clientPassword')
            ) {
                unset($extension['database'][$key]);
            }

            if (
                auth('api')->user()->auth_type == 'ldap' &&
                isset($extension['ldap_support_fields'])
            ) {
                if (
                    in_array($item['variable'], array_values($extension['ldap_support_fields']))
                ) {
                    unset($extension['database'][$key]);
                }
            }

            $opts = [
                'server_id' => server()->id,
                'name' => $item['variable'],
            ];

            if (!isset($item['global']) || $item['global'] === false) {
                $opts['user_id'] = user()->id;
            }

            $obj = DB::table('user_settings')
                ->where($opts)
                ->first();
            if ($obj) {
                if (array_key_exists('user_id', $opts)) {
                    $key = env('APP_KEY') . user()->id . server()->id;
                } else {
                    $key = env('APP_KEY') . $obj->user_id . server()->id;
                    if ($obj->user_id != user()->id) {
                        array_push($globalVars, $item['variable']);
                    }
                }

                if ($item['type'] != 'password') {
                    $similar[$item['variable']] = AES256::decrypt(
                        $obj->value,
                        $key
                    );
                }
            }
        }

        $database = collect($extension["database"])->map(function ($item) use ($similar) {
            if (array_key_exists($item['variable'], $similar)) {
                $item['value'] = $similar[$item['variable']];
            } else {
                $item['value'] = '';
            }

            return $item;
        });

        if (! auth('api')->user()->isAdmin()) {
            $database = $database->whereNotIn('variable', $globalVars);
        }           

        $requiredSettings = $database->where('required', true);
        $advancedSettings = $database->where('required', false);

        return response()->json([
            'required' => $requiredSettings->values()->toArray(),
            'advanced' => $advancedSettings->values()->toArray(),
            'has_global_variables' => ! auth('api')->user()->isAdmin() ? count($globalVars) > 0 : false,
            'values' => collect($similar)->filter(function ($item, $key) use ($globalVars, $database) {
                $key = $database->where('variable', $key)->first();
                if (! $key) {
                    return false;
                }

                if ($key['type'] == 'password') {
                    return false;
                }
                
                if (! auth('api')->user()->isAdmin()) {
                    return ! in_array($key, $globalVars);
                }

                return true;
            })->toArray()
        ]);
    }

    /**
     * Get extension settings
     *
     * @throws GuzzleException
     */
    public function setServerSettings()
    {
        $extension = json_decode(
            file_get_contents(
                '/liman/extensions/' .
                strtolower((string) extension()->name) .
                DIRECTORY_SEPARATOR .
                'db.json'
            ),
            true
        );

        foreach ($extension['database'] as $key) {
            $opts = [
                'server_id' => server()->id,
                'name' => $key['variable'],
            ];

            if (!isset($key['global']) || $key['global'] === false) {
                $opts['user_id'] = user()->id;
            }

            $row = DB::table('user_settings')->where($opts);
            $variable = request($key['variable']);
            if ($variable) {
                if ($row->exists()) {
                    $encKey = env('APP_KEY') . user()->id . server()->id;
                    if ($row->first()->user_id != user()->id && !user()->isAdmin()) {
                        return response()->json([
                            'message' => __('Bu ayar sadece eklentiyi kuran kişi tarafından değiştirilebilir.'),
                        ], 403);
                    }
                    $row->update([
                        'user_id' => user()->id,
                        'server_id' => server()->id,
                        'value' => AES256::encrypt($variable, $encKey),
                        'updated_at' => Carbon::now(),
                    ]);
                } else {
                    $encKey = env('APP_KEY') . user()->id . server()->id;
                    DB::table('user_settings')->insert([
                        'id' => Str::uuid(),
                        'server_id' => server()->id,
                        'user_id' => user()->id,
                        'name' => $key['variable'],
                        'value' => AES256::encrypt($variable, $encKey),
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]);
                }
            }
        }

        //Check Verification
        if (
            array_key_exists('verification', $extension) &&
            $extension['verification'] != null &&
            $extension['verification'] != ''
        ) {
            $client = new Client(['verify' => false]);
            $result = '';
            try {
                $res = $client->request('POST', env('RENDER_ENGINE_ADDRESS', 'https://127.0.0.1:2806'), [
                    'form_params' => [
                        'lmntargetFunction' => $extension['verification'],
                        'extension_id' => extension()->id,
                        'server_id' => server()->id,
                    ],
                    'timeout' => 5,
                ]);
                $output = (string) $res->getBody();
                if (isJson($output)) {
                    $message = json_decode($output);
                    if (isset($message->message)) {
                        $result = $message->message;
                    }
                } else {
                    $result = $output;
                }
            } catch (\Exception) {
                $result = __('Doğrulama başarısız, girdiğiniz bilgileri kontrol edin.');
            }
            if (trim((string) $result) != 'ok') {
                return response()->json([
                    'message' => $result,
                ], 500);
            }
        }

        return response()->json([
            'message' => __('Ayarlar başarıyla kaydedildi.'),
        ]);
    }
}
