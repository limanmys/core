@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h2>{{$extension->name}} Ayarları</h2>
    </div>

    <div class="row" >
        <div class="col-3">
            <div class="form-group">
                <div class="card bg-info text-white">
                    <div class="panel-group" id="accordion1">
                          <div class="panel panel-default">
                                 <div class="panel-heading">
                                     <h4 class="panel-title">
                                        <a style="text-decoration: none; color: white" data-toggle="collapse" data-parent="#accordion1" href="#collapseOne">Files<i class="arrow down"></i></a>
                                    </h4>
                                </div>
                        <div id="collapseOne" class="panel-collapse collapse in">
                    <div class="panel-body"> @include('__system__.dropdown',$files)</div>
                </div>
            </div>
          </div>
        </div>
       </div>
      </div>
        <div class="col-9 " >
            <div class="form-group">
                <div class="card hidden" id="scripts">
                    @include('modal-button',[
                 "class" => "btn-success",
                 "target_id" => "ekle",
                 "text" => "Betik Ekle"
                ])
                    <table class="table">
                        <thead>
                        <tr>
                            <th scope="col">Script Adı</th>
                            <th scope="col">Script Description</th>
                            <th scope="col">Betik Sil</th>
                        </tr>
                        </thead>
                        <tbody>
                            @include('__system__.content', ['scripts' => $scripts,'extension'=>$extension])
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        view="";
        function details(event) {
            view=event.text;
            var x = document.getElementById("scripts");
            x.className = "card";
           //$('.list-group-item').removeClass('active');$("#" + id).addClass('active')
            console.log(view);
        }
    </script>
    @include('modal',[
          "id"=>"ekle",
          "title" => "Betik Ekleme",
          "url" => route('server_update'),
          "next" => "reload",
            "selects" => [
            "Alan Ekle:5c0a170f7b57f19953126e37" => [

            ],
            "Ters Alan Ekle:5c0a1c5f7b57f19953126e38" => [

            ],
             "Dns Detayları:5c0a1c5f7b57f19953126e38" => [

            ],
        ],
        "inputs" => [
            "Açıklama" => "aciklama:text",
            "Extension Id:$extension->id" => "extension_id:hidden",
            //"Script Id:$script->id" => "script_id:hidden",
        ],
          "submit_text" => "Ekle"
      ])
@endsection