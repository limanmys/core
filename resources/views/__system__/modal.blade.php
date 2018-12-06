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
            <form onsubmit="return @isset($function){{$function}}(this)@endisset">
                <div class="modal-body">
                    @if(isset($inputs) && is_array($inputs))
                        @foreach ($inputs as $name => $type)
                            <input type="{{$type}}" name="{{$name}}" placeholder="{{$name}}" class="form-control" required><br>
                        @endforeach
                    @endisset
                </div>
                <div class="modal-footer">
                <button type="button" class="btn btn-success">@isset($submit_text){{__($submit_text)}}@endisset</button>
                </div>
            </form>
        </div>
    </div>
</div>