@extends('layouts.app')

@section('content')

@include('title',[
    "title" => "Yetki Talebi"
])
<form onsubmit="return request('{{route('request_send')}}',this,reload)">
    <div class="form-group">
      <label>{{__("İletişim Adresi")}}</label>
    <input name="email" type="email" class="form-control" value="{{Auth::user()->email}}" required>
    </div>
    <div class="form-group">
    <label>{{__("Yetki Tipi")}}</label>
      <select name="type" class="form-control" required>
        <option value="server">{{__("Sunucu")}}</option>
        <option value="script">{{__("Betik")}}</option>
        <option value="extension">{{__("Eklenti")}}</option>
        <option value="other">{{__("Diğer")}}</option>
      </select>
    </div>
    <div class="form-group">
        <label>{{__("Önem Derecesi")}}</label>
          <select name="speed" class="form-control" required>
          <option value="normal">{{__("Normal")}}</option>
            <option value="urgent">{{__("Acil")}}</option>
          </select>
        </div>
    <div class="form-group">
    <label>{{__("Açıklama")}}</label>
      <textarea name="note" class="form-control" rows="3" required></textarea>
    </div>
    <button class="btn btn-success" type="submit">Talebi Gönder</button>
  </form>

@endsection