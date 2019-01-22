@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h2>{{ __("SSH Anahtarları") }}</h2>
    </div>
    <button type="button" class="btn btn-success" data-toggle="modal" data-target="#add_key">
        {{ __("Anahtar Ekle") }}
    </button><br><br>
    <table class="table">
        <thead>
        <tr>
            <th scope="col">{{ __("Sunucu") }}</th>
            <th scope="col">{{ __("Kullanıcı") }}</th>
            <th scope="col">{{ __("Port") }}</th>
        </tr>
        </thead>
        <tbody data-toggle="modal" data-target="#duzenle">
        @foreach ($keys as $key)
            <tr>
                <td>{{$key->name}}</td>
                <td>{{$key->username}}</td>
                <td>{{$servers->where('_id',$key->server_id)->first()->control_port}}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <?php 
        $input_servers = [];
        foreach($servers as $server){
            $input_servers[$server->name] = $server->_id;
        }
    ?>

    @include('modal',[
        "id"=>"add_key",
        "title" => "SSH Anahtar Ekle",
        "url" => route('key_add'),
        "next" => "reload",
        "inputs" => [
            "Adı" => "name:text",
            "Sunucu Secin:server_id" => $input_servers,
            "Kullanıcı Adı" => "username:text",
            "Parola" => "password:password"
        ],
        "submit_text" => "Ekle"
    ])
@endsection