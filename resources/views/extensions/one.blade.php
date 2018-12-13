@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h2>{{$extension->name}} Ayarları</h2>
    </div>

    <div class="row">
        <div class="col-3">
        <li class="dropdown">
            <a href="#" data-toggle="dropdown">Files<i class="icon-arrow"></i></a>
            <ul class="dropdown-menu">
                <li>@each('__system__.dropdown',$files,'file')</li>
            </ul>
        </li>
        </div>
        <div class="col-9">
            <div class="form-group">
                <div class="card">
                    <buttton id="ekle"class="btn btn-success">ekle</buttton>
                    <table class="table">
                        <thead>
                        <tr>
                            <th scope="col">Script Adı</th>
                            <th scope="col">Script Description</th>
                        </tr>
                        </thead>
                        <tbody>
                        @each('__system__.content',$scripts,'scripts')
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function details() {

           //$('.list-group-item').removeClass('active');$("#" + id).addClass('active')
        }
    </script>
    @include('modal',[
          "id"=>"ekle",
          "title" => "Betik Ekleme",
          "url" => route('server_update'),
          "next" => "reload",

          "submit_text" => "Düzenle"
      ])
@endsection