@extends('layouts.app')

@section('content')
    <button class="btn btn-success" onclick="history.back();">Geri Don</button><br><br>
    <h1 class="h2">Sunucular</h1>
    <table class="table">
        <thead>
        <tr>
            <th scope="col">Sunucu Adı</th>
            <th scope="col">İp Adresi</th>
            <th scope="col">Port</th>
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

