<?php
$item = \App\Notification::where([
    "user_id" => auth()->id(),
    "id" => request('notification_id')
])->first();
if(!$item){
    if(auth()->user()->isAdmin() && \App\AdminNotification::find(request('notification_id'))->exists()){
        header("Location: " . route('system_notification',[
                "notification_id" => request('notification_id')
            ]), true);
        exit();
    }else{
        return redirect()->back();
    }
}

?>

@extends('layouts.app')
@section('content')
    @include('l.errors')
    <ul class="timeline">
        <li class="time-label">
        <span class="bg-green">
            {{\Carbon\Carbon::parse($item->created_at)->format("d.m.Y")}}
        </span>
        </li>
        <li>
            @if($item->read)
                <i class="fa fa-bell-o @if($item->type=="error") bg-red @else bg-blue @endif"></i>
            @else
                <i class="fa fa-bell @if($item->type=="error") bg-red @else bg-blue @endif"></i>
            @endif
            <div class="timeline-item">
                <span class="time"><i class="fa fa-clock-o"></i> {{\Carbon\Carbon::parse($item->created_at)->format("h:i:s")}}</span>

                <h3 class="timeline-header">
                    @if(!$item->read)<a href="javascript:void(0)">@endif
                        {{$item->title}}
                        @if(!$item->read)</a>@endif
                </h3>

                <div class="timeline-body">
                    {{$item->message}}
                </div>
                <div class="timeline-footer">
                    @if(!$item->read)
                        <a class="btn btn-primary btn-xs mark_read"
                           notification-id="{{$item->id}}">{{__('Okundu Olarak İşaretle')}}</a>
                    @endif
                    <a class="btn btn-danger btn-xs delete_not" notification-id="{{$item->id}}">{{__('Sil')}}</a>
                </div>
            </div>
        </li>
    </ul>
    <script>
        $('.mark_read').click(function () {
            let data = new FormData();
            data.append('notification_id', $(this).attr('notification-id'));
            request('{{route('notification_read')}}', data, function (response) {
                location.reload();
            });
        });
        $('.delete_not').click(function () {
            let data = new FormData();
            data.append('notification_id', $(this).attr('notification-id'));
            request('{{route('notification_delete')}}', data, function (response) {
                location.href = "{{route('all_user_notifications')}}";
            });
        });
    </script>
@endsection