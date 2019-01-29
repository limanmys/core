@extends('layouts.app')

@section('content_header')
    <h1>Yetki Talepleri</h1>
@stop

@section('content')

@include('title',[
    "title" => "Yetki Talepleri"
])

<button class="btn btn-success" onclick="history.back()">{{__("Geri Dön")}}</button><br><br>
<table class="table">
    <thead>
    <tr>
        <th scope="col">{{__("Tipi")}}</th>
        <th scope="col">{{__("Kullanıcı")}}</th>
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
        <tr class="highlight" onclick="window.location = '{{route('request_one',$request->_id)}}'">
            <td>{{__($list[$request->type])}}</td>
            <td>{{$request->user_name}}</td>
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

@endsection