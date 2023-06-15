<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ExtensionController;
use App\Http\Controllers\API\MenuController;
use App\Http\Controllers\API\SearchController;
use App\Http\Controllers\API\Server;
use App\Http\Controllers\API\ServerController;
use App\Http\Controllers\API\Settings;
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
        Route::post('/', [ServerController::class, 'create']);

        // Server Creation Validations
        Route::post('/check_access', [ServerController::class, 'checkAccess']);
        Route::post('/check_connection', [ServerController::class, 'checkConnection']);
        Route::post('/check_name', [ServerController::class, 'checkName']);

        Route::group(['prefix' => '{server_id}', 'middleware' => ['server']], function () {
            Route::get('/', [Server\DetailsController::class, 'server']);
            Route::get('/specs', [Server\DetailsController::class, 'specs']);

            // Stats
            Route::group(['prefix' => 'stats'], function () {
                Route::get('/', [Server\DetailsController::class, 'stats']);
                Route::get('/cpu', [Server\DetailsController::class, 'topCpuProcesses']);
                Route::get('/ram', [Server\DetailsController::class, 'topMemoryProcesses']);
                Route::get('/disk', [Server\DetailsController::class, 'topDiskUsage']);
            });

            // Extensions
            Route::group(['prefix' => 'extensions'], function () {
                // Extension List That Assigned To Server
                Route::get('/', [Server\ExtensionController::class, 'index']);

                // Extension Renderer
                Route::match(['GET', 'POST'], '/{extension_id}', [ExtensionController::class, 'render'])
                    ->middleware(['extension']);
            });

            // Services
            Route::group(['prefix' => 'services'], function () {
                Route::get('/', [Server\ServiceController::class, 'index']);
                Route::post('/status', [Server\ServiceController::class, 'status']);
                Route::post('/start', [Server\ServiceController::class, 'start']);
                Route::post('/stop', [Server\ServiceController::class, 'stop']);
                Route::post('/restart', [Server\ServiceController::class, 'restart']);
                Route::post('/enable', [Server\ServiceController::class, 'enable']);
                Route::post('/disable', [Server\ServiceController::class, 'disable']);
            });

            // Packages
            Route::group(['prefix' => 'packages'], function () {
                Route::get('/', [Server\PackageController::class, 'index']);
            });

            // Updates
            Route::group(['prefix' => 'updates'], function () {
                Route::get('/', [Server\UpdateController::class, 'index']);
            });

            // Access Logs
            Route::group(['prefix' => 'access_logs'], function () {
                Route::get('/', [Server\AccessLogController::class, 'index']);
                Route::get('/{log_id}', [Server\AccessLogController::class, 'details']);
            });

            // Ports
            Route::group(['prefix' => 'ports'], function () {
                Route::get('/', [Server\PortController::class, 'index']);
            });

            // Users
            Route::group(['prefix' => 'users'], function () {
                Route::get('/local', [Server\UserController::class, 'getLocalUsers']);
                Route::post('/local', [Server\UserController::class, 'addLocalUser']);

                Route::get('/groups', [Server\UserController::class, 'getLocalGroups']);
                Route::post('/groups', [Server\UserController::class, 'addLocalGroup']);
                Route::get('/groups/users', [Server\UserController::class, 'getLocalGroupDetails']);
                Route::post('/groups/users', [Server\UserController::class, 'addLocalGroupUser']);

                Route::get('/sudoers', [Server\UserController::class, 'getSudoers']);
                Route::post('/sudoers', [Server\UserController::class, 'addSudoers']);
                Route::delete('/sudoers', [Server\UserController::class, 'deleteSudoers']);
            });
        });
    });

    // Extension Controller
    Route::group(['prefix' => 'extensions'], function () {
        Route::get("/", [ExtensionController::class, 'index']);
        Route::post("/assign", [ExtensionController::class, 'assign'])
            ->middleware("server");
        Route::post("/unassign", [ExtensionController::class, 'unassign'])
            ->middleware("server");
    });

    // Settings
    Route::group(['prefix' => 'settings'], function () {
        // Extension
        Route::group(['prefix' => 'extensions'], function () {
            Route::get('/', [Settings\ExtensionController::class, 'index']);
            Route::post('/upload', [Settings\ExtensionController::class, 'upload']);
            Route::delete('/{extension_id}', [Settings\ExtensionController::class, 'delete']);
            Route::post('/{extension_id}/license', [Settings\ExtensionController::class, 'license']);
            Route::get('/{extension_id}/download', [Settings\ExtensionController::class, 'download']);
        });

        // Users
        Route::group(['prefix' => 'users'], function () {
            Route::get('/', [Settings\UserController::class, 'index']);
            Route::post('/', [Settings\UserController::class, 'create']);
            Route::delete('/{user_id}', [Settings\UserController::class, 'delete']);
        });
    });
});
