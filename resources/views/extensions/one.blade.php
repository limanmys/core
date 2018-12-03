@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h2>{{$extension->name}} Ayarları</h2>
    </div>
    <div class="row">
        <div class="col-3">
            <div class="list-group">
                @each('extensions.__system__.dropdown',$files,'file')
            </div>
        </div>
        <div class="col-9">
            <div class="form-group">
                <textarea class="form-control" id="exampleFormControlTextarea1" rows="25"></textarea>
            </div>
        </div>
    </div>

    <script>
        function details() {
            $('.list-group-item').removeClass('active');$("#" + id).addClass('active')
        }


    </script>
@endsection