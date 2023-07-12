<?php

namespace App\Http\Controllers\API\Server;

use App\Http\Controllers\Controller;
use Carbon\Carbon;

class ExtensionController extends Controller
{
    public function index()
    {
        $extensions = server()->extensions()->map(function ($item) {
            $item->updated = Carbon::parse($item->getRawOriginal('updated_at'))->getPreciseTimestamp(3);

            return $item;
        });

        return response()->json($extensions);
    }
}
