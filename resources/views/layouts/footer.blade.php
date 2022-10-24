<footer class="main-footer shadow d-flex align-items-center justify-content-between" style="border-top: none;">
    <div class="d-none d-sm-flex">
        <span data-toggle="tooltip" data-original-title="En iyi Chrome >100 ve Firefox >100 ile çalışmaktadır."><b class="mr-1">{{__("Liman Versiyonu")}}: </b> {{ getVersion() . " - " . getVersionCode() }}</span>
        <a href="https://docs.liman.dev" target="_blank"  class="ml-3 px-3" style="color: #869099; border-left: 1px #eee solid;">{{ __("Dokümantasyon") }}</a>
        <a href="https://liman.havelsan.com.tr/iletisim/" target="_blank" class="pl-3" style="color: #869099; border-left: 1px #eee solid;">{{ __("İletişim") }}</a>
    </div>
    <a href="https://aciklab.org" target="_blank">
        <img src="{{ asset('/images/aciklab-footer.svg') }}" alt="HAVELSAN" style="max-height: 30px; margin-top: -12px; margin-bottom: -8px;">
    </a>
</footer>