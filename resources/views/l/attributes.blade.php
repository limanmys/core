
@php
    $rand = str_random(3);
    if(extensionDb("server_type") == "Active Directory" || extensionDb("server_type") == "Samba"){
        $data = $data["windows"];
    }else{
        $data = $data["linux"];
    }
@endphp
@isset($open)
    <div class="box box-default box-solid">
        @else
            <div class="box box-default box-solid collapsed-box">
                @endisset
                <div class="box-header with-border">
                    <h3 class="box-title">{{$title}}</h3>
                    <div class="box-tools pull-right">
                        <button data-toggle="tooltip" title="Ekle" type="button" class="btn btn-box-tool" data-widget="collapse"><i data-toggle="tooltip" title="Ekle" class="fa fa-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    @isset($onSave)
                    <button id="{{$rand}}_edit" class="btn btn-primary"
                            onclick="edit_{{$rand}}()">{{__("Düzenle")}}</button>
                    <button id="{{$rand}}_save" class="btn btn-success" onclick="save_{{$rand}}()"
                            disabled>{{__("Kaydet")}}</button>
                    <br>
                    @endisset
                    @foreach ($data as $key=>$value)
                        @include('attribute',[
                            "title" => $key,
                            "id" => $value,
                            "class" => $rand
                        ])
                    @endforeach
                </div>
            </div>
            <script>
                function edit_{{$rand}}(){
                    toogleEdit('.{{$rand}}');
                    $('#{{$rand}}_edit').attr('disabled',true);
                    $('#{{$rand}}_save').attr('disabled',false);
                }

                function save_{{$rand}}(){
                    toogleEdit('.{{$rand}}');
                    $('#{{$rand}}_edit').attr('disabled',false);
                    $('#{{$rand}}_save').attr('disabled',true);
                    @isset($onSave)
                        {{$onSave}}('.{{$rand}}');
                    @endisset
                }
            </script>