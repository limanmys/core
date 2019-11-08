@php($random = str_random(20))

<div class="modal fade" id="@isset($id){{$id}}@endisset">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">
                    @isset($title)
                        {{__($title)}}
                    @endisset
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                @isset($search)
                    <input class="form-control" type="search" onchange="searchTree()" id="q"/>
                @endisset
                <div id="{{$random}}"></div>
            </div>
        </div>
    </div>
</div>

<script>

    $('#{{$random}}').jstree({
        "plugins": [
            "contextmenu",
            "search",
        ],
        'core': {
            'data': [
                @include("l.folder",["files" => $data])
            ],
            "check_callback": true
        },
        @isset($menu)
        'contextmenu' : {
            items: customMenu
        }
        @endisset
    }).on('select_node.jstree', function (e, data) {

    });
    @isset($search)
        function searchTree() {
        $('#{{$random}}').jstree(true).search($("#q").val());
        }
    @endisset

    @isset($menu)
        function customMenu() {
            return {
                @foreach($menu as $item)
                    '{{random_int(1,100)}}' : {
                        label : "{{__(explode(':', $item)[0])}}",
                        action : {{explode(':',$item)[1]}}
                    }
                @endforeach
            };
        }
    @endisset
</script>