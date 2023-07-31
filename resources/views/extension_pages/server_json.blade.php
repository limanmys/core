<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liman MYS</title>
    <link rel="stylesheet" href="{{url(mix('/css/liman.css'))}}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="server_id" content="{{request('server_id') ? request('server_id') : ''}}">
    <meta name="extension_id" content="{{request('extension_id') ? request('extension_id') : ''}}">
</head>

<body>
    <script>
        module = {}
        window.addEventListener("contextmenu", e => e.preventDefault());
        function API(target)
        {
            return "{{route('home')}}/engine/" + target;
        }
    </script>
    <script src="{{url(mix('/js/liman.js'))}}"></script>

    <style>
        html, body {
            background: transparent !important;
        }
        #app {
            margin: 0 !important;
        }
    </style>

    @include('errors')
    @if(!isset($dbJson["skeleton"]) || !$dbJson["skeleton"])
        <div class="card-body">
            <div class="tab-content">
    @endif
                <div class="tab-pane fade show active" role="tabpanel" id="mainExtensionWrapper">
                    @if (isset($dbJson["preload"]) && $dbJson["preload"])
                    <script>
                        customRequestData["token"] = "{{ $auth_token }}";
                        customRequestData["locale"] = "{{ app()->getLocale() }}";
                        @if(!isset($dbJson["vite"]) || !$dbJson["vite"])
                        window.onload();
                        $('.modal').on('shown.bs.modal', function() {
                            $(this).find(".alert").fadeOut();
                        });
                        @endif
                    </script>
                    {!! $extContent !!}
                    @else
                    <div class="loader-wrapper d-flex" style="min-height: 100vh; align-items: center; justify-content: center;">
                        <div class="spinner-border" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>
                    @endif
                </div>
    @if(!isset($dbJson["skeleton"]) || !$dbJson["skeleton"])
            </div>
        </div>
    @endif

    @if(!isset($dbJson["preload"]) || !$dbJson["preload"])
    <script>
        $(function() {
            var list = [];
            $("#quickNavBar li>a").each(function() {
                list.push($(this).text());
            });
            if ((new Set(list)).size !== list.length) {

            }
        })

        customRequestData["token"] = "{{ $auth_token }}";
        customRequestData["locale"] = "{{session()->get('locale')}}";
        let formData = new FormData();
        formData.append("lmntargetFunction", "{{request('target_function') ? request('target_function') : 'index'}}")
        request(API("{{request('target_function') ? request('target_function') : 'index'}}"), formData, function(success) {
            $(".loader-wrapper").fadeOut(300, function() {
                $("#mainExtensionWrapper").html(success);
            })
            @if(!isset($dbJson["vite"]) || !$dbJson["vite"])
            window.onload();
            $('.modal').on('shown.bs.modal', function() {
                $(this).find(".alert").fadeOut();
            });
            @endif
        }, function(error) {
            let json = JSON.parse(error);
            showSwal(json.message, 'error', 2000);
        });
    </script>
    @endif

    <script>
        window.onload = function() {
            $(".dropdown-menu").on('click', 'a.dropdown-item', function() {
                $(this).closest('.dropdown').find('.dropdown-toggle').html($(this).html() + '<span class="caret"></span>');
            });
            $(".nav.nav-tabs a").on('click', function() {
                window.location.hash = $(this).attr("href");
            });
            var title = $(".breadcrumb-item.active").text();
            if (title != "") {
                document.title = title + " / Liman";
            }
            initialPresets();
        };

        function publicPath(path, extension_id = null) {
            if (extension_id == null) {
                extension_id = $("meta[name=extension_id]").attr("content");
            }
            return "{{ route('home') }}/eklenti/" + extension_id + "/public/" + path;
        }

        function initialPresets() {
            $('table').not('.notDataTable').not(".bx--data-table").not(".n-data-table-table").DataTable({
                autoFill: true,
                bFilter: true,
                destroy: true,
                "language": {
                    url: "{{asset(__('/turkce.json'))}}"
                }
            });
            $('.js-example-basic-multiple,.js-example-basic-single,.select2').select2({
                width: 'resolve',
                theme: 'bootstrap4',
            });
            $(":input").inputmask();
        }
    </script>
</body>

</html>