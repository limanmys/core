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
            <th scope="col">{{ __("Anahtar Adı") }}</th>
            <th scope="col">{{ __("Kullanıcı") }}</th>
            <th scope="col">{{ __("Sunucu") }}</th>
        </tr>
        </thead>
        <tbody data-toggle="modal" data-target="#duzenle">
        @foreach ($keys as $key)
            <tr onclick="" class="highlight">
                <td>{{$key->name}}</td>
                <td>{{$key->username}}</td>
                <td>{{$key->server_name}}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    @include('modal',[
        "id"=>"add_key",
        "title" => "SSH Anahtar Ekle",
        "url" => route('key_add'),
        "next" => "reload",
        "inputs" => [
            "Adı" => "name:text",
            "Sunucu " => "server_id:text",
            "Kullanıcı Adı" => "username:text",
            "Parola" => "password:password"
        ],
        "submit_text" => "Ekle"
    ])
@endsection