<?php
$ldap_connection = ldap_connect($server->ip_address);
ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($ldap_connection, LDAP_OPT_REFERRALS, 0);

$pass = "SambaPardus01";
try{
    $result = ldap_bind($ldap_connection,"administrator@win.lab",$pass);
}catch (Exception $e){
    dd($e->getMessage());
}
$search = ldap_search($ldap_connection, "dc=win,dc=lab" , "(objectclass=person)");
$users = ldap_get_entries($ldap_connection, $search);
$mert = [];
$user_details = [];
for($i = 0 ; $i < $users["count"] ; $i++){
    $user = $users[$i]["dn"];
    $arr = explode(",", $user);
    $arr = array_reverse($arr);
    $res = array();
    $t   = &$res;
    for($j = 0 ; $j < count($arr) ; $j++){
        $k = $arr[$j];
        if (empty($t[$k])) {
            if($j != count($arr) -1 ){
                $t[$k] = array();
            }else{
                $t[$k] = $k;
                $search = ldap_search($ldap_connection, "dc=win,dc=lab", "(&(objectCategory=person)($k))",[
                    "cn" , "whencreated", "whenchanged", "name" ,"samaccountname"
                ]);
                $attributes = ldap_get_entries($ldap_connection, $search);
                if($attributes["count"] == 0){
                    $user_details[$k]["cn"] = $k;
                    $user_details[$k]["name"] = '';
                    $user_details[$k]["sn"] = '';
                    $user_details[$k]["givenName"] = '';
                    $user_details[$k]["samaccountname"] = '';
                    $user_details[$k]["whenCreated"] = '';
                    $user_details[$k]["whenChanged"] = '';
                    $user_details[$k]["pwdLastSet"] = '';
                    $user_details[$k]["lastLogon"] = '';
                    $user_details[$k]["lastLogoff"] = '';
                    $user_details[$k]["accountExpires"] = '';
                    continue;
                }
                $user_details[$k]["cn"] = $attributes[0]["cn"][0];
                $user_details[$k]["name"] = $attributes[0]["name"][0];
                $user_details[$k]["sn"] = $attributes[0]["sn"][0];
                $user_details[$k]["givenName"] = $attributes[0]["givenName"][0];
                $user_details[$k]["samaccountname"] = $attributes[0]["samaccountname"][0];
                $user_details[$k]["whenCreated"] = $attributes[0]["whenCreated"][0];
                $user_details[$k]["whenChanged"] = $attributes[0]["whenChanged"][0];
                $user_details[$k]["pwdLastSet"] = $attributes[0]["pwdLastSet"][0];
                $user_details[$k]["lastLogon"] = $attributes[0]["lastLogon"][0];
                $user_details[$k]["lastLogoff"] = $attributes[0]["lastLogoff"][0];
                $user_details[$k]["accountExpires"] = $attributes[0]["accountExpires"][0];

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
                    <td>CN</td>
                    <td id="cn"></td>
                </tr>
                <tr>
                    <td>Name</td>
                    <td id="name"></td>
                </tr>
                <tr>
                    <td>SN</td>
                    <td id="sn"></td>
                </tr>
                <tr>
                    <td>Given Name</td>
                    <td id="givenName"></td>
                </tr>
                <tr>
                    <td>SAM Account Name</td>
                    <td id="samaccountname"></td>
                </tr>
                <tr>
                    <td>When Created</td>
                    <td id="whencreated"></td>
                </tr>
                <tr>
                    <td>When Changed</td>
                    <td id="whenchanged"></td>
                </tr>
                <tr>
                    <td>pwdLastSet</td>
                    <td id="pwdLastSet"></td>
                </tr>
                <tr>
                    <td>lastLogon</td>
                    <td id="lastLogon"></td>
                </tr>
                <tr>
                    <td>lastLogoff</td>
                    <td id="lastLogoff"></td>
                </tr>
                <tr>
                    <td>accountExpires</td>
                    <td id="accountExpires"></td>
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
        document.getElementById("cn").innerText = user_details[cn]["cn"];
        document.getElementById("whencreated").innerText = user_details[cn]["whencreated"];
        document.getElementById("whenchanged").innerText = user_details[cn]["whenchanged"];
        document.getElementById("name").innerText = user_details[cn]["name"];
        document.getElementById("sn").innerText = user_details[cn]["sn"];
        document.getElementById("givenName").innerText = user_details[cn]["givenName"];
        document.getElementById("samaccountname").innerText = user_details[cn]["samaccountname"];
        document.getElementById("pwdLastSet").innerText = user_details[cn]["pwdLastSet"];
        document.getElementById("lastLogon").innerText = user_details[cn]["lastLogon"];
        document.getElementById("lastLogoff").innerText = user_details[cn]["lastLogoff"];
        document.getElementById("accountExpires").innerText = user_details[cn]["accountExpires"];
    });
    let user_details = <?php echo json_encode($user_details) ?>;
</script>