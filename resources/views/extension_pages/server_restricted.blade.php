@extends('layouts.master')
@include('layouts.navbar')

@section('body')

<div class="card-body">
        <div class="tab-content">
            <div class="tab-pane fade show active" role="tabpanel" id="mainExtensionWrapper">
                <div class="spinner-grow text-primary"></div>
            </div>
        </div>
    </div>

<script>
    function API(target)
    {
        return "{{route('home')}}/extensionRun/" + target;
    }
    customRequestData["token"] = "{{ $auth_token }}";
    customRequestData["locale"] = "{{session()->get('locale')}}";
    request(API('{{request('target_function') ? request('target_function') : 'index'}}'),new FormData(), function (success){
        $("#mainExtensionWrapper").html(success);
        initialPresets();
    },function (error){ 
        let json = JSON.parse(error);
        showSwal(json.message,'error',2000);
    });
</script>
@stop