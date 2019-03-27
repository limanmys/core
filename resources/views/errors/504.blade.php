@if(request()->wantsJson())
    @php(respond(__($exception->getMessage()),201))
@else
    @extends('layouts.app')

    @section('content')
        <h1 class="ml-auto">{{__($exception->getMessage())}}</h1>
    @endsection
@endif