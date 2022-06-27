<a class="nav-link" data-toggle="dropdown" href="#">
    @if(!isset($systemNotification))
        <i class="far fa-bell"></i>
    @else
        <i class="fas fa-cogs"></i>
    @endif
    <span class="badge badge-warning navbar-badge" id="{{isset($systemNotification) ? 'adminNotificationsCount' : 'userNotificationsCount'}}">{{$notifications->count()}}</span>
</a>
<div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
    <span class="dropdown-item dropdown-header" onclick="@if(!isset($systemNotification)) readNotifications() @else readSystemNotifications() @endif">
        {{__('Tümünü Okundu Olarak İşaretle')}}
    </span>
    <div class="menu" style="max-height: 245px; overflow: scroll; overflow-x: hidden; overflow-y: auto;">
        @foreach ($notifications as $notification)
            @php
                $notificationTitle = json_decode($notification->title);

                if (json_last_error() === JSON_ERROR_NONE) {
                    $notificationTitle = $notificationTitle->{app()->getLocale()};
                } else {
                    $notificationTitle = $notification->title;
                }
            @endphp
            <div class="dropdown-divider"></div>
                @switch($notification->type)
                    @case('error')
                    @case('health_problem')
                    @case('liman_update')
                    <a onclick="window.location.href = '/bildirim/{{$notification->id}}'" href="/bildirim/{{$notification->id}}" class="dropdown-item" style="color: #f56954;width: 100%">
                        {{ $notificationTitle }}
                    </a>
                    @break
                    @default
                    <a onclick="window.location.href = '/bildirim/{{$notification->id}}'" href="/bildirim/{{$notification->id}}" class="dropdown-item" style="color: #00a65a;width: 100%">
                        {{ $notificationTitle }}
                    </a>
                    @break
                @endswitch
            </a>
        @endforeach
    </div>
    <div class="dropdown-divider"></div>
    <a class="dropdown-item dropdown-footer" href="{{isset($systemNotification) ? route('all_system_notifications') : route('all_user_notifications')}}">{{__('Tümünü gör')}}</a>
</div>