@php($log = \App\ServerLog::find(request('log_id')))

@extends('layouts.app')

@section('content')
    <input class="form-control" value="{{$log->command}}"/><br><br>

    <textarea class="form-control" aria-label="output" style="width: 80%;height: 70%">
        {{$log->output}}
    </textarea>
@endsection