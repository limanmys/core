@extends('layouts.app')

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{__("Bileşenler")}}</li>
    </ol>
</nav>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{__("Bileşenler")}}</h3>
    </div>
    <div class="card-body">
        <button class="btn btn-success" onclick="window.location.href = '{{route('widget_add_page')}}'">{{__("Bileşen Ekle")}}</button>
        <br><br>
        @include('errors')
        <?php
            foreach($widgets as $widget){
                $extension = \App\Extension::find($widget->extension_id);
                if($extension){
                    $widget->extension_name = $extension->name;
                }else{
                    $widget->extension_name = "Eklenti Silinmiş";
                }
            }
        ?>
        @include('table',[
            "value" => $widgets,
            "title" => [
                "Sunucu" , "Başlık" , "Eklenti", "*hidden*"
            ],
            "display" => [
                "server_name" , "title" ,"extension_name", "id:widget_id"
            ],
            "menu" => [
                "Düzenle" => [
                    "target" => "edit",
                    "icon" => " context-menu-icon-edit"
                ],
                "Sil" => [
                    "target" => "delete",
                    "icon" => " context-menu-icon-delete"
                ]
            ]
        ])
    </div>
</div>

@include('modal',[
    "id"=>"add_server",
    "title" => "Bileşen Ekle",
    "url" => route('widget_add'),
    "next" => "addToTable",
    "inputs" => [
        "Sunucu Seçin:server_id" => objectToArray(servers(),"name","id"),
        "Eklenti Seçin:extension_id" => objectToArray(extensions(),"name","id"),
        "Başlık" => "title:text",
        "type:-" => "type:hidden",
        "display_type:-" => "display_type:hidden"
    ],
    "submit_text" => "Ekle"
])

@include('modal',[
    "id"=>"edit",
    "title" => "Bileşen Düzenle",
    "url" => route('widget_update'),
    "next" => "updateTable",
    "inputs" => [
        "Sunucu Seçin:server_id" => objectToArray(servers(),"name","id"),
        "Eklenti Seçin:extension_id" => objectToArray(extensions(),"name","id"),
        "Başlık" => "title:text",
        "type:-" => "type:hidden",
        "display_type:-" => "display_type:hidden",
        "widget_id:widget_id" => "widget_id:hidden"
    ],
    "submit_text" => "Düzenle"
])

@include('modal',[
    "id"=>"delete",
    "title" =>"Bileşeni Sil",
    "url" => route('widget_remove'),
    "text" => "Bileşeni silmek istediğinize emin misiniz? Bu işlem geri alınamayacaktır.",
    "next" => "reload",
    "inputs" => [
        "Bileşen Id:'null'" => "widget_id:hidden"
    ],
    "submit_text" => "Bileşeni Sil"
])
@endsection