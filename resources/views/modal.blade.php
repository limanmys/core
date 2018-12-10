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
            <form onsubmit="return @isset($url)request('{{$url}}',this)@endisset" target="#">
                <div class="modal-body">
                    @if(isset($inputs) && is_array($inputs))
                        @foreach ($inputs as $name => $input)
                            <h5>{{__($name)}}</h5>
                            @if(is_array($input))
                                <select name="{{$name}}" class="form-control" required>
                                    @foreach ($input as $key => $value)
                                        <option value="{{$value}}">{{__($key)}}</option>
                                    @endforeach    
                                </select>
                            @else
                                <input type="{{explode(":", $input)[1]}}" name="{{explode(":", $input)[0]}}" placeholder="{{__($name)}}" class="form-control" required><br>
                            @endif
                        @endforeach
                    @endisset
                    @if(isset($text))
                        {{__($text)}}
                    @endisset
                </div>
                <div class="modal-footer">
                <button type="submit" class="btn btn-success">@isset($submit_text){{__($submit_text)}}@endisset</button>
                </div>
            </form>
        </div>
    </div>
</div>