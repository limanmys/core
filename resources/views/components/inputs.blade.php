@php
if (!function_exists('safeExplode')) {
    function safeExplode($explodable, $split, $accessor) {
        $temp = explode($split, $explodable);
        if (isset($temp[$accessor])) {
            return $temp[$accessor];
        }

        return "";
    }
}
@endphp

@foreach ($inputs as $name => $input)
<div class="form-group">
    @if(is_array($input))
        @if(isset($disabled))
            <select name="{{safeExplode($name, ":", 1)}}" class="form-control" required disabled hidden>
                @foreach ($input as $key => $value)
                    <option value="{{$value}}">{{__($key)}}</option>
                @endforeach
            </select>
        @else
            <label>{{__(safeExplode($name, ":", 0))}}</label>
            <select name="{{safeExplode($name, ":", 1)}}" class="form-control" required>
                @foreach ($input as $key => $value)
                    <option value="{{$value}}">{{__($key)}}</option>
                @endforeach
            </select>
        @endif
        @if(safeExplode($name, ":", 2))
        <small class="form-text text-muted">{{__(safeExplode($name, ":", 2))}}</small>
        @endif
    @else
        @php
            $placeholder = safeExplode($input, ":", 2);
        @endphp
        @if(safeExplode($input, ":", 1) == "hidden")
            @if(safeExplode($input, ":", 1) == "checkbox")
                <div class="form-check">
                    <input id="{{safeExplode($input, ":", 0)}}" class="form-check-input @if(isset($random,$id)){{$random}} {{$id}}@endif" type="checkbox" name="{{safeExplode($input, ":", 0)}}">
                    <label for="{{safeExplode($input, ":", 0)}}" class="form-check-label @if(isset($random,$id)){{$random}} {{$id}}@endif">{{__($name)}}</label>
                </div>
            @else
                <input type="{{safeExplode($input, ":", 1)}}" name="{{safeExplode($input, ":", 0)}}" placeholder="{{__($placeholder)}}"
                    class="form-control @if(isset($random,$id)){{$random}} {{$id}}@endif" required value="{{safeExplode($name, ":", 1)}}">@if(safeExplode($input, ":", 1) != "hidden")@endif
            @endif
        @elseif(isset($disabled))
            @if(safeExplode($input, ":", 1) == "checkbox")
                <div class="form-check">
                    <input id="{{safeExplode($input, ":", 0)}}" class="form-check-input @if(isset($random,$id)){{$random}} {{$id}}@endif" type="checkbox" name="{{safeExplode($input, ":", 0)}}">
                    <label for="{{safeExplode($input, ":", 0)}}" class="form-check-label @if(isset($random,$id)){{$random}} {{$id}}@endif">{{__($name)}}</label>
                </div>
            @else
                <label class="@if(isset($random,$id)){{$random}} {{$id}}@endif">{{__(explode(":",$name)[0])}}</label>
                <input type="{{safeExplode($input, ":", 1)}}" name="{{safeExplode($input, ":", 0)}}" placeholder="{{__($placeholder)}}"
                    class="form-control @if(isset($random,$id)){{$random}} {{$id}}@endif" required disabled hidden>
            @endif
        @elseif(safeExplode($input, ":", 1) == "textarea")
            @if(count($inputs))
                <textarea name="{{safeExplode($input, ":", 0)}}"
                        class="form-control" required style="height: 60%"></textarea>
            @else
                <textarea name="{{safeExplode($input, ":", 0)}}"
                        class="form-control" required></textarea>
            @endif
        @elseif(safeExplode($input, ":", 1) == "file")
            <div class="custom-file">
                <input name="{{safeExplode($input, ":", 0)}}" type="file" class="custom-file-input @if(isset($random,$id)){{$random}} {{$id}}@endif">
                <label class="custom-file-label">{{__($name)}}</label>
            </div>
            <style>
                .custom-file-label::after{
                    content: "{{ __('Gözat') }}"
                }
            </style>
        @else
            @if(safeExplode($input, ":", 1) == "checkbox")
                <div class="form-check">
                    <input id="{{safeExplode($input, ":", 0)}}" class="form-check-input @if(isset($random,$id)){{$random}} {{$id}}@endif" type="checkbox" name="{{safeExplode($input, ":", 0)}}">
                    <label for="{{safeExplode($input, ":", 0)}}" class="form-check-label @if(isset($random,$id)){{$random}} {{$id}}@endif">{{__($name)}}</label>
                </div>
            @else
                @if(substr(safeExplode($input, ":", 0),0,2) != "d-")
                    <label>{{__($name)}}</label>
                    <input type="{{safeExplode($input, ":", 1)}}" name="{{safeExplode($input, ":", 0)}}" placeholder="{{__($placeholder)}}"
                        class="form-control @if(isset($random,$id)){{$random}} {{$id}}@endif" required>@if(safeExplode($input, ":", 1) != "hidden")@endif
                @else
                    <label>{{__($name)}}</label>
                    <input type="{{safeExplode($input, ":", 1)}}" name="{{substr(safeExplode($input, ":", 0),2)}}" placeholder="{{__($placeholder)}}"
                        class="form-control @if(isset($random,$id)){{$random}} {{$id}}@endif">@if(safeExplode($input, ":", 1) != "hidden")@endif
                @endif
            @endif
        @endif
        @isset(explode(":", $input,3)[2])
        <small class="form-text text-muted">{{__(explode(":", $input,3)[2])}}</small>
        @endisset
    @endif
</div>
@endforeach
