@extends('layouts.app')

@section('content_header')
    <h1>{{__($message)}}</h1>
@stop

@section('content')

    <button class="btn btn-success" onclick="history.back()">{{__("Geri Dön")}}</button>

@endsection