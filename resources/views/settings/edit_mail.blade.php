@extends('layouts.app')

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
        <li class="breadcrumb-item" aria-current="page"><a href="{{route('settings')}}">{{__("Ayarlar")}}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{__("Mail Ayarı Düzenle")}}</li>
    </ol>
</nav>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{__("Mail Ayarı Ekle")}}</h3>
    </div>
    <div class="card-body">
        @include('errors')
        <form action="{{route('cron_mail_edit', $cron_mail->id)}}" onsubmit="return widget_control(this)" method="POST">
            <label>{{__("Kullanıcı")}}</label>
            <select class="form-control select2" id="user_id" required name="user_id[]" data-placeholder="{{ __("Kullanıcı") }}" multiple="multiple">
                @foreach(users() as $user)
                    <option value="{{$user->id}}" @if($users->where('id', $user->id)->isNotEmpty()) selected @endif>{{$user->name}}</option>
                @endforeach
            </select><br>
            <label>{{__("Sunucu")}}</label>
            <select class="form-control" onchange="getExtensions()" id="server_id" required name="server_id">
                @foreach(servers() as $server)
                    <option value="{{$server->id}}" @if($server->id == $cron_mail->server_id) selected @endif>{{$server->name}}</option>
                @endforeach
            </select><br>
            <label>{{__("Eklenti")}}</label>
            <select class="form-control" id="extension_id" disabled onchange="getCronMailTags()" required name="extension_id"></select><br>
            <label>{{__("Mail Ayarı")}}</label>
            <select class="form-control select2" id="target" disabled="" name="target[]" multiple="multiple" data-placeholder="{{ __("Hedef fonksiyon seçiniz.") }}"></select><br>
            <label>{{__("Rapor Süresi")}}</label>
            <select class="form-control" id="cron_type" name="cron_type">
                <option value="hourly" @if($cron_mail->cron_type == 'hourly') selected @endif>{{ __("Saatlik") }}</option>
                <option value="daily" @if($cron_mail->cron_type == 'daily') selected @endif>{{ __("Günlük") }}</option>
                <option value="weekly" @if($cron_mail->cron_type == 'weekly') selected @endif>{{ __("Haftalık") }}</option>
                <option value="monthly" @if($cron_mail->cron_type == 'monthly') selected @endif>{{ __("Aylık") }}</option>
            </select><br>
            <label>{{__("Hedef Mail")}}</label>
            <select name="to[]" id="to" required class="form-control select2" data-tags="true" data-placeholder="{{ __('Mail adreslerini enter ile ayırın.') }}" data-allow-clear="true" multiple="multiple">
                @foreach($to as $mail)
                    <option value="{{$mail}}" selected>{{$mail}}</option>
                @endforeach
            </select>
            <br>
            @csrf
            <button class="btn btn-success" type="submit">{{__("Mail Ayarı Ekle")}}</button>
        </form>
    </div>
</div>
<script>
    function getExtensions(){
        var form = new FormData();
        var element = $("#extension_id");
        element.text('');
        element.attr('disabled','true');
        form.append('server_id',$("#server_id").val());
        request('{{route('widget_get_extensions')}}',form,function(response){
            var json = JSON.parse(response);
            for(var k in json) {
                element.append('<option value="'+ k+ '" '+((k == '{{ $cron_mail->extension_id }}') ? 'selected' : '')+'>' + fixer(json[k]) + '</option>');
            }
            if(Object.keys(json).length > 0){
                getCronMailTags();
                element.removeAttr('disabled');
            }else{
                $("#target").text('').addAttr('disabled','');
            }
        }, function(response){
            var error = JSON.parse(response);
            showSwal(error.message,'error',2000);
        });
    }

    function decodeEntities(encodedString) {
        var textArea = document.createElement('textarea');
        textArea.innerHTML = encodedString;
        return textArea.value;
    }

    function getCronMailTags(){
        $("#target").text('');
        var form = new FormData();
        form.append('extension_id',$("#extension_id").val());
        request('{{route('cron_mail_get_tags')}}',form,function(response){
            let json = JSON.parse(response);
            let element = $("#target");
            element.text('');
            let selected_items = JSON.parse(decodeEntities("{{ $cron_mail->target }}"));
            $.each(json.message, function( index, value ) {
                element.append('<option value="'+ value["tag"] +'">' + value["description"] + '</option>');
                $.each(selected_items, function (idx, val) {
                    if (value["tag"] == val) {
                        $(element).find(`option[value=${value["tag"]}]`).attr("selected", "selected");
                    }
                })
            });
            element.removeAttr('disabled');
        }, function(response){
            var error = JSON.parse(response);
            showSwal(error.message,'error',2000);
        });
    }

    function widget_control(element){
        if(!$(element).find('select#target').val()){
            showSwal("{{_("Önce bir widget seçmelisiniz!")}}",'error',2000);
            return false;
        }
        return true;
    }

    window.addEventListener('load', function () {
        getExtensions();
    })
</script>
@endsection
