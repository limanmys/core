@extends('layouts.app')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{__(extension()->display_name)}} {{ __('Sunucuları') }}</li>
        </ol>
    </nav>
    @include('errors')
    @if($cities == "")
        <div class="alert alert-warning" role="alert">
            Bu eklentiyi kullanan hiçbir sunucu yok, hemen <a href="{{route('servers')}}">sunucular</a> sayfasına gidip mevcut sunucularınızdan birini bu eklentiyi kullanması için ayarlayabilirsiniz.
        </div>
    @else
        @include('general.harita')  
    @endif
    
@endsection