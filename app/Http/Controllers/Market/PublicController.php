<?php

namespace App\Http\Controllers\Market;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use App\Models\Extension;
use App\Jobs\ExtensionUpdaterJob;
use App\Jobs\LimanUpdaterJob;
use GuzzleHttp\Exception\BadResponseException;
use Illuminate\Contracts\Bus\Dispatcher;

/**
 * Class Public
 * @package App\Http\Controllers\Market
 */
class PublicController extends Controller
{
    
}
