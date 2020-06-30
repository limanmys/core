<!doctype html>
<html lang="">
<head>
    <title>{{ __('Yönlendiriliyor...') }}</title>
    <script>
        window.setTimeout(function () {
            window.location = "{{ $url }}";
        }(), 1000);
    </script>
    <noscript>
        <meta http-equiv="refresh" content="0;url={{ $url }}" />
    </noscript>
</head>
<body>
    Yönlendiriliyorsunuz, 
    eğer otomatik yönlendirilmezseniz lütfen <a href="{{ $url }}">buraya</a> tıklayın.
</body>
</html>