@extends('layouts.app')

@section('content')

<div class="row">
    <div class="col-6">
    <nav aria-label="breadcrumb" style="display:block; width: 100%;">
        <ol class="breadcrumb" style="float:left;">
            <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
            <li class="breadcrumb-item"><a href="{{route('server_one', server()->id)}}">{{ server()->name }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ server()->name }} {{__(extension()->display_name)}} {{ __('eklentisi') }}</li>
        </ol>
        
    </nav>
    </div>
    <div class="col-6">
        <div id="ext_menu" style="float:right;">
                <button data-toggle="tooltip" title="{{__('Eklenti Ayarları')}}" class="btn btn-primary" onclick="window.location.href = '{{route('extension_server_settings_page',[
                    "server_id" => server()->id,
                    "extension_id" => extension()->id
                ])}}'"><i class="fa fa-cogs"></i></button>
                @if(count($tokens) > 0)
                <button data-toggle="tooltip" title="{{__('Sorgu Oluştur')}}" class="btn btn-primary" onclick="showRequestRecords()"><i class="fa fa-book"></i></button>
                @endif
                <button data-toggle="tooltip" title="{{__('Destek Al')}}" class="btn btn-primary" onclick="location.href = 'mailto:{{env('APP_NOTIFICATION_EMAIL')}}?subject={{env('BRAND_NAME')}} {{getVersion()}} - {{extension()->display_name}} {{extension()->version}}'"><i class="fas fa-headset"></i></button>
        </div>  
    </div>
</div>

@include('errors')    

<div class="card">
    <div class="card-body">
        <div class="tab-content">
            <div class="tab-pane fade show active" role="tabpanel" id="mainExtensionWrapper">
                <div class="spinner-grow text-primary"></div>
            </div>
        </div>
    </div>
</div>
@if(count($tokens) > 0)
<div class="float" onclick="toggleRequestRecord()" id="requestRecordButton">
    <i class="fas fa-video my-float"></i>
</div>

@component('modal-component',[
    "id" => "limanRequestsModal",
    "title" => "İstek Kayıtları"
])
<div class="limanRequestsWrapper">
    <div class="row">
        <div class="col-md-4">
        <ul class="list-group" id="limanRequestsList">
          
        </ul>
        </div>
        <div class="col-md-8">
            <p>{{__("Aşağıdaki komut ile Liman MYS'ye dışarıdan istek gönderebilirsiniz.Eğer SSL sertifikanız yoksa, komutun sonuna --insecure ekleyebilirsiniz.")}}</p>
            <b>{{__("Bu sorgu içerisinde ve(ya) sonucunda kurumsal veriler bulunabilir, sorumluluk size aittir.")}}</b>

            <div class="row">
                <div class="col-md-4" style="line-height: 2.25rem;">{{__("Kullanılacak Kişisel Erişim Anahtarı")}}</div>
                <div class="col-md-8">
                    <select id="limanRequestAccessToken" class="select2" onchange="clearCurlCommand()">
                        @foreach($tokens as $token)
                            <option value="{{$token['token']}}">{{$token['name']}}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <pre id="limanRequestCurlOutput"></pre>
        </div>
    </div>
</div>
@endcomponent
<style>

.float{
	position:fixed;
	width:30px;
	height:30px;
	bottom:20px;
	right:20px;
	background-color:grey;
	color:#FFF;
	border-radius:50px;
	text-align:center;
	box-shadow: 2px 2px 3px #999;
}

.my-float{
	line-height:30px;
    font-size : 15px;
}

pre {
    white-space: pre-wrap; 
    white-space: -moz-pre-wrap;
    white-space: -pre-wrap;
    white-space: -o-pre-wrap;
    word-wrap: break-word;
}
</style>

<script>
    function toggleRequestRecord(){
        var element = $("#requestRecordButton");
        limanRecordRequests = !limanRecordRequests;
        if(limanRecordRequests == true){
            element.css("backgroundColor","red");
        }else{
            element.css("backgroundColor","grey");
        }   
    }

    function showRequestRecords(){
        if(limanRequestList.length == 0){
            showSwal('{{ __("Lütfen önce bir sorguyu kaydedin.") }}',"error",2000);
            return;
        }
        var listElement = $("#limanRequestsList");
        var modalElement = $("#limanRequestsModal");
        listElement.html("");
        $.each(limanRequestList, function(index, entries) {
            listElement.append("<li onclick='showCurlCommand(this," + index + ")' class='list-group-item liman-request-item'>" + entries["target"] +"</li>")
        });
        modalElement.modal('show');
    }

    function clearCurlCommand(){
        $("#limanRequestCurlOutput").text("");
        $(".liman-request-item").removeClass("active");
    }

    function showCurlCommand(element,index){
        $(".liman-request-item").removeClass("active");
        $(element).addClass("active");
        $("#limanRequestCurlOutput").text(limanRequestBuilder(index,$("#limanRequestAccessToken").val()));
    }
</script>
@endif
<script>
    $(function(){
        var list = [];
        $("#quickNavBar li>a").each(function(){
            list.push($(this).text());
        });
        if((new Set(list)).size !== list.length){
            
        }
    })
    function API(target)
    {
        return "{{route('home')}}/extensionRun/" + target;
    }
    customRequestData["token"] = "{{ $auth_token }}";
    customRequestData["locale"] = "{{session()->get('locale')}}";
    request(API('{{request('target_function') ? request('target_function') : 'index'}}'),new FormData(), function (success){
        $("#mainExtensionWrapper").html(success);
        window.onload();
        $('.modal').on('shown.bs.modal', function () {
            $(this).find(".alert").fadeOut();
        });
    },function (error){ 
        let json = JSON.parse(error);
        showSwal(json.message,'error',2000);
    });
</script>
@endsection
