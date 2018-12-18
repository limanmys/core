@if(is_array($inputs))
    @foreach ($inputs as $name => $input)
        @if(is_array($input))
            <h5>{{__(explode(":",$name)[0])}}</h5>
            <select name="{{explode(":",$name)[1]}}" class="form-control" required>
                @foreach ($input as $key => $value)
                    <option value="{{$value}}">{{__($key)}}</option>
                @endforeach
            </select><br>
        @else
            @if(explode(":", $input)[1] == "hidden")
                <input type="{{explode(":", $input)[1]}}" name="{{explode(":", $input)[0]}}" placeholder="{{__($name)}}"
                       class="form-control @if(isset($random,$id)){{$random}} {{$id}}@endif" required value="{{explode(":",$name)[1]}}">@if(explode(":", $input)[1] != "hidden")<br>@endif
            @elseif(isset($disabled))
                <input type="{{explode(":", $input)[1]}}" name="{{explode(":", $input)[0]}}" placeholder="{{__($name)}}"
                       class="form-control @if(isset($random,$id)){{$random}} {{$id}}@endif" required disabled hidden style="margin-top:15px">
            @elseif(explode(":", $input)[1] == "textarea")
                <textarea name="{{explode(":", $input)[0]}}"
                       class="form-control" required></textarea><br>
            @else
                <h5>{{__($name)}}</h5>
                <input type="{{explode(":", $input)[1]}}" name="{{explode(":", $input)[0]}}" placeholder="{{__($name)}}"
                       class="form-control @if(isset($random,$id)){{$random}} {{$id}}@endif" required>@if(explode(":", $input)[1] != "hidden")<br>@endif
            @endif
        @endif
    @endforeach
@endisset