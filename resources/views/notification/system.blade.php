@extends('layouts.app')
@section('content')
    <?php
        $notification = \App\AdminNotification::where('id',(request('notification_id')))->first();
        if(!$notification){
            header("Location: /", true);
            exit();
        }
        switch ($notification->type){
            case "cert_request":
                list($hostname, $port, $server_id) = explode(":",$notification->message);
                $url = route('certificate_add_page') . "?notification_id=$notification->id&hostname=$hostname&port=$port&server_id=$server_id";
                header("Location: $url", true);
                exit();
                break;
            case "liman_update":
                $url = route('settings') . "#update";
                $notification->update([
                    "read" => "true"
                ]);
                header("Location: $url", true);
                exit();
                break;
            case "health_problem":
                $url = route('settings') . "#health";
                $notification->update([
                    "read" => "true"
                ]);
                header("Location: $url", true);
                exit();
                break;
            default:
                break;
        }

    ?>
@endsection