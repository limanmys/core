<?php
    $conn = _init($server->ip_address, $server->port, "SambaPardus01", "cn=admin,dc=ldap,dc=lab");

    $results = _search($conn, "dc=ldap,dc=lab" , "(objectclass=posixAccount)", ["dn"]);
?>
<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>

<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/jstree/3.3.7/themes/default/style.min.css" />
<script src="//cdnjs.cloudflare.com/ajax/libs/jstree/3.3.7/jstree.min.js"></script>

<div class="container">
    <div class="row">
        <div class="col">
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
<form id="s">
    <input type="search" id="q" />
    <button type="submit">Search</button>
</form>

<script>
    function asd(){
        $('#ldap_tree').jstree({
            "plugins" : ["search"],
            'core' : {
                'data' : [
                    @include("__system__.folder",["files" => $results])
                ]
            }
        });
    }
    asd();
</script>

<script>
    $("#s").submit(function(e) {
        e.preventDefault();
        $("#ldap_tree").jstree(true).search($("#q").val());
    });
</script>