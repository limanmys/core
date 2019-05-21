@foreach($data as $key=>$d)
@if(strpos($key,"dc") !== false || strpos($key,"DC") !== false)
    @include('l.test',[
        "data" => $d
    ])
    @continue
@endif
@if(!empty($d))
    <details>
        <summary value="{{$key}}">{{explode("=",$key)[1]}}</summary>
        @include('l.test',[
            "data" => $d
        ])
    </details>
@else
    <p>{{$key}}</p>
@endif
    
@endforeach