@extends('layouts.app')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
            <li class="breadcrumb-item"><a href="{{route('settings')}}">{{__("Ayarlar")}}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{__("Sertifika Ekle")}}</li>
        </ol>
    </nav>
    <h3>{{__("Sisteme Sertifika Ekleme")}}</h3>
    <small>{{__("Not : Eklediğiniz sertifika işletim sistemi tarafından güvenilecektir.")}}</small>
    @if(request('server_id'))
        <h5>{{server()->name . " " . __("sunucusu talebi.")}}</h5>
    @endif
    <table class="notDataTable">
        <style>
            td{
                padding: 5px;
            }
        </style>
        <tr>
            <td>{{__("Hostname")}}</td>
            <td><input type="text" name="hostname" class="form-control" id="hostname" value="{{request('hostname')}}"></td>
            <td><b>{{__("Otomatik")}}</b></td>
            <td rowspan="2">{{__("veya")}}</td>
            <td><b>{{__("Dosyadan Oku")}}</b></td>
        </tr>
        <tr>
            <td>{{__("Port")}}</td>
            <td><input type="number" name="port" class="form-control" aria-valuemin="1" aria-valuemax="65555" id="port" value="{{request('port')}}"></td>
            <td><button onclick="retrieveCertificate()" class="btn btn-success">{{__("Al")}}</button></td>
            <td><input id="certificateUpload" type="file" class="form-control"></td>
        </tr>
    </table>
    <pre id="output" style="display: none"></pre>
    <button class="btn btn-success" onclick="verifyCertificate()" id="addButton" style="display:none;">{{__("Sertifikayı Onayla")}}</button>
    <script>
        document.getElementById('certificateUpload').addEventListener("change",function () {
            let reader = new FileReader();
            reader.addEventListener('load',function (e) {
                $("#output").html(e.target.result).fadeIn(0);
                $("#addButton").fadeIn(0);
            });
            reader.readAsBinaryString(this.files[0]);
        });

        function retrieveCertificate() {
            Swal.fire({
                position: 'center',
                type: 'info',
                title: '{{__("Sertifika Alınıyor...")}}',
                showConfirmButton: false,
                allowOutsideClick : false,
            });
            let form = new FormData();
            form.append('hostname',$("#hostname").val());
            form.append('port',$("#port").val());
            request('{{route('certificate_request')}}',form,function (success) {
                let json = JSON.parse(success);
                $("#output").html(json["message"]).fadeIn(0);
                Swal.close();
                $("#addButton").fadeIn(0);
            },function (errors) {
                let json = JSON.parse(errors);
                Swal.fire({
                    position: 'center',
                    type: 'error',
                    title: json["message"],
                    showConfirmButton: false,
                    allowOutsideClick : false,
                    timer : 2000
                });
            });

        }
        
        function verifyCertificate() {
            Swal.fire({
                position: 'center',
                type: 'info',
                title: '{{__("Sertifika Ekleniyor...")}}',
                showConfirmButton: false,
                allowOutsideClick : false,
            });
            let form = new FormData();
            form.append('certificate',$("#output").html());
            form.append('hostname',$("#hostname").val());
            form.append('origin',$("#port").val());
            form.append('notification_id','{{request('notification_id')}}');
            form.append('server_id','{{request('server_id')}}');
            request('{{route('verify_certificate')}}',form,function (success) {
                let json = JSON.parse(success);
                Swal.fire({
                    position: 'center',
                    type: 'info',
                    title: json["message"],
                    showConfirmButton: false,
                    allowOutsideClick : false,
                    timer : 2000
                });
                setTimeout(function () {
                    location.href = "{{route('settings')}}" + "#certificates";
                },1000);
            },function (errors) {
                let json = JSON.parse(errors);
                Swal.fire({
                    position: 'center',
                    type: 'error',
                    title: json["message"],
                    showConfirmButton: false,
                    allowOutsideClick : false,
                    timer : 2000
                });
            });
        }
    </script>
@endsection