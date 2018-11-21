<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LdapController extends Controller
{
    public function connect(){
//        $dn = "cn=admin,dc=parduslab,dc=com";
//        $password = "mert";
        $ldapconn = ldap_connect("10.150.31.133");
        $flag = ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
        if($flag == true){
            return $ldapconn;
        }else{
            return false;
        }
    }

    public function getUsers(){


        //User Listeleme
        $ldaptree = "ou=People,dc=parduslab,dc=com";
        $filter="(|(uid=*))";
        //Grup Listeleme
//        $ldaptree = "ou=Groups,dc=parduslab,dc=com";
//        $filter="(|(cn=*))";
        //Bilgisayar Listeleme
//        $ldaptree = "ou=Computers,dc=parduslab,dc=com";
//        $filter="(|(cn=*))";
        $this->search($ldaptree,$filter);
    }

    public function search($tree,$filter){
        $ldapconn = $this->connect();
        if($ldapconn == false){
            return false;
        }
        $search = ldap_search($ldapconn,$tree,$filter);
        $results = ldap_get_entries($ldapconn,$search);
        dd($results);
    }
}
