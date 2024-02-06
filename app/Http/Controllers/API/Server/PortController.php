<?php

namespace App\Http\Controllers\API\Server;

use App\Exceptions\JsonResponseException;
use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Process;
#use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class PortController extends Controller
{
     
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'os' => 'required|string|in:linux,macos,window',
        ]);

        $this->authorize('viewOpenPorts', 'App\Models\Server');

        if ($request->os !== 'linux') {
            throw new JsonResponseException(
                ['message' => 'Sadece Linux sunucularda portları kontrol edebilirsiniz!'],
                '',
                Response::HTTP_FORBIDDEN
            );
        }

        
        $command = 'lsof -i -P -n | grep -v "\-"| awk -F" " "{print $1,$3,$5,$8,$9}" | sed 1,1d';

        $process = Process::fromShellCommandline($command);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }

        $output = trim($process->getOutput());

        if (empty($output)) {
            return response()->json([]);
        }

        $arr = [];
        foreach (explode("\n", $output) as $line) {
            $row = explode(' ', $line);
            $arr[] = [
                'name' => $row[0],
                'username' => $row[1],
                'ip_type' => $row[2],
                'packet_type' => $row[3],
                'port' => $row[4],
            ];
        }

        return response()->json($arr);
    }
}
