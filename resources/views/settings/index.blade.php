@extends('layouts.app')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{__("Sistem Ayarları")}}</li>
        </ol>
    </nav>
    <input class="form-control" type="search" onchange="searchTree()" id="q"/><br>
    @include('l.tree',[
            "data" => objectToArray(\App\User::all(), "email", "name"),
            "search",
            "click" => "setDetails",
            "menu" => [
                "Parola Sıfırla" => "setDetails"
            ]
        ])
    <script>
        function setDetails(data)
        {
            console.log(data);
        }

        function passwordReset()
        {

        }
    </script>
@endsection