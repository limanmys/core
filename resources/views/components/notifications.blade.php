<a class="nav-link" data-toggle="dropdown" href="#">
    @if(!isset($systemNotification))
    <i class="far fa-bell"></i>
    @else
    <i class="fas fa-cogs"></i>
    @endif
    <span class="badge badge-warning navbar-badge" id="{{isset($systemNotification) ? 'adminNotificationsCount' : 'userNotificationsCount'}}">{{$notifications->count()}}</span>
</a>
<div class="dropdown-menu dropdown-menu-right">
    <a href="#" class="notif-action dropdown-item dropdown-header text-center btn-link @if(count($notifications) == 0) d-none @endif" onclick="@if(!isset($systemNotification)) readNotifications() @else readSystemNotifications() @endif">
        <i class="fas fa-eye" style="font-size: 14px;"></i> {{__('Tümünü Okundu Olarak İşaretle')}}
    </a>

    @if(count($notifications) == 0)
    <a class="dropdown-item d-flex align-items-start no-notif">
        <div class="text" style="width: 100% !important; padding: 15px 0">
            <h4 style="text-align: center; color: grey; font-size: 12px; text-transform: uppercase">{{ __('Okunmamış bildiriminiz bulunmamaktadır.') }}</h4>
        </div>
    </a>
    @endif

    <div class="menu">
    @foreach ($notifications as $notification)
        @php
            $notificationTitle = json_decode($notification->title);

            if (json_last_error() === JSON_ERROR_NONE) {
                $notificationTitle = $notificationTitle->{app()->getLocale()};
            } else {
                $notificationTitle = $notification->title;
            }
        @endphp
        @switch($notification->type)
            @case('error')
            @case('health_problem')
            @case('liman_update')
            <a class="dropdown-item d-flex align-items-start" onclick="window.location.href = '/bildirim/{{$notification->id}}'" href="/bildirim/{{$notification->id}}">
                <div class="text">
                    <h4 style="color: #ff4444">{{ $notificationTitle }}</h4>
                    <span class="time">{{ $notification->created_at->diffForHumans() }}</span>
                </div>
            </a>
            @break
            @default
            <a class="dropdown-item d-flex align-items-start" onclick="window.location.href = '/bildirim/{{$notification->id}}'" href="/bildirim/{{$notification->id}}">
                <div class="text">
                    <h4>{{ $notificationTitle }}</h4>
                    <span class="time">{{ $notification->created_at->diffForHumans() }}</span>
                </div>
            </a>
            @break
        @endswitch
    @endforeach
    </div>

    
    <a class="notif-action dropdown-item text-center btn-link @if(count($notifications) == 0) d-none @endif" style="background: #fff !important" href="{{isset($systemNotification) ? route('all_system_notifications') : route('all_user_notifications')}}">
        {{ __('Tümünü Gör') }}
        <span class="fas fa-arrow-right" style="font-size: 14px;"></span>
    </a>
</div>