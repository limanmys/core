<?php

namespace App\Http\Controllers\API\Server;

use App\Exceptions\JsonResponseException;
use App\Http\Controllers\Controller;
use App\Models\Extension;
use App\Models\Permission;
use App\System\Command;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Response;

/**
 * Access Log Controller
 */
class AccessLogController extends Controller
{
    public function __construct()
    {
        if (! isset(auth('api')->user()->id)) {
            throw new JsonResponseException([
                'message' => 'Tekrar giriş yapınız.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (! Permission::can(auth('api')->user()->id, 'liman', 'id', 'view_logs')) {
            throw new JsonResponseException(
                ['message' => 'Sunucu günlük kayıtlarını görüntülemek için yetkiniz yok.'],
                Response::HTTP_FORBIDDEN
            );
        }
    }

    /**
     * Returns server access logs
     *
     * You can use API calls on this endpoint
     * Parameters
     *  - page Page number.
     *  - count How much records will be retrieved.
     *  - query Search query (OPTIONAL)
     *  - server_id Server Id
     *
     * @return JsonResponse|Response
     */
    public function index()
    {
        $data = Command::runLiman(
            'cat /liman/logs/liman_new.log | grep @{:user_id} | grep @{:server_id} | grep -v "recover middleware catch" | tail -{:page} | tac',
            [
                'page' => 500,
                'user_id' => strlen(request('log_user_id')) > 5 ? request('log_user_id') : '',
                'server_id' => request('server_id'),
            ]
        );
        $clean = [];

        $knownUsers = [];
        $knownExtensions = [];

        if ($data == '') {
            return response()->json([]);
        }

        foreach (explode("\n", (string) $data) as $row) {
            $row = json_decode($row);

            try {
                if (isset($row->request_details->extension_id)) {
                    if (! isset($knownExtensions[$row->request_details->extension_id])) {
    
                        $extension = Extension::find($row->request_details->extension_id);
                        if ($extension) {
                            $knownExtensions[$row->request_details->extension_id] =
                                $extension->display_name;
                        } else {
                            $knownExtensions[$row->request_details->extension_id] =
                                $row->request_details->extension_id;
                        }
                    }
                    $row->extension_id = $knownExtensions[$row->request_details->extension_id];
                } else {
                    $row->extension_id = __('Komut');
                }
    
                if (! isset($knownUsers[$row->user_id])) {
                    $user = User::find($row->user_id);
                    if ($user) {
                        $knownUsers[$row->user_id] = $user->name;
                    } else {
                        $knownUsers[$row->user_id] = $row->user_id;
                    }
                }
    
                $row->user_id = $knownUsers[$row->user_id];
    
                if (isset($row->request_details->lmntargetFunction)) {
                    $row->view = $row->request_details->lmntargetFunction;
    
                    if (isset($row->request_details->lmntargetFunction) && $row->request_details->lmntargetFunction == '') {
                        if ($row->lmn_level == 'high_level' && isset($row->request_details->title)) {
                            $row->view = base64_decode($row->request_details->title);
                        }
                    }
                } else {
                    $row->view = __('Komut');
                }
    
                $clean[] = $row;
            } catch (\Throwable $e) {
                continue;
            }
        }

        return response()->json($clean);
    }

    /**
     * Shows log detail modal
     *
     * @return JsonResponse|Response
     */
    public function details()
    {
        $query = request('log_id');
        $data = Command::runLiman('grep @{:query} /liman/logs/liman_new.log', [
            'query' => $query,
        ]);
        if ($data == '') {
            return response()->json([
                'message' => 'Bu loga ait detay bulunamadı.'
            ], Response::HTTP_NOT_FOUND);
        }
        $data = explode("\n", (string) $data);
        $logs = [];
        foreach ($data as $k_ => $row) {
            $row = mb_convert_encoding($row, 'UTF-8', 'auto');
            $row = json_decode($row);
            foreach ($row as $k => &$v) {
                if ($k == 'level' || $k == 'log_id') {
                    continue;
                }

                if ($k == 'ts') {
                    $v = Carbon::parse($v)->isoFormat('LLLL');
                }

                if ($row->lmn_level == 'high_level' && $k == 'request_details') {
                    foreach ($row->request_details as $key => $val) {
                        if ($key == 'level' || $key == 'log_id' || $key == 'token') {
                            continue;
                        }

                        if ($key == 'title' || $key == 'message') {
                            $val = base64_decode((string) $val);
                        }

                        $logs[] = [
                            'title' => __($key),
                            'message' => $val,
                        ];
                    }

                    continue;
                }

                if ($row->lmn_level != 'high_level' && $k == 'request_details' && $k != 'token') {
                    $logs[] = [
                        'title' => __($k),
                        'message' => json_encode($v, JSON_PRETTY_PRINT),
                    ];

                    continue;
                }

                $logs[] = [
                    'title' => __($k),
                    'message' => $v,
                ];
            }
            if ($k_ < count($data) - 1) {
                $logs[] = [
                    'title' => '---------------------',
                    'message' => 'Log seperator',
                ];
            }
        }

        return response()->json($logs);
    }
}
