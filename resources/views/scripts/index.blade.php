@extends('layouts.app')

@section('content_header')
    <h1>{{__("Betikler")}}</h1>
@stop

@section('content')

    @include('l.modal-button',[
        "class" => "btn-primary",
        "target_id" => "scriptUpload",
        "text" => "Yükle"
    ])
    @include('l.modal-button',[
        "class" => "btn-secondary",
        "target_id" => "scriptExport",
        "text" => "Indir"
    ])<br><br>

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
            "Betik Secin:script_id" => objectToArray($scripts,"name", "_id")
        ],
        "submit_text" => "İndir"
    ])

    @include('l.table',[
        "value" => $scripts,
        "title" => [
            "Betik Adı" , "Açıklama" , "Tipi" , "*hidden*"
        ],
        "display" => [
            "name" , "description", "extensions" , "_id:script_id"
        ],
        "menu" => [
            "Sil" => [
                "target" => "delete",
                "icon" => "delete"
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
       "submit_text" => "Sunucuyu Sil"
    ])
    <script>
        function downloadFile(form) {
            window.location.assign('/indir/betik/' + form.getElementsByTagName('select')[0].value);
            return false;
        }
    </script>
@endsection