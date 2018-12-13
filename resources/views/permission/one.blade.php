@extends('layouts.app')

@section('content')
	@include('title',[
	    "title" => $request->user_name . " kullanıcısının " . $request->created_at. " tarihli talebi"
	])

	<button class="btn btn-success" onclick="history.back()">{{__("Geri Dön")}}</button>
@endsection