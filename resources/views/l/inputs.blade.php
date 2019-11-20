@foreach ($inputs as $name => $input)
@if(is_array($input))
    @if(isset($disabled))
        {{--<h5>{{__(explode(":",$name)[0])}}</h5>--}}
        <select name="{{explode(":",$name)[1]}}" class="form-control" required disabled hidden>
            @foreach ($input as $key => $value)
                <option value="{{$value}}">{{__($key)}}</option>
            @endforeach
        </select>
    @else
        <h5>{{__(explode(":",$name)[0])}}</h5>
        <select name="{{explode(":",$name)[1]}}" class="form-control" required>
            @foreach ($input as $key => $value)
                <option value="{{$value}}">{{__($key)}}</option>
            @endforeach
        </select>
    @endif
    @isset(explode(":", $name)[2])
      <small>{{__(explode(":", $name)[2])}}</small></br>
    @endisset
@else
    @if(explode(":", $input)[1] == "hidden")
        @if(explode(":", $input)[1] == "checkbox")
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="{{explode(":", $input)[0]}}">
                <label class="form-check-label h5">
                    {{__($name)}}
                </label>
            </div>
        @else
            <input type="{{explode(":", $input)[1]}}" name="{{explode(":", $input)[0]}}" placeholder="{{__($name)}}"
                   class="form-control @if(isset($random,$id)){{$random}} {{$id}}@endif" required value="{{explode(":",$name)[1]}}">@if(explode(":", $input)[1] != "hidden")@endif
        @endif
    @elseif(isset($disabled))
        @if(explode(":", $input)[1] == "checkbox")
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="{{explode(":", $input)[0]}}">
                <label class="form-check-label h5">
                    {{__($name)}}
                </label>
            </div>
        @else
            <h5 class="@if(isset($random,$id)){{$random}} {{$id}}@endif" style="padding-top: 15px;">{{__(explode(":",$name)[0])}}</h5>
            <input type="{{explode(":", $input)[1]}}" name="{{explode(":", $input)[0]}}" placeholder="{{__($name)}}"
                   class="form-control @if(isset($random,$id)){{$random}} {{$id}}@endif" required disabled hidden style="margin-top:15px">
        @endif
    @elseif(explode(":", $input)[1] == "textarea")
        @if(count($inputs))
            <textarea name="{{explode(":", $input)[0]}}"
                      class="form-control" required style="height: 60%"></textarea>
        @else
            <textarea name="{{explode(":", $input)[0]}}"
                      class="form-control" required></textarea>
        @endif
    @elseif(explode(":", $input)[1] == "file")
        <div class="custom-file">
            <input name="{{explode(":", $input)[0]}}" type="file" class="custom-file-input @if(isset($random,$id)){{$random}} {{$id}}@endif">
            <label class="custom-file-label">{{__($name)}}</label>
        </div>
        <style>
            .custom-file-label::after{
                content: "{{ __('Gözat') }}"
            }
        </style>
    @else
        @if(explode(":", $input)[1] == "checkbox")
            <div class="form-check">
                <input class="form-control-input" type="checkbox" name="{{explode(":", $input)[0]}}">
                <label class="form-control-label h5">
                    {{__($name)}}
                </label>
            </div>
        @else
            @if(substr(explode(":", $input)[0],0,2) != "d-")
                <h5>{{__($name)}}</h5>
                <input type="{{explode(":", $input)[1]}}" name="{{explode(":", $input)[0]}}" placeholder="{{__($name)}}"
                       class="form-control @if(isset($random,$id)){{$random}} {{$id}}@endif" required>@if(explode(":", $input)[1] != "hidden")@endif
            @else
                <h5>{{__($name)}}</h5>
                <input type="{{explode(":", $input)[1]}}" name="{{substr(explode(":", $input)[0],2)}}" placeholder="{{__($name)}}"
                       class="form-control @if(isset($random,$id)){{$random}} {{$id}}@endif">@if(explode(":", $input)[1] != "hidden")@endif
            @endif
        @endif
    @endif
    @isset(explode(":", $input)[2])
      <small>{{__(explode(":", $input)[2])}}</small></br>
    @endisset
@endif
@endforeach
