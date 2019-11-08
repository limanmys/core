@extends('layouts.app')

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{__("Yetki Taleplerim")}}</li>
    </ol>
</nav>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{__("Talepleriniz")}}</h3>
    </div>
    <div class="card-body">
        @include('l.errors')    
        @include('l.modal-button',[
            "class" => "btn-success",
            "target_id" => "request",
            "text" => "Talep Oluştur"
        ])<br><br>
        @include('l.table',[
            "value" => $requests,
            "title" => [
                "Açıklama" , "Durumu", "Son Guncelleme", "*hidden*"
            ],
            "display" => [
                "note" , "status", "updated_at", "_id:server_id"
            ]
        ])
    </div>
</div>

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