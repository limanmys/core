@extends('layouts.app')

@section('content_header')
    <h1>{{request('server')->name}} sunucusu <b>{{$extension->name}}</b> ayarları</h1>
@stop

@section('content')

    <button class="btn btn-success" onclick="history.back()">{{__("Geri Dön")}}</button>
    <form action="{{route('extension_server_settings',[
                        "extension_id" => request()->route('extension_id'),
                        "server_id" => request()->route('server_id')
                    ])}}" method="POST">
        @csrf
        @foreach($extension->database as $item)
            @if($item["variable"] == "certificate")
                <h5>{{$item["name"]}}</h5>
                <textarea name="certificate" cols="30" rows="10" class="form-control">

                </textarea><br>
            @else
                @include('l.inputs',[
                "inputs" => [
                    $item["name"] => $item["variable"] . ":" . $item["type"]
                ]
            ])
            @endif

        @endforeach
        <button type="submit" class="btn btn-success">Kaydet</button>
    </form>
@endsection