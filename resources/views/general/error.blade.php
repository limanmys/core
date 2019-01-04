@extends('layouts.app')

@section('content')
    @include('title',[
    "title" => $message
    ])

    <button class="btn btn-success" onclick="history.back()">{{__("Geri Dön")}}</button>

@endsection