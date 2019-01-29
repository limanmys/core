@extends('layouts.app')

@section('content_header')
    <h1>{{__("Başlamak için lütfen bir şehir seçin.")}}</h1>
@stop

@section('content')

    @include('general.harita')
@endsection