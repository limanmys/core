<?php

namespace App\Classes\Connector;

class LdapConnection
{
    private $connection;
    private $dn = "";
    private $isAD = true;
    private $domain = "";
    private $fqdn = "";

    private $ipAddress;
    private $username;
    private $password;

    public function __construct($ipAddress, $username, $password)
    {
        $this->ipAddress = $ipAddress;
        $this->username = $username;
        $this->password = $password;
        try {
            $connection = ldap_connect($this->ipAddress,389);
            ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_bind($connection);
            $outputs = ldap_read($connection,'','objectclass=*');
            $entries = ldap_get_entries($connection,$outputs)[0];
            $this->fqdn = (array_key_exists("dnshostname",$entries)) ? $entries["dnshostname"][0] : "";
            $this->isAD = (array_key_exists("domainfunctionality",$entries)) ? true : false;
            $this->domain = $entries['rootdomainnamingcontext'][0];
        } catch (\Exception $e) {
            throw $e;
        }

        if(substr($this->username,0,2) == "cn" || substr($this->username,0,2) == "CN"){
            $this->dn = $this->username;
        }else{
            $this->dn = $this->username . "@" . $this->getDomain();
        }

        if(!$this->isAD){
            throw new \Exception("Şu an için bu sunucuyu yönetebilecek bir mimari oluşturulmamış.");
        }
        $this->connection = $this->initWindows();
    }

    private function initWindows()
    {
        // Create Ldap Connection Object
        $ldap_connection = ldap_connect('ldaps://' . $this->ipAddress);

        // Set Protocol Version
        ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);

        ldap_set_option($ldap_connection, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);

        ldap_set_option($ldap_connection, LDAP_OPT_REFERRALS,0);
        // Try to Bind Ldap
        try {
            ldap_bind($ldap_connection, $this->dn, $this->password);
        } catch (\Exception $e) {
            throw $e;
        }

        // Return Object to use it later.
        return $ldap_connection;
    }

    public function searchAsTree($filter,$attributeList = ["dn"],$page = "1",$perPage = "10")
    {
        $results = $this->search($filter,$attributeList,$page,$perPage);

        // Create Array To use it later on.
        $array = [];
        for ($i = 0; $i < $results["count"]; $i++) {
            $user = $results[$i]["dn"];
            $arr = explode(",", $user);
            $arr = array_reverse($arr);
            $res = array();
            $t = &$res;
            foreach ($arr as $k) {
                if (empty($t[$k])) {
                    if (!starts_with($k, "cn")) {
                        $t[$k] = array();
                    } else {
                        $t[$k] = $k;
                    }
                    $t = &$t[$k];
                }
            }
            unset($t);
            $array = array_merge_recursive($array, $res);
        }
        return $array;
    }

    public function search($filter,$options = [])
    {
        $searchOn = (array_key_exists("searchOn",$options) && $options["searchOn"] != null) ? $options["searchOn"] : $this->domain;
        $page = (array_key_exists("page",$options)) ? $options["page"] : "1";
        $perPage = (array_key_exists("perPage",$options)) ? $options["perPage"] : "500";
        $attributeList = (array_key_exists("attributeList",$options)) ? $options["attributeList"] : ["dn"];
        $stopOn = (array_key_exists("stopOn",$options)) ? $options["stopOn"] : "-1";

        $filter = html_entity_decode($filter);
        $searchOn = html_entity_decode($searchOn);
        
        // Set Variables
        $cookie = "";
        $size = 0;
        $entries = [];
        $loop = 0 ;

        // First, retrieve real size of search.
        do{

            // Break If that's enough
            if($stopOn != "-1" && $size > $stopOn){
                break;
            }
            
            // First Increase Loop Count
            $loop++;

            // Limit Search for each loop.
            ldap_control_paged_result($this->connection, intval($perPage), true, $cookie);

            // Make Search
            $search = ldap_search($this->connection, $searchOn, $filter, $attributeList);

            // Retrieve Entries if specified
            if($loop == intval($page) || $page == "-1"){
                $entries = array_merge(ldap_get_entries($this->connection,$search),$entries);
            }

            // Count Results and sum with total size.
            $size += ldap_count_entries($this->connection,$search);

            // Update Cookie
            ldap_control_paged_result_response($this->connection, $search, $cookie);

        }while($cookie !== null && $cookie != '');

        // Return what we have.
        return [$size,$entries];
    }

    public function addObject($cn,$data)
    {
        $cn = html_entity_decode($cn);
        $flag = ldap_add($this->connection, $cn, $data);
        return $flag ? true : ldap_error($this->connection);
    }

    public function getAttributes($cn)
    {
        $cn = html_entity_decode($cn);
        $cn = ldap_escape($cn);
        $search = ldap_search($this->connection, $this->domain, '(distinguishedname=' . $cn . ')');
        $first = ldap_first_entry($this->connection,$search);
        return ldap_get_attributes($this->connection,$first);
    }

    public function convertTime($ldapTime) {
        $secsAfterADEpoch = $ldapTime / 10000000;
        $ADToUnixConverter = ((1970 - 1601) * 365 - 3 + round((1970 - 1601) / 4)) * 86400;
        return intval($secsAfterADEpoch - $ADToUnixConverter);
    }

    public function countSearch($query)
    {
        $search = ldap_search($this->connection, $this->domain, $query);

        return ldap_count_entries($this->connection, $search);
    }

    public function updateAttributes($cn, $array)
    {
        $cn = html_entity_decode($cn);
        $toUpdate = [];
        $toDelete = [];
        foreach($array as $key=>$item){
            if($item == null){
                $toDelete[$key] = array();
                continue;
            }
            $toUpdate[$key] = $item;
        }
        $flagUpdate = true;
        $flagDelete = true;
        if(count($toUpdate)){
            $flagUpdate = ldap_mod_replace($this->connection,$cn,$toUpdate);
        }

        if(count($toDelete)){
            $flagDelete = ldap_modify($this->connection,$cn,$toDelete);
        }
        
        return $flagUpdate && $flagDelete;
    }

    public function removeObject($cn)
    {
        return ldap_delete($this->connection, $cn);
    }

    public function addAttribute($cn, $array)
    {
        $cn = html_entity_decode($cn);
        try{
            return ldap_mod_add($this->connection,$cn,$array);
        }catch (\Exception $exception){
            return false;
        }
    }

    public function deleteAttribute($ou,$array)
    {
        $ou = html_entity_decode($ou);
        return ldap_mod_del($this->connection, $ou,$array);
    }

    public function getDomain()
    {
        $domain = $this->domain;
        $domain = str_replace("dc=","",strtolower($domain));
        return str_replace(",", ".", $domain);
    }

    public function getDC()
    {
        return $this->domain;
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function changePassword($ou, $password)
    {
        return $this->updateAttributes($ou,["unicodepwd" => mb_convert_encoding("\"" . $password . "\"","UTF-16LE")]);
    }

    public function renameOU($dn, $ou, $cn)
    {
        $cn = html_entity_decode($cn);
        $dn = html_entity_decode($dn);
        $ou = html_entity_decode($ou);
        $flag = ldap_rename($this->connection, $dn, $cn, $ou, true);
        return $flag ? true : ldap_error($this->connection);
    }

    public function getFQDN()
    {
        return $this->fqdn;
    }
}