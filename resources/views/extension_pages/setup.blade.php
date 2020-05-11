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
@include("extension_pages.setup_data")
@endsection
