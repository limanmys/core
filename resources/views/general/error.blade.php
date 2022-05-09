@extends('layouts.app')

@section('content_header')
    <div class="container-fluid">
        <div class="error-page mt-5">
            <h2 class="headline text-warning"><i class="fas fa-exclamation-triangle text-warning"></i></h2>
            <div class="error-content">
                <h3>{{ __('Uyarı') }}</h3>
                <p>
                    {{ __($message) }}
                    <br>
                    @if ($status == 403)
                        <a class="btn btn-success mt-3" href="{{ route('keys') }}"><i
                                class="fa-solid fa-vault mr-2"></i>{{ __('Kasa') }}</a>
                    @else
                        <button class="btn btn-success mt-3" onclick="history.back()">{{ __('Geri Dön') }}</button>
                    @endif
                </p>
            </div>
        </div>
    </div>
@stop

@section('content')

    <!-- -->

@endsection
