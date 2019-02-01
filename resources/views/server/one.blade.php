@extends('layouts.app')

@section('content_header')
    <h1>{{$server->name}}</h1>
@stop


@section('content')

    <button class="btn btn-success" onclick="location.href = '/sunucular/';">{{__("Geri Dön")}}</button>

    @include('l.modal-button',[
        "class" => "btn-primary",
        "target_id" => "edit",
        "text" => "Düzenle"
    ])
    @if(count($available_extensions))
        @include('l.modal-button',[
            "class" => "btn-secondary",
            "target_id" => "install_extension",
            "text" => "Servisi Aktifleştir"
        ])
    @endif
    @include('l.modal-button',[
        "class" => "btn-info",
        "target_id" => "give_permission",
        "text" => "Yetki Ver"
    ])<br><br>
    @if(count($installed_extensions) > 0)
        <h4>{{__("Servis Durumları")}}</h4>
        @foreach($installed_extensions as $extension)
            <button type="button" class="btn btn-secondary btn-lg" style="cursor:default;"
                    id="status_{{$extension->service}}">
                {{strtoupper($extension->name)}}
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

@endsection