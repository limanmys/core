@if(count($notifications) == 0)
    <li class="header alert alert-success" style="margin:15px;">{{__("Hiç okunmamış mesajınız yok")}}</li>
@else
<li>
    <ul id="notificationArea" class="menu" style="list-style: none">
        @foreach($notifications->take(3) as $notification)
            @switch($notification->type)
                @case("notify")
                <li class="notification alert alert-info">
                @break
                @case("success")
                <li class="notification alert alert-success">
                @break
                @case("working")
                @case("onhold")
                <li class="notification alert alert-warning">
                @break
                @case("error")
                <li class="notification alert alert-danger">
                @break
                @default
                <li class="notification alert alert-secondary">
                    @endswitch
                    <button type="button" class="close" onclick="dismissNotification('{{$notification->_id}}')">
                        <span aria-hidden="true">×</span>
                    </button>
                    <b>{{$notification->title}}</b>
                    <p>{{$notification->message}}</p>
                </li>
                @endforeach
    </ul>
</li>
@if(count($notifications) > 3)
    <a href="/bildirimler">Devamı</a>
@endif
@endif