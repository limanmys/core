@extends('layouts.app')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{__("Kasa")}}</li>
        </ol>
    </nav>
    @include('l.errors')
    

    <div class="nav-tabs-custom">
        <ul class="nav nav-tabs">
            <li class="active"><a href="#settings" data-toggle="tab" aria-expanded="false">{{__("Kasa")}}</a></li>
        </ul>
        <div class="tab-content">
            <button type="button" class="btn btn-success" data-toggle="modal" data-target="#add_settings"><i class="fa fa-key "></i> {{__("Anahtar Ekle")}}</button>
            <div class="tab-pane active" id="settings">
                <h4>{{__("Güvenliğiniz için varolan verileriniz gösterilmemektedir.")}}</h4>
                @include('l.table',[
                "value" => $settings,
                    "title" => [
                        "Ayar Adı" , "Sunucu" , "*hidden*"
                    ],
                    "display" => [
                        "name" , "server_name", "id:setting_id"
                    ],
                    "menu" => [
                        "Güncelle" => [
                            "target" => "update_settings",
                            "icon" => "fa-edit"
                        ],
                        "Sil" => [
                            "target" => "delete_settings",
                            "icon" => "fa-trash"
                        ]
                    ]
                ])
            </div>
        </div>
    </div>


    @include('l.modal',[
        "id"=>"add_settings",
        "title" => "Anahtar Ekle",
        "url" => route('key_add'),
        "next" => "reload",
        "inputs" => [
            "Sunucu:server_id" => objectToArray(servers(),"name", "id"),
            "Kullanıcı Adı" => "username:text",
            "Şifre" => "password:password",
        ],
        "submit_text" => "Ekle"
    ])

    @include('l.modal',[
        "id"=>"update_settings",
        "title" => "Ayarı Güncelle",
        "url" => route('user_setting_update'),
        "next" => "reload",
        "inputs" => [
            "Yeni Değer" => "new_value:password",
            "-:-" => "setting_id:hidden",
        ],
        "submit_text" => "Ekle"
    ])

    @include('l.modal',[
       "id"=>"delete_settings",
       "title" =>"Ayarı Sil",
       "url" => route('user_setting_remove'),
       "text" => "Ayarı silmek istediğinize emin misiniz? Bu işlem geri alınamayacaktır.",
       "next" => "reload",
       "inputs" => [
           "Setting Id:'null'" => "setting_id:hidden"
       ],
       "submit_text" => "Sunucuyu Sil"
   ])
@endsection