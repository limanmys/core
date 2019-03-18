@extends('layouts.app')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
            <li class="breadcrumb-item" aria-current="page"><a href="{{route('extensions_settings')}}">{{__("Eklenti Yönetimi")}}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ __('Yeni Eklenti') }}</li>
        </ol>
    </nav>
    <form action="" class="form-group">
        <h3>{{__("Eklenti Adı")}}</h3>
        <input type="text" name="name" class="form-control">
        <h3>{{__("Yayınlayan")}}</h3>
        <input type="text" name="name" class="form-control" value="{{auth()->user()->name}}" disabled>
        <h3>{{__("Destek Email'i")}}</h3>
        <input type="text" name="email" class="form-control" value="{{auth()->user()->email}}">
        <h3>{{__("Eklenti için sunucuda betik çalıştırılması gerekiyor mu?")}}</h3>
        <div class="bd-example">
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="serverless" id="inlineRadio1" value="true">
                <label class="form-check-label" for="inlineRadio1">{{__("Evet")}}</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="serverless" id="inlineRadio2" value="false">
                <label class="form-check-label" for="inlineRadio2">{{__("Hayır")}}</label>
            </div>
        </div>
        <h3>{{__("Logo (Font Awesome Ikon)")}}</h3>
        <input type="text" name="icon" class="form-control">
    </form>
@endsection