@php($random = (isset($id)? $id : str_random(20)))
<input class="form-control" type="search" onchange="search{{$random}}()" id="q"/>
<br>
<div id="{{$random}}"></div>
<script>
    $('#{{$random}}').jstree({
        "plugins": [
            @isset($menu)
            "contextmenu",
            @endisset
            "search",
            "state"
        ],
        'core': {
            'data': [
                @include("l.folder",["files" => $data])
            ],
            "check_callback": true
        },
        @isset($menu)
        'contextmenu': {
            items: customMenu
        }
        @endisset
    }).on('select_node.jstree', function (e, data) {
        @isset($click)
                {{$click}}(getPath());
        @endisset
    });

    function getPath() {
        @isset($ldapStyle)
            let path = $('#{{$random}}').jstree().get_path($('#{{$random}}').jstree("get_selected")[0], ',',true);
            return path.split(",").reverse().join(',');
        @else
            return $('#{{$random}}').jstree().get_path($('#{{$random}}').jstree("get_selected")[0], ',',true)
        @endisset
        
    }

    function search{{$random}}() {
        $('#{{$random}}').jstree(true).search($("#q").val());
    }

    @isset($menu)
    function customMenu() {
        return {
            @foreach($menu as $key=>$item)
            '{{random_int(1,100)}}': {
                label: "{{__($key)}}",
                action: {{$item}}
            }
            @endforeach
        };
    }
    @endisset
</script>