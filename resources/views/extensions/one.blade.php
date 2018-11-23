@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h2>{{$extension->name}} Ayarları</h2>
    </div>
    <div class="row">
        <div class="col-3">
            <div class="list-group">
                <a href="#" class="list-group-item list-group-item-action" onclick="$(this).addClass('active')">Dapibus ac facilisis in</a>
            </div>
        </div>
        <div class="col-9">
            <div class="form-group">
                <textarea class="form-control" id="exampleFormControlTextarea1" rows="25"></textarea>
            </div>
        </div>
    </div>
    <div class="form-group">
        <ul>
            @foreach($files as $file)
                @if(!is_array($file))
                    <li>{{$file}}</li>
                @else
                    <ul>
                    @foreach($file as $i)
                            @if(!is_array($i))
                                <li>{{$i}}</li>
                            @endif

                    @endforeach
                    </ul>
                @endif
            @endforeach
        </ul>
    </div>
@endsection