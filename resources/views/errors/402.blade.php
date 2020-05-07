@if(request()->wantsJson() || $_SERVER['REMOTE_ADDR'] == "127.0.0.1")
    @php(respond(__($exception->getMessage()),201))
@else
    @extends('layouts.app')
    @section('content')
        <div class="error-page">
            <h2 class="headline text-warning"><i class="fas fa-exclamation-triangle text-warning"></i></h2>
            <div class="error-content" style="margin-top: 3rem;margin-left: 150px;">
                <h3>{{__("Bir şeyler ters gitti.")}}</h3>
    
                <p>
                    {{ $exception->getMessage() }}
                    <a class="btn btn-primary mt-2" href="{{ URL::previous() }}">{{_("Geri Dön")}}</a>
                </p>
            </div>
        </div>
    @endsection
@endif