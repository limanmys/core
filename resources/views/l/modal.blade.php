<div class="modal fade" id="@isset($id){{$id}}@endisset" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">×</span></button>
                <h3 class="modal-title">
                    @isset($title)
                        {{__($title)}}
                    @endisset
                </h3>
            </div>
            @php($rand = bin2hex(random_bytes(10)))
            @isset($onsubmit)
                <form @isset($id)id="{{$id}}_form"@endisset onsubmit="return {{$onsubmit}}(this)" target="#">
            @else
                <form @isset($id)id="{{$id}}_form"@endisset onsubmit="return @isset($url)request('{{$url}}',this,@isset($next){{$next}}@endisset)"@endisset target="#">
            @endif
                <div class="modal-body">
                    <div id="{{$id}}_alert" class="alert" role="alert" hidden>
                    </div>
                    @if(isset($selects) && is_array($selects))
                        <h5>{{__("Servis Seçimi")}}</h5>
                        <select class="form-control" required onchange="cs_{{$id}}(this.value)">
                            @foreach ($selects as $key => $select)
                                <option value="{{explode(":",$key)[1]}}">{{__(explode(":",$key)[0])}}</option>
                            @endforeach
                            @foreach ($selects as $key => $select)
                                @include('l.inputs',[
                                        "inputs" => $select,
                                        "disabled" => "true",
                                        "id" => explode(":",$key)[1],
                                        "random" => $id
                                ])
                            @endforeach
                        </select><br>
                    @endif
                    @isset($inputs)
                        @include('l.inputs',$inputs)
                    @endisset
                    @isset($text)
                        {{__($text)}}
                    @endisset
                    @isset($output)
                    <pre>
                        <textarea class="form-control" id="{{$output}}" hidden readonly rows="10"></textarea>
                        </pre>
                        <br>
                    @endisset
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">@isset($submit_text){{__($submit_text)}}@endisset</button>
                </div>
            </form>
        </div>
    </div>
</div>
@isset($selects)
    <script type="text/javascript">
        function cs_{{$id}}(target){
            Array.prototype.forEach.call(document.getElementsByClassName('{{$id}}'),function(element){
                element.setAttribute('hidden',"true");
                element.setAttribute('disabled',"true");
            });
            Array.prototype.forEach.call(document.getElementsByClassName(target),function(element){
                element.removeAttribute('hidden');
                element.removeAttribute('disabled');
            });
        }
        cs_{{$id}}('{{explode(':',key($selects))[1]}}')
    </script>
@endisset