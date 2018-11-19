@extends('layouts.app')

@section('content')
    <button class="btn btn-success" onclick="location.href = '/sunucular/';">Geri Don</button>
    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#editModal">
        Düzenle
    </button>

    <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#commandModal">
        Komut Çalıştır
    </button><br><br>
    <button type="button" class="btn btn-secondary btn-lg" data-toggle="modal" data-target="#addService">
        Servisleri Düzenle
    </button><br><br>
    <h3>{{$server->name}}</h3><br>

    <pre>
        @isset($stats)
            {{$stats}}
        @endisset
    </pre>

    <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#deleteModal">
        Sunucu Sil
    </button>
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Sunucu Sil</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <h2><b>{{$server->name }}</b></h2> isimli sunucuyu silmek istediğinize emin misiniz? Bu işlem geri alınamayacaktır.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-danger" onclick="deleteServer('{{$server->id}}')">Sunucu Sil</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="commandModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Komut Çalıştır</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <textarea class="form-control" id="run_command" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <textarea class="form-control" id="commandOutput" rows="3" readonly></textarea>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-warning" onclick="runCommand('{{$server->id}}')">Çalıştır</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="addService" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Servisleri Düzenle</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <table class="table">
                        <thead>
                        <tr>
                            <th scope="col"></th>
                            <th scope="col">Adı</th>
                            <th scope="col">Durumu</th>
                        </tr>
                        </thead>
                        <tbody>
                    @foreach($features as $feature)
                            <tr>
                                <th scope="row">{{$loop->index + 1}}</th>
                                <td>{{$feature->name}}</td>
                                <td>
                                    @if($server_features->where('_id',$feature->_id)->count() > 0)
                                        <button type="button" class="btn btn-danger">Devre Dışı Bırak</button>
                                    @else
                                        <button type="button" class="btn btn-success">Servisi Ekle</button>
                                    @endif
                                </td>
                            </tr>
                    @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-warning" onclick="runCommand('{{$server->id}}')">Çalıştır</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        $("#commandOutput").fadeOut();
        function deleteServer(id) {
            $.ajax({
                url : "{{ route('server_remove') }}",
                type : "POST",
                data :{
                    id : id
                },
                success : function (data) {
                    if(data["result"] === 200){
                        window.location.replace("{{route('servers')}}");
                    }else{
                        alert("Hata");
                    }
                }
            });
        }
        function runCommand(id) {
            var command = $("#run_command").val();
            $.ajax({
                url : "{{ route('server_run') }}",
                type : "POST",
                data :{
                    server_id : id,
                    command : command
                },
                success : function (data) {
                    if(data["result"] === 200){
                        $("#commandOutput").fadeIn().html(data["data"]);
                    }else{
                        alert("Hata");
                    }
                }
            });
        }
    </script>
@endsection