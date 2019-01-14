@extends('layouts.app')

@section('content')
    <script src="{{asset('/js/treeview.min.js')}}"></script>
    <link rel="stylesheet" href="{{asset('/css/tree.css')}}">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h2>{{$extension->name}} Eklentisi Ayarları</h2>
    </div>
    <button class="btn btn-success" onclick="history.back()">{{__("Geri Dön")}}</button>
    <div class="row">
        <div class="col-3">
            <div id="tree"></div>
        </div>
        <div class="col-9">
            <div class="form-group">
                <textarea aria-label="textarea" class="form-control" id="exampleFormControlTextarea1" rows="25"></textarea>
            </div>
        </div>
    </div>
    <script>
        let tree = new TreeView([
            @include("__system__.folder",$files)
        ], 'tree');
        tree.on('select',function(e){
            console.log(e);
        });
    </script>
@endsection