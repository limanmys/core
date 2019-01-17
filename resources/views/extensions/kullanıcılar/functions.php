<?php
function _init(){
    $extension_id = request()->route('extension_id');
    $ldap_connection = ldap_connect(request('server')->ip_address, request('server')->extensions[$extension_id]["port"]);
    ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
    try{
        ldap_bind($ldap_connection,request('server')->extensions[$extension_id]["dn"],
            request('server')->extensions[$extension_id]["password"]);
    }catch (Exception $e){
        echo $e->getMessage();
    }
    return $ldap_connection;
}

function _search($connection ,$filter, $extra){
    $extension_id = request()->route('extension_id');
    $search = ldap_search($connection, request('server')->extensions[$extension_id]["search_dn"], $filter, $extra);
    $results = ldap_get_entries($connection, $search);
    $mert = [];
    for($i = 0 ; $i < $results["count"] ; $i++){
        $user = $results[$i]["dn"];
        $arr = explode(",", $user);
        $arr = array_reverse($arr);
        $res = array();
        $t   = &$res;
        foreach ($arr as $k) {
            if (empty($t[$k])) {
                if(!starts_with($k,"cn")){
                    $t[$k] = array();
                }else{
                    $t[$k] = $k;
                }
                $t = &$t[$k];
            }
        }
        unset($t);
        $mert = array_merge_recursive($mert,$res);
    }
    return $mert;
}

function addUser(){
    $user["givenName"] = request("firstname");
    $user["sn"] = request("surname");
    $user["displayName"] = request("fullname");
    $user["sAMAccountName"] = request("username");
    $user["password"] = request("password");
    if(request()->exists('forcechangepass')){
        $user["pwdLastSet"] = intval(11644473600.0 + time()) * 10000000;
    }
    $user["userAccountControl"] = (request()->exists('neverexpired')) ? "512" : "66048";
    if(request()->exists('lockaccount')){
        $user["lockoutTime"] = 0;
    }
    $user["objectclass"] = "posixAccount";
    if(request()->exists('cantchangepass')){
        $user["msDS-SupportedEncryptionTypes"] = 0;
    }
    $ldap_connection = _init();
    ldap_add($ldap_connection,request('tree_path'),$user);
}