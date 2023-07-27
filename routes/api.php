<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\ExtensionController;
use App\Http\Controllers\API\ExternalNotificationController;
use App\Http\Controllers\API\MenuController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\SearchController;
use App\Http\Controllers\API\Server;
use App\Http\Controllers\API\ServerController;
use App\Http\Controllers\API\Settings;
use Illuminate\Support\Facades\Route;

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

Route::post('/notifications/send', [ExternalNotificationController::class, 'accept']);

// Protected Routes
Route::group(['middleware' =>  ['auth:api', 'permissions']], function () {
    // Dashboard Routes
    Route::group(['prefix' => 'dashboard'], function () {
        Route::get('/latest_logged_in_users', [DashboardController::class, 'latestLoggedInUsers']);
        Route::get('/favorite_servers', [DashboardController::class, 'favoriteServers']);
        Route::get('/most_used_extensions', [DashboardController::class, 'mostUsedExtensions']);
    });

    // Search Controller Routes
    Route::post('/search', [SearchController::class, 'search']);

    // Locale
    Route::post('/locale', [ProfileController::class, 'setLocale']);
    Route::group(['prefix' => 'profile'], function () {
        Route::get('/', [ProfileController::class, 'getInformation']);
        Route::post('/', [ProfileController::class, 'setInformation']);
        Route::get('/auth_logs', [ProfileController::class, 'authLogs']);
    });

    // Notifications
    Route::group(['prefix' => 'notifications'], function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread', [NotificationController::class, 'unread']);
        Route::post('/seen', [NotificationController::class, 'seen']);
        Route::post('/read', [NotificationController::class, 'read']);
    });

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
        Route::post('/{server_id}/favorites', [Server\DetailsController::class, 'favorite']);

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
    Route::group(['prefix' => 'settings', 'middleware' => ['admin']], function () {
        // Extension
        Route::group(['prefix' => 'extensions'], function () {
            Route::get('/', [Settings\ExtensionController::class, 'index']);
            Route::post('/upload', [Settings\ExtensionController::class, 'upload']);
            Route::delete('/{extension_id}', [Settings\ExtensionController::class, 'delete']);
            Route::post('/{extension_id}/license', [Settings\ExtensionController::class, 'license']);
            Route::get('/{extension_id}/download', [Settings\ExtensionController::class, 'download']);
            Route::get("/{extension_id}/functions", [Settings\RoleController::class, 'getExtensionFunctions']);
        });

        // Users
        Route::group(['prefix' => 'users'], function () {
            Route::get('/', [Settings\UserController::class, 'index']);
            Route::post('/', [Settings\UserController::class, 'create']);
            Route::delete('/{user_id}', [Settings\UserController::class, 'delete']);
        });

        // Roles
        Route::group(['prefix' => 'roles'], function () {
            Route::get('/', [Settings\RoleController::class, 'index']);
            Route::post('/', [Settings\RoleController::class, 'create']);
            Route::get('/details', [Settings\RoleController::class, 'detailedList']);
            Route::get('/details/csv', [Settings\RoleController::class, 'exportDetailedListAsCsv']);

            Route::group(['prefix' => '{role_id}'], function () {
                Route::get('/', [Settings\RoleController::class, 'show']);
                Route::delete('/', [Settings\RoleController::class, 'delete']);

                Route::get('/users', [Settings\RoleController::class, 'users']);
                Route::post('/users', [Settings\RoleController::class, 'setUsers']);

                Route::get('/servers', [Settings\RoleController::class, 'servers']);
                Route::post('/servers', [Settings\RoleController::class, 'setServers']);

                Route::get('/extensions', [Settings\RoleController::class, 'extensions']);
                Route::post('/extensions', [Settings\RoleController::class, 'setExtensions']);

                Route::get('/functions', [Settings\RoleController::class, 'functions']);
                Route::post('/functions', [Settings\RoleController::class, 'setFunctions']);
                Route::delete('/functions', [Settings\RoleController::class, 'deleteFunctions']);

                Route::get('/liman', [Settings\RoleController::class, 'limanPermissions']);
                Route::post('/liman', [Settings\RoleController::class, 'setLimanPermissions']);

                Route::get('/variables', [Settings\RoleController::class, 'variables']);
                Route::post('/variables', [Settings\RoleController::class, 'setVariables']);
                Route::delete('/variables', [Settings\RoleController::class, 'deleteVariables']);
            });
        });

        // Subscriptions
        Route::group(['prefix' => 'subscriptions'], function () {
            Route::get('/liman', [Settings\SubscriptionController::class, 'limanLicense']);
            Route::post('/liman', [Settings\SubscriptionController::class, 'setLimanLicense']);

            Route::get('/', [Settings\SubscriptionController::class, 'index']);
            Route::get('/{extension}/servers', [Settings\SubscriptionController::class, 'servers']);
            Route::get('/{extension}/{server}', [Settings\SubscriptionController::class, 'show']);
        });

        // Access Control
        Route::group(['prefix' => 'access'], function () {
            // LDAP routes
            Route::group(['prefix' => 'ldap'], function () {
                Route::get('/configuration', [Settings\LdapConnectionController::class, 'getConfiguration']);
                Route::post('/configuration', [Settings\LdapConnectionController::class, 'saveConfiguration']);

                Route::post('/login', [Settings\LdapConnectionController::class, 'auth']);

                Route::group(['prefix' => 'permissions'], function () {
                    Route::get('/users', [Settings\LdapPermissionsController::class, 'getUsers']);
                    Route::post('/users', [Settings\LdapPermissionsController::class, 'setUsers']);

                    Route::get('/groups', [Settings\LdapPermissionsController::class, 'getGroups']);
                    Route::post('/groups', [Settings\LdapPermissionsController::class, 'setGroups']);
                });
            });

            // Keycloak Routes
            Route::group(['prefix' => 'keycloak'], function () {
                Route::get('/configuration', [Settings\KeycloakConnectionController::class, 'getConfiguration']);
                Route::post('/configuration', [Settings\KeycloakConnectionController::class, 'saveConfiguration']);
            });
        });

        // Vault
        Route::group(['prefix' => 'vault'], function () {
            Route::get('/', [Settings\VaultController::class, 'index']);
            Route::post('/', [Settings\VaultController::class, 'create']);
            Route::post('/key', [Settings\VaultController::class, 'createKey']);
            Route::patch('/', [Settings\VaultController::class, 'update']);
            Route::delete('/', [Settings\VaultController::class, 'delete']);
        });

        // Notifications
        Route::group(['prefix' => 'notifications'], function () {
            // External Notifications
            Route::group(['prefix' => 'external'], function () {
                Route::get('/', [Settings\NotificationController::class, 'externalNotifications']);
                Route::post('/', [Settings\NotificationController::class, 'createExternalNotification']);
                Route::delete('/{id}', [Settings\NotificationController::class, 'deleteExternalNotification']);
            });
        });
    });
});
