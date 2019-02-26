@extends('layouts.app')

@section('content')
<h1 class="ml-auto">{{__($exception->getMessage())}}</h1>
@endsection