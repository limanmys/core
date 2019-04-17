@extends('layouts.app')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{__("Yetki Taleplerim")}}</li>
        </ol>
    </nav>
@include('l.modal-button',[
    "class" => "btn-success",
    "target_id" => "request",
    "text" => "Talep Oluştur"
])<br><br>
    <h3>{{__("Talepleriniz")}}</h3>
    @include('l.table',[
            "value" => $requests,
            "title" => [
                "Açıklama" , "Durumu", "Son Guncelleme", "*hidden*"
            ],
            "display" => [
                "note" , "status", "updated_at", "_id:server_id"
            ]
        ])

@include('l.modal',[
    "id"=>"request",
    "title" => "Talep Oluştur",
    "url" => route('request_send'),
    "next" => "reload",
    "inputs" => [
        "Talep Tipi:type" => [
            "Sunucu" => "server",
            "Betik" => "script",
            "Eklenti" => "extension",
            "Diğer" => "other"
        ],
        "Önem Derecesi:speed" => [
            "Normal" => "normal",
            "Acil" => "urgent"
        ],
        "Açıklama" => "note:text"
    ],
    "submit_text" => "Oluştur"
])
@endsection