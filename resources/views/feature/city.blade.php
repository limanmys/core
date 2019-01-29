@extends('layouts.app')

@section('content_header')
    <h1>{{$name}} {{ __('Sunucuları') }}</h1>
@stop

@section('content')

    <button class="btn btn-success" onclick="history.back();">{{ __('Geri Dön') }}</button><br><br>
    <table class="table">
        <thead>
        <tr>
            <th scope="col">{{ __('Sunucu Adı') }}</th>
            <th scope="col">{{ __('İp Adresi') }}</th>
            <th scope="col">{{ __('Port') }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($servers as $server)
            <tr onclick="location.href = location.href + '/{{$server->_id}}';" class="highlight" style="cursor: pointer">
                <td>{{$server->name}}</td>
                <td>{{$server->ip_address}}</td>
                <td>{{$server->control_port}}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection