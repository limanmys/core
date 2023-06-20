<div class="row">
    <div class="col-5 col-sm-3">
        <div class="nav flex-column nav-tabs h-100" id="vert-tabs-tab" role="tablist" aria-orientation="vertical">
            <a class="nav-link active" data-toggle="pill" href="#general" role="tab" aria-controls="vert-tabs-home" aria-selected="true">{{__("Genel")}}</a>
            <a class="nav-link" data-toggle="pill" href="#market" role="tab" aria-controls="vert-tabs-profile" aria-selected="false">{{__("Market Ayarları")}}</a>
            <a class="nav-link" data-toggle="pill" href="#mail" role="tab" aria-controls="vert-tabs-messages" aria-selected="false">{{__("Mail Ayarları")}}</a>
            <a class="nav-link" data-toggle="pill" href="#keycloak" role="tab" aria-controls="vert-tabs-keycloak" aria-selected="false">{{__("Keycloak Ayarları")}}</a>
            <a class="nav-link" data-toggle="pill" href="#advanced" role="tab" aria-controls="vert-tabs-settings" aria-selected="false">{{__("Gelişmiş")}}</a>
        </div>
    </div>
    <div class="col-7 col-sm-9">
        <div class="tab-content" id="vert-tabs-tabContent">
            <div class="tab-pane text-left fade active show" id="general" role="tabpanel" aria-labelledby="vert-tabs-home-tab">
                <div>
                    <div class="form-group">
                        <label for="APP_LANG">{{__("Sistem Dili")}}</label><br>
                        <small>{{__("Sistemin genel dil ayarı. Dil seçimi yapmamış kullanıcıların ayarlarını da değiştirir.")}}</small>
                        <select name="APP_LANG" id="APP_LANG" class="form-control liman_env select2">
                            @foreach (getLanguageNames() as $short => $long)
                            <option value="{{ $short }}">{{ $long }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="BRAND_NAME">{{__("Özel İsim")}}</label><br>
                        <small>{{__("Giriş ekranında gözükecek özel isim.")}}</small>
                        <input type="text" class="form-control liman_env" id="BRAND_NAME">
                    </div>
                    <div class="form-group">
                        <label for="OTP_ENABLED">{{__("Google 2FA Sistemi")}}</label><br>
                        <select id="OTP_ENABLED" class="select2 liman_env">
                            <option value="true">{{__("Aktif")}}</option>
                            <option value="false">{{__("Pasif")}}</option>
                        </select>
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
                    <div class="form-group">
                        <label for="icon-file-input">{{__("Giriş Ekranı Logosu")}}</label><br>
                        <small>{{__("Giriş ekranının üzerinde görünmesi için kendi logonuzu ekleyebilirsiniz.")}}</small>
                    </div>
                    <div class="input-group" id="icon-file-input" style="margin-top: -15px;">
                        <input type="text" id="icon-selected-file" class="form-control" readonly="">
                        <span class="input-group-btn">
                            <button type="button" class="btn btn-labeled btn-secondary" id="icon-browse" style="border-radius: 0px;">{{ __('Gözat') }}</button>
                        </span>
                        <span class="input-group-btn">
                            <button type="button" class=" btn btn-labeled btn-primary" id="icon-upload" disabled="" style="border-radius: 0px;">{{ __('Yükle') }}</button>
                        </span>
                        <input type="file" name="" id="icon-upload-file" style="display:none;">
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="market" role="tabpanel" aria-labelledby="vert-tabs-profile-tab">
                <div>
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
                <div>
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
                    <div class="form-group">
                        <label for="MAIL_ENCRYPTION">{{__("Mail Şifreleme Methodu")}}</label><br>
                        <select id="MAIL_ENCRYPTION" class="select2 liman_env">
                            <option value="tls">{{__("TLS")}}</option>
                            <option value="ssl">{{__("SSL")}}</option>
                            <option value="null">{{__("Hiçbiri")}}</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 0">
                        <button class="btn btn-primary" 
                            onclick="testMailSettings()"
                            style="text-transform: uppercase;
                            font-weight: 600;
                            width: 100%;"><i class="fa-solid fa-circle-play fa-lg mr-1"></i> Test</button>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="keycloak" role="tabpanel" aria-labelledby="vert-tabs-keycloak-tab">
                <div>
                    <div class="form-group">
                        <label for="KEYCLOAK_ACTIVE">{{__("Keycloak Durumu")}}</label><br>
                        <select id="KEYCLOAK_ACTIVE" class="select2 liman_env">
                            <option value="true">{{__("Aktif")}}</option>
                            <option value="false">{{__("Pasif")}}</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="KEYCLOAK_CLIENT_ID">{{__("Client ID")}}</label><br>
                        <input type="text" class="form-control liman_env" id="KEYCLOAK_CLIENT_ID">
                    </div>
                    <div class="form-group">
                        <label for="KEYCLOAK_CLIENT_SECRET">{{__("Client Secret")}}</label><br>
                        <input type="password" class="form-control liman_env" id="KEYCLOAK_CLIENT_SECRET">
                    </div>
                    <div class="form-group">
                        <label for="KEYCLOAK_REDIRECT_URI">{{__("Redirect URI")}}</label><br>
                        <input type="text" class="form-control liman_env" id="KEYCLOAK_REDIRECT_URI">
                    </div>
                    <div class="form-group">
                        <label for="KEYCLOAK_BASE_URL">{{__("Base URL")}}</label><br>
                        <input type="text" class="form-control liman_env" id="KEYCLOAK_BASE_URL">
                    </div>
                    <div class="form-group">
                        <label for="KEYCLOAK_REALM">{{__("Realm")}}</label><br>
                        <input type="text" class="form-control liman_env" id="KEYCLOAK_REALM">
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="advanced" role="tabpanel" aria-labelledby="vert-tabs-settings-tab">
                <div>
                    <div class="form-group">
                        <label for="EXTENSION_TIMEOUT">{{__("İstek Zaman Aşımı Süresi")}}</label><br>
                        <input type="text" class="form-control liman_env" id="EXTENSION_TIMEOUT">
                    </div>
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
                        <label for="NEW_LOG_LEVEL">{{__("Log Seviyesi")}}</label><br>
                        <small>{{__("Log Seviyesini düzenle.")}}</small>
                        <select id="NEW_LOG_LEVEL" class="select2 liman_env">
                            <option value="1">{{__("Minimal")}}</option>
                            <option value="2">{{__("Eklenti Log")}}</option>
                            <option value="3">{{__("Detaylı Eklenti Logları")}}</option>
                            <option value="0">{{__("Tüm İşlemleri Logla")}}</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="LDAP_IGNORE_CERT">{{__("Ldap Sertifika Kontrolünü Devre Dışı Bırak")}}</label><br>
                        <select id="LDAP_IGNORE_CERT" class="select2 liman_env">
                            <option value="true">{{__("Aktif")}}</option>
                            <option value="false">{{__("Pasif")}}</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-12 mt-4">
        <button class="btn btn-success float-right" onclick="setLimanTweaks()">{{__("Kaydet")}}</button>
    </div>
</div>
<script>
    function getLimanTweaks(){
        showSwal("{{ __('Yükleniyor...') }}","info");
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
        showSwal("{{ __('Kaydediliyor...') }}","info");
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
        },function (error) {
            let json = JSON.parse(error);
            showSwal(json.message,"error",2000);
        });
    }

    function testMailSettings(){
        showSwal("{{ __('Yükleniyor...') }}", "info");
        let form = new FormData();
        $(".liman_env").each(function(){
            let current = $(this);
            form.append(current.attr("id"), current.val());
        });
        let mail_password = $("#MAIL_PASSWORD");

        if (mail_password.val() !== "") {
            form.append("MAIL_PASSWORD",mail_password.val());
        }

        request("{{route("test_mail_settings")}}", form, function (success) {
            let json = JSON.parse(success);
            Swal.fire("", json.message, "success");
        },function (error) {
            let json = JSON.parse(error);
            Swal.fire("", json.message, "error");
        });
    }

    jQuery(document).ready(function ($) {
        var uploadButton = $('#icon-upload'),
        selectedFile = $('#icon-selected-file');
        
        $('#icon-file-input').on('change', function (e) {
        var name = e.target.value.split('\\').reverse()[0];
    
        if (name) {
            selectedFile.val(name);
            uploadButton.attr('disabled', false);
        } else {
            selectedFile.val('');
            uploadButton.attr('disabled', true);
        }
        });

        $('#icon-browse, #icon-selected-file').click(function(){
            $('#icon-upload-file').click();
        });

    });

    $( "#icon-upload" ).click(function() {
        var selectedFile = $('#icon-upload-file').prop('files');
        let data = new FormData();
        data.append("photo", selectedFile[0])
        request("{{ route('upload_login_logo') }}", data, function (response) {
            response = JSON.parse(response);
            showSwal(response.message, "success", 2000);
        }, function (error) {
            let response = JSON.parse(error);
            showSwal(response.message, "error", 2000);
        });
    });
</script>