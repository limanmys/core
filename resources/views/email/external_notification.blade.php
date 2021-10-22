<div style="font-family: sans-serif; display: block; margin: auto; max-width: 900px;" class="main">
    <p>Merhaba,</p><br>
    <pre>{{ $notification->message }}</pre><br>
    <p>Bilginize.</p>
    <br><br>
    <p>Bu email <a href="https://liman.dev">Liman MYS</a> dış bildirim sisteminde <b>{{ explode("->", $notification->title)[1] ? explode("->", $notification->title)[1] : 'Liman' }}</b> tarafından oluşturulmuştur.</p>
</div>

<style>
  .main { background-color: white; }
  a:hover { border-left-width: 1em; min-height: 2em; }
</style>