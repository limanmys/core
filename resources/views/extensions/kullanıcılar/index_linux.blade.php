<?php
    $ldap_connection = ldap_connect($server->ip_address);
    ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);

    $pass = "SambaPardus01";
    try{
        $result = ldap_bind($ldap_connection,"cn=admin,dc=ldap,dc=lab",$pass);
    }catch (Exception $e){
        echo $e->getMessage();
    }
    $search = ldap_search($ldap_connection, "dc=ldap,dc=lab" , "(objectclass=posixAccount)", ["dn"]);
    $users = ldap_get_entries($ldap_connection, $search);
    $mert = [];
    $user_details = [];
    for($i = 0 ; $i < $users["count"] ; $i++){
        $user = $users[$i]["dn"];
        $arr = explode(",", $user);
        $arr = array_reverse($arr);
        $current = [];
        $tail = null;
        $res = array();
        $t   = &$res;
        foreach ($arr as $k) {
            if (empty($t[$k])) {
                if(!starts_with($k,"cn")){
                    $t[$k] = array();
                }else{
                    $t[$k] = $k;
                    $search = ldap_search($ldap_connection, "dc=ldap,dc=lab", $k);
                    $attributes = ldap_get_entries($ldap_connection, $search)[0];
                    $user_details["cn=" . $attributes["cn"][0]]["uid"] = $attributes["uid"][0];
                    $user_details["cn=" . $attributes["cn"][0]]["uidnumber"] = $attributes["uidnumber"][0];
                    $user_details["cn=" . $attributes["cn"][0]]["homedirectory"] = $attributes["homedirectory"][0];
                    $user_details["cn=" . $attributes["cn"][0]]["gidnumber"] = $attributes["gidnumber"][0];
                    $user_details["cn=" . $attributes["cn"][0]]["cn"] = $attributes["cn"][0];
                }
                $t = &$t[$k];
            }
        }
        unset($t);
        $mert = array_merge_recursive($mert,$res);
    }
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
                    @include("__system__.folder",["files" => $mert])
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