<div class="form-group">
    <label for="NAV_SERVER_COUNT">{{__("Sol Menü Sunucu Sayısı")}}</label><br>
    <small>{{__("Sol menüde gösterilecek sunucu adeti.")}}</small>
    <input type="text" class="form-control liman-log-forwarding" id="NAV_SERVER_COUNT" name="NAV_SERVER_COUNT">
</div>
<div class="form-group">
    <label for="BRAND_NAME">{{__("Özel İsim")}}</label><br>
    <small>{{__("Giriş ekranında gözükecek özel isim.")}}</small>
    <input type="text" class="form-control liman-log-forwarding" id="BRAND_NAME">
</div>
<div class="form-group">
    <label for="OTP_ENABLED">{{__("Google 2FA Sistemi")}}</label><br>
    <select id="OTP_ENABLED" class="select2 liman-log-forwarding">
        <option value="true">{{__("Aktif")}}</option>
        <option value="false">{{__("Pasif")}}</option>
    </select>
</div>
<div class="col-md-12 mt-4">
    <button class="btn btn-success float-right" onclick="setLogForwarding()">{{__("Kaydet")}}</button>
</div>

<script>

function setLogForwarding(){
    showSwal("{{ __('Kaydediliyor...') }}","info");
    let form = new FormData();
    $(".liman-log-forwarding").each(function(){
        let current = $(this);
        form.append(current.attr("id"),current.val());
    });
  
    request("{{route('set_log_forwarding')}}",form,function (success){
        let json = JSON.parse(success);
        showSwal(json.message,"success",2000);
    },function (error) {
        let json = JSON.parse(error);
        showSwal(json.message,"error",2000);
    });
}
</script>