@extends('layouts.app')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{__("Betikler")}}</li>
        </ol>
    </nav>
    @include('l.modal-button',[
        "class" => "btn-primary",
        "target_id" => "scriptUpload",
        "text" => "Yükle"
    ])<br><br>

    @include('l.errors')    
    @include('l.modal',[
        "id"=>"scriptUpload",
        "title" => "Betik Yükle",
        "url" => route('script_upload'),
        "next" => "reload",
        "inputs" => [
            "Lütfen Betik Dosyasını(.lmns) Seçiniz" => "script:file",
        ],
        "submit_text" => "Yükle"
    ])

    @include('l.modal',[
        "id"=>"scriptExport",
        "onsubmit" => "downloadFile",
        "title" => "Betik İndir",
        "next" => "",
        "inputs" => [
            "Betik Secin:script_id" => objectToArray($scripts,"name", "id")
        ],
        "submit_text" => "İndir"
    ])

    @include('l.table',[
        "value" => $scripts,
        "title" => [
            "Betik Adı" , "Açıklama" , "Tipi" , "Kodu", "*hidden*", "Son Güncelleme"
        ],
        "display" => [
            "name" , "description", "extensions" , "unique_code", "id:script_id" , "updated_at"
        ],
        "menu" => [
            "İndir" => [
                "target" => "scriptExport",
                "icon" => "fa-download"
            ],
            "Sil" => [
                "target" => "delete",
                "icon" => "fa-trash"
            ]
        ]
    ])

    @include('l.modal',[
       "id"=>"delete",
       "title" =>"Betiği Sil",
       "url" => route('script_delete'),
       "text" => "Betiği silmek istediğinize emin misiniz? Bu işlem geri alınamayacaktır.",
       "next" => "reload",
       "inputs" => [
           "Betik Id:'null'" => "script_id:hidden"
       ],
       "submit_text" => "Betiği Sil"
    ])
    <script>
        function downloadFile(form) {
            window.location.assign('/indir/betik/' + form.getElementsByTagName('select')[0].value);
            return false;
        }
    </script>
@endsection