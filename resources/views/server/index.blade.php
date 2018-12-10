@extends('layouts.app')



@section('content')
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">{{__("Sunucular")}}</h1>
    </div>
    <button type="button" class="btn btn-success" data-toggle="modal" data-target="#exampleModal">
        {{__("Server Ekle")}}
    </button><br><br>

    <table class="table">
        <thead>
        <tr>
            <th scope="col">{{__("Sunucu Adı")}}</th>
            <th scope="col">{{__("İp Adresi")}}</th>
            <th scope="col">{{__("Port")}}</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($servers as $server)
            <tr onclick="location.href = '/sunucular/{{$server->id}}';" class="highlight">
                <td>{{$server->name}}</td>
                <td>{{$server->ip_address}}</td>
                <td>{{$server->port}}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <script>
        $(document).ready(function () {
            $("#add_port").focusout(function () {
                var ip = $("#add_ip").val();
                var port = $("#add_port").val();
                var id_error = document.getElementById("id_error");
                var id_error_success = document.getElementById("id_error_success");
                document.getElementById("id_error").style.color = 'red';

                $.post("/api/status", {
                    ip: ip,
                    port: port,
                }, function (data, status) {
                    if (data === "true") {
                        id_error.textContent = "";
                        id_error_success.textContent = "Herşey Yolunda";
                        ip.textContent.style.color = 'green';
                    }
                    else
                    {
                        id_error.textContent = "Bağlantı Gerçekleştirilemedi";
                        id_error_success.textContent = "";
                    }

                });
            });
        });

    </script>
    @include('modal',[
                     "id"=>"exampleModal",
                     "title" => __("Sunucu Ekle"),
                     "url" => "/sunucu/ekle",
                     "inputs" => [
                         __("Adı") => "name:text",
                         __("İp Adresi") => "ipaddress:text",
                         __("Bağlantı Portu") => "port:text",
                         __("Şehirler") => "port:text",
                         __("Anahtar Kullanıcı Adı") => "username:text",
                         __("Anahtar Parola") => "password:password"
                     ],
                     "submit_text" => __("Ekle")
                 ])

@endsection