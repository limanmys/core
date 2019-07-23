@extends('layouts.app')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
            <li class="breadcrumb-item" aria-current="page"><a href="{{route('widgets')}}">{{__("Widgetlar")}}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{__("Widget Ekle")}}</li>
        </ol>
    </nav>
    <h4>{{__("Sunucu")}}</h4>
    @include('l.errors')
    <form action="{{route('widget_add')}}" method="POST">
        <select class="form-control" onchange="getExtensions()" id="server_id" required name="server_id">
            @foreach(servers() as $server)
                <option value="{{$server->id}}">{{$server->name}}</option>
            @endforeach
        </select><br>
        <h4>{{__("Eklenti")}}</h4>
        <select class="form-control" id="extension_id" disabled onchange="getWidgets()" required name="extension_id"></select><br>
        <h4>{{__("Widget")}}</h4>
        <select class="form-control" id="widget_name" disabled="" required name="widget_name"></select><br>
        @csrf
        <button class="btn btn-success" type="submit">{{__("Widget Ekle")}}</button>
    </form>
    <script>
        function getExtensions(){
            let form = new FormData();
            let element = $("#extension_id");
            element.html('');
            element.attr('disabled','true');
            form.append('server_id',$("#server_id").val());
            request('{{route('widget_get_extensions')}}',form,function(response){
                let json = JSON.parse(response);
                for(let k in json) {
                    element.append('<option value="'+ k+ '">' + json[k] + '</option>');
                }
                if(Object.keys(json).length > 0){
                    getWidgets();
                    element.removeAttr('disabled');
                }else{
                    $("#widget_name").html('').addAttr('disabled','');
                }
            });
        }

        function getWidgets(){
            $("#widget_name").html('');
            let form = new FormData();
            form.append('extension_id',$("#extension_id").val());
            request('{{route('widget_list')}}',form,function(response){
                let json = JSON.parse(response);
                let element = $("#widget_name");
                element.html('');
                for(let k in json) {
                    element.append('<option value="'+ json[k]["target"] + ':' + json[k]["name"] + ':' + json[k]["type"] + ':' + json[k]["icon"] +'">' + json[k]["name"] + '</option>');
                }
                element.removeAttr('disabled');
            });
        }

        window.addEventListener('load', function () {
            getExtensions();
        })
    </script>
@endsection
