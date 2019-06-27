@extends('layouts.app')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
            <li class="breadcrumb-item"><a href="{{route('settings')}}">{{__("Ayarlar")}}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{$user->name . __(" kullanıcısı ayarları")}}</li>
        </ol>
    </nav>
    <h2>{{$user->name . __(" kullanıcısı ayarları")}}</h2>
    <div class="nav-tabs-custom">
        <ul class="nav nav-tabs">
            <li class="active"><a href="#tab_1" data-toggle="tab" aria-expanded="true">{{__("Genel Ayarlar")}}</a></li>
            <li id="server_type"><a href="#tab_2" data-toggle="tab"
                                    aria-expanded="false">{{__("Eklenti Yetkileri")}}</a></li>
            <li id="server_type"><a href="#tab_3" data-toggle="tab" aria-expanded="false">{{__("Betik Yetkileri")}}</a>
            </li>
            <li class=""><a href="#tab_4" data-toggle="tab" aria-expanded="false">{{__("Sunucu Yetkileri")}}</a></li>
            <li class=""><a href="#tab_5" data-toggle="tab" aria-expanded="false">{{__("Fonksiyon Yetkileri")}}</a></li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane active" id="tab_1" style="height: 300px;">
                <form onsubmit="return updateUser(this);">
                    <div style="width: 300px;height: 300px;display: block;float: left;padding: 10px;">
                        <h4>{{__("Hesap Türü")}}</h4>
                        <select name="status" class="form-control">
                            <option value="0" @if($user->status == "0") selected @endif>{{__("Kullanıcı")}}</option>
                            <option value="1" @if($user->status == "1") selected @endif>{{__("Yönetici")}}</option>
                        </select><br>
                        <h4>{{__("Adı")}}</h4>
                        <input class="form-control" type="text" value="{{$user->name}}" name="username"><br>
                        <h4>{{__("Email Adresi")}}</h4>
                        <input class="form-control" type="text" value="{{$user->email}}" name="email">
                    </div>
                    <div style="width: 300px;height: 300px;display: block;float: left;padding-top: 75px;margin-left:50px;">
                        <button class="btn btn-danger btn-block" onclick="removeUser();return false;">{{__("Kullanıcıyı Sil")}}</button><br>
                        <button class="btn btn-warning btn-block" onclick="resetPassword();return false;">{{__("Parola Sıfırla")}}</button><br>
                        <button class="btn btn-success btn-block" type="submit">{{__("Değişiklikleri Kaydet")}}</button>
                    </div>
                </form>
            </div>

            <div class="tab-pane" id="tab_2">
                <button onclick="getList('extension')" class="btn btn-success"><i class="fa fa-plus"></i></button>
                <button onclick="removePermission('extension')" class="btn btn-danger"><i class="fa fa-minus"></i></button><br><br>
                @include('l.table',[
                    "id" => "extension_table",
                    "value" => $extensions,
                    "title" => [
                        "Adı" , "*hidden*"
                    ],
                    "display" => [
                        "name" , "id:id"
                    ],
                    "noInitialize" => "true"
                ])
            </div>
            <div class="tab-pane" id="tab_3">
                <button onclick="getList('script')" class="btn btn-success"><i class="fa fa-plus"></i></button>
                <button onclick="removePermission('script')" class="btn btn-danger"><i class="fa fa-minus"></i></button><br><br>
                @include('l.table',[
                    "id" => "script_table",
                    "value" => $scripts,
                    "title" => [
                        "Adı" , "*hidden*"
                    ],
                    "display" => [
                        "name" , "id:id"
                    ],
                    "noInitialize" => "true"
                ])
            </div>
            <div class="tab-pane" id="tab_4">
                <button onclick="getList('server')" class="btn btn-success"><i class="fa fa-plus"></i></button>
                <button onclick="removePermission('server')" class="btn btn-danger"><i class="fa fa-minus"></i></button><br><br>
                @include('l.table',[
                    "id" => "server_table",
                    "value" => $servers,
                    "title" => [
                        "Adı" , "*hidden*"
                    ],
                    "display" => [
                        "name" , "id:id"
                    ],
                    "noInitialize" => "true"
                ])
            </div>
            <div class="tab-pane" id="tab_5">
                <button class="btn btn-success" data-toggle="modal" data-target="#functionsModal"><i class="fa fa-plus"></i></button>
                <button onclick="removeFunctions()" class="btn btn-danger"><i class="fa fa-minus"></i></button><br><br>
                @include('l.table',[
                    "id" => "extensionFunctions",
                    "value" => $functions,
                    "title" => [
                        "Fonksiyon Adı" , "Eklenti"
                    ],
                    "display" => [
                        "name" , "extension_name"
                    ],
                ])
            </div>
                
                <div id="functionsModal" class="modal" tabindex="-1" role="dialog">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                        <div class="modal-header">
                        <h3 class="modal-title">{{__("Fonksiyon Yetkileri")}}</h3>
                              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                              </button>
                            </div>
                            <div class="modal-body">
                                    <h4>{{__("Lütfen Bir Eklenti Seçin")}}</h4>
                                    <select id="extensionId" class="form-control" onchange="getFunctionList()">
                                        <option selected disabled>{{__("...")}}</option>
                                        @foreach(extensions() as $extension)
                                            <option value="{{$extension->id}}">{{$extension->name}}</option>
                                        @endforeach
                                    </select><br>
                                <div class="functionsTable"></div>
                            <div class="modal-footer">
                              <button type="button" class="btn btn-success" onclick="addFunctions()">{{__("Seçili Fonksiyonlara Yetki Ver")}}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('l.modal',[
            "id" => "server_modal",
            "title" => "Sunucu Listesi",
            "submit_text" => "Seçili Sunuculara Yetki Ver",
            "onsubmit" => "addData"
        ])
    @include('l.modal',[
            "id" => "script_modal",
            "title" => "Betik Listesi",
            "submit_text" => "Seçili Betiklere Yetki Ver",
            "onsubmit" => "addData"
        ])
    @include('l.modal',[
            "id" => "extension_modal",
            "title" => "Eklenti Listesi",
            "submit_text" => "Seçili Eklentilere Yetki Ver",
            "onsubmit" => "addData"
        ])

    @include('l.modal',[
       "id"=>"removeUser",
       "title" =>"Kullanıcıyı Sil",
       "url" => route('user_remove'),
       "text" => "Kullanıcıyı silmek istediğinize emin misiniz? Bu işlem geri alınamayacaktır.",
       "next" => "reload",
       "inputs" => [
           "Kullanici Id:'null'" => "user_id:hidden"
       ],
       "submit_text" => "Kullanıcıyı Sil"
   ])

    @include('l.modal',[
       "id"=>"resetPassword",
       "title" =>"Parolayı Sıfırla",
       "url" => route('user_password_reset'),
       "text" => "Parolayı sıfırlamak istediğinize emin misiniz? Bu işlem geri alınamayacaktır.",
       "next" => "nothing",
       "inputs" => [
           "Kullanici Id:'null'" => "user_id:hidden"
       ],
       "submit_text" => "Parolayı Sıfırla"
   ])
    <script>
        function getFunctionList(){
            let extension_id = $("#extensionId :selected").val();
            let form = new FormData();
            form.append('extension_id', extension_id);
            form.append('user_id','{{$user->id}}');
            request('{{route('extension_function_list')}}', form, function (response) {
                $(".functionsTable").html(response);
                $('.functionsTable table').DataTable({
                bFilter: true,
                select: {
                    style: 'multi'
                },
                "language" : {
                    url : "/turkce.json"
                }
            });
            });
        }

        function addFunctions(){
            Swal.fire({
                position: 'center',
                type: 'info',
                title: '{{__("Güncelleniyor...")}}',
                showConfirmButton: false,
            });
            let data = [];
            let table = $('.functionsTable table').DataTable();
            table.rows( { selected: true } ).data().each(function(element){
                data.push(element[1]);
            });
            let form = new FormData();
            let extension_id = $("#extensionId :selected").val();
            form.append("extension_id",extension_id);
            form.append("functions",data);
            form.append("user_id",'{{$user->id}}');
            request('{{route("extension_function_add")}}',form,function(){
                location.reload();
            });
        }

        function removeFunctions(){
            Swal.fire({
                position: 'center',
                type: 'info',
                title: '{{__("Güncelleniyor...")}}',
                showConfirmButton: false,
            });
            let data = [];
            let table = $('#extensionFunctions').DataTable();
            table.rows( { selected: true } ).data().each(function(element){
                data.push(element[2] + "_" + element[1]);
            });
            let form = new FormData();
            form.append("functions",data);
            form.append("user_id",'{{$user->id}}');
            request('{{route("extension_function_remove")}}',form,function(response){
                let json = JSON.parse(response);
                Swal.fire({
                    position: 'center',
                    type: 'success',
                    title: json["message"],
                    showConfirmButton: false,
                    timer: 2000
                });
                setTimeout(function () {
                    location.reload();
                },2000);
            });
        }

        function updateUser(data) {
            Swal.fire({
                position: 'center',
                type: 'info',
                title: '{{__("Güncelleniyor...")}}',
                showConfirmButton: false,
            });
            let form = new FormData(data);
            form.append('user_id','{{$user->id}}');
            request('{{route('update_user')}}',form,function () {
                Swal.close();
                location.reload();
            });
            return false;
        }
        
        function getList(type) {
            let form = new FormData();
            form.append('type', type);
            form.append('user_id','{{$user->id}}');
            request('{{route('settings_get_list')}}', form, function (response) {
                Swal.close();
                $("#" + type + "_modal .modal-body").html(response);
                $("#" + type + "_modal table").DataTable({
                    bFilter: true,
                    select: {
                        style: 'multi'
                    },
                    "language": {
                        url: "{{asset('turkce.json')}}"
                    }
                });
                $("#" + type + "_modal").modal('show');
            })
        }
        
        function removePermission(element){
            let data = [];
            let table = $("#" + element + "_table").DataTable();
            table.rows( { selected: true } ).data().each(function(element){
                data.push(element[2]);
            });

            if(data === []){
                Swal.fire({
                    type: 'error',
                    title: 'Lütfen önce seçim yapınız.',
                    timer : 2000
                });
                return false;
            }
            Swal.fire({
                position: 'center',
                type: 'info',
                title: '{{__("Siliniyor...")}}',
                showConfirmButton: false,
            });

            let form = new FormData();
            form.append('ids',JSON.stringify(data));
            form.append('user_id','{{$user->id}}');
            form.append('type',element);
            request('{{route('settings_remove_from_list')}}', form, function (response) {
                let json = JSON.parse(response);
                Swal.fire({
                        position: 'center',
                        type: 'success',
                        title: json["message"],
                        showConfirmButton: false,
                        timer: 2000
                    });
                    setTimeout(function () {
                        location.reload();
                },2000);
            });
            return false;
        }

        function addData(modalElement) {
            Swal.fire({
                position: 'center',
                type: 'info',
                title: '{{__("Ekleniyor...")}}',
                showConfirmButton: false,
            });
            let data = [];
            let table = $(modalElement).find('table').DataTable();
            table.rows( { selected: true } ).data().each(function(element){
                data.push(element[1]);
            });
            let form = new FormData();
            form.append('ids',JSON.stringify(data));
            form.append('user_id','{{$user->id}}');
            form.append('type',modalElement.getAttribute('id').split('_')[0]);
            request('{{route('settings_add_to_list')}}', form, function (response) {
                let json = JSON.parse(response);
                Swal.fire({
                        position: 'center',
                        type: 'success',
                        title: json["message"],
                        showConfirmButton: false,
                        timer: 2000
                    });
                    setTimeout(function () {
                        location.reload();
                    },2000);
            });
            return false;
        }

        $('table').DataTable({
            bFilter: true,
            select: {
                style: 'multi'
            },
            "language" : {
                url : "{{asset('turkce.json')}}"
            }
        });

        function resetPassword(){
            $("#resetPassword [name='user_id']").val('{{$user->id}}');
            $("#resetPassword").modal('show');
        }

        function removeUser(){
            $("#removeUser [name='user_id']").val('{{$user->id}}');
            $("#removeUser").modal('show');
        }
    </script>
@endsection