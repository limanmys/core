@foreach($files as $key => $file)
    @if(is_array($file))
        { "text" : "{{$key}}", "children" : [@include('__system__.folder',["files" => $file])]},
    @else
        { "text" : "{{$file}}" },
    @endif
@endforeach