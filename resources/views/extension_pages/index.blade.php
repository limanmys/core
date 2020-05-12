@extends('layouts.app')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{__(extension()->display_name)}} {{ __('Sunucuları') }}</li>
        </ol>
    </nav>
    @include('errors')
    @include('general.harita')
@endsection