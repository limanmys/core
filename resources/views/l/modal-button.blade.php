@if(isset($text,$class,$target_id))
<button type="button" class="btn {{$class}}" data-toggle="modal" data-target="#{{$target_id}}">
            {{__($text)}}
</button>
@endif