<?php
    $conn = _init();

    $results = _search($conn , "(objectclass=posixAccount)", ["dn"]);
?>
<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>

<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/jstree/3.3.7/themes/default/style.min.css" />
<script src="//cdnjs.cloudflare.com/ajax/libs/jstree/3.3.7/jstree.min.js"></script>

<button class="btn btn-secondary" onclick="location.href = '{{route('extension_server_settings_page',["extension_id"=>$extension->_id,"server_id"=> request('server')->_id])}}'">Ayarlar</button>
<br><br>

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
                "search",
                "dnd",
                "checkbox"
            ],
            'core' : {
                'data' : [
                    @include("__system__.folder",["files" => $results])
                ],
                "check_callback" : true
            },
            'contextmenu' : {
                "items" : customMenu
            }

        });

        /*if ($(node).hasClass("folder")) {
            // Delete the "delete" menu item
            delete items.deleteUser;
        }*/
    }
    asd();
    function customMenu(node) {
        let check=""
        let tree_path=0
        let name_position=0
        if (node.text.substring(0,2)!="cn") //If node is a folder
            check = "Folder";
        else
            check = "File";
        if(check=="Folder"){
            var items = {
                addUser: {
                    label: "{{__("Kullanıcı Ekle")}}",
                    action: function(obj) {
                        let path_useradd=GetParents(node);
                        var modal = new Modal('#add_user');
                        modal.show();
                        var elms=document.getElementById("add_user").getElementsByTagName("*");
                        for (var i = 0; i < elms.length; i++) {
                            if(elms[i].name === "tree_path")
                                tree_path=i;
                            if(elms[i].name === "username"){
                                name_position=i;
                                elms[tree_path].value="cn="+elms[name_position].value+","+path_useradd;
                            }
                        }
                        elms[name_position].onfocusout = function () {
                            elms[tree_path].value="cn="+elms[name_position].value+","+path_useradd;
                        }
                    },
                    icon: "fa fa-user"
                },
                deleteUser: {
                    label: "{{__("Yetki ver")}}",
                    action: function(obj) {
                        console.log(obj);
                    },
                    icon: "fa fa-trash"
                }
            };
        }
        else if(check=="File"){
            var items = {
                updateUser: {
                    label: "{{__("Kullanıcı Düzenle")}}",
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
                },
                locationUser: {
                    label: "{{__("Taşı")}}",
                    action: function(obj) {
                        console.log(obj);
                    },
                    icon: "fa fa-reply-all"
                }
            };
        }
        return items;
    }
    function GetParents(loSelectedNode) {
        try {
            var lnLevel = loSelectedNode.parents.length;
            var lsSelectedID = loSelectedNode.id;
            var loParent = $("#" + lsSelectedID);
            var lsParents =  loSelectedNode.text + ',';
            for (var ln = 0; ln <= lnLevel -1 ; ln++) {
                var loParent = loParent.parent().parent();
                if (loParent.children()[1] != undefined) {
                    lsParents += loParent.children()[1].text + ",";
                }
            }
            if (lsParents.length > 0) {
                lsParents = lsParents.substring(0, lsParents.length - 1);
            }
            return lsParents;
        }
        catch (err) {
            alert('Hata');
        }
    }
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