@extends('layouts.app')

@section('content')
    @include('title',[
        "title" => "Betikler"
    ])
    <button type="button" class="btn btn-success" onclick="window.location.href = '{{route('script_add')}}'">
        {{ __("Betik Oluştur") }}
    </button>

    @include('modal-button',[
        "class" => "btn-primary",
        "target_id" => "scriptUpload",
        "text" => "Betik Yükle"
    ])<br><br>

    @include('modal',[
        "id"=>"scriptUpload",
        "title" => "Betik Yükle",
        "url" => route('script_upload'),
        "inputs" => [
            "Lütfen Betik Dosyasını(.lmn) Seçiniz" => "script:file",
        ],
        "submit_text" => "Yükle"
    ])

    <table class="table">
        <thead>
        <tr>
            <th scope="col">{{ __("Betik Adı") }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($scripts as $script)
            <tr class="highlight" onclick="window.location.href = '{{route('script_one',$script->_id)}}'">
                <td>{{$script->name}}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

@endsection