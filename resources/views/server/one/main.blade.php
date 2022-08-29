@extends('layouts.app')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
            <li class="breadcrumb-item"><a href="{{route('servers')}}">{{__("Sunucular")}}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{$server->name}}</li>
        </ol>
    </nav>
    
    <div class="row mb-2 serverName">
        <div class="col-auto align-self-center">
            @if($favorite)
                <button onclick="favorite('false')" class="btn btn-warning btn-sm" data-toggle="tooltip" title="{{ __('Sabitlemeyi kaldır') }}">
                    <i class="fas fa-star"></i>
                </button>
            @else
                <button onclick="favorite('true')" class="btn btn-success btn-sm" data-toggle="tooltip" title="{{ __('Sunucuyu sabitle') }}">
                    <i class="far fa-star"></i>
                </button>
            @endif
        </div>
        <div class="col-auto align-self-center">
            <h5 class="font-weight-bold pt-2">{{$server->name}}</h5>
        </div>
    </div>

    @include('errors')

    @if (count(server()->extensions()) < 1)
    <div class="alert alert-success alert-dismissible">
        <h5><i class="icon fas fa-smile-beam"></i> {{ __("Tavsiye") }}</h5>
        @if (session('locale') == "tr")
        Bu sunucuya hiç eklenti eklememişsiniz. Limanı daha verimli kullanabilmek için <a data-toggle='pill' href='#extensionsTab' role='tab'><i class='fas fa-plug'></i> eklentiler</a> sekmesinden eklenti ekleyebilirsiniz veya <a href='/market'><i class='fas fa-shopping-cart'></i> eklenti mağazamızı</a> kullanarak açık kaynaklı eklentileri tek tuş ile yükleyebilirsiniz.
        @else
        You haven't added any extensions on this server. For using Liman more effectively add<a data-toggle='pill' href='#extensionsTab' role='tab'><i class='fas fa-plug'></i> extensions</a> or download and install with one click from our <a href='/market'><i class='fas fa-shopping-cart'></i> extension store</a>
        @endif
    </div>
    @endif

    <div class="row">
        @include('server.one.general.details',["shell" => false])

        @include('server.one.one')

    </div>

    @include('server.one.general.modals')

    @include('server.one.general.scripts')

@endsection