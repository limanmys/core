@foreach($files as $key => $file)
    @if(is_array($file))
        @if(strpos($key,"="))
            { "text" : "{{explode("=",$key)[1]}}", "children" : [@include('folder',["files" => $file])], "id" : "{{$key}}"},
        @else
            { "text" : "{{$key}}", "children" : [@include('folder',["files" => $file])],"id" : "{{$key}}"}},
        @endif
        
    @else
        { "text" : "{{$file}}" },
    @endif
@endforeach