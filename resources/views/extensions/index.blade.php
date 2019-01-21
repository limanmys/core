@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h2>Eklenti Yönetimi</h2>
    </div>
    @include('modal-button',[
        "class" => "btn-primary",
        "target_id" => "extensionUpload",
        "text" => "Yükle"
    ])
    @include('modal-button',[
        "class" => "btn-secondary",
        "target_id" => "extensionExport",
        "text" => "Indir"
    ])<br><br>
    <table class="table">
        <thead>
        <tr>
            <th scope="col">Eklenti Adı</th>
        </tr>
        </thead>
        <tbody>
        @foreach($extensions as $extension)
            <tr>
                <td>{{$extension->name}}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    
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
    <?php 
        $input_extensions = [];
        foreach($extensions as $extension){
            $input_extensions[$extension->name] = $extension->_id;
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
<script>
        function downloadFile(form){
            window.location.assign('/indir/eklenti/' + form.getElementsByTagName('select')[0].value);
            loading();
            return false;
        }
</script>
@endsection