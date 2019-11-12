@extends('layouts.master')

@section('body_class', 'sidebar-mini ' . ((\Session::has('collapse')) ? 'sidebar-collapse' : ''))

@section('body')
    <div class="wrapper">
        @auth
            @include('layouts.header')
        @endauth
        @include('layouts.content')
    </div>
@stop