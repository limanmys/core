<?php

if (!function_exists('respond')) {
    function respond($message, $status = 200)
    {
        if (\request()->wantsJson()) {
            return response()->json([
                "message" => __($message),
                "status" => $status
            ], $status);
        } else {
            return response()->view('general.error', [
                "message" => __($message),
                "status" => $status
            ], $status);
        }
    }
}

if (!function_exists('notifications')) {
    function notifications()
    {
        return \App\Notification::where([
            "user_id" => \Auth::id(),
            "read" => false
        ])->orderBy('updated_at', 'desc')->take(5)->get();
    }
}

if (!function_exists('log')) {
    function log($message)
    {
        Log::info(Auth::id() . ":" . $message);
    }
}

if (!function_exists('server_log')) {
    function server_log($server_id, $message)
    {
        Log::info(Auth::id() . ":" . $server_id . ":" . $message);
    }
}

if (!function_exists('server')) {
    function server()
    {
        $server = request('server');
        $key = \App\Key::where('server_id',$server->_id)->first();
        $server->key = $key;
        return $server;
    }
}

if (!function_exists('servers')) {
    function servers()
    {
        return \App\Server::getAll();
    }
}

if (!function_exists('extensions')) {
    function extensions()
    {
        return \App\Extension::all();
    }
}

if (!function_exists('getObject')) {
    function getObject($type, $id = null)
    {
        // Check for type
        switch ($type) {
            case "Script":
            case "script":
                return \App\Script::where('_id', $id)->first();
                break;
            case "Extension":
            case "extension":
                return \App\Extension::where('_id', $id)->first();
                break;
            case "Server":
            case "server":
                return \App\Server::where('_id', $id)->first();
                break;
            default:
                return false;
        }
    }
}

if (!function_exists('objectToArray')) {
    function objectToArray($array,$key, $value)
    {
        $combined_array = [];
        foreach($array as $item){
            $combined_array[$item->__get($key)] = $item->__get($value);
        }
        return $combined_array;
    }
}