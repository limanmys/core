@extends('layouts.app')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
            <li class="breadcrumb-item"><a href="{{route('settings')}}">{{__("Ayarlar")}}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{$user->name}}</li>
        </ol>
    </nav>
    <h2>{{__("Kullanıcı Ayarları")}}</h2>
    <div class="nav-tabs-custom">
        <ul class="nav nav-tabs">
            <li class="active"><a href="#tab_1" data-toggle="tab" aria-expanded="true">{{__("Genel Ayarlar")}}</a></li>
            <li id="server_type"><a href="#tab_2" data-toggle="tab"
                                    aria-expanded="false">{{__("Eklenti Yetkileri")}}</a></li>
            <li id="server_type"><a href="#tab_3" data-toggle="tab" aria-expanded="false">{{__("Betik Yetkileri")}}</a>
            </li>
            <li class=""><a href="#tab_4" data-toggle="tab" aria-expanded="false">{{__("Sunucu Yetkileri")}}</a></li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane active" id="tab_1">
                <h3>{{__("Kullanıcı Türü")}}</h3>
                <select name="status" class="form-control" >
                    <option value="0" @if($user->status == "0") selected @endif>{{__("Kullanıcı")}}</option>
                    <option value="1" @if($user->status == "1") selected @endif>{{__("Yönetici")}}</option>
                </select>
            </div>
            <div class="tab-pane" id="tab_2">
                <button onclick="getList('extension')" class="btn btn-success">{{__("Eklenti Ekle")}}</button><br><br>
                @include('l.table',[
                    "value" => $extensions,
                    "title" => [
                        "Adı" , "Id"
                    ],
                    "display" => [
                        "name" , "_id"
                    ],
                ])
            </div>
            <div class="tab-pane" id="tab_3">
                <button onclick="getList('script')" class="btn btn-success">{{__("Betik Ekle")}}</button><br><br>
                @include('l.table',[
                    "value" => $scripts,
                    "title" => [
                        "Adı" , "Id"
                    ],
                    "display" => [
                        "name" , "_id"
                    ],
                ])
            </div>
            <div class="tab-pane" id="tab_4">
                <button onclick="getList('server')" class="btn btn-success">{{__("Sunucu Ekle")}}</button><br><br>
                @include('l.table',[
                    "value" => $servers,
                    "title" => [
                        "Adı" , "Id"
                    ],
                    "display" => [
                        "name" , "_id"
                    ],
                ])
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
    <script>
        function getList(type) {
            let form = new FormData();
            form.append('type', type);
            form.append('user_id','{{$user->_id}}');
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
            form.append('user_id','{{$user->_id}}');
            form.append('type',modalElement.getAttribute('id').split('_')[0]);
            request('{{route('settings_add_to_list')}}', form, function (response) {
                let json = JSON.parse(response);
                if(json["status"] === "yes"){
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
                }else{
                    Swal.fire({
                        position: 'center',
                        type: 'error',
                        title: json["message"],
                        showConfirmButton: false,
                        timer: 2000
                    });
                }
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
    </script>

@endsection