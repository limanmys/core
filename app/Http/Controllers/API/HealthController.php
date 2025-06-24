<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

class HealthController extends Controller
{
    public function health(Request $request, $component = null, $nested = null)
    {
        if ($component) {
            $path = $component;
            if ($nested) {
                $path .= '/' . $nested;
            }
            $result = $this->getComponentHealth($path);
            
            if ($result['status'] === 'DOWN') {
                return response()->json($result, 503);
            }
            return response()->json($result);
        }
        
        $result = $this->getHealth();
        if (!$request->query('details', true)) {
            return response()->json(['status' => $result['status']]);
        }
        if ($result['status'] === 'DOWN') {
            return response()->json($result, 503);
        }
        return response()->json($result);
    }

    private function checkServicesHealth(array $services)
    {
        $result = [];
        foreach ($services as $service) {
            try {
                $status = trim(shell_exec("systemctl is-active $service 2>/dev/null"));
                $result[$service] = [
                    'status' => ($status === 'active') ? 'UP' : 'DOWN',
                    'details' => [ 'systemctl_status' => $status ]
                ];
            } catch (\Throwable $e) {
                $result[$service] = [
                    'status' => 'UNKNOWN',
                    'details' => [ 'error' => $e->getMessage() ]
                ];
            }
        }
        return $result;
    }

    private function checkPort($port, $host = '127.0.0.1')
    {
        $connection = @fsockopen($host, $port, $errno, $errstr, 1);
        if (is_resource($connection)) {
            fclose($connection);
            return [
                'status' => 'UP',
                'details' => [
                    'message' => 'Port is open'
                ]
            ];
        } else {
            return [
                'status' => 'DOWN',
                'details' => [
                    'error' => $errstr
                ]
            ];
        }
    }

    private function getHealth(array $components = [])
    {
        $all = empty($components);
        $result = [
            'status' => 'UP',
            'components' => []
        ];

        

        // Port checks
        $result['components']['render'] = $this->checkPort(2806);
        if ($result['components']['render']['status'] !== 'UP') {
            $result['status'] = 'DOWN';
        }
        $result['components']['systemsvc'] = $this->checkPort(3008);
        if ($result['components']['systemsvc']['status'] !== 'UP') {
            $result['status'] = 'DOWN';
        }
        $result['components']['ui'] = $this->checkPort(3000);
        if ($result['components']['ui']['status'] !== 'UP') {
            $result['status'] = 'DOWN';
        }
        $result['components']['websocket'] = $this->checkPort(6001);
        if ($result['components']['websocket']['status'] !== 'UP') {
            $result['status'] = 'DOWN';
        }

        // Database health
        if ($all || in_array('db', $components)) {
            $result['components']['db'] = $this->dbHealth();
            if ($result['components']['db']['status'] !== 'UP') {
                $result['status'] = 'DOWN';
            }
        }

        // Redis health
        if ($all || in_array('redis', $components)) {
            $result['components']['redis'] = $this->redisHealth();
            if ($result['components']['redis']['status'] !== 'UP') {
                $result['status'] = 'DOWN';
            }
        }

        // Queue health
        if ($all || in_array('queue', $components)) {
            $result['components']['queue'] = $this->queueHealth();
            if ($result['components']['queue']['status'] !== 'UP') {
                $result['status'] = 'DOWN';
            }
        }

        // Storage health
        if ($all || in_array('storage', $components)) {
            $result['components']['storage'] = $this->storageHealth();
            if ($result['components']['storage']['status'] !== 'UP') {
                $result['status'] = 'DOWN';
            }
        }

        // Disk health
        if ($all || in_array('diskSpace', $components)) {
            $result['components']['diskSpace'] = $this->diskHealth();
            if ($result['components']['diskSpace']['status'] !== 'UP') {
                $result['status'] = 'DOWN';
            }
        }

        if (!(bool) env('CONTAINER_MODE', false)) {
            // Services health
            $services = [
                'nginx',
                'php8.4-fpm',
                'liman-ui',
                'liman-system',
                'liman-render',
                'liman-socket',
                'supervisor',
                'redis',
                'postgresql'
            ];
            $result['components']['services'] = $this->checkServicesHealth($services);
            foreach ($result['components']['services'] as $service) {
                if ($service['status'] !== 'UP') {
                    $result['status'] = 'DOWN';
                }
            }
        } else {
            $result['components']['services'] = [];
        }
        
        return $result;
    }

    private function getComponentHealth($componentPath)
    {
        $parts = explode('/', $componentPath);
        $main = $parts[0] ?? null;
        $nested = $parts[1] ?? null;
        $all = $this->getHealth();
        if (!isset($all['components'][$main])) {
            return [
                'status' => 'UNKNOWN',
                'details' => ['message' => 'Component not found']
            ];
        }
        $comp = $all['components'][$main];
        // Eğer component services ise, status hesapla
        if ($main === 'services') {
            $statuses = array_column($comp, 'status');
            $overall = in_array('DOWN', $statuses) ? 'DOWN' : (in_array('UNKNOWN', $statuses) ? 'UNKNOWN' : 'UP');
            if ($nested && isset($comp[$nested])) {
                return [
                    'status' => $comp[$nested]['status'],
                    'details' => $comp[$nested]['details']
                ];
            }
            return [
                'status' => $overall,
                'details' => $comp
            ];
        }
        if ($nested && isset($comp['details'][$nested])) {
            return [
                'status' => $comp['status'],
                'details' => $comp['details'][$nested]
            ];
        }
        return $comp;
    }

    private function dbHealth()
    {
        try {
            $result = DB::select('SELECT 1 as result');
            return [
                'status' => 'UP',
                'details' => [
                    'database' => config('database.default'),
                    'result' => $result[0]->result ?? null,
                    'validationQuery' => 'SELECT 1'
                ]
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'DOWN',
                'details' => [
                    'error' => $e->getMessage()
                ]
            ];
        }
    }

    private function diskHealth()
    {
        $root = base_path();
        $total = @disk_total_space($root);
        $free = @disk_free_space($root);
        $threshold = 10485760; // 10MB
        $status = ($free !== false && $free > $threshold) ? 'UP' : 'DOWN';
        return [
            'status' => $status,
            'details' => [
                'total' => $total,
                'free' => $free,
                'threshold' => $threshold
            ]
        ];
    }

    private function redisHealth()
    {
        try {
            $pong = Redis::ping();
            return [
                'status' => $pong ? 'UP' : 'DOWN',
                'details' => [
                    'pong' => $pong
                ]
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'DOWN',
                'details' => [
                    'error' => $e->getMessage()
                ]
            ];
        }
    }

    private function queueHealth()
    {
        try {
            $size = Queue::size();
            return [
                'status' => 'UP',
                'details' => [
                    'size' => $size
                ]
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'DOWN',
                'details' => [
                    'error' => $e->getMessage()
                ]
            ];
        }
    }

    private function storageHealth()
    {
        try {
            $exists = Storage::disk()->exists('/');
            return [
                'status' => $exists ? 'UP' : 'DOWN',
                'details' => [
                    'root_exists' => $exists
                ]
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'DOWN',
                'details' => [
                    'error' => $e->getMessage()
                ]
            ];
        }
    }
}
