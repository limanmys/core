@extends('layouts.app')

@section('content')
    <link href="js/form-validator/theme-default.min.css" rel="stylesheet" type="text/css"/>
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
    <script src="js/form-validator/jquery.form-validator.min.js"></script>

    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h2>SSH Anahtarları</h2>
    </div>
    <button type="button" class="btn btn-success" data-toggle="modal" data-target="#exampleModal">
        Anahtar Ekle
    </button><br><br>
    <table class="table">
        <thead>
        <tr>
            <th scope="col">Anahtar Adı</th>
            <th scope="col">Kullanıcı</th>
            <th scope="col">Sunucu</th>
        </tr>
        </thead>
        <tbody data-toggle="modal" data-target="#duzenle">
        @foreach ($keys as $key)
            <tr onclick="" class="highlight">
                <td>{{$key->name}}</td>
                <td>{{$key->username}}</td>
                <td>{{$key->server_name}}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <script>
        function add() {
            var name = $("#add_name").val();
            var username = $("#add_username").val();
            var password = $("#add_password").val();
            var server_id = $("#add_server").val();
            $.post("{{route('key_add')}}" ,{
                name : name,
                username : username,
                password : password,
                server_id : server_id
            },function (data,status) {
                if(data["result"] === 200){
                    location.reload();
                }else{
                    alert("Hata!");
                }
            });
        }
        function degistir() {

            var name = $("#change_name").val();
            var username = $("#change_username").val();
            var password = $("#change_password").val();
            var server_id = $("#change_server").val();
            $.post("" ,{
                name : name,
                username : username,
                password : password,
                server_id : server_id
            },function (data,status) {
                if(data["result"] === 200){
                    location.reload();
                }else{
                    alert("Hata!");
                }
            });
        }
    </script>

    <!-- Modal -->
    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title" id="exampleModalLabel">Anahtar Ekle</h1>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form>
                    <div class="modal-body">
                        <div class="form-group">
                            <h3>Adı</h3>
                            <input id="add_name" type="text" class="form-control" placeholder="Anahtar kısa adı" data-validation="length" data-validation-length="min4" data-validation-error-msg="4 karakterden az olmaz.">
                        </div>
                        <div class="form-group">
                            <h3>Sunucu</h3>
                            <select class="form-control" id="add_server">
                                @foreach ($servers as $server)
                                    <option value="{{$server->id}}">{{$server->name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <h3>Kullanıcı Adı</h3>
                            <input id="add_username" type="text" class="form-control" placeholder="Anahtar Kullanıcı Adı" data-validation="required" data-validation-error-msg="Girilmesi Zorunlu Alan">
                        </div>
                        <div class="form-group">
                            <h3>Parola</h3>
                            <input id="add_password" data-validation-error-msg="Girilmesi zorunlu alan." placeholder="Parola" data-validation="required"  name="password" type="password" class="form-control">
                        </div>
                        <div>
                            <input type="checkbox" onclick="myFunction()">Show Password
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
    <div class="modal fade" id="duzenle" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Anahtar Düzenle</h2>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form>
                    <div class="modal-body">
                        <div class="form-group">
                            <h3>Adı</h3>
                            <input id="change_name" type="text" class="form-control" placeholder="Anahtar kısa adı" data-validation="length" data-validation-length="min4" data-validation-error-msg="4 karakterden az olmaz.">
                        </div>
                        <div class="form-group">
                            <h3>Sunucu</h3>
                            <select class="form-control" id="change_server">
                                @foreach ($servers as $server)
                                    <option value="{{$server->id}}">{{$server->name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <h3>Kullanıcı Adı</h3>
                            <input id="change_username" type="text" class="form-control" placeholder="Anahtar Kullanıcı Adı" data-validation="required" data-validation-error-msg="Girilmesi Zorunlu Alan">
                        </div>
                        <div class="form-group">
                            <h3>Parola</h3>
                            <input  id="change_pass" data-validation-error-msg="Girilmesi zorunlu alan." placeholder="Parola" data-validation="required"  name="password" type="password" class="form-control">
                        </div>
                        <div>
                            <input type="checkbox" onclick="myFunction()">Show Password
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                        <button type="button" class="btn btn-success" onclick="degistir()">Düzenle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        $.validate({
            addValidClassOnAll : true,
            modules: 'security'
        });
        function myFunction() {
            var x = document.getElementById("add_password");
            var y=document.getElementById("change_pass");
            if (x.type === "password") {
                x.type = "text";
            } else {
                x.type = "password";
            }
            if (y.type === "password") {
                y.type = "text";
            } else {
                y.type = "password";
            }
        }
    </script>
@endsection