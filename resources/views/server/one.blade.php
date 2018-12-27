@extends('layouts.app')

@section('content')

    @include('title',[
        "title" => $server->name
    ])
    <button class="btn btn-success" onclick="location.href = '/sunucular/';">{{__("Geri Dön")}}</button>

    @include('modal-button',[
        "class" => "btn-primary",
        "target_id" => "edit",
        "text" => "Düzenle"
    ])
    @include('modal-button',[
        "class" => "btn-secondary",
        "target_id" => "install_extension",
        "text" => "Servisi Aktifleştir"
    ])<br><br>
    @if(count($services) > 0)
        <h4>{{__("Servis Durumları")}}</h4>
        @foreach($services as $service)
            <button type="button" class="btn btn-info btn-lg" style="cursor:default;" id="status_{{$service}}">
                {{strtoupper($service)}}
            </button>
        @endforeach
    @else
        <h4>{{__("Yüklü servis yok.")}}</h4>
    @endif
    <br><br>
    <pre>
        @isset($stats)
            {{$stats}}
        @endisset
    </pre>

    @include('modal-button',[
        "class" => "btn-danger",
        "target_id" => "delete",
            "text" => "Sunucuyu Sil"
    ])

    @include('modal',[
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

    @include('modal',[
        "id"=>"edit",
        "title" => "Sunucuyu Düzenle",
        "url" => route('server_update'),
        "next" => "reload",
        "inputs" => [
            "Sunucu Adı" => "name:text",
            "Kontrol Portu" => "control_port:number",
            "Sunucu Id:$server->_id" => "server_id:hidden"
        ],
        "submit_text" => "Düzenle"
    ])

    @include('modal',[
        "id"=>"install_extension",
        "title" => "Servisi Aktifleştir",
        "url" => route('server_extension'),
        "next" => "message",
        "selects" => [
            "DNS:5c0a170f7b57f19953126e37" => [
                "DNS:5c0a170f7b57f19953126e37" => "extension_id:hidden"
            ],
            "DHCP:5c0a1c5f7b57f19953126e38" => [
                "DHCP:5c0a1c5f7b57f19953126e38" => "extension_id:hidden"
            ]
        ],
        "inputs" => [
            "Sunucu Id:$server->_id" => "server_id:hidden"
        ],
        "submit_text" => "Aktifleştir"
    ])

@endsection