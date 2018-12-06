@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h2>{{ __("SSH Anahtarları") }}</h2>
    </div>
    <button type="button" class="btn btn-success" data-toggle="modal" data-target="#exampleModal">
        {{ __("Anahtar Ekle") }}
    </button><br><br>
    <table class="table">
        <thead>
        <tr>
            <th scope="col">{{ __("Anahtar Adı") }}</th>
            <th scope="col">{{ __("Kullanıcı") }}</th>
            <th scope="col">{{ __("Sunucu") }}</th>
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
        function add(event,flag) {
            event.preventDefault();
            var x = $("#form").serializeArray();
            dataObj = {};
            $.each(x, function(i, field){

                dataObj[field.name] = field.value;
            });
            var route=flag=='add' ? "{{route('key_add')}}" :'';
            $.post(route,{
                name : dataObj['name'],
                username : dataObj['username'],
                password : dataObj['password'],
                server_id : dataObj['server']
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
                    <h1 class="modal-title" id="exampleModalLabel">{{ __("Anahtar Ekle") }}</h1>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="form"onsubmit="add(event,'add')">
                    <div class="modal-body">
                        <div class="form-group">
                            <h3>{{ __("Adı") }}</h3>
                            <input id="add_name" name="name" type="text" class="form-control" placeholder="{{ __("Anahtar Kısa Adı") }}" value="" required minlength="4">
                        </div>
                        <div class="form-group">
                            <h3>{{ __("Sunucu") }}</h3>
                            <select class="form-control" id="add_server" name="server">
                                @foreach ($servers as $server)
                                    <option value="{{$server->id}}">{{$server->name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <h3>{{ __("Kullanıcı Adı") }}</h3>
                            <input id="add_username" name="username" type="text" value=""class="form-control" placeholder="{{ __("Anahtar Kullanıcı Adı") }}" required>
                        </div>
                        <div class="form-group">
                            <h3>{{ __("Parola") }}</h3>
                            <input id="add_password"  placeholder="{{ __("Parola") }}"  value="" name="password" type="password" type="password" required>
                        </div>
                        <div>
                            <input type="checkbox" onclick="myFunction()">{{ __("Şifreyi Göster") }}
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __("İptal") }}</button>
                        <button class="btn btn-success" type="submit">{{ __("Ekle") }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="duzenle" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>{{ __("Anahtar Düzenle") }}</h2>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="form" onsubmit="add(event,'update')">
                    <div class="modal-body">
                        <div class="form-group">
                            <h3>{{ __("Adı") }}</h3>
                            <input id="change_name" name="name" type="text" class="form-control" placeholder="{{ __("Anahtar Kısa Adı") }}" required minlength="4">
                        </div>
                        <div class="form-group">
                            <h3>{{ __("Sunucu") }}</h3>
                            <select class="form-control" id="change_server" name="server">
                                @foreach ($servers as $server)
                                    <option value="{{$server->id}}">{{$server->name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <h3>{{ __("Kullanıcı Adı") }}</h3>
                            <input id="change_username" name="username" type="text" class="form-control" placeholder="{{ __("Anahtar Kullanıcı Adı") }}" required>
                        </div>
                        <div class="form-group">
                            <h3>{{ __("Parola") }}</h3>
                            <input  id="change_pass" name="password" type="password" type="password" placeholder="{{ __("Parola") }}" required>
                        </div>
                        <div>
                            <input type="checkbox" onclick="myFunction()">{{ __("Şifreyi Göster") }}
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __("İptal") }}</button>
                        <button type="submit" class="btn btn-success" >{{ __("Düzenle") }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        function myFunction() {
            var x = document.getElementById("add_password");
            var y=document.getElementById("change_pass");
            x.type=="password"?x.type="text":x.type="password";
            y.type=="password"?y.type="text":y.type="password";
        }
    </script>
@endsection