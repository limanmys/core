<?php
    use Illuminate\Support\Facades\DB;
    use App\Permission;
    $navServers = DB::select("SELECT * FROM \"server_groups\" WHERE \"servers\" LIKE \"%" . server()->id . "%\"");
    $cleanServers = [];
    foreach($navServers as $rawServers){
        $servers = explode(",",$rawServers->servers);
        foreach($servers as $server){
            if(Permission::can(user()->id,"server","id",$server)){
                array_push($cleanServers,$server);
            }
        }
    }

    $cleanServers = array_unique($cleanServers);
    $cleanExtensions = [];

    $serverObjects = App\Server::find($cleanServers);
    
    foreach($serverObjects as $server){
        $cleanExtensions[$server->id] = $server->extensions();
    }
    if(empty($cleanExtensions)){
        $cleanExtensions[server()->id] = server()->extensions();
    }
?>
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
<div class="right" id="ext_menu" style="float:right;margin-top:-55px">
        <button class="btn btn-primary" onclick="location.href = '{{route('extension_server_settings_page',[
            "server_id" => server()->id,
            "extension_id" => extension()->id
        ])}}'"><i class="fa fa-cogs"></i></button>
        <button class="btn btn-primary" onclick="location.href = '{{route('server_one',[
            "server_id" => server()->id,
        ])}}'"><i class="fa fa-server"></i></button>
</div>
@include('errors')    
<div class="card">
    <div class="card-header">
            <ul class="nav nav-tabs" role="tablist">
                @foreach ($cleanExtensions as $server_id=>$extensions)
                    @foreach($extensions as $extension)
                    <li class="nav-item">
                        <a class="nav-link @if(request('extension_id') == $extension->id) active @endif" href="{{route('extension_server',[
                                'extension_id' => $extension->id,
                                'city' => '06',
                                'server_id' => $server_id
                            ])}}" role="tab">{{__($extension->name)}}</a>
                    </li>
                    @endforeach
                @endforeach
            </ul>
    </div>
    <div class="card-body">
        <div class="tab-content">
            <div class="tab-pane fade show active" role="tabpanel">
                {!!$view!!}
            </div>
        </div>
    </div>
</div>
<br><span style="padding-left: 30px;">{{__("İstek")}} {{$timestamp}} {{__("saniyede tamamlandı.")}}</span>
@endsection
