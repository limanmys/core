@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h2>Eklenti Yönetimi</h2>
    </div>
    <button type="button" class="btn btn-success" data-toggle="modal" data-target="#scriptUpload">
        Eklenti Yükle
    </button><br><br>
    <table class="table">
        <thead>
        <tr>
            <th scope="col">Eklenti Adı</th>
        </tr>
        </thead>
        <tbody>
        @foreach($features as $feature)
            <tr class="highlight">
                <td>{{$feature->name}}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

@endsection