<div class="modal fade" id="@isset($id){{$id}}@endisset" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title">
                    @isset($title)
                        {{__($title)}}
                    @endisset
                </h1>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            @if(isset($onsubmit))
                <form @isset($id)id="{{$id}}_form"@endisset onsubmit="return {{$onsubmit}}" target="#">
            @else
                <form @isset($id)id="{{$id}}_form"@endisset onsubmit="return @isset($url)request('{{$url}}',this,@isset($next){{$next}}@endisset)"@endisset target="#">
            @endif
                <div class="modal-body">
                    @if(isset($inputs) && is_array($inputs))
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
                                    <input type="{{explode(":", $input)[1]}}" name="{{explode(":", $input)[0]}}" placeholder="{{__($name)}}" class="form-control" required value="{{explode(":",$name)[1]}}">@if(explode(":", $input)[1] != "hidden")<br>@endif
                                @else
                                    <h5>{{__($name)}}</h5>
                                    <input type="{{explode(":", $input)[1]}}" name="{{explode(":", $input)[0]}}" placeholder="{{__($name)}}" class="form-control" required>@if(explode(":", $input)[1] != "hidden")<br>@endif
                                @endif
                            @endif
                        @endforeach
                    @endisset
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">@isset($submit_text){{__($submit_text)}}@endisset</button>
                </div>
            </form>
        </div>
    </div>
</div>