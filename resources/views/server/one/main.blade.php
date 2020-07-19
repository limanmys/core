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
                <button onclick="favorite('false')" class="btn btn-warning fas fa-star btn-sm" data-toggle="tooltip" title="Favorilerden Çıkar"></button>
            @else
                <button onclick="favorite('true')" class="btn btn-success far fa-star btn-sm" data-toggle="tooltip" title="Favorilere Ekle"></button>
            @endif
        </div>
        <div class="col-auto align-self-center">
            <h5>{{$server->name}}</h5>
        </div>
    </div>

    @include('errors')

    <div class="row">
        @include('server.one.general.details',["shell" => false])

        @include('server.one.one')

    </div>

    @include('server.one.general.modals')

    @include('server.one.general.scripts')

@endsection