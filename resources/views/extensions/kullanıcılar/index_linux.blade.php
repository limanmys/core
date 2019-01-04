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
<script src="{{asset('/js/treeview.min.js')}}"></script>
<link rel="stylesheet" href="{{asset('/css/tree.css')}}">
<div class="container">
    <div class="row">
        <div class="col">
            <div id="tree"></div>
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
    let tree = new TreeView([
        @include("__system__.folder",["files" => $mert])
    ], 'tree');
    tree.on('select',function(e){
        let cn = e.data.name;
        document.getElementById("uid").innerText = user_details[cn]["uid"];
        document.getElementById("uidnumber").innerText = user_details[cn]["uidnumber"];
        document.getElementById("homedirectory").innerText = user_details[cn]["homedirectory"];
        document.getElementById("gidnumber").innerText = user_details[cn]["gidnumber"];
        document.getElementById("cn").innerText = user_details[cn]["cn"];
    });
    let user_details = <?php echo json_encode($user_details) ?>;
</script>