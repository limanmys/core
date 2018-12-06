@extends('layouts.app')



@section('content')

    <link href="js/form-validator/theme-default.min.css" rel="stylesheet" type="text/css"/>
    <script src="js/form-validator/jquery.form-validator.min.js"></script>
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
        function state(event) {
            var id_error = document.getElementById("id_error");
            var id_error_success = document.getElementById("id_error_success");
            id_error.textContent = "";
            id_error_success = "";
        }
    </script>
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
    <!-- Modal -->
    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
         aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title" id="exampleModalLabel">Sunucu Ekle</h1>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form>
                    <div class="modal-body">
                        <div class="form-group">
                            <h3>Adı</h3>
                            <input id="add_name" type="text" class="form-control" placeholder="Sunucu kısa adı" data-validation="required" data-validation-error-msg="Girilmesi Zorunlu Alan">
                        </div>
                        <div class="form-group">
                            <h3>İp Adresi</h3>
                            <input id="add_ip" type="text" class="form-control" placeholder="Sunucu Ipv4 Adresi"
                                   data-validation-help="Ex Ip:192.168.56.10" data-validation="custom"
                                   data-validation-regexp="^(?=.*[^\.]$)((25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.?){4}$"
                                   data-validation-error-msg="Geçerli Ip Adress Girin." onkeypress="state(this)">
                        </div>
                        <div class="form-group">
                            <h3>Bağlantı Portu</h3>
                            <input id="add_port" type="text" class="form-control" placeholder="Bağlantı Portu"
                                   value="22">
                            <div class="login-error" id="id_error"></div>
                            <div class="login-error" id="id_error_success"></div>
                        </div>
                        <div class="form-group">
                            <h3>Şehirler</h3>
                            @include("server.cities")
                        </div>
                        <div class="form-group">
                            <h3>Anahtar Kullanıcı Adı</h3>
                            <input id="add_username" type="text" class="form-control"
                                   placeholder="Anahtar Kullanıcı Adı">
                        </div>
                        <div class="form-group">
                            <h3>Anahtar Parola</h3>
                            <input id="add_password" type="password" class="form-control" placeholder="Anahtar Parola">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                        <button type="button" class="btn btn-success" onclick="add()">Ekle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        $.validate({
            addValidClassOnAll: true
        });
        function add() {
            var name = $("#add_name").val();
            var ip = $("#add_ip").val();
            var port = $("#add_port").val();
            var city_value = $("#city").val();
            var username = $("#add_username").val();
            var password = $("#add_password").val();
            var city = $("#city").val();
            $.post("{{route('server_add')}}", {
                name: name,
                ip_address: ip,
                port: port,
                username: username,
                city:city_value,
                password: password,
                city : city
            }, function (data, status) {
                if (data["result"] === 200) {
                    $('#exampleModal').modal('hide');
                    window.location.replace("{{route('servers')}}" + "/" + data["id"]);
                } else {
                    alert("Hata!");
                }
            });
        }
    </script>
@endsection