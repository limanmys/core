<?php

use App\Http\Controllers\API\HealthController;
use Illuminate\Support\Facades\Route;

// Isolated health routes for no middleware
Route::middleware([])
    ->get(
        '/api/health/{component?}/{nested?}', 
        [HealthController::class, 'health']
    );
