@extends('layouts.app')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
            <li class="breadcrumb-item"><a href="{{route('extensions_settings')}}">{{__("Eklenti Yönetimi")}}</a></li>
            <li class="breadcrumb-item"><a href="{{route('extension_one',["extension_id" => extension()->_id])}}">{{ extension()->name }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ request('page_name') }}</li>
        </ol>
    </nav>
    <script src="{{asset('js/editor/loader.js')}}"></script>
    <button class="btn btn-success" onclick="history.back()">{{__("Geri Dön")}}</button>
    <button class="btn btn-danger" onclick="save()">{{__("Kaydet")}}</button><br><br>
    <div id="codeEditor" style="width: 100%;height: 50em">
    </div>
    <div id="code" style="display: none;">{{$file}}</div>
    <script>
        require.config({ paths: { 'vs': '/js/editor' }});
        require(['vs/editor/editor.main'], function() {
            window.editor = monaco.editor.create(document.getElementById('codeEditor'), {
                value: decodeHTMLEntities(document.getElementById('code').innerHTML),
                language: 'php'
            });
        });
        function decodeHTMLEntities(text) {
            var entities = [
                ['amp', '&'],
                ['apos', '\''],
                ['#x27', '\''],
                ['#x2F', '/'],
                ['#39', '\''],
                ['#47', '/'],
                ['lt', '<'],
                ['gt', '>'],
                ['nbsp', ' '],
                ['quot', '"']
            ];

            for (var i = 0, max = entities.length; i < max; ++i)
                text = text.replace(new RegExp('&'+entities[i][0]+';', 'g'), entities[i][1]);

            return text;
        }

        function save(){
            Swal.fire({
                position: 'center',
                type: 'info',
                title: '{{__("Kaydediliyor...")}}',
                showConfirmButton: false,
            });
            let code = JSON.stringify(window.editor.getValue());
            let data = new FormData();
            data.append('code',code);
            data.append('page','{{request('page_name')}}');
            data.append('extension_id','{{extension()->_id}}');
            request('{{route('extension_code_update')}}',data,function(response){
                Swal.fire({
                    position: 'center',
                    type: 'success',
                    title: "{{__("Başarıyla kaydedildi")}}",
                    showConfirmButton: false,
                    timer: 1500
                });
            }, function(response){
                let error = JSON.parse(response);
                Swal.fire({
                    type: 'error',
                    title: error.message,
                    timer : 2000
                });
            });
        }
    </script>
@endsection