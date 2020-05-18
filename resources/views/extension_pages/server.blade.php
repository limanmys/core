@extends('layouts.app')

@section('content')
<ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
    <li class="breadcrumb-item"><a href="/l/{{extension()->id}}">{{extension()->display_name}} {{ __('Sunucuları') }}</a>
    </li>
    <li class="breadcrumb-item"><a href="/l/{{extension()->id}}/{{request('city')}}">{{cities(request('city'))}}</a></li>
    @if($viewName === "index")
      <li class="breadcrumb-item active" aria-current="page">{{server()->name}} - {{extension()->display_name}}</li>
    @else
      <li class="breadcrumb-item"><a href="/l/{{extension()->id}}/{{request('city')}}/{{server()->id}}">{{server()->name}} - {{extension()->display_name}}</a></li>
      <li class="breadcrumb-item active" aria-current="page">{{__($viewName)}}</li>
    @endif
</ol>
<div class="right" id="ext_menu" style="float:right;margin-top:-55px">
        <button data-toggle="tooltip" title="Eklenti Ayarları" class="btn btn-primary" onclick="location.href = '{{route('extension_server_settings_page',[
            "server_id" => server()->id,
            "extension_id" => extension()->id
        ])}}'"><i class="fa fa-cogs"></i></button>
        <button data-toggle="tooltip" title="Sunucuya Git" class="btn btn-primary" onclick="location.href = '{{route('server_one',[
            "server_id" => server()->id,
        ])}}'"><i class="fa fa-server"></i></button>
        <button data-toggle="tooltip" title="Destek Al" class="btn btn-primary" onclick="location.href = 'mailto:{{env('APP_NOTIFICATION_EMAIL')}}?subject={{env('BRAND_NAME')}} {{extension()->display_name}} {{extension()->version}}'"><i class="fas fa-headset"></i></button>
</div>
@include('errors')    
<div class="card">
    <div class="card-header">
            <ul id="quickNavBar" class="nav nav-tabs" role="tablist">
                @foreach ($last as $extension=>$servers)
                    @php(list($extension_id,$extension_name) = explode(":",$extension))
                    @if(count($servers) == 1)
                        <li class="nav-item">
                            <a class="nav-link @if(request('extension_id') == $extension_id) active @endif" href="{{route('extension_server',[
                                    'extension_id' => $extension_id,
                                    'city' => '06',
                                    'server_id' => $servers[0]['id']
                                ])}}" role="tab">{{__($extension_name)}}</a>
                        </li>
                    @else
                        <li class="dropdown nav-item" style="line-height:2.6"><!--  2.6 means absolutely nothing -->
                            <a class="dropdown-toggle @if(request('extension_id') == $extension_id) active @endif" data-toggle="dropdown" href="#">{{__($extension_name)}}
                            <span class="caret"></span></a>
                            <ul class="dropdown-menu">
                            @foreach($servers as $server)
                                <li class="nav-item"><a class="nav-link" href="{{route('extension_server',[
                                    'extension_id' => $extension_id,
                                    'city' => '06',
                                    'server_id' => $server['id']
                                ])}}">{{$server['name']}}</a></li>
                            @endforeach
                            </ul>
                        </li>
                        
                    @endif
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
<script>
    $(function(){
        let list = [];
        $("#quickNavBar li>a").each(function(){
            list.push($(this).text());
        });
        if((new Set(list)).size !== list.length){
            
        }
    })

    function API(target)
    {
        return "{{route('extension_server', [
            "extension_id" => extension()->id,
            "city" => server()->city,
            "server_id" => server()->id,
        ])}}/" + target;
    }
</script>
@endsection
