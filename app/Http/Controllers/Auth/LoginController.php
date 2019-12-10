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
        if(!$flag && env('LDAP_HOSTS', false)){
            $this->setBaseDn();
            $locateUsers = config('ldap_auth.identifiers.ldap.locate_users_by', 'samaccountname');
            $guidColumn = config('ldap_auth.identifiers.database.guid_column', 'objectguid');
            $domain = config('app.domain');
            $credientials = (object) $this->credentials($request);
            $flag = Adldap::auth()->attempt($credientials->email."@".$domain, $credientials->password, true);
            if($flag){
                $ldapUser = Adldap::search()
                    ->select(['objectguid', '*'])
                    ->where($locateUsers, '=', $credientials->email)
                    ->first();
                $user = \App\User::where($guidColumn, $ldapUser->getConvertedGuid())->first();
                if(!$user){
                    $user = User::create([
                        "name" => $ldapUser->cn[0],
                        "email" => $ldapUser->userprincipalname[0] ? $ldapUser->userprincipalname[0] : $ldapUser->cn[0],
                        "password" => Hash::make(str_random("16")),
                        $guidColumn => $ldapUser->getConvertedGuid()
                    ]);
                }else{
                    $user->update([
                        "name" => $ldapUser->cn[0],
                        "email" => $ldapUser->userprincipalname[0] ? $ldapUser->userprincipalname[0] : $ldapUser->cn[0]
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
        $connection = ldap_connect(config('ldap.connections.default.settings.hosts')[0],389);
        ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_bind($connection);
        $outputs = ldap_read($connection,'','objectclass=*');
        $entries = ldap_get_entries($connection,$outputs)[0];
        config(['ldap.connections.default.settings.base_dn' => $entries["rootdomainnamingcontext"][0]]);

        $domain = str_replace("dc=","",strtolower($entries["rootdomainnamingcontext"][0]));
        $domain = str_replace(",", ".", $domain);
        config(['app.domain' => $domain]);
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
