@extends('layouts.app')

@section('content')
<ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
    <li class="breadcrumb-item"><a href="/l/{{extension()->id}}">{{extension()->name}} {{ __('Sunucuları') }}</a>
    </li>
    <li class="breadcrumb-item"><a href="/l/{{extension()->id}}/{{request('city')}}">{{cities(request('city'))}}</a></li>
    @if($viewName === "index")
      <li class="breadcrumb-item active" aria-current="page">{{server()->name}} - {{extension()->name}}</li>
    @else
      <li class="breadcrumb-item"><a href="/l/{{extension()->id}}/{{request('city')}}/{{server()->id}}">{{server()->name}} - {{extension()->name}}</a></li>
      <li class="breadcrumb-item active" aria-current="page">{{__($viewName)}}</li>
    @endif
</ol>
<div class="right" style="float:right;margin-top:-55px">
        <button class="btn btn-primary" onclick="location.href = '{{route('extension_server_settings_page',[
            "server_id" => server()->id,
            "extension_id" => extension()->id
        ])}}'"><i class="fa fa-cogs"></i></button>
        <button class="btn btn-primary" onclick="location.href = '{{route('server_one',[
            "server_id" => server()->id,
        ])}}'"><i class="fa fa-server"></i></button>
</div>
@include('l.errors')    
<div class="nav-tabs-custom">
        <ul class="nav nav-tabs">
        @foreach (server()->extensions() as $extension)
            @if(request('extension_id') == $extension->id)
                <li class="active"><a onclick="return" data-toggle="tab" aria-expanded="true">{{$extension->name}}</a></li>
            @else
                <li><a onclick="location.href = '{{route('extension_server',[
                    'extension_id' => $extension->id,
                    'city' => server()->city,
                    'server_id' => server()->id
                ])}}'" aria-expanded="false">{{$extension->name}}</a>
            @endif
        @endforeach
        </ul>
    <div class="tab-content">
        <div class="tab-pane active" id="current" style="display:flow-root;height:100%;width:100%">
            {!!$view!!}
        </div>
    </div>
</div>
</div>


<br>{{__("İstek")}} {{$timestamp}} {{__("saniyede tamamlandı.")}}
@endsection
