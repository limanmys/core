<html>
    <body>
        <h2>Merhaba {{$user->name}}</h2>
        <h4>{{$notification->title}}</h4>
        {{$notification->type}}
        <p>
            {{$notification->message}}
        </p>
    </body>
</html>