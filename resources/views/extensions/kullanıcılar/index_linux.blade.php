<?php
    $conn = _init($server->ip_address, $server->port, "SambaPardus01", "cn=admin,dc=ldap,dc=lab");

    $results = _search($conn, "dc=ldap,dc=lab" , "(objectclass=posixAccount)", ["dn"]);
?>
<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>

<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/jstree/3.3.7/themes/default/style.min.css" />
<script src="//cdnjs.cloudflare.com/ajax/libs/jstree/3.3.7/jstree.min.js"></script>

@include('modal-button',[
    "class" => "btn-primary",
    "target_id" => "add_user",
    "text" => "Kullanıcı Ekle"
])<br><br>

<div class="container">
    <div class="row">
        <div class="col">
            <input type="search" onchange="search()" id="q" /><br><br>
            <div id="ldap_tree"></div>
        </div>
        <div class="col pt-3">
            <table class="table">
                <tbody>
                <tr>
                    <td>UID</td>
                    <td id="uid"></td>
                </tr>
                <tr>
                    <td>UID Number</td>
                    <td id="uidnumber"></td>
                </tr>
                <tr>
                    <td>Home Directory</td>
                    <td id="homedirectory"></td>
                </tr>
                <tr>
                    <td>GID Number</td>
                    <td id="gidnumber"></td>
                </tr>
                <tr>
                    <td>CN</td>
                    <td id="cn"></td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function asd(){
        $('#ldap_tree').jstree({
            "plugins" : [
                "contextmenu",
                "search"
            ],
            'core' : {
                'data' : [
                    @include("__system__.folder",["files" => $results])
                ]
            },
            'contextmenu' : {
                "items" : items = {
                    addUser: {
                        label: "{{__("Kullanıcı Ekle")}}",
                        action: function(obj) {
                            console.log(obj);
                        },
                        icon: "fa fa-cog"
                    },
                    deleteUser: {
                        label: "{{__("Kullanıcıyı Sil")}}",
                        action: function(obj) {
                            console.log(obj);
                        },
                        icon: "fa fa-trash"
                    }
                }
            }
        });
    }
    asd();
</script>

<script>
    function search(){
        $("#ldap_tree").jstree(true).search($("#q").val());
    }
</script>

@include('modal',[
        "id"=>"add_user",
        "title" => "Kullanıcı Ekle",
        "url" => route('extension_function_api',[
            "extension_id" => $extension->_id,
            "function_name" => "addUser"
        ]),
        "next" => "reload",
        "inputs" => [
            "İsim" => "firstname:text",
            "Soyisim" => "surname:text",
            "Tüm İsim" => "fullname:text",
            "Kullanıcı Adı" => "username:text",
            "Parola" => "password:password",
            "İlk oturum açılışında değişime zorla" => "forcechangepass:checkbox",
            "Parola süresi asla bitmesin" => "neverexpired:checkbox",
            "Hesabı kilitle" => "lockaccount:checkbox",
            "Kullanıcı parolasını değiştiremez" => "cantchangepass:checkbox",
            "LDAP PATH" => "tree_path:text",
            "Sunucu Id:$server->_id" => "server_id:hidden"
        ],
        "submit_text" => "Yetkilendir"
    ])