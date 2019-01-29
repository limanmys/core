@if(!isset($name_list) && !isset($value_list) && !isset($display))
    @php(__("Tablo Oluşturulamadı."))
@else
    @php($rand = str_random(10))
    <table class="table" id="{{$rand}}">
        <thead>
        <tr>
            @foreach($name_list as $name)
                @if($name == "*hidden*")
                    <th scope="col" hidden>{{ __($name) }}</th>
                @else
                    <th scope="col">{{ __($name) }}</th>
                @endif
            @endforeach
        </tr>
        </thead>
        <tbody data-toggle="modal" data-target="#duzenle">
        @foreach ($value_list as $value)
            <tr id="{{str_random(10)}}">
                @foreach($display as $item)
                    @if($item == "server_id" || $item == "extension_id" || $item == "script_id")
                        <td id="{{$item}}" hidden>{{$value->__get($item)}}</td>
                    @elseif(count(explode(':',$item)) > 1)
                        <td id="{{explode(':',$item)[1]}}" hidden>{{$value->__get(explode(':',$item)[0])}}</td>
                    @else
                        <td id="{{$item}}">{{$value->__get($item)}}</td>
                    @endif
                @endforeach
            </tr>
        @endforeach
        </tbody>
    </table>
    @if(isset($menu_items))
        <script>
            $.contextMenu({
                selector: '#{{$rand}} tr',
                callback: function (key, options) {
                    let target = $("#" + key);
                    $("#" + key + " input , #" + key + ' select').each(function (index, value) {
                        let element_value = $("#" + options.$trigger[0].getAttribute("id") + " #" + value.getAttribute('name')).html();
                        $("#" + key + " select[name='" + value.getAttribute('name') + "']" + " , "
                            + "#" + key + " input[name='" + value.getAttribute('name') + "']").val(element_value);
                    });
                    target.modal('show');
                },
                items: {
                    @foreach($menu_items as $name=>$config)
                        "{{$config['target']}}" : {name: "{{$name}}" , icon: "{{$config['icon']}}"},
                    @endforeach
                }
            });
        </script>
    @endif
@endif