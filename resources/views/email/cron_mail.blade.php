<!doctype html>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  </head>
  <body style="font-family: sans-serif;">
    <div style="display: block; margin: auto; max-width: 900px;" class="main">
        <p>Merhaba,</p><br>
        <p><b>{{$user_name}}</b> isimli kullanıcı, <b>{{$server->name}}</b>  sunucusundaki <b>{{$extension->display_name}}</b> eklentisindeki <b>{{$target}}</b> işlemini {{$before}} - {{$now}} tarihleri arasında <b>{{$result}}</b> kere gerçekleştirmiştir.</p><br><br>
        <p>Bilginize.</p><br><br>
        @if(count($data) > 0)
          <h3>İşlemler</h3>
          @foreach ($data as $row)
            @foreach ($row as $key => $value)
              <b>{{ $key }}: </b>{{ $value }}<br>
            @endforeach
            <br><hr /><br>
          @endforeach
        @endif
        <br><br>
        <p>Bu email <a href="https://liman.dev">Liman MYS</a> tarafından oluşturulmuştur.</p>
    </div>
    
    <style>
      .main { background-color: white; }
      a:hover { border-left-width: 1em; min-height: 2em; }
    </style>
  </body>
</html>