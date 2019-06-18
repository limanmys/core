@extends('layouts.app')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{route('home')}}">Ana Sayfa</a></li>
            <li class="breadcrumb-item"><a href="{{route('servers')}}">Sunucular</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{$server->name}}</li>
        </ol>
    </nav>
    @if(isset(auth()->user()->favorites) && in_array(server()->_id,auth()->user()->favorites))
        <button onclick="favorite('false')" class="btn btn-warning">{{__("Favorilerden Sil")}}</button>
    @else
        <button onclick="favorite('true')" class="btn btn-warning">{{__("Favorilere Ekle")}}</button>
    @endif
    @include('l.modal-button',[
        "class" => "btn-primary",
        "target_id" => "edit",
        "text" => "Düzenle"
    ])

    @if(count($available_extensions))
        @include('l.modal-button',[
            "class" => "btn-primary",
            "target_id" => "install_extension",
            "text" => "Servisi Aktifleştir"
        ])
    @endif
    @include('l.modal-button',[
        "class" => "btn-info",
        "target_id" => "give_permission",
        "text" => "Yetki Ver"
    ])
    @include('l.modal-button',[
        "class" => "btn-danger",
        "target_id" => "log_table",
        "text" => "Sunucu Logları"
    ])
    <br><br>
    @if(count($installed_extensions) > 0)
        <h4>{{__("Servis Durumları")}}</h4>
        @foreach($installed_extensions as $extension)
            <button type="button" class="btn btn-outline-primary btn-lg status_{{$extension->service}}"
                    style="cursor:default;" onclick="location.href = '{{route('extension_server',["extension_id" => $extension->_id, "city" => $server->city, "server_id" => $server->_id])}}'">
                {{$extension->name}}
            </button>
        @endforeach
    @else
        <h4>{{__("Yüklü servis yok.")}}</h4>
    @endif
    <br><br>

    @include('l.modal-button',[
        "class" => "btn-danger",
        "target_id" => "delete",
            "text" => "Sunucuyu Sil"
    ])
    @include('l.modal-table',[
            "id" => "log_table",
            "title" => "Sunucu Logları",
            "table" => [
                "value" => \App\ServerLog::retrieve(true),
                "title" => [
                    "Komut" , "User ID", "Tarih", "*hidden*"
                ],
                "display" => [
                    "command" , "username", "created_at", "_id:_id"
                ],
                "onclick" => "logDetails"
            ]
        ])
    @include('l.modal',[
        "id"=>"delete",
        "title" => $server->name,
        "url" => route('server_remove'),
        "text" => "isimli sunucuyu silmek istediğinize emin misiniz? Bu işlem geri alınamayacaktır.",
        "next" => "redirect",
        "inputs" => [
            "Sunucu Id:$server->_id" => "server_id:hidden"
        ],
        "submit_text" => "Sunucuyu Sil"
    ])

    @include('l.modal',[
        "id"=>"edit",
        "title" => "Sunucuyu Düzenle",
        "url" => route('server_update'),
        "next" => "reload",
        "inputs" => [
            "Sunucu Adı" => "name:text",
            "Kontrol Portu" => "control_port:number",
            "Şehir:city" => cities(),
            "Sunucu Id:$server->_id" => "server_id:hidden"
        ],
        "submit_text" => "Düzenle"
    ])
    <?php
    $new_extensions = [];
    foreach ($available_extensions as $current) {
        $new_extensions[$current->name . ":" . $current->_id] = [
            $current->name . ":" . $current->_id => "extension_id:hidden"
        ];
    }
    ?>
@if(count($available_extensions))
    @include('l.modal',[
        "id"=>"install_extension",
        "title" => "Servisi Aktifleştir",
        "url" => route('server_extension'),
        "next" => "reload",
        "selects" => $new_extensions,
        "inputs" => [
            "Sunucu Id:$server->_id" => "server_id:hidden"
        ],
        "submit_text" => "Aktifleştir"
    ])

@endif
    @include('l.modal',[
        "id"=>"give_permission",
        "title" => "Kullanıcıya Yetki Ver",
        "url" => route('server_grant_permission'),
        "next" => "function(){return false;}",
        "inputs" => [
            "Kullanıcı Emaili" => "email:text",
            "Sunucu Id:$server->_id" => "server_id:hidden"
        ],
        "text" => "Güvenlik sebebiyle kullanıcı listesi sunulmamaktadır.",
        "submit_text" => "Yetkilendir"
    ])
<script>
    function checkStatus(service) {
        let data = new FormData();
        if(!service){
            return false;
        }
        data.append('server_id', '{{$server->_id}}');
        data.append('service', service);
        request('{{route('server_check')}}', data, function (response) {
            let json = JSON.parse(response);
            let element = $(".status_" + service);
            element.removeClass('btn-secondary').addClass(json["message"]);
        });
    }

    @if(count($installed_extensions) > 0)
    @foreach($installed_extensions as $service)
    setInterval(function () {
        checkStatus('{{$service->service}}');
    }, 3000);

    @endforeach
    @endif

    function logDetails(element){
        let log_id = element.querySelector('#_id').innerHTML;
        window.location.href = "/logs/" + log_id
    }
    function favorite(action){
        let form = new FormData();
        form.append('server_id','<?php echo e(server()->_id); ?>');
        form.append('action',action);
        request('<?php echo e(route('server_favorite')); ?>',form,function (response) {
            location.reload();
        })
    }
</script>
@endsection