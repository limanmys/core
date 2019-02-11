@php($random = str_random(20))

<div class="modal fade" id="@isset($id){{$id}}@endisset" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span></button>
                <h3 class="modal-title">
                    @isset($title)
                        {{__($title)}}
                    @endisset
                </h3>
            </div>
            <div class="modal-body">
                @isset($search)
                    <input class="form-control" type="search" onchange="searchTree()" id="q"/>
                @endisset
                <div id="{{$random}}"></div>
            </div>
            <div class="modal-footer">
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
        alert('clicked');
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