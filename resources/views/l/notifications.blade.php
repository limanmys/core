<a href="#" class="dropdown-toggle" data-toggle="dropdown">
    <i class="fa fa-bell-o"></i>
    <span class="label label-warning" id="{{isset($id) ? $id : "notificationCount"}}">{{$notifications->count()}}</span>
</a>
<ul class="dropdown-menu">
    <li class="header">
        @if(!isset($systemNotification))
        <button class="btn btn-primary float-right" onclick="readNotifications()"><i class="fa fa-check"></i></button>
            <span style="line-height: 40px;font-size: 15px">
                          {{__(":count bildiriminiz var.",[
                            "count" => $notifications->count()
                        ])}}
                      </span>
            @else
            <span style="line-height: 40px;font-size: 15px">
                          {{__(":count sistem bildirimi var.",[
                            "count" => $notifications->count()
                        ])}}
                      </span>
        @endif

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
    @if(!isset($systemNotification))
    <li class="footer"><a href="{{route('all_user_notifications')}}">{{__('Tümünü gör')}}</a></li>
    @endif
</ul>