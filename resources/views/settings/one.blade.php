@extends('layouts.app')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
            <li class="breadcrumb-item"><a href="{{route('settings')}}">{{__("Sistem Ayarları")}}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{$user->name . __(" kullanıcısı ayarları")}}</li>
        </ol>
    </nav>
    <h2>{{ __(":user_name kullanıcısı ayarları", ["user_name" => $user->name])}}</h2>
    <div class="card">
        <div class="card-header p-2">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="pill" href="#general" role="tab" aria-selected="true">{{__("Genel Ayarlar")}}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#user_roles" role="tab" aria-selected="true">{{__("Rol Grupları")}}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#extension" role="tab" >{{__("Eklenti Yetkileri")}}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#server" role="tab">{{__("Sunucu Yetkileri")}}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#function" role="tab">{{__("Fonksiyon Yetkileri")}}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#liman" role="tab">{{__("Liman Yetkileri")}}</a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            @include('errors')
            <div class="tab-content">
                <div class="tab-pane fade show active" id="general" role="tabpanel">
                    <form onsubmit="return updateUser(this);">
                        <div style="width: 300px;height: 300px;display: block;float: left;padding: 10px;">
                            <h4>{{__("Hesap Türü")}}</h4>
                            <select name="status" class="form-control">
                                <option value="0" @if($user->status == "0") selected @endif>{{__("Kullanıcı")}}</option>
                                <option value="1" @if($user->status == "1") selected @endif>{{__("Yönetici")}}</option>
                            </select><br>
                            @if ($user->auth_type !== "ldap")
                                <h4>{{__("Adı")}}</h4>
                                <input class="form-control" type="text" value="{{$user->name}}" name="username"><br>
                            @endif
                            <h4>{{__("Email Adresi")}}</h4>
                            <input class="form-control" type="email" value="{{$user->email}}" name="email">
                        </div>
                        <div style="width: 300px;height: 300px;display: block;float: left;padding-top: 75px;margin-left:50px;">
                            <button class="btn btn-danger btn-block" onclick="removeUser();return false;">{{__("Kullanıcıyı Sil")}}</button><br>
                            @if ($user->auth_type !== "ldap")
                                <button class="btn btn-warning btn-block" onclick="resetPassword();return false;">{{__("Parola Sıfırla")}}</button><br>
                            @endif
                            <button class="btn btn-success btn-block" type="submit">{{__("Değişiklikleri Kaydet")}}</button>
                        </div>
                    </form>
                </div>
                <div class="tab-pane fade show" id="user_roles" role="tabpanel">
                    <button onclick="getList('role')" class="btn btn-success"><i data-toggle="tooltip" title="Ekle" class="fa fa-plus"></i></button>
                    <button onclick="removeRole()" class="btn btn-danger"><i data-toggle="tooltip" title="Kaldır" class="fa fa-minus"></i></button><br><br>
                    @include('table',[
                        "id" => "role_table",
                        "value" => $user->roles,
                        "title" => [
                            "Adı" , "*hidden*"
                        ],
                        "display" => [
                            "name" , "id:role_id"
                        ],
                        "menu" => [
                            "Görüntüle" => [
                                "target" => "roleDetails",
                                "icon" => "fa-arrow-right"
                            ]
                        ],
                        "noInitialize" => "true"
                    ])
                </div>
                <div class="tab-pane fade show" id="extension" role="tabpanel">
                    <button onclick="getList('extension')" class="btn btn-success"><i data-toggle="tooltip" title="Ekle" class="fa fa-plus"></i></button>
                    <button onclick="removePermission('extension')" class="btn btn-danger"><i data-toggle="tooltip" title="Kaldır" class="fa fa-minus"></i></button><br><br>
                    @include('table',[
                        "id" => "extension_table",
                        "value" => $extensions,
                        "title" => [
                            "Adı" , "*hidden*"
                        ],
                        "display" => [
                            "display_name" , "id:id"
                        ],
                        "noInitialize" => "true"
                    ])
                </div>
                <div class="tab-pane fade show" id="server" role="tabpanel">
                    <button onclick="getList('server')" class="btn btn-success"><i data-toggle="tooltip" title="Ekle" class="fa fa-plus"></i></button>
                    <button onclick="removePermission('server')" class="btn btn-danger"><i data-toggle="tooltip" title="Kaldır" class="fa fa-minus"></i></button><br><br>
                    @include('table',[
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
                <div class="tab-pane fade show" id="function" role="tabpanel">
                    <button class="btn btn-success" data-toggle="modal" data-target="#functionsModal"><i data-toggle="tooltip" title="Ekle" class="fa fa-plus"></i></button>
                    <button onclick="removeFunctions()" class="btn btn-danger"><i data-toggle="tooltip" title="Kaldır" class="fa fa-minus"></i></button><br><br>
                    @include('table',[
                        "id" => "extensionFunctions",
                        "value" => $user->permissions->where('type','function'),
                        "title" => [
                            "Fonksiyon Adı" , "Eklenti" , "*hidden*"
                        ],
                        "display" => [
                            "extra" , "value", "id:id"
                        ],
                        "menu" => [
                            "Yetki Verilerini Düzenle" => [
                                "target" => "permissionData",
                                "icon" => "fa-database"
                            ]
                        ],
                        "noInitialize" => "true"
                    ])
                </div>
                <div class="tab-pane fade show" id="liman" role="tabpanel">
                    <button onclick="getList('liman')" class="btn btn-success"><i data-toggle="tooltip" title="Ekle" class="fa fa-plus"></i></button>
                    <button onclick="removePermission('liman')" class="btn btn-danger"><i data-toggle="tooltip" title="Kaldır" class="fa fa-minus"></i></button><br><br>
                    @include('table',[
                        "id" => "liman_table",
                        "value" => getLimanPermissions($user->id),
                        "title" => [
                            "Adı" , "*hidden*"
                        ],
                        "display" => [
                            "name" , "id:id"
                        ],
                        "noInitialize" => "true"
                    ])
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="functionsModal">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">
                    {{__("Fonksiyon Yetkileri")}}
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <h4>{{__("Lütfen Bir Eklenti Seçin")}}</h4>
                <select id="extensionId" class="form-control" onchange="getFunctionList()">
                    <option selected disabled>{{__("...")}}</option>
                    @foreach(extensions() as $extension)
                        <option value="{{$extension->id}}">{{$extension->display_name}}</option>
                    @endforeach
                </select><br>
                <div class="functionsTable"></div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-success" onclick="addFunctions()">{{__("Seçili Fonksiyonlara Yetki Ver")}}</button>
            </div>
            </div>
        </div>
    </div>
    @include('modal',[
        "id" => "role_modal",
        "title" => "Rol Grubu Listesi",
        "submit_text" => "Seçili Grupları Ekle",
        "onsubmit" => "addRole"
    ])
    @include('modal',[
            "id" => "server_modal",
            "title" => "Sunucu Listesi",
            "submit_text" => "Seçili Sunuculara Yetki Ver",
            "onsubmit" => "addData"
        ])
    @include('modal',[
            "id" => "extension_modal",
            "title" => "Eklenti Listesi",
            "submit_text" => "Seçili Eklentilere Yetki Ver",
            "onsubmit" => "addData"
        ])
    @include('modal',[
            "id" => "liman_modal",
            "title" => "Özellik Listesi",
            "submit_text" => "Seçili Özelliklere Yetki Ver",
            "onsubmit" => "addData"
        ])

    @component('modal-component',[
        "id" => "permissionDataModal",
        "title" => "Fonksiyon Parametreleri",
        "footer" => [
            "class" => "btn-success",
            "onclick" => "writePermissionData()",
            "text" => "Kaydet"
        ]
    ])
        <form id="parameterInputsForm">
            <div id="parameterInputs"></div>
        </form>
    @endcomponent

    @include('modal',[
       "id"=>"removeUser",
       "title" =>"Kullanıcıyı Sil",
       "url" => route('user_remove'),
       "text" => "Kullanıcıyı silmek istediğinize emin misiniz? Bu işlem geri alınamayacaktır.",
       "next" => "redirect",
       "inputs" => [
           "Kullanici Id:'null'" => "user_id:hidden"
       ],
       "submit_text" => "Kullanıcıyı Sil"
   ])

    @include('modal',[
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
            var extension_id = $("#extensionId :selected").val();
            var form = new FormData();
            form.append('extension_id', extension_id);
            form.append('user_id','{{$user->id}}');
            request('{{route('extension_function_list')}}', form, function (response) {
                $(".functionsTable").html(response);
                $('.functionsTable table').DataTable(dataTablePresets('multiple'));
            }, function(response){
                var error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            });
        }

        function addFunctions(){
            showSwal('{{__("Güncelleniyor...")}}','info');
            var data = [];
            var table = $('.functionsTable table').DataTable();
            table.rows( { selected: true } ).data().each(function(element){
                data.push(element[1]);
            });
            if(data.length == 0){
                showSwal("{{__('Lütfen önce seçim yapınız.')}}",'error',2000);
                return false;
            }
            var form = new FormData();
            var extension_id = $("#extensionId :selected").val();
            form.append("extension_id",extension_id);
            form.append("functions",data);
            form.append("user_id",'{{$user->id}}');
            request('{{route("extension_function_add")}}',form,function(){
                location.reload();
            }, function(response){
                var error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            });
        }

        function removeFunctions(){
            showSwal('{{__("Güncelleniyor...")}}','info');
            var data = [];
            var table = $('#extensionFunctions').DataTable();
            table.rows( { selected: true } ).data().each(function(element){
                data.push(element[3]);
            });
            var form = new FormData();
            form.append("functions",data);
            form.append("user_id",'{{$user->id}}');
            request('{{route("extension_function_remove")}}',form,function(response){
                var json = JSON.parse(response);
                showSwal(json["message"],'success',2000);
                setTimeout(function () {
                    location.reload();
                },2000);
            }, function(response){
                var error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            });
        }

        function updateUser(data) {
            showSwal('{{__("Güncelleniyor...")}}','info');
            var form = new FormData(data);
            form.append('user_id','{{$user->id}}');
            request('{{route('update_user')}}',form,function () {
                Swal.close();
                location.reload();
            }, function(response){
                var error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            });
            return false;
        }
        
        function getList(type) {
            showSwal("Okunuyor...",'info');
            var form = new FormData();
            form.append('type', type);
            form.append('user_id','{{$user->id}}');
            request('{{route('settings_get_list')}}', form, function (response) {
                Swal.close();
                $("#" + type + "_modal .modal-body").html(response);
                $("#" + type + "_modal table").DataTable(dataTablePresets('multiple'));
                $("#" + type + "_modal").modal('show');
            }, function(response){
                var error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            })
        }
        function removeRole(element){
            var data = [];
            var table = $("#role_table").DataTable();
            table.rows( { selected: true } ).data().each(function(element){
                data.push(element[2]);
            });

            if(data === []){
                showSwal("{{__('Lütfen önce seçim yapınız.')}}",'error',2000);
                return false;
            }
            showSwal('{{__("Siliniyor...")}}','info');

            var form = new FormData();
            form.append('ids',JSON.stringify(data));
            form.append('user_id','{{$user->id}}');

            request('{{route('remove_roles_to_user')}}', form, function (response) {
                var json = JSON.parse(response);
                showSwal(json["message"],'success',2000);
                setTimeout(function () {
                    location.reload();
                },2000);
            }, function(response){
                var error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            });
            return false;
        }
        function removePermission(element){
            var data = [];
            var table = $("#" + element + "_table").DataTable();
            table.rows( { selected: true } ).data().each(function(element){
                data.push(element[2]);
            });

            if(data === []){
                showSwal("{{__('Lütfen önce seçim yapınız.')}}",'error',2000);
                return false;
            }
            showSwal('{{__("Siliniyor...")}}','info');

            var form = new FormData();
            form.append('ids',JSON.stringify(data));
            form.append('user_id','{{$user->id}}');
            form.append('type',element);
            request('{{route('settings_remove_from_list')}}', form, function (response) {
                var json = JSON.parse(response);
                showSwal(json["message"],'success',2000);
                setTimeout(function () {
                    location.reload();
                },2000);
            }, function(response){
                var error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            });
            return false;
        }

        function addData(modalElement) {
            showSwal('{{__("Ekleniyor...")}}','info');
            var data = [];
            var table = $(modalElement).find('table').DataTable();
            table.rows( { selected: true } ).data().each(function(element){
                data.push(element[1]);
            });
            if(data.length == 0){
                showSwal("{{__('Lütfen önce seçim yapınız.')}}",'error',2000);
                return false;
            }
            var form = new FormData();
            form.append('ids',JSON.stringify(data));
            form.append('user_id','{{$user->id}}');
            form.append('type',modalElement.getAttribute('id').split('_')[0]);
            request('{{route('settings_add_to_list')}}', form, function (response) {
                var json = JSON.parse(response);
                showSwal(json["message"],'success',2000);
                setTimeout(function () {
                    location.reload();
                },2000);
            }, function(response){
                var error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            });
            return false;
        }

        function addRole(modalElement) {
            showSwal('{{__("Ekleniyor...")}}','info');
            var data = [];
            var table = $(modalElement).find('table').DataTable();
            table.rows( { selected: true } ).data().each(function(element){
                data.push(element[1]);
            });
            if(data.length == 0){
                showSwal("{{__('Lütfen önce seçim yapınız.')}}",'error',2000);
                return false;
            }
            var form = new FormData();
            form.append('ids',JSON.stringify(data));
            form.append('user_id','{{$user->id}}');
            request('{{route('add_roles_to_user')}}', form, function (response) {
                var json = JSON.parse(response);
                showSwal(json["message"],'success',2000);
                setTimeout(function () {
                    location.reload();
                },2000);
            }, function(response){
                var error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            });
            return false;
        }

        $('table').DataTable(dataTablePresets('multiple'));

        function resetPassword(){
            $("#resetPassword [name='user_id']").val('{{$user->id}}');
            $("#resetPassword").modal('show');
        }

        function removeUser(){
            $("#removeUser [name='user_id']").val('{{$user->id}}');
            $("#removeUser").modal('show');
        }

        function roleDetails(row){
            var role_id = row.querySelector('#role_id').innerHTML;
            partialPageRequest('/rol/' + role_id);
        }
        var currentPermissionId = null;
        function permissionData(row){
            var permission_id = row.querySelector('#id').innerHTML;
            var function_name = row.querySelector('#extra').innerHTML;
            var extension_name = row.querySelector('#value').innerHTML;

            currentPermissionId = permission_id;
            if(permission_id == null){
                return;
            }
            $("#permissionDataPre").text("");
            showSwal('{{__("Okunuyor...")}}','info');
            var form = new FormData();
            form.append('id',permission_id);
            form.append('function_name',function_name);
            form.append('extension_name',extension_name);

            request('{{route('get_permission_data')}}', form, function (response) {
                var json = JSON.parse(response);
                $("#parameterInputs").html(json.message.inputs);
                json.message.data.forEach(function(item){
                    $("#parameterInputs").find('[name='+item.variable+']').val(item.value).change(); 
                });
                $("#permissionDataModal").modal('show');
            }, function(response){
                var error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            });
            return false;
        }

        function writePermissionData(){
            showSwal('{{__("Kaydediliyor...")}}','info');
            var form = new FormData();
            form.append('id',currentPermissionId);
            var inputs = $('#parameterInputsForm').serializeArray();
            var data = {};
            inputs.forEach(function(item){
                data[item.name] = item.value;
            });
            if(data.length == 0){
                showSwal("{{__('Lütfen önce seçim yapınız.')}}",'error',2000);
                return false;
            } 
            form.append('data',JSON.stringify(data));
            request('{{route('write_permission_data')}}', form, function (response) {
                location.reload();
            }, function(response){
                var error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            });
            return false;
        }
    </script>
@endsection