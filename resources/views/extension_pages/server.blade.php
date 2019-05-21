@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
        <li class="breadcrumb-item"><a href="/l/{{extension()->_id}}">{{extension()->name}} {{ __('Sunucuları') }}</a>
        </li>
        <li class="breadcrumb-item"><a href="/l/{{extension()->_id}}/{{request('city')}}">{{cities(request('city'))}}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{server()->name}}</li>
    </ol>
    <div class="right" style="float:right;margin-top:-55px">
            <button class="btn btn-primary" onclick="location.href = '{{route('extension_server_settings_page',[
                "server_id" => server()->_id,
                "extension_id" => extension()->_id
            ])}}'"><i class="fa fa-cogs"></i></button>
            <button class="btn btn-primary" onclick="location.href = '{{route('server_one',[
                "server_id" => server()->_id,
            ])}}'"><i class="fa fa-server"></i></button>
    </div>
    {{-- <pre>{{$command}}</pre> --}}
    <div class="card">
        <div class="card-body mainArea">{!!$view!!}</div>
    </div>
@endsection