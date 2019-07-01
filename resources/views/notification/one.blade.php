@php($notification = \App\Notification::where([
            "user_id" => auth()->id(),
            "id" => request('notification_id')
        ])->first())

@extends('layouts.app')

@section('content')
    <h3>{{$notification["title"]}}</h3>
    @switch($notification["type"])
        @case('error')
        <div class="alert alert-danger" role="alert">
            {{$notification["message"]}}
        </div>
        @break
        @default
        <div class="alert alert-success" role="alert">
            {{$notification["message"]}}
        </div>
        @break
    @endswitch

@endsection