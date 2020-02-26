@extends('layouts.app')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
            <li class="breadcrumb-item"><a href="{{route('settings')}}">{{__("Ayarlar")}}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{$role->name . __(" rol grubunun ayarları")}}</li>
        </ol>
    </nav>
    <h2>{{$role->name . __(" rol grubunun ayarları")}}</h2>
    <div class="card">
        <div class="card-header p-2">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="pill" href="#role_users" role="tab" aria-selected="true">{{__("Kullanıcılar")}}</a>
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
            </ul>
        </div>
        <div class="card-body">
            @include('errors')
            <div class="tab-content">
                <div class="tab-pane fade show active" id="role_users" role="tabpanel">
                    <button onclick="getUserList()" class="btn btn-success"><i data-toggle="tooltip" title="Ekle" class="fa fa-plus"></i></button>
                    <button onclick="removeUsers()" class="btn btn-danger"><i data-toggle="tooltip" title="Kaldır" class="fa fa-minus"></i></button><br><br>
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
                            "name" , "id:id"
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
                    <button onclick="getList('liman')" class="btn btn-success"><i data-toggle="tooltip" title="Ekle" class="fa fa-plus"></i></button>
                    <button onclick="removePermission('liman')" class="btn btn-danger"><i data-toggle="tooltip" title="Kaldır" class="fa fa-minus"></i></button><br><br>
                    @include('table',[
                        "id" => "liman_table",
                        "value" => $role->permissions->where('type','liman'),
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
                        <option value="{{$extension->id}}">{{$extension->name}}</option>
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

    <script>
        function getFunctionList(){
            let extension_id = $("#extensionId :selected").val();
            let form = new FormData();
            form.append('extension_id', extension_id);
            form.append('user_id','{{$role->id}}');
            request('{{route('extension_function_list')}}', form, function (response) {
                $(".functionsTable").html(response);
                $('.functionsTable table').DataTable(dataTablePresets('multiple'));
            }, function(response){
                let error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            });
        }

        function getUserList(){
            request('{{route('get_user_list_admin')}}', new FormData(), function (response) {
                $("#user_modal .modal-body").html(response);
                $('#user_modal table').DataTable(dataTablePresets('multiple'));
                $("#user_modal").modal('show');
            }, function(response){
                let error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            });
        }

        function addUsers(){
            showSwal('{{__("Güncelleniyor...")}}','info');
            let data = [];
            let table = $('#user_modal table').DataTable();
            table.rows( { selected: true } ).data().each(function(element){
                data.push(element[3]);
            });
            if(data.length == 0){
                showSwal('{{__("Lütfen önce seçim yapınız."}}','error',2000);
                return false;
            }
            let form = new FormData();
            form.append("users",JSON.stringify(data));
            form.append("role_id",'{{$role->id}}');
            request('{{route("add_role_users")}}',form,function(){
                reload();
            }, function(response){
                let error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            });
            return false;
        }

        function removeUsers(){
            showSwal('{{__("Güncelleniyor...")}}','info');
            let data = [];
            let table = $('#role_users table').DataTable();
            table.rows( { selected: true } ).data().each(function(element){
                data.push(element[3]);
            });
            if(data.length == 0){
                showSwal("{{__('Lütfen önce seçim yapınız.')}}",'error',2000);
                return false;
            }
            let form = new FormData();
            form.append("users",JSON.stringify(data));
            form.append("role_id",'{{$role->id}}');
            request('{{route("remove_role_users")}}',form,function(){
                reload();
            }, function(response){
                let error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            });
            return false;
        }

        function addFunctions(){
            showSwal('{{__("Güncelleniyor...")}}','info');
            let data = [];
            let table = $('.functionsTable table').DataTable();
            table.rows( { selected: true } ).data().each(function(element){
                data.push(element[1]);
            });
            if(data.length == 0){
                showSwal('{{__("Lütfen önce seçim yapınız.")}}','error',2000);
                return false;
            }
            let form = new FormData();
            let extension_id = $("#extensionId :selected").val();
            form.append("extension_id",extension_id);
            form.append("functions",data);
            form.append("role_id",'{{$role->id}}');
            request('{{route("add_role_function")}}',form,function(){
                location.reload();
            }, function(response){
                let error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            });
        }

        function removeFunctions(){
            showSwal('{{__("Güncelleniyor...")}}','info');
            let data = [];
            let table = $('#extensionFunctions').DataTable();
            table.rows( { selected: true } ).data().each(function(element){
                data.push(element[3]);
            });
            let form = new FormData();
            form.append("functions",data);
            form.append("role_id",'{{$role->id}}');
            request('{{route("remove_role_function")}}',form,function(response){
                let json = JSON.parse(response);
                showSwal(json["message"],'success',2000);
                setTimeout(function () {
                    location.reload();
                },2000);
            }, function(response){
                let error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            });
        }
        
        function getList(type) {
            let form = new FormData();
            form.append('type', type);
            form.append('role_id','{{$role->id}}');
            request('{{route('role_permission_list')}}', form, function (response) {
                Swal.close();
                $("#" + type + "_modal .modal-body").html(response);
                $("#" + type + "_modal table").DataTable(dataTablePresets('multiple'));
                $("#" + type + "_modal").modal('show');
            }, function(response){
                let error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            })
        }
        
        function removePermission(element){
            let data = [];
            let table = $("#" + element + "_table").DataTable();
            table.rows( { selected: true } ).data().each(function(element){
                data.push(element[2]);
            });

            if(data === []){
                showSwal('{{__("Lütfen önce seçim yapınız.")}}','error',2000);
                return false;
            }
            showSwal('{{__("Siliniyor...")}}','info');

            let form = new FormData();
            form.append('ids',JSON.stringify(data));
            form.append('role_id','{{$role->id}}');
            form.append('type',element);
            request('{{route('remove_role_permission_list')}}', form, function (response) {
                let json = JSON.parse(response);
                showSwal(json["message"],'success',2000);
                setTimeout(function () {
                        location.reload();
                },2000);
            }, function(response){
                let error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            });
            return false;
        }

        function addData(modalElement) {
            showSwal('{{__("Ekleniyor...")}}','info');
            let data = [];
            let table = $(modalElement).find('table').DataTable();
            table.rows( { selected: true } ).data().each(function(element){
                data.push(element[1]);
            });
            let form = new FormData();
            form.append('ids',JSON.stringify(data));
            form.append('role_id','{{$role->id}}');
            form.append('type',modalElement.getAttribute('id').split('_')[0]);
            request('{{route('add_role_permission_list')}}', form, function (response) {
                let json = JSON.parse(response);
                showSwal(json["message"],'success',2000);
                setTimeout(function () {
                    location.reload();
                },2000);
            }, function(response){
                let error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            });
            return false;
        }

        $('table').DataTable(dataTablePresets('multiple'));
    </script>
@endsection