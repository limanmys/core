@extends('layouts.master')

@section('body_class', 'sidebar-mini layout-fixed ' . ((\Session::has('collapse')) ? 'sidebar-collapse' : ''))

@section('body')
{{-- <div class="winter-is-coming">

    <div class="snow snow--near"></div>
    <div class="snow snow--near snow--alt"></div>

    <div class="snow snow--mid"></div>
    <div class="snow snow--mid snow--alt"></div>

    <div class="snow snow--far"></div>
    <div class="snow snow--far snow--alt"></div>
</div> --}}
    <div class="wrapper">
        @auth
            @include('layouts.header')
        @endauth
        @include('layouts.content')
    </div>
    
    <style>
    winter-is-coming, .snow {
    z-index: 1000000;
    pointer-events: none;
}

.winter-is-coming {
    overflow: hidden;
    position: absolute;
    top: 0;
    height: 100%;
    width: 100%;
    max-width: 100%;
}

.snow {
    position: absolute;
    top: 0;
    right: 0;
    bottom: 0;
    left: 0;
    -webkit-animation: falling linear infinite both;
    animation: falling linear infinite both;
    -webkit-transform: translate3D(0, -100%, 0);
    transform: translate3D(0, -100%, 0);
}
.snow--near {
    -webkit-animation-duration: 10s;
    animation-duration: 10s;
    background-image: url("/snow/snow-large.png");
    background-size: contain;
}
.snow--near + .snow--alt {
    -webkit-animation-delay: 5s;
    animation-delay: 5s;
}
.snow--mid {
    -webkit-animation-duration: 20s;
    animation-duration: 20s;
    background-image: url("/snow/snow-medium.png");
    background-size: contain;
}
.snow--mid + .snow--alt {
    -webkit-animation-delay: 10s;
    animation-delay: 10s;
}
.snow--far {
    -webkit-animation-duration: 30s;
    animation-duration: 30s;
    background-image: url("/snow/snow-small.png");
    background-size: contain;
}
.snow--far + .snow--alt {
    -webkit-animation-delay: 15s;
    animation-delay: 15s;
}

@-webkit-keyframes falling {
    0% {
        -webkit-transform: translate3D(-7.5%, -100%, 0);
        transform: translate3D(-7.5%, -100%, 0);
    }
    100% {
        -webkit-transform: translate3D(7.5%, 100%, 0);
        transform: translate3D(7.5%, 100%, 0);
    }
}

@keyframes falling {
    0% {
        -webkit-transform: translate3D(-7.5%, -100%, 0);
        transform: translate3D(-7.5%, -100%, 0);
    }
    100% {
        -webkit-transform: translate3D(7.5%, 100%, 0);
        transform: translate3D(7.5%, 100%, 0);
    }
}
</style>
@stop