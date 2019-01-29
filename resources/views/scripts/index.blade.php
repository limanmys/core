@extends('layouts.app')

@section('content_header')
    <h1>{{__("Betikler")}}</h1>
@stop

@section('content')

    {{--<button type="button" class="btn btn-success" onclick="window.location.href = '{{route('script_add')}}'">--}}
        {{--{{ __("Betik Oluştur") }}--}}
    {{--</button>--}}

    @include('modal-button',[
        "class" => "btn-primary",
        "target_id" => "scriptUpload",
        "text" => "Yükle"
    ])
    @include('modal-button',[
        "class" => "btn-secondary",
        "target_id" => "scriptExport",
        "text" => "Indir"
    ])<br><br>

    @include('modal',[
        "id"=>"scriptUpload",
        "title" => "Betik Yükle",
        "url" => route('script_upload'),
        "next" => "reload",
        "inputs" => [
            "Lütfen Betik Dosyasını(.lmns) Seçiniz" => "script:file",
        ],
        "submit_text" => "Yükle"
    ])
    <?php 
        $input_scripts = [];
        foreach($scripts as $script){
            $input_scripts[$script->name] = $script->_id;
        }
    ?>
    @include('modal',[
        "id"=>"scriptUpload",
        "title" => "Betik Yükle",
        "url" => route('script_upload'),
        "next" => "reload",
        "inputs" => [
            "Lütfen Betik Dosyasını(.lmns) Seçiniz" => "script:file",
        ],
        "submit_text" => "Yükle"
    ])

    @include('modal',[
        "id"=>"scriptExport",
        "onsubmit" => "downloadFile",
        "title" => "Betik İndir",
        "next" => "",
        "inputs" => [
            "Betik Secin:script_id" => $input_scripts
        ],
        "submit_text" => "İndir"
    ])

    <table class="table">
        <thead>
        <tr>
            <th scope="col">Adı</th>
            <th scope="col">Açıklaması</th>
            <th scope="col">Tipi</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($scripts as $script)
            <tr>
                <td>{{$script->name}}</td>
                <td>{{$script->description}}</td>
                <td>{{$script->extensions}}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
<script>
        function downloadFile(form){
            window.location.assign('/indir/betik/' + form.getElementsByTagName('select')[0].value);
            loading();
            return false;
        }
</script>
@endsection