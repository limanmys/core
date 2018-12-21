@foreach($notifications->take(5) as $notification)
    @switch($notification->type)
        @case("notify")
        @case("success")
            <li class="notification alert alert-success">
            @break
        @case("working")
            <li class="notification alert alert-warning">
            @break
        @case("error")
            <li class="notification alert alert-danger">
            @break
        @default
            <li class="notification alert alert-secondary">
            @endswitch
            <b>{{$notification->title}}</b>
            <p>{{$notification->message}}</p>
        </li>
@endforeach