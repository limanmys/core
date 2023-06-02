<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ExtensionController;
use App\Http\Controllers\API\MenuController;
use App\Http\Controllers\API\SearchController;
use App\Http\Controllers\API\ServerController;
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
    Route::get('/menu/servers', [MenuController::class, 'servers']);
    Route::get('/menu/servers/{server}', [MenuController::class, 'serverDetails']);

    // Server Controller
    Route::get('/servers', [ServerController::class, 'index']);

    // Extension Controller
    Route::match(['GET', 'POST'], '/servers/{server_id}/extensions/{extension_id}', [ExtensionController::class, 'render'])
        ->middleware(['server', 'extension']);
});
