@extends('layouts.app')

@section('content')
    <script src="{{asset('js/file_upload/jquery.ui.widget.js')}}"></script>
    <script src="{{asset('js/file_upload/jquery.iframe-transport.js')}}"></script>
    <script src="{{asset('js/file_upload/jquery.fileupload.js')}}"></script>
    <button type="button" class="btn btn-success" onclick="window.location.href = '{{route('script_add')}}'">
        Betik Oluştur
    </button>
    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#scriptUpload">
        Betik Yükle
    </button><br><br>
    <h1 class="h2">Betikler</h1>
    <table class="table">
        <thead>
        <tr>
            <th scope="col">Betik Adı</th>
            <th scope="col"></th>
        </tr>
        </thead>
        <tbody>
        @foreach ($scripts as $script)
            <tr class="highlight" onclick="window.location.href = '{{route('script_one',$script->_id)}}'">
                <td>{{$script->name}}</td>
                <td>
                    <button class="btn btn-primary">Düzenle</button>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <!-- Modal -->
    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title" id="exampleModalLabel">Sunucu Ekle</h1>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form>
                    <div class="modal-body">
                        <div class="form-group">
                            <h3>Adı</h3>
                            <input id="add_name" type="text" class="form-control" placeholder="Sunucu kısa adı" data-validation="length" data-validation-length="min0">
                        </div>
                        <div class="form-group">
                            <h3>İp Adresi</h3>
                            <input id="add_ip" type="text" class="form-control" placeholder="Sunucu Ipv4 Adresi"  data-validation="length" data-validation-length="min0">
                        </div>
                        <div class="form-group">
                            <h3>Bağlantı Portu</h3>
                            <input id="add_port" type="text" class="form-control" placeholder="Bağlantı Portu" value="22">
                        </div>
                        <div class="form-group">
                            <h3>Anahtar Kullanıcı Adı</h3>
                            <input id="add_username" type="text" class="form-control" placeholder="Anahtar Kullanıcı Adı">
                        </div>
                        <div class="form-group">
                            <h3>Anahtar Parola</h3>
                            <input id="add_password" type="password" class="form-control" placeholder="Anahtar Parola">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                        <button type="button" class="btn btn-success" onclick="add()">Ekle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

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