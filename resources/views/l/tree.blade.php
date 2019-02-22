@php($random = str_random(20))
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
        {{$click}}(uiGetParents(data));
    });
    function uiGetParents(loSelectedNode) {
        try {
            let lnLevel = loSelectedNode.node.parents.length;
            let lsSelectedID = loSelectedNode.node.id;
            let loParent = $("#" + lsSelectedID);
            let lsParents =  loSelectedNode.node.text + ',';
            for (let ln = 0; ln <= lnLevel -1 ; ln++) {
                loParent = loParent.parent().parent();
                if (loParent.children()[1] !== undefined) {
                    lsParents += loParent.children()[1].text + ",";
                }
            }
            if (lsParents.length > 0) {
                lsParents = lsParents.substring(0, lsParents.length - 1);
            }
            return lsParents;
        }
        catch (err) {}
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