    @foreach ($inputs as $name => $input)
        @if(is_array($input))
            @if(isset($disabled))
                {{--<h5>{{__(explode(":",$name)[0])}}</h5>--}}
                <select name="{{explode(":",$name)[1]}}" class="form-control" required disabled hidden>
                    @foreach ($input as $key => $value)
                        <option value="{{$value}}">{{__($key)}}</option>
                    @endforeach
                </select><br>
            @else
                <h5>{{__(explode(":",$name)[0])}}</h5>
                <select name="{{explode(":",$name)[1]}}" class="form-control" required>
                    @foreach ($input as $key => $value)
                        <option value="{{$value}}">{{__($key)}}</option>
                    @endforeach
                </select><br>
            @endif
        @else
            @if(explode(":", $input)[1] == "hidden")
                @if(explode(":", $input)[1] == "checkbox")
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="{{explode(":", $input)[0]}}">
                        <label class="form-check-label h5">
                            {{$name}}
                        </label>
                    </div><br>
                @else
                    <input type="{{explode(":", $input)[1]}}" name="{{explode(":", $input)[0]}}" placeholder="{{__($name)}}"
                           class="form-control @if(isset($random,$id)){{$random}} {{$id}}@endif" required value="{{explode(":",$name)[1]}}">@if(explode(":", $input)[1] != "hidden")<br>@endif
                @endif
            @elseif(isset($disabled))
                @if(explode(":", $input)[1] == "checkbox")
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="{{explode(":", $input)[0]}}">
                        <label class="form-check-label h5">
                            {{$name}}
                        </label>
                    </div><br>
                @else
                    <h5 class="@if(isset($random,$id)){{$random}} {{$id}}@endif" style="padding-top: 15px;">{{__(explode(":",$name)[0])}}</h5>
                    <input type="{{explode(":", $input)[1]}}" name="{{explode(":", $input)[0]}}" placeholder="{{__($name)}}"
                           class="form-control @if(isset($random,$id)){{$random}} {{$id}}@endif" required disabled hidden style="margin-top:15px">
                @endif
            @elseif(explode(":", $input)[1] == "textarea")
                @if(count($inputs))
                    <textarea name="{{explode(":", $input)[0]}}"
                              class="form-control" required style="height: 60%"></textarea><br>
                @else
                    <textarea name="{{explode(":", $input)[0]}}"
                              class="form-control" required></textarea><br>
                @endif

            @else
                @if(explode(":", $input)[1] == "checkbox")
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="{{explode(":", $input)[0]}}">
                        <label class="form-check-label h5">
                            {{$name}}
                        </label>
                    </div><br>
                @else
                    {{--@if(explode(":", $name) > 1)--}}
{{--                        <h5>{{explode(":", $name)[0]}}</h5>--}}
                        {{--<input type="{{explode(":", $input)[1]}}" name="{{explode(":", $input)[0]}}" placeholder="{{__($name)}}"--}}
                               {{--class="form-control @if(isset($random,$id)){{$random}} {{$id}}@endif" required--}}
                               {{--value="{{explode(":", $name)[1]}}"--}}
{{--                        >@if(explode(":", $input)[1] != "hidden")<br>@endif--}}
                    {{--@else--}}
                        <h5>{{$name}}</h5>
                        <input type="{{explode(":", $input)[1]}}" name="{{explode(":", $input)[0]}}" placeholder="{{__($name)}}"
                               class="form-control @if(isset($random,$id)){{$random}} {{$id}}@endif" required>@if(explode(":", $input)[1] != "hidden")<br>@endif
                    {{--@endif--}}

                @endif
            @endif
        @endif
    @endforeach