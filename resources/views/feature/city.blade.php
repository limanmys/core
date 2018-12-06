@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">{{ __('Sunucular') }}</h1>
    </div>
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
            <tr onclick="location.href = location.href + '/{{$server->_id}}';" class="highlight">
                <td>{{$server->name}}</td>
                <td>{{$server->ip_address}}</td>
                <td>{{$server->port}}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection