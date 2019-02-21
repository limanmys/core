@extends('layouts.app')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active" aria-current="page">{{__("Ana Sayfa")}}</li>
        </ol>
    </nav>

    <section class="content">
        @foreach($widgets as $widget)
            <div class="col-md-3 col-sm-6 col-xs-12">
                <div class="info-box">
                    <span class="info-box-icon bg-aqua" style="padding-top:20px"><i class="fa fa-user-o"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">{{$widget->server_name . " " . __("Sunucusu")}}</span>
                        <span class="info-box-number" id="{{$widget->_id}}">{{$widget->title}}</span>
                        <span class="float-right limanWidget" id="{{$widget->_id}}" style="font-size: 20px"></span>
                    </div>
                </div>
            </div>
        @endforeach
    </section>

    <script>
        setInterval(function(){
            $(".limanWidget").each(function(){
                let element = $(this);
                let form = new FormData();
                form.append('widget_id',element.attr('id'));
                request('{{route('widget_one')}}', form, function(response){
                    element.html(response);
                });
            });
        },2000);
    </script>
@stop