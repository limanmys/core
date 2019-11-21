@foreach($data as $key=>$d)
@if(strpos($key,"dc") !== false || strpos($key,"DC") !== false)
    @include('test',[
        "data" => $d
    ])
    @continue
@endif
@if(!empty($d))
    <details>
        <summary value="{{$key}}">{{explode("=",$key)[1]}}</summary>
        @include('test',[
            "data" => $d
        ])
    </details>
@else
    <p>{{$key}}</p>
@endif
    
@endforeach