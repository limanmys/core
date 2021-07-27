@extends('layouts.app')

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{__("Bileşenler")}}</li>
    </ol>
</nav>
<div class="row">
    <div class="col-md-3">
        <div class="card card-primary card-outline">
            <div class="card-body box-profile">
                <h3 class="profile-username text-center font-weight-bold">{{__("Bileşenler")}}</h3>
                <p class="text-muted text-center mb-0">{{__("Bu sayfadan mevcut bileşenleri görebilirsiniz. Ayrıca yeni bileşen eklemek için Bileşen Ekle butonunu kullanabilirsiniz.")}}</p>
            </div>
        </div>
    </div>
    <div class="col-md-9">
    <div class="card">
        <div class="card-body">
            <button class="btn btn-success" onclick="window.location.href = '{{route('widget_add_page')}}'">{{__("Bileşen Ekle")}}</button>
                <br><br>
                @include('errors')
                <?php foreach ($widgets as $widget) {
                    $extension = \App\Models\Extension::find(
                        $widget->extension_id
                    );
                    if ($extension) {
                        $widget->extension_name = $extension->display_name;
                    } else {
                        $widget->extension_name = "Eklenti Silinmiş";
                    }
                } ?>
                @include('table',[
                    "value" => $widgets,
                    "title" => [
                        "Sunucu" , "Başlık" , "Eklenti", "*hidden*"
                    ],
                    "display" => [
                        "server_name" , "title" ,"extension_name", "id:widget_id"
                    ],
                    "menu" => [
                        "Sil" => [
                            "target" => "delete",
                            "icon" => " context-menu-icon-delete"
                        ]
                    ]
                ])
            </div>
        </div>
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