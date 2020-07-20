@extends('layouts.master')
@include('layouts.navbar')

@section('body')

<div style="padding:30px;">
    {!!$view!!}
</div>

<script>
    function API(target)
    {
        return "{{route('extension_server', [
            "extension_id" => extension()->id,
            "city" => server()->city,
            "server_id" => server()->id,
        ])}}/" + target;
    }

    Echo.private('extension_renderer_{{auth()->user()->id}}').listen("ExtensionRendered", function(response){
        console.log(response);
    });
</script>
@stop