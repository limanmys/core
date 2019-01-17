@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">{{request('server')->name}} sunucusu <b>{{$extension->name}}</b> ayarları</h1>
    </div>
    <button class="btn btn-success" onclick="history.back()">{{__("Geri Dön")}}</button>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body mainArea">
                    <form action="{{route('extension_server_settings',[
                        "extension_id" => request()->route('extension_id'),
                        "server_id" => request()->route('server_id')
                    ])}}" method="POST">
                        @csrf
                    @foreach($extension->setup as $key => $item)
                        @include('__system__.inputs',[
                            "inputs" => [
                                $item["name"] => $key . ":" . $item["type"]
                            ]
                        ])
                    @endforeach
                        <button type="submit" class="btn btn-success">Kaydet</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection