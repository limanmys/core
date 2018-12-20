@foreach ($files as $key => $file)
    @if(is_string($key))
        <hr>
        <li>
            <div class="btn-group dropright">
                <button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">{{$key}}</button>
            <ul class="dropdown-menu">
                @foreach ($files[$key] as $key2 => $file)
                   {{--@if(is_array($files[$key][$key2]))
                        @include('__system__.dropdown',$files[$key])
                    @else--}}
                    <a onclick="details(this)">{{$files[$key][$key2]}}</a>
        {{--@endif--}}
                @endforeach
            </ul>
            </div>
        </li>
    @else
        <hr><li> <a class="btn btn-default glyphicon glyphicon-hand-up" onclick="details(this)">{{$file}}</a></li>
        @endif
@endforeach



