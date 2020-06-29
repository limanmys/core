@extends('layouts.app')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
            <li class="breadcrumb-item"><a href="{{route('settings')}}">{{__("Sistem Ayarları")}}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{__("Sertifika Ekle")}}</li>
        </ol>
    </nav>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{__("Sisteme SSL Sertifikası Ekleme")}}</h3>
        </div>
        <div class="card-body">
            @if(request('server_id'))
                <h5>{{server()->name . " " . __("sunucusu talebi.")}}</h5>
            @endif
            <div class="row">
                <div class="col-md-4">
                    <label for="hostname">{{__("Hostname")}}</label>
                    <input type="text" name="hostname" class="form-control" id="hostname" value="{{request('hostname')}}"></td>
                </div>
                <div class="col-md-4">
                    <label for="port">{{__("Port")}}</label>
                    <input type="number" name="port" class="form-control" aria-valuemin="1" aria-valuemax="65555" id="port" value="{{request('port')}}">
                </div>
                <div class="col-md-4" style="line-height: 95px">
                    <button onclick="retrieveCertificate()" class="btn btn-success">{{__("Al")}}</button>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="box box-solid">
                    <div class="box-header with-border">
                        <h3 class="box-title">{{__("İmzalayan")}}</h3>
                    </div>
                    <div class="box-body clearfix">
                        <div class="form-group">
                            <label>{{__("İstemci")}}</label>
                            <input type="text" id="issuerCN" readonly class="form-control">
                        </div>
                        <div class="form-group">
                            <label>{{__("Otorite")}}</label>
                            <input type="text" id="issuerDN" readonly class="form-control">
                        </div>
                    </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="box box-solid">
                        <div class="box-header with-border">
                        <h3 class="box-title">{{__("Parmak İzleri")}}</h3>
                        </div>
                        <div class="box-body clearfix">
                        <div class="form-group">
                            <label>{{__("İstemci")}}</label>
                            <input type="text" id="subjectKeyIdentifier" readonly class="form-control">
                        </div>
                        <div class="form-group">
                            <label>{{__("Otorite")}}</label>
                            <input type="text" id="authorityKeyIdentifier" readonly class="form-control">
                        </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="box box-solid">
                    <div class="box-header with-border">
                        <h3 class="box-title">{{__("Geçerlilik Tarihi")}}</h3>
                    </div>
                    <div class="box-body clearfix">
                        <div class="form-group">
                            <label>{{__("Başlangıç Tarihi")}}</label>
                            <input type="text" id="validFrom" readonly class="form-control">
                        </div>
                        <div class="form-group">
                            <label>{{__("Bitiş Tarihi")}}</label>
                            <input type="text" id="validTo" readonly class="form-control">
                        </div>
                    </div>
                    </div>
                </div>
            </div>
            <div class="row">
                    <div class="col-md-4">
                        <div class="box box-solid">
                            <div class="box-header with-border">
                                <h3 class="box-title">{{__("Sertifikayı Onayla")}}</h3>
                            </div>
                            <div class="box-body clearfix">
                                <span>{{__("Not : Eklediğiniz sertifika işletim sistemi tarafından güvenilecektir.")}}</span><br><br>
                                <button class="btn btn-success" onclick="verifyCertificate()" id="addButton" disabled>{{__("Sertifikayı Onayla")}}</button>
                            </div>
                        </div>
                    </div>
            </div>
        </div>
    </div>
    <script>
        var path = "";
        function retrieveCertificate() {
            showSwal('{{__("Sertifika Alınıyor...")}}','info');
            var form = new FormData();
            form.append('hostname',$("#hostname").val());
            form.append('port',$("#port").val());
            request('{{route('certificate_request')}}',form,function (success) {
                var json = JSON.parse(success)["message"];
                if(json["issuer"]["DC"]){
                    $("#issuerCN").val(json["issuer"]["CN"]);
                }
                if(json["issuer"]["DC"]){
                    $("#issuerDN").val(json["issuer"]["DC"].reverse().join('.'));
                }
                $("#validFrom").val(json["validFrom_time_t"]);
                $("#validTo").val(json["validTo_time_t"]);
                $("#authorityKeyIdentifier").val(json["authorityKeyIdentifier"]);
                $("#subjectKeyIdentifier").val(json["subjectKeyIdentifier"]);
                $("#addButton").prop('disabled',false);
                path = json["path"];
                Swal.close();
            },function (errors) {
                var json = JSON.parse(errors);
                showSwal(json["message"],'error',2000);
            });

        }
        
        function verifyCertificate() {
            showSwal('{{__("Sertifika Ekleniyor...")}}','info');
            var form = new FormData();
            form.append('path',path);
            form.append('server_hostname',$("#hostname").val());
            form.append('origin',$("#port").val());
            form.append('notification_id','{{request('notification_id')}}');
            form.append('server_id','{{request('server_id')}}');
            request('{{route('verify_certificate')}}',form,function (success) {
                var json = JSON.parse(success);
                showSwal(json["message"],'info',2000);
                setTimeout(function () {
                    partialPageRequest("{{route('settings')}}" + "#certificates");
                },1000);
            },function (errors) {
                var json = JSON.parse(errors);
                showSwal(json["message"],"error",2000);
            });
        }
    </script>
@endsection