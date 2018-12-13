@if(is_array($scripts))
    @each('__system__.content',$scripts,'scripts')
@else
            <td>{{$scripts->name}} </td>
            <td>{{$scripts->description}}</td>
@endif