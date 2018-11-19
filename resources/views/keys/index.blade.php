@extends('layouts.app')

@section('content')
    <button type="button" class="btn btn-success" data-toggle="modal" data-target="#exampleModal">
        Anahtar Ekle
    </button><br><br>
    <h1 class="h2">Anahtarlar</h1>
    <table class="table">
        <thead>
        <tr>
            <th scope="col">Anahtar Adı</th>
            <th scope="col">Kullanıcı</th>
            <th scope="col">Sunucu</th>
        </tr>
        </thead>
        <tbody>
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
                            <input id="add_name" type="text" class="form-control" placeholder="Anahtar kısa adı" data-validation="length" data-validation-length="min0">
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
                            <input id="add_username" type="text" class="form-control" placeholder="Anahtar Kullanıcı Adı">
                        </div>
                        <div class="form-group">
                            <h3>Parola</h3>
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
@endsection