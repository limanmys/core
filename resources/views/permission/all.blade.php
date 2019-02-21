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
    "text" => "Yetki İste"
])<br><br>

<table class="table">
    <thead>
    <tr>
        <th scope="col">{{__("Tipi")}}</th>
        <th scope="col">{{__("Notu")}}</th>
        <th scope="col">{{__("Durumu")}}</th>
    </tr>
    </thead>
    <tbody>
    <?php
        $list = [
             "server" => "Sunucu",
             "script" => "Betik",
             "extension" => "Eklenti",
             "other" => "Diğer"
        ];
    ?>
    @foreach ($requests as $request)
        <tr class="highlight">
            <td>{{__($list[$request->type])}}</td>
            <td>{{$request->note}}</td>
            <td>
                @switch($request->status)
                    @case(0)
                        {{__("Talep Alındı")}}
                        @break
                    @case(1)
                        {{__("İşleniyor")}}
                        @break
                    @default
                        {{__("Tamamlandı.")}}
                @endswitch
            </td>
        </tr>
    @endforeach
    </tbody>
</table>

@include('l.modal',[
    "id"=>"request",
    "title" => "Yetki İste",
    "url" => route('request_send'),
    "next" => "reload",
    "inputs" => [
        "Yetki Tipi:type" => [
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
    "submit_text" => "Talep Aç"
])
@endsection