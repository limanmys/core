@extends('layouts.app')

@section('content')
    <script src="{{asset('js/file_upload/jquery.ui.widget.js')}}"></script>
    <script src="{{asset('js/file_upload/jquery.iframe-transport.js')}}"></script>
    <script src="{{asset('js/file_upload/jquery.fileupload.js')}}"></script>
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">{{ __("Betikler") }}</h1>
    </div>
    <button type="button" class="btn btn-success" onclick="window.location.href = '{{route('script_add')}}'">
        {{ __("Betik Oluştur") }}
    </button>
    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#scriptUpload">
        {{ __("Betik Yükle") }}
    </button><br><br>
    <table class="table">
        <thead>
        <tr>
            <th scope="col">{{ __("Betik Adı") }}</th>
            {{--<th scope="col"></th>--}}
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
    @include('modal',[
                  "id"=>"exampleModal",
                  "title" => __("Sunucu Ekle"),
                  "url" => "/user/add",
                  "inputs" => [
                      __("Adı") => "name:text",
                      __("İp Adresi") => "ip:text",
                      __("Parola") => "password:password",
                      __("Bağlantı Portu") => "port:text",
                      __("Anahtar Kullanıcı Adı") => "username:text",
                      __("Anahtar Parola") => "anahtar_parola:password"
                  ],
                  "submit_text" => "Ekle"
              ])
    <div class="modal fade" id="scriptUpload" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title" id="exampleModalLabel">Betik Yükle</h1>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form>
                    <div class="modal-body">
                        <div class="form-group">
                            <h5><label for="exampleFormControlFile1">Lütfen Betik Dosyasını(.lmn) Seçiniz</label></h5>
                            <input id="fileupload" class="form-control-file" type="file" name="script" data-url="{{route('script_upload')}}">
                        </div>
                        <div id="progress">
                            <div class="bar" style="width: 0%;"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                        <button type="button" class="btn btn-success" id="uploadButton">Yükle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        $(function () {
            $('#fileupload').fileupload({
                dataType: 'json',
                add: function (e, data) {
                    $("#uploadButton").click(function () {
                        data.submit();
                    });
                },
                progressall: function (e, data) {
                    var progress = parseInt(data.loaded / data.total * 100, 10);
                    $('#progress .bar').css(
                        'width',
                        progress + '%'
                    );
                },
                done: function (e, data) {
                    if(data.result["result"] === 200){
                        location.reload();
                    }else{
                        alert('hata');
                    }
                }
            });
        });
    </script>


@endsection