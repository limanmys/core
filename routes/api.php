<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ExtensionController;
use App\Http\Controllers\API\MenuController;
use App\Http\Controllers\API\SearchController;
use App\Http\Controllers\API\Server;
use Illuminate\Support\Facades\Route;

Route::get('/market/bagla', function () {
    return view('redirect', [
        'url' => route('connect_market', ['code' => request('code'), 'auth' => request('auth')]),
    ]);
});

Route::get("/", function () {
    return response()->json([
        'message' => 'Welcome to the Liman MYS API!',
        'version' => '2.0',
    ]);
});

Route::group([
    'prefix' => 'auth'
], function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/user', [AuthController::class, 'userProfile']);
});

// Protected Routes
Route::group(['middleware' =>  ['auth:api', 'permissions']], function () {
    // Search Controller Routes
    Route::post('/search', [SearchController::class, 'search']);

    // Menu Items
    Route::group(['prefix' => 'menu'], function () {
        Route::get('/servers', [MenuController::class, 'servers']);
        Route::get('/servers/{server}', [MenuController::class, 'serverDetails']);
    });

    // Server Controller
    Route::group(['prefix' => 'servers'], function () {
        // Server Details
        Route::get('/', [Server\DetailsController::class, 'index']);

        Route::group(['prefix' => '{server_id}'], function () {
            Route::get('/', [Server\DetailsController::class, 'server'])
                ->middleware(['server']);
            Route::get('/specs', [Server\DetailsController::class, 'specs'])
                ->middleware(['server']);

            // Stats
            Route::group(['prefix' => 'stats'], function () {
                Route::get('/', [Server\DetailsController::class, 'stats'])
                    ->middleware(['server']);
                Route::get('/cpu', [Server\DetailsController::class, 'topCpuProcesses'])
                    ->middleware(['server']);
                Route::get('/ram', [Server\DetailsController::class, 'topMemoryProcesses'])
                    ->middleware(['server']);
                Route::get('/disk', [Server\DetailsController::class, 'topDiskUsage'])
                    ->middleware(['server']);
            });

            // Extensions
            Route::group(['prefix' => 'extensions'], function () {
                // Extension List That Assigned To Server
                Route::get('/', [Server\ExtensionController::class, 'index'])
                    ->middleware(['server']);

                // Extension Renderer
                Route::match(['GET', 'POST'], '/{extension_id}', [ExtensionController::class, 'render'])
                    ->middleware(['server', 'extension']);
            });

            
        });
    });    
});
