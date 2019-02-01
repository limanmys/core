@extends('adminlte::page')

<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="{{asset('css/main.css')}}">
@section('js')
    <script src="{{asset('js/liman.js')}}"></script>
    <script>
        $(document).ready(function() {
            $('table').not('.notDataTable').DataTable({
                autoFill : true,
                bFilter: true,
            });
        } );
    </script>
@stop