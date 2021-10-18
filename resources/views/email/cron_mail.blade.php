From: {{$from}}
To: {{$to}}
Subject: {{$subject}}
Content-Type: multipart/alternative; boundary="boundary-string"

--boundary-string
Content-Type: text/plain; charset="utf-8"
Content-Transfer-Encoding: quoted-printable
Content-Disposition: inline

Congrats for sending test email with Mailtrap!

Inspect it using the tabs above and learn how this email can be improved.
Now send your email using our fake SMTP server and integration of your choice!

Good luck! Hope it works.

--boundary-string
Content-Type: text/html; charset="utf-8"
Content-Transfer-Encoding: quoted-printable
Content-Disposition: inline

<!doctype html>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  </head>
  <body style="font-family: sans-serif;">
    <div style="display: block; margin: auto; max-width: 900px;" class="main">
        <p>Merhaba</p><br>
        <p><b>{{$user->name}}</b> isimli kullanıcı, <b>{{$server->name}}</b>  sunucusundaki <b>{{$extension->display_name}}</b> eklentisindeki <b>{{$target}}</b> işlemini {{$before}} - {{$now}} tarihleri arasında <b>{{$result}}</b> kere gerçekleştirmiştir.</p><br><br>
        @if(count($data) > 0)
          <h3>İşlemler</h3>
          @foreach ($data as $row)
            @foreach ($row as $key => $value)
              <b>{{ $key }}: </b>{{ $value }}<br>
            @endforeach
            <br><br>------------<br><br>
          @endforeach
        @endif
        <p>Saygılarımızla</p><br><br>
        <p>Bu email <a href="https://liman.dev">Liman MYS</a> tarafından oluşturulmuştur.</p>
    </div>
    
    <style>
      .main { background-color: white; }
      a:hover { border-left-width: 1em; min-height: 2em; }
    </style>
  </body>
</html>

--boundary-string--