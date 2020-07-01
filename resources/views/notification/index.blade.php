@extends('layouts.app')
@section('content')
@php($notifications = $notifications->groupBy(function ($date) {
    return \Carbon\Carbon::parse($date->created_at)->format("d.m.Y");
}))
<div class="row">
    <div class="col-md-12">
        @if($system)
            <button type="button" onclick="readSystemNotifications();reload();" style="margin-bottom: 5px;" class="btn btn-default btn-flat">{{__('Tümünü Okundu Olarak İşaretle')}}</button>
        @else
            <button type="button" onclick="readNotifications();reload();" style="margin-bottom: 5px;" class="btn btn-default btn-flat">{{__('Tümünü Okundu Olarak İşaretle')}}</button>
            <button type="button" id="delete_read" style="margin-bottom: 5px;" class="btn btn-default btn-flat">{{__('Okunanları Sil')}}</button>
        @endif
        
        @include('errors')    
        <div class="timeline">
            @foreach ($notifications as $date => $items)
                 <div class="time-label">
                    <span class="bg-green">
                        {{$date}}
                    </span>
                </div>
                @foreach ($items as $item)
                     <div>
                        @if($item->read)
                            <i class="far fa-bell @if($item->type=="error") bg-red @else bg-blue @endif"></i>
                        @else
                            <i class="fas fa-bell @if($item->type=="error") bg-red @else bg-blue @endif"></i>
                        @endif
                        <div class="timeline-item">
                            <span class="time"><i class="fa fa-clock-o"></i> {{\Carbon\Carbon::parse($item->created_at)->format("h:i:s")}}</span>
                
                            <h3 class="timeline-header">
                                @if(!$item->read)<a href="javascript:void(0)">@endif
                                    {{$item->title}}
                                @if(!$item->read)</a>@endif
                            </h3>
                
                            <div class="timeline-body">
                                {!!$item->message!!}
                            </div>
                            <div class="timeline-footer">
                                @if(!$item->read)
                                    <a class="btn btn-primary btn-xs mark_read" notification-id="{{$item->id}}">{{__('Okundu Olarak İşaretle')}}</a>
                                @endif
                                @if(!$system)
                                <a class="btn btn-danger btn-xs delete_not" notification-id="{{$item->id}}">{{__('Sil')}}</a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            @endforeach
        </div>
    </div>
</div>
<script>
    $('#read_all').click(function(){
        var data = new FormData();
        request('{{route('notifications_read')}}', data, function(response){
            location.reload();
        }, function(response){
            var error = JSON.parse(response);
            showSwal(error.message,'error',2000);
        });
    });
    $('#delete_read').click(function(){
        var data = new FormData();
        request('{{route('notification_delete_read')}}', data, function(response){
            location.reload();
        }, function(response){
            var error = JSON.parse(response);
            showSwal(error.message,'error',2000);
        });
    });
    $('.mark_read').click(function(){
        var data = new FormData();
        data.append('notification_id', $(this).attr('notification-id'));
        request('{{route('notification_read')}}', data, function(response){
            location.reload();
        }, function(response){
            var error = JSON.parse(response);
            showSwal(error.message,'error',2000);
        });
    });
    $('.delete_not').click(function(){
        var data = new FormData();
        data.append('notification_id', $(this).attr('notification-id'));
        request('{{route('notification_delete')}}', data, function(response){
            location.reload();
        }, function(response){
            var error = JSON.parse(response);
            showSwal(error.message,'error',2000);
        });
    });
</script>
@endsection
