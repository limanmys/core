<a class="nav-link" data-toggle="dropdown" href="#">
    @if(!isset($systemNotification))
        <i class="far fa-bell"></i>
    @else
        <i class="fas fa-cogs"></i>
    @endif
    <span class="badge badge-warning navbar-badge" id="{{isset($systemNotification) ? 'adminNotificationsCount' : 'userNotificationsCount'}}">{{$notifications->count()}}</span>
</a>
<div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
    <span class="dropdown-item dropdown-header">
        <button class="btn btn-primary float-right" 
            onclick="@if(!isset($systemNotification)) readNotifications() @else readSystemNotifications() @endif">
            <i class="far fa-check"></i>
        </button>
    </span>
    @foreach ($notifications as $notification)
    <div class="dropdown-divider"></div>
    <a href="/bildirim/{{$notification->id}}" class="dropdown-item">
        @switch($notification->type)
            @case('error')
            @case('health_problem')
            @case('liman_update')
            <span class="float-right text-muted text-sm" style="color: #f56954;width: 100%">
                    {{$notification->title}}
                </span>
            @break
            @default
            <span class="float-right text-muted text-sm" style="color: #00a65a;width: 100%">
                    {{$notification->title}}
                </span>
            @break
        @endswitch
    </a>
    @endforeach
    <div class="dropdown-divider"></div>
    <a class="dropdown-item dropdown-footer" href="{{isset($systemNotification) ? route('all_system_notifications') : route('all_user_notifications')}}">{{__('Tümünü gör')}}</a>
</div>