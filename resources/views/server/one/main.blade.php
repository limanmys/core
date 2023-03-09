@extends('layouts.app')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
            <li class="breadcrumb-item"><a href="{{route('servers')}}">{{__("Sunucular")}}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{$server->name}}</li>
        </ol>
    </nav>
    
    @include('errors')

    @if (count(server()->extensions()) < 1)
    <div class="alert alert-success alert-dismissible">
        <h5><i class="icon fas fa-smile-beam"></i> {{ __("Tavsiye") }}</h5>
        @if (session('locale') == "tr")
        Bu sunucuya hiç eklenti eklememişsiniz. Limanı daha verimli kullanabilmek için <i class='fas fa-plug'></i> eklentiler sekmesinden eklenti ekleyebilirsiniz veya <a href='/market'><i class='fas fa-shopping-cart'></i> eklenti mağazamızı</a> kullanarak açık kaynaklı eklentileri tek tuş ile yükleyebilirsiniz.
        @else
        You haven't added any extensions on this server. For using Liman more effectively add <i class='fas fa-plug'></i> extensions or download and install with one click from our <a href='/market'><i class='fas fa-shopping-cart'></i> extension store</a>
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