@extends('layouts.app')

@section('content')
    <nav class="row">
        <div class="col-sm-6">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ __('Eklenti Yönetimi') }}</li>
            </ol>
        </div>
        <div class="col-sm-6">
            <div class="float-sm-right">
                <button data-toggle="tooltip" title="Ayarlar" class="btn btn-primary" data-toggle="modal" data-target="#extSettings"><i class="fa fa-cogs"></i></button>
            </div>
        </div>
    </nav>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{__("Eklentiler")}}</h3>
        </div>
        <div class="card-body">
            @include('errors')    

            @include('modal-button',[
                "class" => "btn-primary",
                "target_id" => "extensionUpload",
                "text" => "Yükle"
            ])
            @if(env("EXTENSION_DEVELOPER_MODE"))
                @include('modal-button',[
                    "class" => "btn-secondary",
                    "target_id" => "extensionExport",
                    "text" => "İndir"
                ])
                @include('modal-button',[
                    "class" => "btn-info",
                    "target_id" => "newExtension",
                    "text" => "Yeni"
                ])
            @endif
        
            <br><br>
        
            @include('table',[
                "value" => extensions(),
                "sortable" => true,
                "sortUpdateUrl" => route('update_ext_orders'),
                "afterSortFunction" => 'location.reload',
                "title" => [
                    "Eklenti Adı" , "Versiyon", "Son Güncelleme Tarihi", "*hidden*"
                ],
                "display" => [
                    "name" , "version", "updated_at", "id:extension_id"
                ],
                "menu" => [
                    "Sil" => [
                        "target" => "delete",
                        "icon" => " context-menu-icon-delete"
                    ]
                ],
                "onclick" => env("EXTENSION_DEVELOPER_MODE") ? "details" : ""
            ])
        </div>
    </div>

    @include('modal',[
        "id"=>"extSettings",
        "title" => "Ayarlar",
        "url" => route('save_settings'),
        "next" => "reload",
        "inputs" => [
            "Sol menüde kaç eklenti gözüksün?" => "ext_count:number",
        ],
        "submit_text" => "Kaydet"
    ])

    @include('modal',[
        "id"=>"extensionUpload",
        "title" => "Eklenti Yükle",
        "url" => route('extension_upload'),
        "next" => "reload",
        "inputs" => [
            "Lütfen Eklenti Dosyasını(.lmne) Seçiniz" => "extension:file",
        ],
        "submit_text" => "Yükle"
    ])
    @if(env("EXTENSION_DEVELOPER_MODE"))
    <?php
        $input_extensions = [];
        foreach(extensions() as $extension){
            $input_extensions[$extension->name] = $extension->id;
        }
    ?>

    @include('modal',[
        "id"=>"extensionExport",
        "onsubmit" => "downloadFile",
        "title" => "Eklenti İndir",
        "next" => "",
        "inputs" => [
            "Eklenti Secin:extension_id" => $input_extensions
        ],
        "submit_text" => "İndir"
    ])


    @include('modal',[
        "id"=>"newExtension",
        "url" => route('extension_new'),
        "title" => "Yeni Eklenti Oluştur",
        "inputs" => [
            "Eklenti Adı" => "name:text"
        ],
        "submit_text" => "Oluştur"
    ])
@endif
    @include('modal',[
       "id"=>"delete",
       "title" =>"Eklentiyi Sil",
       "url" => route('extension_remove'),
       "text" => "Eklentiyi silmek istediğinize emin misiniz? Bu işlem geri alınamayacaktır.",
       "next" => "reload",
       "inputs" => [
           "Extension Id:'null'" => "extension_id:hidden"
       ],
       "submit_text" => "Eklentiyi Sil"
   ])

<script>
        $('input[name=ext_count]').val('{{env('NAV_EXTENSION_HIDE_COUNT', 10)}}');
        function downloadFile(form){
            window.location.assign('/indir/eklenti/' + form.getElementsByTagName('select')[0].value);
            setTimeout(function(){
              Swal.close();
            }, 1000);
            return false;
        }
        $("#extensionUpload input").on('change',function(){
            if(this.files[0].size / 1024 / 1024 > 100){
                $(this).val('');
                Swal.fire({
                    position: 'center',
                    type: 'error',
                    title: '{{__("Maksimum eklenti boyutunu (100MB) aştınız!")}}',
                    showConfirmButton: false,
                });
            }
        });
        
        function downloadDebFile(form){
            window.location.assign('/indir/eklenti_deb/' + form.getElementsByTagName('select')[0].value);
            setTimeout(function(){
              Swal.close();
            }, 1000);
            return false;
        }

        @if(env("EXTENSION_DEVELOPER_MODE"))
        function details(element){
            let extension_id = element.querySelector('#extension_id').innerHTML;
            window.location.href = "/eklentiler/" + extension_id
        }
        @endif
</script>
@endsection
