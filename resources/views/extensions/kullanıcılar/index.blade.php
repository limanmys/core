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

<div id="tree"></div>
<script>
    let tree = new TreeView([
        @include("__system__.folder",["files" => $mert])
    ], 'tree');
    tree.on('select',function(e){
        console.log(e);
    });
</script>