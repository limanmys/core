@extends('layouts.app')

@section('content')

    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Sistem Ayarları</h1>
    </div>


    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link active" href="#">{{__("Kullanıcılar")}}</a>
        </li>
        <li class="nav-item">
        <a class="nav-link" href="#">{{__("Ayarlar")}}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#">Link</a>
        </li>
        <li class="nav-item">
            <a class="nav-link disabled" href="#">Disabled</a>
        </li>
    </ul>

@endsection