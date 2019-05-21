{{-- 
    "current"
    "count"
    "onclick"
--}}
<ul class="pagination" style="cursor:pointer">
    <li class="paginate_button previous @if($current == 1) disabled @endif" id="example2_previous">
        <a @if($current != 1) onclick="{{$onclick . '(' . ($current - 1 ). ')'}}" @endif data-dt-idx="0" tabindex="0">{{__("Önceki")}}</a>
    </li>
    @for($i = 1 ; $i <= $count; $i++)
        <li class="paginate_button @if($i == $current) active @endif">
            <a onclick="{{$onclick . '(' . ($i). ')'}}" tabindex="0">{{$i}}</a>
        </li>
    @endfor
    <li class="paginate_button next @if($current == $count) disabled @endif" id="example2_next">
        <a @if($current != $count) onclick="{{$onclick . '(' . ($current + 1 ). ')'}}" @endif data-dt-idx="0" tabindex="0">{{__("Sonraki")}}</a>
    </li>
</ul>