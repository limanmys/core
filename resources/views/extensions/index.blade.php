@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h2>Eklenti Yönetimi</h2>
    </div>
    <button type="button" class="btn btn-success" data-toggle="modal" data-target="#extensionModal">
        Eklenti Yükle
    </button><br><br>
    <table class="table">
        <thead>
        <tr>
            <th scope="col">Eklenti Adı</th>
        </tr>
        </thead>
        <tbody>
        @foreach($features as $feature)
            <tr class="highlight" onclick="extensionModal('{{$feature->name}}','{{$feature->publisher}}','{{$feature->support}}','{{$feature->status}}')">
                <td>{{$feature->name}}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <div class="modal fade" id="extensionModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title" id="exampleModalLabel"></h1>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form>
                    <div class="modal-body">
                        <h4 id="creator"></h4>
                        <h4>Destek : <a href="" id="supportEmail"></a></h4>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                        <button type="button" class="btn btn-danger" id="disableButton">Devre Dışı Bırak</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        function extensionModal(feature, publisher, email, status) {
            $(".modal-title").html(feature + " eklentisi");
            $("#creator").html("Yapımcı : " + publisher);
            if(status === "0" || status === "2"){
                $("#disableButton").attr("disabled","disabled");
            }
            $("#supportEmail").html(email).attr("href", "mailto:" + email);
            $("#extensionModal").modal('show');
        }
    </script>
@endsection