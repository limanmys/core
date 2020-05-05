@if(request()->wantsJson() || $_SERVER['REMOTE_ADDR'] == "127.0.0.1")
    @php(respond(__($exception->getMessage()),201))
@else
    @extends('layouts.app')
    @section('content')
        <h1 class="ml-auto">{{__($exception->getMessage())}}</h1>
    @endsection
@endif