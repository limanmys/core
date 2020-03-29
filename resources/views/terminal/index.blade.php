<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link href="{{asset('css/terminal/xterm.min.css')}}" rel="stylesheet" type="text/css"/>
</head>
<body onload="start('{{$id}}')">

<div class="container" style="height: 100%;">
    <div id="terminal" style="height: 100%"></div>
</div>

<script src="{{asset('js/xterm.min.js')}}"></script>
<script src="/js/terminal.js"></script>
</body>
</html>