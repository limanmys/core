<?php

namespace App\Http\Controllers\Auth;

use Adldap\Laravel\Facades\Adldap;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use App\User;
use Illuminate\Support\Facades\Hash;

/**
 * Class LoginController
 * @package App\Http\Controllers\Auth
 */
class LoginController extends Controller
{
    use AuthenticatesUsers;

    /**
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * LoginController constructor.
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function authenticated(Request $request, $user)
    {
        $user->last_login_at = Carbon::now()->toDateTimeString();
        $user->last_login_ip = $request->ip();
        $user->save();

        system_log(7,"LOGIN_SUCCESS");
    }

    public function attemptLogin(Request $request)
    {   
        $flag =  $this->guard()->attempt(
            $this->credentials($request), $request->filled('remember')
        );
        if(!$flag && config('ldap.ldap_host', false)){
            $this->setBaseDn();
            $guidColumn = config('ldap.ldap_guid_column', 'objectguid');
            $base_dn = config('ldap.ldap_base_dn');
            $domain = config('ldap.ldap_domain');
            $credientials = (object) $this->credentials($request);
            try{
                $ldapConnection = ldap_connect("ldap://" . config('ldap.ldap_host'), 389);
                ldap_set_option($ldapConnection, LDAP_OPT_PROTOCOL_VERSION, 3);
                ldap_set_option($ldapConnection, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);
                ldap_set_option($ldapConnection, LDAP_OPT_REFERRALS,0);
                $flag = ldap_bind($ldapConnection, $credientials->email."@".$domain, $credientials->password);
            }catch(\Exception $ex){
                return false;
            }
            if($flag){
                $sr = ldap_search($ldapConnection, $base_dn, '(&(objectClass=user)(sAMAccountName='.ldap_escape($credientials->email).'))', [$guidColumn, 'samaccountname']);
                $ldapUser = ldap_get_entries($ldapConnection, $sr);
                if(!$ldapUser[0][$guidColumn][0]){
                    return false;
                }
                $user = \App\User::where($guidColumn, bin2hex($ldapUser[0][$guidColumn][0]))->first();
                if(!$user){
                    $user = User::create([
                        "name" => $credientials->email,
                        "email" => $credientials->email."@".$domain,
                        "password" => Hash::make(str_random("16")),
                        $guidColumn => bin2hex($ldapUser[0][$guidColumn][0]),
                    ]);
                }else{
                    $user->update([
                        "name" => $credientials->email,
                        "email" => $credientials->email."@".$domain,
                    ]);
                }
                $this->guard()->login($user, true);
                return true;
            }else{
                system_log(5,"LOGIN_FAILED");
            }
        }
        return $flag;
    }

    private function setBaseDn()
    {
        if(!config('ldap.ldap_domain', false)){
            $connection = ldap_connect(config('ldap.ldap_host'),389);
            ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_bind($connection);
            $outputs = ldap_read($connection,'','objectclass=*');
            $entries = ldap_get_entries($connection,$outputs)[0];
            $domain = str_replace("dc=","",strtolower($entries["rootdomainnamingcontext"][0]));
            $domain = str_replace(",", ".", $domain);
            setEnv([
                "LDAP_BASE_DN" => $entries["rootdomainnamingcontext"][0],
                "LDAP_DOMAIN" => $domain
            ]);
        }
    }

    protected function validateLogin(Request $request)
    {
        $request->request->add([
            $this->username() => $request->liman_email_mert,
            "password" => $request->liman_password_baran
        ]);
        $request->validate([
            $this->username() => 'required|string',
            'password' => 'required|string',
        ]);
    }
}
