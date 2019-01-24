@extends('adminlte::page')

<meta name="csrf-token" content="{{ csrf_token() }}">

@section('js')
    <script src="{{asset('js/liman.js')}}"></script>
    <script>
        $(document).ready(function() {
            $('table').DataTable({
                autoFill : true
            });
        } );
    </script>
@stop