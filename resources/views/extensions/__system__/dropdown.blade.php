@if(is_array($file))
    @each('extensions.__system__.dropdown',$file,'file')
@else
    <a href="#" class="list-group-item list-group-item-action" onclick="details()">{{$file}}</a>
@endif
