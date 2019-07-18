@extends('layouts.app')

@section('content')

<ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
    <li class="breadcrumb-item"><a href="/l/{{extension()->id}}">{{extension()->name}} {{ __('Sunucuları') }}</a></li>
    <li class="breadcrumb-item"><a href="/l/{{extension()->id}}/{{server()->city}}">{{cities(server()->city)}}</a></li>
<li class="breadcrumb-item"><a href='/l/{{extension()->id}}/{{server()->city}}/{{server()->id}}'>{{server()->name}}</a></li>

    <li class="breadcrumb-item active" aria-current="page">{{__("Eklenti Ayarları")}}</li>
</ol>

<button class="btn btn-primary" onclick="history.back()">{{__("Geri Dön")}}</button><br><br>
@if(!empty($errors) && count($errors))
    <div class="alert alert-danger" role="alert">
        {{$errors->getBag('default')->first('message')}}
    </div>
@endif
@if(count($similar))
    <div class="alert alert-info" role="alert">
        {{__("Önceki ayarlarınızdan sizin için birkaç veri eklendi.")}}
    </div>
@endif
@if($extension["database"])
<form action="{{route('extension_server_settings',[
                        "extension_id" => request()->route('extension_id'),
                        "server_id" => request()->route('server_id')
                    ])}}" method="POST">
        @csrf
        @foreach($extension["database"] as $item)
            @if($item["variable"] == "certificate")
                <h5>{{$item["name"]}}</h5>
                <textarea name="certificate" cols="30" rows="10" class="form-control"></textarea><br>
            @elseif($item["type"] == "extension")
                <h5>{{$item["name"]}}</h5>
                <select class="form-control" name="{{$item["variable"]}}">
                    @foreach(extensions() as $extension)
                        <option value="{{$extension->id}}">{{$extension->name}}</option>
                    @endforeach
                </select><br>
            @elseif($item["type"] == "server")
                <h5>{{$item["name"]}}</h5>
                <select class="form-control" name="{{$item["variable"]}}">
                    @foreach(servers() as $server)
                        <option value="{{$server->id}}">{{$server->name}}</option>
                    @endforeach
                </select><br>
            @else
                <h5>{{__($item["name"])}}</h5>
                <input class="form-control" type="{{$item["type"]}}"
                       name="{{$item["variable"]}}" placeholder="{{__($item["name"])}}"
                       @if($item["type"] != "password")
                           @if(extensionDb($item["variable"]))
                               value="{{extensionDb($item["variable"])}}"
                           @elseif(array_key_exists($item["variable"],$similar))
                               value="{{$similar[$item["variable"]]}}"
                           @endif
                       @endif
                >
            @endif

        @endforeach
        <br>
        <button type="submit" class="btn btn-success">{{__("Kaydet")}}</button>
    </form>
@else
<br>
    <h3>{{__("Bu eklentinin hiçbir ayarı yok.")}}</h3>
@endif

@endsection