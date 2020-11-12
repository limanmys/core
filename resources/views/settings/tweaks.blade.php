<div class="row">
    <div class="col-5 col-sm-3">
        <div class="nav flex-column nav-tabs h-100" id="vert-tabs-tab" role="tablist" aria-orientation="vertical">
            <a class="nav-link active" data-toggle="pill" href="#general" role="tab" aria-controls="vert-tabs-home" aria-selected="true">{{__("Genel")}}</a>
            <a class="nav-link" data-toggle="pill" href="#market" role="tab" aria-controls="vert-tabs-profile" aria-selected="false">{{__("Market Ayarları")}}</a>
            <a class="nav-link" data-toggle="pill" href="#mail" role="tab" aria-controls="vert-tabs-messages" aria-selected="false">{{__("Mail Ayarları")}}</a>
            <a class="nav-link" data-toggle="pill" href="#advanced" role="tab" aria-controls="vert-tabs-settings" aria-selected="false">{{__("Gelişmiş")}}</a>
        </div>
    </div>
    <div class="col-7 col-sm-9">
        <div class="tab-content" id="vert-tabs-tabContent">
            <div class="tab-pane text-left fade active show" id="general" role="tabpanel" aria-labelledby="vert-tabs-home-tab">
                <div class="card-body">
                    <div class="form-group">
                        <label for="BRAND_NAME">{{__("Özel İsim")}}</label><br>
                        <small>{{__("Giriş ekranında gözükecek özel isim.")}}</small>
                        <input type="text" class="form-control liman_env" id="BRAND_NAME">
                    </div>
                    <div class="form-group">
                        <label for="APP_NOTIFICATION_EMAIL">{{__("İletişim Maili")}}</label><br>
                        <small>{{__("Eklenti sayfasında kullanıcıların yardım alması için oluşturulan mail adresi.")}}</small>
                        <input type="text" class="form-control liman_env" id="APP_NOTIFICATION_EMAIL">
                    </div>
                    <div class="form-group">
                        <label for="APP_URL">{{__("Liman'ın Adresi")}}</label><br>
                        <small>{{__("Maillerde ve bildimlerde eklenmesi gereken Liman'ın adresi")}}</small>
                        <input type="text" class="form-control liman_env" id="APP_URL">
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="market" role="tabpanel" aria-labelledby="vert-tabs-profile-tab">
                <div class="card-body">
                    <div class="form-group">
                        <label for="MARKET_URL">{{__("Market Adresi")}}</label><br>
                        <small>{{__("Liman'ın güncellemeleri kontrol edeceği market adresi.")}}</small>
                        <input type="text" class="form-control liman_env" id="MARKET_URL">
                    </div>
                    <div class="form-group">
                        <label for="MARKET_CLIENT_ID">{{__("Market Client ID")}}</label><br>
                        <input type="text" class="form-control liman_env" id="MARKET_CLIENT_ID">
                    </div>
                    <div class="form-group">
                        <label for="MARKET_CLIENT_SECRET">{{__("Market Secret Key")}}</label><br>
                        <input type="text" class="form-control liman_env" id="MARKET_CLIENT_SECRET">
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="mail" role="tabpanel" aria-labelledby="vert-tabs-messages-tab">
                <div class="card-body">
                    <div class="form-group">
                        <label for="MAIL_ENABLED">{{__("Mail Sistemi Durumu")}}</label><br>
                        <select id="MAIL_ENABLED" class="select2 liman_env">
                            <option value="true">{{__("Aktif")}}</option>
                            <option value="false">{{__("Pasif")}}</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="MAIL_HOST">{{__("Mail Sunucusu")}}</label><br>
                        <input type="text" class="form-control liman_env" id="MAIL_HOST">
                    </div>
                    <div class="form-group">
                        <label for="MAIL_PORT">{{__("Mail Portu")}}</label><br>
                        <input type="number" class="form-control liman_env" id="MAIL_PORT">
                    </div>
                    <div class="form-group">
                        <label for="MAIL_USERNAME">{{__("Mail Kullanıcı Adı")}}</label><br>
                        <input type="text" class="form-control liman_env" id="MAIL_USERNAME">
                    </div>
                    <div class="form-group">
                        <label for="MAIL_PASSWORD">{{__("Mail Parolası")}}</label><br>
                        <input type="password" class="form-control" id="MAIL_PASSWORD">
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="advanced" role="tabpanel" aria-labelledby="vert-tabs-settings-tab">
                <div class="card-body">
                    <div class="form-group">
                        <label for="APP_DEBUG">{{__("Debug Modu")}}</label><br>
                        <small>{{__("Liman'ın debug modunu aktifleştir.")}}</small>
                        <select id="APP_DEBUG" class="select2 liman_env">
                            <option value="true">{{__("Aktif")}}</option>
                            <option value="false">{{__("Pasif")}}</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="EXTENSION_DEVELOPER_MODE">{{__("Eklenti Geliştirici Modu")}}</label><br>
                        <small>{{__("Eklenti geliştirici modunu aktifleştir.")}}</small>
                        <select id="EXTENSION_DEVELOPER_MODE" class="select2 liman_env">
                            <option value="true">{{__("Aktif")}}</option>
                            <option value="false">{{__("Pasif")}}</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="LOG_LEVEL">{{__("Log Seviyesi")}}</label><br>
                        <small>{{__("Log Seviyesini düzenle.")}}</small>
                        <select id="LOG_LEVEL" class="select2 liman_env">
                            <option value="emergency">{{__("Emergency")}}</option>
                            <option value="alert">{{__("Alert")}}</option>
                            <option value="critical">{{__("Critical")}}</option>
                            <option value="error">{{__("Error")}}</option>
                            <option value="warning">{{__("Warning")}}</option>
                            <option value="notice">{{__("Notice")}}</option>
                            <option value="info">{{__("Info")}}</option>
                            <option value="debug">{{__("Debug")}}</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <button class="btn btn-success" onclick="setLimanTweaks()">{{__("Kaydet")}}</button>
</div>
<script>
    function getLimanTweaks(){
        showSwal("Yükleniyor...","info");
        request("{{route("get_liman_tweaks")}}",new FormData(),function (success){
            let json = JSON.parse(success);
            $.each( json.message, function( key, value ) {
                $("#" + key).val(value).trigger('change');
            });
            Swal.close();
        },function (error) {
            let json = JSON.parse(error);
            showSwal(json.message,"error",2000);
        });
    }

    function setLimanTweaks(){
        showSwal("Kaydediliyor...","info");
        let form = new FormData();
        $(".liman_env").each(function(){
            let current = $(this);
            form.append(current.attr("id"),current.val());
        });
        let mail_password = $("#MAIL_PASSWORD");

        if(mail_password.val() !== ""){
            form.append("MAIL_PASSWORD",mail_password.val());
        }

        request("{{route("set_liman_tweaks")}}",form,function (success){
            let json = JSON.parse(success);
            showSwal(json.message,"success",2000);
            setTimeout(function () {
                getLimanTweaks();
            },2000);
        },function (error) {
            let json = JSON.parse(error);
            showSwal(json.message,"error",2000);
        });
    }
</script>