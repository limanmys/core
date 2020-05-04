@extends('layouts.app')

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
        <li class="breadcrumb-item" aria-current="page"><a href="{{route('widgets')}}">{{__("Bileşenler")}}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{__("Bileşen Ekle")}}</li>
    </ol>
</nav>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{__("Bileşen Ekle")}}</h3>
    </div>
    <div class="card-body">
        @include('errors')
        <form action="{{route('widget_add')}}" onsubmit="return widget_control(this)" method="POST">
            <h4>{{__("Sunucu")}}</h4>
            <select class="form-control" onchange="getExtensions()" id="server_id" required name="server_id">
                @foreach(servers() as $server)
                    <option value="{{$server->id}}">{{$server->name}}</option>
                @endforeach
            </select><br>
            <h4>{{__("Eklenti")}}</h4>
            <select class="form-control" id="extension_id" disabled onchange="getWidgets()" required name="extension_id"></select><br>
            <h4>{{__("Bileşen")}}</h4>
            <select class="form-control" id="widget_name" disabled="" name="widget_name"></select><br>
            @csrf
            <button class="btn btn-success" type="submit">{{__("Bileşen Ekle")}}</button>
        </form>
    </div>
</div>
<script>
    function getExtensions(){
        let form = new FormData();
        let element = $("#extension_id");
        element.text('');
        element.attr('disabled','true');
        form.append('server_id',$("#server_id").val());
        request('{{route('widget_get_extensions')}}',form,function(response){
            let json = JSON.parse(response);
            for(let k in json) {
                element.append('<option value="'+ k+ '">' + fixer(json[k]) + '</option>');
            }
            if(Object.keys(json).length > 0){
                getWidgets();
                element.removeAttr('disabled');
            }else{
                $("#widget_name").text('').addAttr('disabled','');
            }
        }, function(response){
            let error = JSON.parse(response);
            showSwal(error.message,'error',2000);
        });
    }

    function getWidgets(){
        $("#widget_name").text('');
        let form = new FormData();
        form.append('extension_id',$("#extension_id").val());
        request('{{route('widget_list')}}',form,function(response){
            let json = JSON.parse(response);
            let element = $("#widget_name");
            element.text('');
            for(let k in json) {
                console.log(json[k]);
                element.append('<option value="'+ fixer(json[k]["target"]) + ':' + fixer(json[k]["name"]) + ':' + fixer(json[k]["type"]) + ':' + fixer(json[k]["icon"]) +'">' + fixer(json[k]["name"]) + '</option>');
            }
            element.removeAttr('disabled');
        }, function(response){
            let error = JSON.parse(response);
            showSwal(error.message,'error',2000);
        });
    }

    function widget_control(element){
        if(!$(element).find('select[name=widget_name]').val()){
            showSwal("{{_("Önce bir widget seçmelisiniz!")}}",'error',2000);
            return false;
        }
        return true;
    }

    window.addEventListener('load', function () {
        getExtensions();
    })
</script>
@endsection
