@extends('layouts.app')

@section('content')

<ol class="breadcrumb">
    <!--<li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
    <li class="breadcrumb-item"><a href="/l/{{extension()->id}}">{{extension()->display_name}} {{ __('Sunucuları') }}</a></li>
    <li class="breadcrumb-item"><a href="/l/{{extension()->id}}/{{server()->city}}">{{cities(server()->city)}}</a></li>
    <li class="breadcrumb-item"><a href='/l/{{extension()->id}}/{{server()->city}}/{{server()->id}}'>{{server()->name}}</a></li> 
    <li class="breadcrumb-item active" aria-current="page">{{__("Eklenti Ayarları")}}</li>-->
    <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
    <li class="breadcrumb-item"><a href="{{route('server_one', server()->id)}}">{{ server()->name }}</a></li>
    <li class="breadcrumb-item"><a href="/l/{{ server()->id }}/{{ extension()->id }}">{{__(extension()->display_name)}}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{__(extension()->display_name)}} eklenti ayarları</li>
</ol>
@include("extension_pages.setup_data")
@endsection
