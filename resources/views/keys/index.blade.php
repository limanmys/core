@extends('layouts.app')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{__("Anahtarlar")}}</li>
        </ol>
    </nav>
    <div class="nav-tabs-custom">
        <ul class="nav nav-tabs">
            <li class="active"><a href="#keys" data-toggle="tab" aria-expanded="true">{{__("Anahtarlar")}}</a></li>
            <li><a href="#settings" data-toggle="tab" aria-expanded="false">{{__("Eklenti Ayarları")}}</a></li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane active" id="keys">
                @include('l.modal-button',[
                    "text" => "Anahtar Ekle",
                    "class" => "btn-success",
                    "target_id" => "add_key"
                ])<br><br>

                @include('l.table',[
                    "value" => $keys,
                    "title" => [
                        "Sunucu" , "Kullanıcı" , "*hidden*" , "*hidden*"
                    ],
                    "display" => [
                        "server_name" , "username", "id:key_id" , "server_id:server_id"
                    ],
                    "menu" => [
                        "Sil" => [
                            "target" => "delete",
                            "icon" => "fa-trash"
                        ]
                    ]
                ])
            </div>
            <div class="tab-pane" id="settings">
                @include('l.table',[
                "value" => $settings,
                    "title" => [
                        "Ayar Adı" , "Sunucu" , "Eklenti" , "*hidden*"
                    ],
                    "display" => [
                        "name" , "server_name", "extension_name" , "id:settings_id"
                    ],
                ])
            </div>
        </div>
    </div>


    @include('l.modal',[
        "id"=>"add_key",
        "title" => "Anahtar Ekle",
        "url" => route('key_add'),
        "next" => "reload",
        "inputs" => [
            "Sunucu Seçin:server_id" => $servers,
            "Kullanıcı Adı" => "username:text",
            "Parola" => "password:password"
        ],
        "submit_text" => "Ekle"
    ])

    @include('l.modal',[
       "id"=>"delete",
       "title" =>"Anahtarı Sil",
       "url" => route('key_delete'),
       "text" => "Anahtarı silmek istediğinize emin misiniz? Bu işlem geri alınamayacaktır.",
       "next" => "reload",
       "inputs" => [
           "Key Id:'null'" => "key_id:hidden"
       ],
       "submit_text" => "Sunucuyu Sil"
   ])
@endsection