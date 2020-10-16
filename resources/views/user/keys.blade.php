@extends('layouts.app')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item" aria-current="page"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
            <li class="breadcrumb-item" aria-current="page"><a href="{{route('my_profile')}}">{{__("Profilim")}}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{__("Kişisel Erişim Anahtarları")}}</li>
        </ol>
    </nav>
    @include('errors')
    <div class="row">
        <div class="col-md-3">
            <div class="card card-primary card-outline">
              <div class="card-body box-profile">
                <h3 class="profile-username text-center">{{__("Kişisel Erişim Anahtarları")}}</h3>
                <p class="text-muted text-center">{{__("Size ait Kişisel Erişim Anahtarları'nın listesini görüntüleyebilirsiniz. Mevcut anahtar üzerinde işlem yapmak için sağ tıklayabilirsiniz.")}}</p>
              </div>
            </div>
        </div>
        <div class="col-md-9">
            <div class="card">
                <div class="card-body">
                @include('modal-button',[
                    "class" => "btn-success",
                    "target_id" => "addAccessToken",
                    "text" => "Oluştur"
                ])<br><br>
                    @include('table',[
                        "value" => $access_tokens,
                        "title" => [
                            "Adı", "Token", "İp Adresi / Hostname","*hidden*"
                        ],
                        "display" => [
                            "name" , "token", "ip_range" ,"id:token_id"
                        ],
                        "menu" => [
                            "Sil" => [
                                "target" => "removeAccessToken",
                                "icon" => " context-menu-icon-delete"
                            ]
                        ]
                    ])
                </div>
            </div>
        </div>
    </div>

@include('modal',[
    "id"=>"addAccessToken",
    "title" => "Anahtar Oluştur",
    "url" => route('create_access_token'),
    "next" => "reload",
    "text" => "İp Adresi bölümüne izin vermek istediğiniz bir subnet adresini ya da ip adresini yazarak erişimi kısıtlayabilirsiniz. Örneğin : 192.168.1.0/24. Ayrıca, bu kısıtı kaldırmak için adres yerine -1 yazabilirsiniz.",
    "inputs" => [
        "İsim" => "name:text",
        "İp Adresi / Hostname" => "ip_range:text",
    ],
    "submit_text" => "Anahtar Ekle"
])


@include('modal',[
    "id"=>"removeAccessToken",
    "title" => "Anahtarı Sil",
    "url" => route('revoke_access_token'),
    "next" => "reload",
    "text" => "Veri'yi silmek istediğinize emin misiniz? Bu işlem geri alınamayacaktır.",
    "inputs" => [
        "-:-" => "token_id:hidden"
    ],
    "submit_text" => "Anahtarı Sil"
])

@endsection