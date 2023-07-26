<?php

namespace App\Http\Controllers\API\Server;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Collection;

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
        return server()->extensions()->map(function ($item) {
            $item->updated = Carbon::parse($item->getRawOriginal('updated_at'))->getPreciseTimestamp(3);

            return $item;
        });
    }
}
