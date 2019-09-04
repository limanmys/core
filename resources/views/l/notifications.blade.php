<a href="#" class="dropdown-toggle" data-toggle="dropdown">
@if(!isset($systemNotification))
<i class="fa fa-bell"></i>
@else
    <i class="fa fa-cogs"></i>
@endif
    <span class="label label-warning" id="{{isset($systemNotification) ? 'adminNotificationsCount' : 'userNotificationsCount'}}">{{$notifications->count()}}</span>
</a>
<ul class="dropdown-menu">
    <li class="header">
    <button class="btn btn-primary float-right" 
        onclick="@if(!isset($systemNotification)) readNotifications() @else readSystemNotifications() @endif">
        <i class="fa fa-check"></i>
    </button>
    </li>
    <li>
        <ul class="menu">
            @foreach ($notifications as $notification)
                <li>
                    <a href="/bildirim/{{$notification->id}}">
                        @switch($notification->type)
                            @case('error')
                            @case('health_problem')
                            @case('liman_update')
                            <span style="color: #f56954;width: 100%">
                                    {{$notification->title}}
                                </span>
                            @break
                            @default
                            <span style="color: #00a65a;width: 100%">
                                    {{$notification->title}}
                                </span>
                            @break
                        @endswitch
                    </a>
                </li>
            @endforeach
        </ul>
    </li>
    <li class="footer"><a href="{{isset($systemNotification) ? route('all_system_notifications') : route('all_user_notifications')}}">{{__('Tümünü gör')}}</a></li>
</ul>