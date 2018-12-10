@extends('layouts.app')

@section('content')
    @include('title',[
        "title" => $user->name
    ])
@endsection