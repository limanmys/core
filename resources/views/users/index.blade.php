@extends('layouts.app')

@section('content')
    <link href="//cdnjs.cloudflare.com/ajax/libs/jquery-form-validator/2.3.26/theme-default.min.css"
          rel="stylesheet" type="text/css" />
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery-form-validator/2.3.26/jquery.form-validator.min.js"></script>
    <button type="button" class="btn btn-success" data-toggle="modal" data-target="#userAdd">
        Kullanıcı Ekle
    </button><br><br>
    <h1 class="h2">Kullanıcılar</h1>
    <table id="mainTable" class="table">
        <thead>
        <tr>
            <th scope="col">Kullanıcı Adı</th>
            <th scope="col">Email</th>
            <th scope="col"></th>
        </tr>
        </thead>
        <tbody data-toggle="modal" data-target="#new">
        @foreach ($users as $user)
            <tr class="highlight" style="cursor: default;">
                <td>{{$user->name}}</td>
                <td>{{$user->email}}</td>

            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="modal fade" id="new" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Kullanıcı İşlemleri</h2>
                </div>
                <form>
                    <div class="modal-body">
                        <td>
                            <div class="form-group">
                                <h3>Kullanıcı Adı</h3>
                                <input id="change_name" type="text" class="form-control" placeholder="Kullanıcı adı" data-validation="length" data-validation-length="min4" data-validation-error-msg="Kullanıcı adı en az 4 harfli olmalı." >
                            </div>
                            <div class="form-group">
                                <h3>Email Adresi</h3>
                                <input id="change_email" type="text" class="form-control" placeholder="Email Adresi"  data-validation="email" data-validation-error-msg="Geçerli bir e-mail address girin.">
                            </div>
                            <div class="form-group">
                                <h3>Parola</h3>
                                <input  id="change_pass" data-validation-error-msg="Girilmesi zorunlu alan." placeholder="Parola" data-validation="required"  name="password" type="password" class="form-control">
                            </div>
                            <div>
                                <input type="checkbox" onclick="myFunction()">Show Password
                            </div>
                        </td>
                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-danger" id="button1" data-dismiss="modal" data-toggle="modal" data-target="#check" onclick="deletion()">Hesabı Kapat</button>
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        <button type="button" class="btn btn-secondary"  data-dismiss="modal">Çıkış</button>
                        <button  class="btn btn-primary" onclick="update()">Düzenle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="check" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Sunucu Sil</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>

                </div>
                <div class="modal-body">
                    <h3>Sunucuyu silmek istediğinize emin misiniz? Bu işlem geri alınamayacaktır.</h3>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-danger" onclick="deletion()">Sunucu Sil</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="userAdd" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title" id="exampleModalLabel">Kullanıcı Ekle</h1>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form>
                    <div class="modal-body">
                        <div class="form-group">
                            <h3>Kullanıcı Adı</h3>
                            <input id="add_name" type="text" class="form-control" placeholder="Kullanıcı adı" data-validation="length" data-validation-length="min4" data-validation-error-msg="Kullanıcı adı en az 4 harfli olmalı.">
                        </div>
                        <div class="form-group">
                            <h3>Email Adresi</h3>
                            <input id="add_email" type="text" class="form-control" placeholder="Email Adresi"  data-validation="email" data-validation-error-msg="Geçerli bir e-mail address girin.">
                        </div>
                        <div class="form-group">
                            <h3>Parola</h3>
                            <input id="add_parola" data-validation-error-msg="Girilmesi zorunlu alan." placeholder="Parola" data-validation="required"  name="password" type="password" class="form-control" >
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
    <script>
        $.validate({
            addValidClassOnAll : true,
            modules: 'security'
        });
        function update(){
            var name = $("#change_name").val();
            var mail = $("#change_email").val();
            var pass = $("#change_pass").val();

            $.post("" ,{
                name : name,
                mail : mail,
                pass:pass,

            },function (data,status) {
                if(data["result"] === 200){
                    location.reload();
                }else{
                    alert("Hata!");
                }
            });
        }
        function deletion(){
            var name = $("#change_name").val();
            var mail = $("#change_email").val();
            var pass = $("#change_pass").val();
            $.post("" ,{
                name : name,
                mail : mail,
                pass:pass,

            },function (data,status) {
                if(data["result"] === 200){
                    location.reload();
                }else{
                    alert("Hata!");
                }
            });

        }
        function add() {
            var name = $("#add_name").val();
            var email = $("#add_email").val();
            var parola = $("#add_parola").val();

            $("#add_dhcp").prop("checked") ? features = features + "1" : 0;
            $("#add_dns").prop("checked") ? features = features + "2" : 0;
            $("#add_ldap").prop("checked") ? features = features + "3" : 0;
            $.post("{{route('server_add')}}" ,{
                name : name,
                email : email,
                parola : parola,
            },function (data,status) {
                if(data["result"] === 200){
                    //window.location.replace("{{route('users')}}" + "/" + data["id"]);
                }else{
                    alert("Hata!");
                }
            });
        }
        function myFunction() {
            var x = document.getElementById("change_pass");
            var y=document.getElementById("add_parola");
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