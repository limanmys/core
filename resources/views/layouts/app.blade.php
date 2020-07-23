@if(request('partialRequest'))
    @include('layouts.content')
    @php(die())
@else
    @extends('layouts.master')

    @section('body_class', 'sidebar-mini layout-fixed ' . ((session()->has('collapse')) ? 'sidebar-collapse' : ''))

    @section('body')
        <div class="wrapper">
            @auth
                @include('layouts.header')
            @endauth
            @include('layouts.content')
        </div>
    @stop
@endif