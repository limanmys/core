@extends('layouts.app')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ __('Eklenti Yönetimi') }}</li>
        </ol>
    </nav>
    @include('l.modal-button',[
        "class" => "btn-primary",
        "target_id" => "extensionUpload",
        "text" => "Yükle"
    ])
    @include('l.modal-button',[
        "class" => "btn-secondary",
        "target_id" => "extensionExport",
        "text" => "Indir"
    ])<br><br>

    @include('l.table',[
        "value" => extensions(),
        "title" => [
            "Eklenti Adı" , "*hidden*"
        ],
        "display" => [
            "name" , "_id:extension_id"
        ],
        "menu" => [
            "Sil" => [
                "target" => "delete",
                "icon" => "delete"
            ]
        ],
        "onclick" => "details"
    ])

    @include('l.modal',[
        "id"=>"extensionUpload",
        "title" => "Eklenti Yükle",
        "url" => route('extension_upload'),
        "next" => "reload",
        "inputs" => [
            "Lütfen Eklenti Dosyasını(.lmne) Seçiniz" => "extension:file",
        ],
        "submit_text" => "Yükle"
    ])
    <?php 
        $input_extensions = [];
        foreach(extensions() as $extension){
            $input_extensions[$extension->name] = $extension->_id;
        }
    ?>
    @include('l.modal',[
        "id"=>"extensionExport",
        "onsubmit" => "downloadFile",
        "title" => "Eklenti İndir",
        "next" => "",
        "inputs" => [
            "Eklenti Secin:extension_id" => $input_extensions
        ],
        "submit_text" => "İndir"
    ])

    @include('l.modal',[
       "id"=>"delete",
       "title" =>"Eklentiyi Sil",
       "url" => route('extension_remove'),
       "text" => "Eklentiyi silmek istediğinize emin misiniz? Bu işlem geri alınamayacaktır.",
       "next" => "reload",
       "inputs" => [
           "Extension Id:'null'" => "extension_id:hidden"
       ],
       "submit_text" => "Sunucuyu Sil"
   ])

<script>
        function downloadFile(form){
            window.location.assign('/indir/eklenti/' + form.getElementsByTagName('select')[0].value);
            // loading();
            return false;
        }
</script>
@endsection