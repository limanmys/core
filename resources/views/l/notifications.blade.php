<a href="#" class="dropdown-toggle" data-toggle="dropdown">
        <i class="fa fa-bell-o"></i>
        <span class="label label-warning" id="notificationCount">{{notifications()->count()}}</span>
    </a>
    <ul class="dropdown-menu">
        <li class="header">
            <button class="btn btn-primary float-right" onclick="readNotifications()"><i class="fa fa-check"></i></button>
            <span style="line-height: 40px;font-size: 15px">
                          {{__(":count bildiriminiz var.",[
                            "count" => notifications()->count()
                        ])}}
                      </span>
        </li>
        <li>
            <ul class="menu">
                @foreach (notifications() as $notification)
                    <li>
                        <a href="/bildirim/{{$notification->_id}}">
                            @switch($notification->type)
                                @case('error')
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
    </ul>