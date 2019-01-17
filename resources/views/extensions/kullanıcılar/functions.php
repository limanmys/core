<?php
function _init($ip, $port, $password, $rdn){

    $ldap_connection = ldap_connect($ip, $port);
    ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
    try{
        ldap_bind($ldap_connection,$rdn,$password);
    }catch (Exception $e){
        echo $e->getMessage();
    }
    return $ldap_connection;
}

function _search($connection, $dn ,$filter, $extra){
    $search = ldap_search($connection, $dn, $filter, $extra);
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

function addUser($server,$password,$rdn){
    $user["firstname"] = request("firstname");
    $user["surname"] = request("surname");
    $user["fullname"] = request("fullname");
    $user["username"] = request("username");
    $user["password"] = request("password");
    $user["forcechangepass"] = (request()->exists('forcechangepass')) ? "true" : "false";
    $user["neverexpired"] = (request()->exists('neverexpired')) ? "true" : "false";
    $user["lockaccount"] = (request()->exists('lockaccount')) ? "true" : "false";
    $user["objectclass"] = "posixAccount";
    $user["cantchangepass"] = (request()->exists('cantchangepass')) ? "true" : "false";
    $ldap_connection = _init($server->ip_address,$server->port, $password, $rdn);
    ldap_add($ldap_connection,request('tree_path'),$user);
}