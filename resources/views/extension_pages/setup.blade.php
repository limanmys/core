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
<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{__("Eklenti Ayarları")}}</h3>
    </div>
    <form action="{{route('extension_server_settings',[
            "extension_id" => request()->route('extension_id'),
            "server_id" => request()->route('server_id')
        ])}}" method="POST">
    @csrf
        <div class="card-body">
            @if(!empty($errors) && count($errors))
                <div class="alert alert-danger" role="alert">
                {!! $errors->getBag('default')->first('message') !!}
                </div>
            @elseif(count($similar))
                <div class="alert alert-info" role="alert">
                    {{__("Önceki ayarlarınızdan sizin için birkaç veri eklendi.")}}
                </div>
            @endif
            @if($extension["database"])
                @foreach($extension["database"] as $item)
                        @if($item["variable"] == "certificate")
                            <div class="form-group">
                                <label>{{$item["name"]}}</label>
                                <textarea name="certificate" cols="30" rows="10" class="form-control" @if(!isset($item["required"]) || $item["required"] === true) required @endif></textarea><br>
                            </div>                    
                        @elseif($item["type"] == "extension")
                            <div class="form-group">
                                <label>{{$item["name"]}}</label>
                                <select class="form-control" name="{{$item["variable"]}}" @if(!isset($item["required"]) || $item["required"] === true) required @endif>
                                    <option>{{$item["name"]}}</option>
                                    @foreach(extensions() as $extension)
                                        <option value="{{$extension->id}}" @if($extension->id == old($item["variable"], extensionDb($item["variable"]))) selected @endif >{{$extension->name}}</option>
                                    @endforeach
                                </select>
                            </div>                    
                        @elseif($item["type"] == "server")
                            <div class="form-group">
                                <label>{{$item["name"]}}</label>
                                <select class="form-control" name="{{$item["variable"]}}" @if(!isset($item["required"]) || $item["required"] === true) required @endif>
                                    <option>{{$item["name"]}}</option>
                                    @foreach(servers() as $server)
                                        <option value="{{$server->id}}" @if($server->id == old($item["variable"], extensionDb($item["variable"]))) selected @endif>{{$server->name}}</option>
                                    @endforeach
                                </select>
                            </div>                    
                        @else
                            <div class="form-group">
                                <label>{{__($item["name"])}}</label>
                                <input @if(!isset($item["required"]) || $item["required"] === true) required @endif class="form-control" type="{{$item["type"]}}"
                                    name="{{$item["variable"]}}" placeholder="{{__($item["name"])}}"
                                    @if($item["type"] != "password")
                                        @if(extensionDb($item["variable"]))
                                            value="{{old($item["variable"], extensionDb($item["variable"]))}}"
                                        @elseif(array_key_exists($item["variable"],$similar))
                                            value="{{old($item["variable"], $similar[$item["variable"]])}}"
                                        @endif
                                    @endif
                                >
                            </div>                    
                            @if($item["type"] == "password")
                            <div class="form-group">
                                <label>{{__($item["name"])}} {{__('Tekrar')}}</label>
                                <input @if(!isset($item["required"]) || $item["required"] === true) required @endif class="form-control" type="{{$item["type"]}}"
                                        name="{{$item["variable"]}}_confirmation" placeholder="{{__($item["name"])}} {{__('Tekrar')}}"
                                >
                            </div>                    
                            @endif
                        @endif
                @endforeach
            @else
            <br>
                <h3>{{__("Bu eklentinin hiçbir ayarı yok.")}}</h3>
            @endif
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-success">{{__("Kaydet")}}</button>
        </div>
    </form>
</div>
@endsection
