@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h2>{{ __("SSH Anahtarları") }}</h2>
    </div>
    <button type="button" class="btn btn-success" data-toggle="modal" data-target="#exampleModal">
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
    <script>
        function add(event,flag) {
            event.preventDefault();
            var x = $("#form").serializeArray();
            dataObj = {};
            $.each(x, function(i, field){

                dataObj[field.name] = field.value;
            });
            var route=flag=='add' ? "{{route('key_add')}}" :'';
            $.post(route,{
                name : dataObj['name'],
                username : dataObj['username'],
                password : dataObj['password'],
                server_id : dataObj['server']
            },function (data,status) {
                if(data["result"] === 200){
                    location.reload();
                }else{
                    alert("Hata!");

                }
            });
        }
    </script>

    @include('modal',[
                     "id"=>"exampleModal",
                     "title" => __("Anahtar Ekle"),
                     "url" => "/anahtar/ekle",
                     "inputs" => [
                         __("Adı") => "name:text",
                         __("Sunucu") => $servers,
                         __("Kullanıcı Adı") => "username:text",
                         __("Parola") => "password:password"
                     ],
                     "submit_text" => __("Ekle")
                 ])
    @include('modal',[
                         "id"=>"duzenle",
                         "title" => __("Anahtar Düzenle"),
                         "url" => "/anahtar/ekle",
                         "inputs" => [
                             __("Adı") => "name:text",
                             __("Sunucu") => [

                                    "asd"=>"asd2",
                                    "ert"=>"ert2"

                             ],
                             __("Kullanıcı Adı") => "username:text",
                             __("Parola") => "password:password"
                         ],
                         "submit_text" => __("Düzenle")
                     ])
@endsection