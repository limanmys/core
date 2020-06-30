@extends('layouts.app')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
            <li class="breadcrumb-item" aria-current="page"><a href="{{route('settings')}}">{{__("Sistem Ayarları")}}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{__("Sağlık Kontrolü")}}</li>
        </ol>
    </nav>
@endsection