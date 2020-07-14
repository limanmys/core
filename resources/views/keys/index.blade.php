@extends('layouts.app')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{__("Kasa")}}</li>
        </ol>
    </nav>
    @include('errors')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{__("Kasa")}}</h3>
        </div>
        <div class="card-body">
            <button type="button" class="btn btn-success" data-toggle="modal" data-target="#add_settings"><i class="fa fa-key "></i> {{__("Anahtar Ekle")}}</button>
            <button type="button" class="btn btn-secondary" onclick="cleanSessions()">{{__("Önbelleği Temizle")}}</button>
            <div class="tab-pane active" id="settings" style="margin-top: 15px;">
                <div class="alert alert-info alert-dismissible">
                    <h5><i class="icon fas fa-info"></i> {{ __('Bilgilendirme!') }}</h5>
                    {{__("Güvenliğiniz için varolan verileriniz gösterilmemektedir.")}}
                </div>
                @include('table',[
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
                            "icon" => " context-menu-icon-edit"
                        ],
                        "Sil" => [
                            "target" => "delete_settings",
                            "icon" => " context-menu-icon-delete"
                        ]
                    ]
                ])
            </div>
        </div>
    </div>

@include('modal',[
        "id"=>"add_settings",
        "title" => "Anahtar Ekle",
        "url" => route('key_add'),
        "next" => "reload",
        "inputs" => [
            "Sunucu:server_id" => $servers,
            "Kullanıcı Adı" => "username:text",
            "Şifre" => "password:password",
        ],
        "submit_text" => "Ekle"
    ])

    @include('modal',[
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

    @include('modal',[
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

   <script>
        function cleanSessions(){
            request('{{route('clean_sessions')}}', new FormData(), function(response){
                showSwal("{{__("Önbellek temizlendi!")}}",'success',2000);
                reload();
            }, function(response){
                var error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            });
        }
   </script>
@endsection