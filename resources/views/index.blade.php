@extends('layouts.app')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active" aria-current="page">{{__("Ana Sayfa")}}</li>
        </ol>
    </nav>
    <section class="content">
        @if($widgets->count())
            @foreach($widgets as $widget)
                <div class="col-md-3 col-sm-4 col-xs-12">
                    <div class="info-box">
                        <span class="info-box-icon bg-aqua" style="padding:20px"><i class="fa fa-{{$widget->text}}"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">{{$widget->server_name . " " . __("Sunucusu")}}</span>
                            <span class="info-box-number" id="{{$widget->id}}">{{$widget->title}}</span>
                            <span class="float-right limanWidget" id="{{$widget->id}}" style="font-size: 20px">{{__("Yükleniyor...")}}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            {{__("Liman Sistem Yönetimi'ne Hoşgeldiniz!")}}
        @endif
    </section>

    <script>
        setInterval(function(){
            $(".limanWidget").each(function(){
                let element = $(this);
                let form = new FormData();
                form.append('widget_id',element.attr('id'));
                request('{{route('widget_one')}}', form, function(response){
                    let json = JSON.parse(response);
                    element.html(json["message"]);
                });
            });
        },2000);
    </script>
@stop