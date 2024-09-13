<?php

use App\Models\Liman;
use Illuminate\Support\Facades\Artisan;

Artisan::command('register_liman', function () {
    Liman::updateOrCreate([
        'last_ip' => env('LIMAN_IP', trim((string) `hostname -I | cut -d' ' -f1 | xargs`)),
    ], [
        'last_ip' => env('LIMAN_IP', trim((string) `hostname -I | cut -d' ' -f1 | xargs`)),
    ]);
})->describe('Register liman');
