@extends('layouts.app')

@section('content')

    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">{{__("Hoşgeldiniz")}}</h1>
    </div>

    <pre>
        {{$stats}}
    </pre>

@endsection