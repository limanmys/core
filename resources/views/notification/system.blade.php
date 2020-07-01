<?php
$notification = \App\Models\AdminNotification::where(
    'id',
    request('notification_id')
)->first();
if (!$notification) {
    header("Location: /", true);
    exit();
}
switch ($notification->type) {
    case "cert_request":
        list($hostname, $port, $server_id) = explode(
            ":",
            $notification->message
        );
        $url =
            route('certificate_add_page') .
            "?notification_id=$notification->id&hostname=$hostname&port=$port&server_id=$server_id";
        header("Location: $url", true);
        exit();
        break;
    case "liman_update":
        $url = route('settings') . "#limanMarket";
        $notification->update([
            "read" => "true",
        ]);
        header("Location: $url", true);
        exit();
        break;
    case "health_problem":
        $url = route('settings') . "#health";
        $notification->update([
            "read" => "true",
        ]);
        header("Location: $url", true);
        exit();
        break;
    case "new_module":
        $url = route('modules_index');
        $notification->update([
            "read" => "true",
        ]);
        header("Location: $url", true);
        exit();
        break;
    case "extension_update":
        $url = route('settings') . "#extensions";
        $notification->update([
            "read" => "true",
        ]);
        header("Location: $url", true);
        exit();
        break;
    default:
        break;
}
?>

@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-md-12">
            @include('errors')
            <div class="timeline">
                <div class="time-label">
                    <span class="bg-green">
                        {{\Carbon\Carbon::parse($notification->created_at)->format("d.m.Y")}}
                    </span>
                </div>
                <div>
                    <div class="timeline-item">
                        <span class="time"><i class="fas fa-clock"></i> {{\Carbon\Carbon::parse($notification->created_at)->format("h:i:s")}}</span>

                        <h3 class="timeline-header">
                            @if(!$notification->read)<a href="javascript:void(0)">@endif
                                {{$notification->title}}
                                @if(!$notification->read)</a>@endif
                        </h3>

                        <div class="timeline-body">
                            {!!$notification->message!!}
                        </div>
                        <div class="timeline-footer">
                            @if(!$notification->read)
                                <a class="btn btn-primary btn-xs mark_read"
                                notification-id="{{$notification->id}}">{{__('Okundu Olarak İşaretle')}}</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        $('.mark_read').click(function () {
            var data = new FormData();
            data.append('notification_id', $(this).attr('notification-id'));
            request('{{route('notification_read')}}', data, function (response) {
                location.reload();
            }, function(response){
                var error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            });
        });
        $('.delete_not').click(function () {
            var data = new FormData();
            data.append('notification_id', $(this).attr('notification-id'));
            request('{{route('notification_delete')}}', data, function (response) {
                partialPageRequest("{{route('all_user_notifications')}}");
            }, function(response){
                var error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            });
        });
    </script>
@endsection