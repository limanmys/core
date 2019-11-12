@extends('layouts.master')

@section('body_class', 'sidebar-mini ' . ((\Session::has('collapse')) ? 'sidebar-collapse' : ''))

@section('body')
    <div class="wrapper">
        @auth
            @include('layouts.header')
        @endauth
        @include('layouts.content')
        <footer class="main-footer">
            <div class="float-right d-none d-sm-block">
                <b>{{ __("Versiyon") }}</b> {{ env('APP_VERSION') }}
            </div>
            <img src="{{ url('/images/havelsan_logo.png') }}" style="max-height: 30px;" alt="HAVELSAN A.Ş.">
        </footer>
    </div>
@stop