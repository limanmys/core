<?php

namespace App\Http\Controllers;

use App\Models\MonitorServer;
use App\Models\UserMonitors;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class ServerMonitorController extends Controller
{
    /**
     * Add a server to watch status.
     *
     * @return JsonResponse
     */
    public function add()
    {
        try {
            $this->validateRequest();

            $server = MonitorServer::firstOrNew([
                'ip_address' => request('ip_address'),
                'port' => request('port'),
            ]);

            if (!$server->exists) {
                $status = checkPort(request('ip_address'), request('port'));
                $server->fill([
                    'online' => $status,
                    'last_checked' => Carbon::now(),
                ])->save();
            }

            UserMonitors::create([
                'name' => request('name'),
                'server_monitor_id' => $server->id,
                'user_id' => user()->id,
            ]);

            return respond('Başarıyla eklendi!');
        } catch (ValidationException $e) {
            return respond($e->validator->errors()->first(), 422);
        } catch (\Exception $e) {
            return respond('Bir hata oluştu!', 500);
        }
    }

    /**
     * Remove a server from the watch list.
     *
     * @return JsonResponse
     */
    public function remove()
    {
        try {
            $obj = UserMonitors::find(request('server_monitor_id'));
            if (!$obj) {
                return respond('Bu sunucu takibi bulunamadı!', 404);
            }

            $this->deleteServerIfNoOtherMonitors($obj);

            $obj->delete();

            return respond('Sunucu takibi başarıyla silindi!');
        } catch (\Exception $e) {
            return respond('Bir hata oluştu!', 500);
        }
    }

    /**
     * Re-run health check.
     *
     * @return JsonResponse
     */
    public function refresh()
    {
        try {
            $obj = UserMonitors::find(request('server_monitor_id'));
            if (!$obj) {
                return respond('Bu sunucu takibi bulunamadı!', 404);
            }

            $server = MonitorServer::find($obj->server_monitor_id);
            if (!$server) {
                return respond('Bu sunucu takibi bulunamadı!', 404);
            }

            $status = checkPort($server->ip_address, $server->port);
            $server->update([
                'online' => $status,
                'last_checked' => Carbon::now(),
            ]);

            return respond('Başarıyla yenilendi!');
        } catch (\Exception $e) {
            return respond('Bir hata oluştu!', 500);
        }
    }

    /**
     * Returns the list of watched servers.
     *
     * @return JsonResponse
     */
    public function list()
    {
        try {
            $servers = $this->getUserMonitorsWithServerDetails();

            return magicView('monitor.index', [
                'monitor_servers' => $servers,
                'servers' => servers(),
            ]);
        } catch (\Exception $e) {
            return respond('Bir hata oluştu!', 500);
        }
    }

    /**
     * Validate the incoming request.
     *
     * @return void
     * @throws ValidationException
     */
    private function validateRequest()
    {
        $this->validate(request(), [
            'name' => 'required',
            'ip_address' => 'required',
            'port' => 'required|numeric|min:-1|max:65537',
        ]);
    }

    /**
     * Delete the server if no other monitors are tracking it.
     *
     * @param \App\Models\UserMonitors $monitor
     * @return void
     */
    private function deleteServerIfNoOtherMonitors(UserMonitors $monitor)
    {
        $monitors = UserMonitors::where('server_monitor_id', $monitor->server_monitor_id)->get();
        if ($monitors->count() == 1) {
            MonitorServer::find($monitor->server_monitor_id)->delete();
        }
    }

    /**
     * Get user monitors with server details.
     *
     * @return mixed
     */
    private function getUserMonitorsWithServerDetails()
    {
        return UserMonitors::where('user_id', user()->id)->get()->map(function ($server) {
            $obj = MonitorServer::find($server->server_monitor_id);
            if (!$obj) {
                return $server;
            }

            $server->online = $obj->online;
            $server->last_checked = $obj->last_checked;
            $server->ip_address = $obj->ip_address;
            $server->port = $obj->port;

            return $server;
        });
    }
}
