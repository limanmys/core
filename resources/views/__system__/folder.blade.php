@foreach($files as $key => $file)
    @if(is_array($file))
        { name: '{{$key}}', children: [@include('__system__.folder',["files" => $file])] },
    @else
        { name: '{{$file}}', children: [] },
    @endif
@endforeach