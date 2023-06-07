<?php

namespace App\Http\Controllers\API\Server;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ExtensionController extends Controller
{
    public function index()
    {
        $extensions = server()->extensions();

        return response()->json($extensions);
    }
}
