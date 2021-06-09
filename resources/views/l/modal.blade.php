@php($id = isset($id) ? $id : bin2hex(random_bytes(10)))
<div class="modal fade" id="{{$id}}">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">
                    @isset($title)
                        {{__($title)}}
                    @endisset                    
                </h4>
                <button type="button" class="close" aria-label="Close" onclick="closeCurrentModal('{{$id}}')">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            @isset($onsubmit)
                <form id="{{$id}}_form" onsubmit="return {{$onsubmit}}(this)" target="#">
            @else
                <form id="{{$id}}_form" onsubmit="return @isset($url)request('{{$url}}',this,@isset($next){{$next}}@endisset,@isset($error){{$error}}@endisset)"@endisset target="#">
            @endif
                <div class="modal-body">
                    <div id="{{$id}}_alert" class="alert" role="alert" hidden></div>
                    @if(isset($selects) && is_array($selects))
                        <div class="form-group">
                            <label>{{__("Tipi")}}</label>
                            <select class="form-control" required onchange="cs_{{$id}}(this.value)">
                                @foreach ($selects as $key => $select)
                                    <option value="{{explode(":",$key)[1]}}">{{__(explode(":",$key)[0])}}</option>
                                @endforeach
                            </select>
                        </div>
                        @foreach ($selects as $key => $select)
                        @include('inputs',[
                                    "inputs" => $select,
                                    "disabled" => "true",
                                    "id" => explode(":",$key)[1],
                                    "random" => $id
                            ])
                        @endforeach
                    @endif
                    @isset($inputs)
                        @include('inputs',$inputs)
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
                <div class="modal-footer justify-content-right">
                    @isset($noEnter)
                        <button type="button" class="btn btn-success">@isset($submit_text){{__($submit_text)}}@endisset</button>
                    @else
                        <button type="submit" class="btn btn-success">@isset($submit_text){{__($submit_text)}}@endisset</button>
                    @endisset
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