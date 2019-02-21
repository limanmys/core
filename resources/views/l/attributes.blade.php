@php($rand = str_random(3))
<div class="box box-default collapsed-box">
    <div class="box-header with-border">
        <h3 class="box-title">{{$title}}</h3>
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i>
            </button>
        </div>
    </div>
    <div class="box-body">
        <button class="btn btn-primary" onclick="toogleEdit('.{{$rand}}');$('#{{$rand}}_save').toog">{{__("Düzenle")}}</button><br>
        <button id="{{$rand}}_save" class="btn btn-primary" onclick="saveData('.{{$rand}}')">{{__("Kaydet")}}</button><br>
        @foreach ($data as $key=>$value)
            @include('l.attribute',[
                "title" => $key,
                "id" => $value,
                "class" => $rand
            ])
        @endforeach
    </div>
</div>