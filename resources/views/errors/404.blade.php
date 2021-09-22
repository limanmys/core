@if(request()->wantsJson() || $_SERVER['REMOTE_ADDR'] == "127.0.0.1")
    @php(respond(__($exception->getMessage()),201))
@else
    @extends('layouts.app')
    @section('content')
        <div class="error-page mt-5">
            <h2 class="headline text-warning"><i class="fas fa-exclamation-triangle text-warning"></i></h2>
            <div class="error-content">
                <h3>{{ __("Bir şeyler ters gitti.") }}</h3>
    
                <p>
                    {{ $exception->getMessage() }}
                    <br><a class="btn btn-primary mt-2" href="{{ URL::current() }}">{{ __("Yenile") }}</a>
                </p>
            </div>
        </div>
    @endsection
@endif