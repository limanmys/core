@extends('layouts.app')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
            <li class="breadcrumb-item"><a href="{{route('settings')}}">{{__("Sistem Ayarları")}}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{$role->name . __(" rol grubunun ayarları")}}</li>
        </ol>
    </nav>
    <div class="card">
        <div class="card-header p-2">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="pill" href="#extension" role="tab" aria-selected="true">{{__("Eklenti Yetkileri")}}</a>
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
                <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#variables" role="tab">{{__("Özel Veriler")}}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#role_users" role="tab">{{__("Kullanıcılar")}}</a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            @include('errors')
            <div class="tab-content">
                <div class="tab-pane fade show active" id="extension" role="tabpanel">
                    <button onclick="getList('extension')" class="btn btn-success"><i data-toggle="tooltip" title="{{__('Ekle')}}" class="fa fa-plus"></i></button>
                    <button onclick="removePermission('extension')" class="btn btn-danger"><i data-toggle="tooltip" title="{{__('Kaldır')}}" class="fa fa-minus"></i></button><br><br>
                    @include('table',[
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
                <div class="tab-pane fade show" id="server" role="tabpanel">
                    <button onclick="getList('server')" class="btn btn-success"><i data-toggle="tooltip" title="{{__('Ekle')}}" class="fa fa-plus"></i></button>
                    <button onclick="removePermission('server')" class="btn btn-danger"><i data-toggle="tooltip" title="{{__('Kaldır')}}" class="fa fa-minus"></i></button><br><br>
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
                    <button class="btn btn-success" data-toggle="modal" data-target="#functionsModal"><i data-toggle="tooltip" title="{{__('Ekle')}}" class="fa fa-plus"></i></button>
                    <button onclick="removeFunctions()" class="btn btn-danger"><i data-toggle="tooltip" title="{{__('Kaldır')}}" class="fa fa-minus"></i></button><br><br>
                    @include('table',[
                        "id" => "extensionFunctions",
                        "value" => $role->permissions->where('type','function'),
                        "title" => [
                            "Fonksiyon Adı" , "Eklenti" , "*hidden*"
                        ],
                        "display" => [
                            "extra" , "value", "id:id"
                        ],
                    ])
                </div>
                <div class="tab-pane fade show" id="liman" role="tabpanel">
                    <button onclick="getList('liman')" class="btn btn-success"><i data-toggle="tooltip" title="{{__('Ekle')}}" class="fa fa-plus"></i></button>
                    <button onclick="removePermission('liman')" class="btn btn-danger"><i data-toggle="tooltip" title="{{__('Kaldır')}}" class="fa fa-minus"></i></button><br><br>
                    @include('table',[
                        "id" => "liman_table",
                        "value" => $limanPermissions,
                        "title" => [
                            "Adı" , "*hidden*"
                        ],
                        "display" => [
                            "name" , "id:id"
                        ],
                        "noInitialize" => "true"
                    ])
                </div>
                <div class="tab-pane fade show" id="variables" role="tabpanel">
                    <button class="btn btn-success" data-toggle="modal" data-target="#variables_modal"><i data-toggle="tooltip" title="{{__('Ekle')}}" class="fa fa-plus"></i></button>
                    <button onclick="removeVariables()" class="btn btn-danger"><i data-toggle="tooltip" title="{{__('Kaldır')}}" class="fa fa-minus"></i></button><br><br>
                    @include('table',[
                        "id" => "variables_table",
                        "value" => $variablesPermissions,
                        "title" => [
                            "Adı" , "*hidden*", "Değeri"
                        ],
                        "display" => [
                            "key" , "id:id", "value"
                        ],
                        "noInitialize" => "true"
                    ])
                </div>
                <div class="tab-pane fade show" id="role_users" role="tabpanel">
                    <button onclick="getUserList()" class="btn btn-success"><i data-toggle="tooltip" title="{{__('Ekle')}}" class="fa fa-plus"></i></button>
                    <button onclick="removeUsers()" class="btn btn-danger"><i data-toggle="tooltip" title="{{__('Kaldır')}}" class="fa fa-minus"></i></button><br><br>
                    @include('table',[
                        "id" => "role_users_table",
                        "value" => $role->users,
                        "title" => [
                            "Kullanıcı Adı" , "Email" , "*hidden*"
                        ],
                        "display" => [
                            "name", "email", "id:id"
                        ],
                        "noInitialize" => "true"
                    ])
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="functionsModal">
        <div class="modal-dialog modal-dialog-centered modal-xl">
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
                <label>{{__("Lütfen Bir Eklenti Seçin")}}</label>
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
        "id" => "user_modal",
        "title" => "Kullanıcı Listesi",
        "submit_text" => "Seçili Kullanıcıları Gruba Ekle",
        "onsubmit" => "addUsers"
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

    @include('modal',[
        "id" => "variables_modal",
        "title" => "Özel Veri Ekle",
        "submit_text" => "Ekle",
        "url" => route('permission_add_variable'),
        "inputs" => [
            "Adı" => "key:text",
            "Değeri" => "value:text",
            "$role->id:$role->id" => "object_id:hidden",
            "roles:roles" => "object_type:hidden"
        ],
        "next" => "reload"
    ])

    <script>
        function getFunctionList(){
            var extension_id = $("#extensionId :selected").val();
            var form = new FormData();
            form.append('extension_id', extension_id);
            form.append('user_id','{{$role->id}}');
            request('{{route('extension_function_list')}}', form, function (response) {
                $(".functionsTable").html(response);
                $('.functionsTable table').DataTable(dataTablePresets('multiple'));
            }, function(response){
                var error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            });
        }

        function getUserList(){
            request('{{route('get_user_list_admin')}}', new FormData(), function (response) {
                $("#user_modal .modal-body").html(response);
                $('#user_modal table').DataTable(dataTablePresets('multiple'));
                $("#user_modal").modal('show');
            }, function(response){
                var error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            });
        }

        function addUsers(){
            showSwal('{{__("Güncelleniyor...")}}','info');
            var data = [];
            var table = $('#user_modal table').DataTable();
            table.rows( { selected: true } ).data().each(function(element){
                data.push(element[3]);
            });
            if(data.length == 0){
                showSwal("{{__('Lütfen önce seçim yapınız.')}}",'error',2000);
                return false;
            }
            var form = new FormData();
            form.append("users",JSON.stringify(data));
            form.append("role_id",'{{$role->id}}');
            request('{{route("add_role_users")}}',form,function(){
                reload();
            }, function(response){
                var error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            });
            return false;
        }

        function removeUsers(){
            showSwal('{{__("Güncelleniyor...")}}','info');
            var data = [];
            var table = $('#role_users table').DataTable();
            table.rows( { selected: true } ).data().each(function(element){
                data.push(element[3]);
            });
            if(data.length == 0){
                showSwal("{{__('Lütfen önce seçim yapınız.')}}",'error',2000);
                return false;
            }
            var form = new FormData();
            form.append("users",JSON.stringify(data));
            form.append("role_id",'{{$role->id}}');
            request('{{route("remove_role_users")}}',form,function(){
                reload();
            }, function(response){
                var error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            });
            return false;
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
            form.append("role_id",'{{$role->id}}');
            request('{{route("add_role_function")}}',form,function(){
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
            if(data.length == 0){
                showSwal("{{__('Lütfen önce seçim yapınız.')}}",'error',2000);
                return false;
            }
            var form = new FormData();
            form.append("functions",data);
            form.append("role_id",'{{$role->id}}');
            request('{{route("remove_role_function")}}',form,function(response){
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

        function removeVariables(){
            showSwal('{{__("Güncelleniyor...")}}','info');
            var data = [];
            var table = $('#variables_table').DataTable();
            table.rows( { selected: true } ).data().each(function(element){
                data.push(element[2]);
            });
            var form = new FormData();
            form.append("variables",data);
            request('{{route("permission_remove_variable")}}',form,function(response){
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
        
        function getList(type) {
            var form = new FormData();
            form.append('type', type);
            form.append('role_id','{{$role->id}}');
            request('{{route('role_permission_list')}}', form, function (response) {
                Swal.close();
                $("#" + type + "_modal .modal-body").html(response);
                $("#" + type + "_modal table").DataTable(dataTablePresets('multiple'));
                $("#" + type + "_modal").modal('show');
            }, function(response){
                var error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            })
        }
        
        function removePermission(element){
            var data = [];
            var table = $("#" + element + "_table").DataTable();
            table.rows( { selected: true } ).data().each(function(element){
                data.push(element[2]);
            });
            if(data.length == 0){
                showSwal("{{__('Lütfen önce seçim yapınız.')}}",'error',2000);
                return false;
            }
            
            showSwal('{{__("Siliniyor...")}}','info');

            var form = new FormData();
            form.append('ids',JSON.stringify(data));
            form.append('role_id','{{$role->id}}');
            form.append('type',element);
            request('{{route('remove_role_permission_list')}}', form, function (response) {
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
            form.append('role_id','{{$role->id}}');
            form.append('type',modalElement.getAttribute('id').split('_')[0]);
            request('{{route('add_role_permission_list')}}', form, function (response) {
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
    </script>
@endsection