<button class="btn btn-primary" onclick="location.href = '{{route("cron_mail_add_page")}}'">{{__("Yeni Mail Ayarı Ekle")}}</button><br><br>
@include('table',[
    "id" => "cronMailsTable",
    "value" => $cronMails,
    "title" => [
        "Takip Edilecek Kullanıcı", "Gönderilecek Mail", "Sunucu", "Eklenti", "Özellik", "*hidden*"
    ],
    "display" => [
        "username", "to", "server_name", "extension_name", "tag_string" , "id:cron_id"
    ],
    "menu" => [
        "Şimdi Gönder" => [
            "target" => "sendCronMail",
            "icon" => "fa-paper-plane"
        ],
        "Düzenle" => [
            "target" => "editCronMail",
            "icon" => " context-menu-icon-edit"
        ],
        "Sil" => [
            "target" => "removeCronMail",
            "icon" => " context-menu-icon-delete"
        ]
    ],
])

@include('modal',[
    "id"=>"sendCronMail",
    "title" =>"Şimdi Gönder",
    "url" => route('cron_mail_now'),
    "next" => "reload",
    "text" => "Bu maili şimdi göndermek istediğinize emin misiniz?",
    "inputs" => [
        "-:-" => "cron_id:hidden"
    ],
    "submit_text" => "Gönder"
])

@include('modal',[
    "id"=>"removeCronMail",
    "title" =>"Mail Ayarını Sil",
    "url" => route('cron_mail_delete'),
    "next" => "reload",
    "text" => "Bu maili ayarını silmek istediğinize emin misiniz?",
    "inputs" => [
        "-:-" => "cron_id:hidden"
    ],
    "submit_text" => "Sil"
])

@component('modal-component',[
    "id" => "addServerGroup",
    "title" => "Sunucuları Gruplama",
    "footer" => [
        "class" => "btn-success",
        "onclick" => "addServerGroup()",
        "text" => "Ekle"
    ],
])
@endcomponent

<script>
    function editCronMail(node)
    {
        let id = $(node).find("#cron_id").html();

        location.href = `/mail/edit/${id}`;
    }
</script>