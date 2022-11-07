<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\Certificate;
use App\Models\Extension;
use App\Models\Permission;
use App\Models\PermissionData;
use App\Models\Role;
use App\Models\RoleMapping;
use App\Models\RoleUser;
use App\Models\Server;
use App\Models\ServerGroup;
use App\System\Command;
use App\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MainController extends Controller
{
    public function __construct()
    {
        // Specifiy that this controller requires admin middleware in all functions.
        $this->middleware('admin');
    }

    public function index()
    {
        $updateAvailable = is_file(storage_path('extension_updates'));
        $extensions = extensions()->map(function ($item) {
            if (! $item['issuer']) {
                $item['issuer'] = __('Güvenli olmayan üretici!');
            }

            return $item;
        });

        return view('settings.index', [
            'users' => User::all(),
            'updateAvailable' => $updateAvailable,
            'extensions' => $extensions,
        ]);
    }

    public function getLimanTweaks()
    {
        return respond([
            'APP_DEBUG' => env('APP_DEBUG') ? 'true' : 'false',
            'BRAND_NAME' => env('BRAND_NAME'),
            'APP_NOTIFICATION_EMAIL' => env('APP_NOTIFICATION_EMAIL'),
            'NEW_LOG_LEVEL' => env('NEW_LOG_LEVEL'),
            'APP_URL' => env('APP_URL'),
            'MAIL_ENABLED' => env('MAIL_ENABLED') ? 'true' : 'false',
            'MAIL_HOST' => env('MAIL_HOST'),
            'MAIL_PORT' => env('MAIL_PORT'),
            'MAIL_USERNAME' => env('MAIL_USERNAME'),
            'MAIL_ENCRYPTION' => env('MAIL_ENCRYPTION'),
            'MARKET_URL' => env('MARKET_URL'),
            'MARKET_CLIENT_ID' => env('MARKET_CLIENT_ID'),
            'MARKET_CLIENT_SECRET' => env('MARKET_CLIENT_SECRET'),
            'EXTENSION_DEVELOPER_MODE' => env('EXTENSION_DEVELOPER_MODE') ? 'true' : 'false',
            'APP_LANG' => env('APP_LANG', 'tr'),
            'NAV_SERVER_COUNT' => env('NAV_SERVER_COUNT', 20),
            'EXTENSION_TIMEOUT' => env('EXTENSION_TIMEOUT', 30),
            'KEYCLOAK_ACTIVE' => env('KEYCLOAK_ACTIVE'),
            'KEYCLOAK_CLIENT_ID' => env('KEYCLOAK_CLIENT_ID'),
            'KEYCLOAK_REDIRECT_URI' => env('KEYCLOAK_REDIRECT_URI'),
            'KEYCLOAK_BASE_URL' => env('KEYCLOAK_BASE_URL'),
            'KEYCLOAK_REALM' => env('KEYCLOAK_REALM'),
        ]);
    }

    public function setLimanTweaks()
    {
        validate([
            'NAV_SERVER_COUNT' => 'required|numeric|digits_between:1,2|min:1',
        ]);

        auth()->user()->update([
            "locale" => request('APP_LANG')
        ]);
        \Session::put('locale', request('APP_LANG'));

        $flag = setEnv([
            'APP_DEBUG' => request('APP_DEBUG'),
            'BRAND_NAME' => '"'.request('BRAND_NAME').'"',
            'APP_NOTIFICATION_EMAIL' => request('APP_NOTIFICATION_EMAIL'),
            'NEW_LOG_LEVEL' => request('NEW_LOG_LEVEL'),
            'APP_URL' => request('APP_URL'),
            'MAIL_ENABLED' => request('MAIL_ENABLED'),
            'MAIL_HOST' => request('MAIL_HOST'),
            'MAIL_PORT' => request('MAIL_PORT'),
            'MAIL_USERNAME' => request('MAIL_USERNAME'),
            'MAIL_ENCRYPTION' => request('MAIL_ENCRYPTION'),
            'MARKET_URL' => request('MARKET_URL'),
            'MARKET_CLIENT_ID' => request('MARKET_CLIENT_ID'),
            'MARKET_CLIENT_SECRET' => request('MARKET_CLIENT_SECRET'),
            'EXTENSION_DEVELOPER_MODE' => request('EXTENSION_DEVELOPER_MODE'),
            'APP_LANG' => request('APP_LANG'),
            'NAV_SERVER_COUNT' => request('NAV_SERVER_COUNT'),
            'EXTENSION_TIMEOUT' => request('EXTENSION_TIMEOUT'),
            'KEYCLOAK_ACTIVE' => request('KEYCLOAK_ACTIVE'),
            'KEYCLOAK_CLIENT_ID' => request('KEYCLOAK_CLIENT_ID'),
            'KEYCLOAK_REDIRECT_URI' => request('KEYCLOAK_REDIRECT_URI'),
            'KEYCLOAK_BASE_URL' => request('KEYCLOAK_BASE_URL'),
            'KEYCLOAK_REALM' => request('KEYCLOAK_REALM'),
        ]);

        if (request()->has('MAIL_PASSWORD')) {
            $flag = setEnv([
                'MAIL_PASSWORD' => request('MAIL_PASSWORD'),
                'KEYCLOAK_CLIENT_SECRET' => request('KEYCLOAK_CLIENT_SECRET'),
            ]);
        }

        if ($flag) {
            Command::runSystem('systemctl restart liman-render');

            return respond('Ayarlar başarıyla kaydedildi!');
        } else {
            return respond('Ayarlar kaydedilemedi!', 201);
        }
    }

    public function testMailSettings()
    {
        $flag = setEnv([
            'MAIL_ENABLED' => request('MAIL_ENABLED'),
            'MAIL_HOST' => request('MAIL_HOST'),
            'MAIL_PORT' => request('MAIL_PORT'),
            'MAIL_USERNAME' => request('MAIL_USERNAME'),
            'MAIL_ENCRYPTION' => request('MAIL_ENCRYPTION'),
        ]);

        if (request()->has('MAIL_PASSWORD')) {
            $flag = setEnv([
                'MAIL_PASSWORD' => request('MAIL_PASSWORD'),
            ]);
        }

        if (! $flag) {
            return respond('Mail ayarları kaydedilemedi!', 201);
        }

        try {
            Mail::to(request('MAIL_USERNAME'))->send(
                new \App\Mail\TestMail('Test Mail', __('Liman MYS test mail gönderimi.'))
            );
        } catch (\Throwable) {
            return respond('Mail gönderimi başarısız oldu!', 201);
        }

        return respond('Mail ayarları geçerlidir.');
    }

    public function one(User $user)
    {
        return view('settings.one', [
            'user' => $user,
            'servers' => Server::find(
                $user->permissions
                    ->where('type', 'server')
                    ->pluck('value')
                    ->toArray()
            ),
            'extensions' => Extension::find(
                $user->permissions
                    ->where('type', 'extension')
                    ->pluck('value')
                    ->toArray()
            ),
        ]);
    }

    public function getUserList()
    {
        return view('table', [
            'value' => \App\User::all(),
            'title' => ['İsim Soyisim', 'Kullanıcı Adı', 'Email', '*hidden*'],
            'display' => ['name', 'username', 'email', 'id:user_id'],
            'menu' => [
                'Parolayı Sıfırla' => [
                    'target' => 'passwordReset',
                    'icon' => 'fa-lock',
                ],
                'Sil' => [
                    'target' => 'delete',
                    'icon' => ' context-menu-icon-delete',
                ],
            ],
            'onclick' => 'userDetails',
        ]);
    }

    public function getSimpleUserList()
    {
        return view('table', [
            'value' => \App\User::all(),
            'title' => ['İsim Soyisim', 'Kullanıcı Adı', 'Email', '*hidden*'],
            'display' => ['name', 'username', 'email', 'id:user_id'],
            'onclick' => 'userDetails',
        ]);
    }

    public function getList()
    {
        $user = User::find(request('user_id'));
        $data = [];
        $title = [];
        $display = [];
        switch (request('type')) {
            case 'server':
                $data = Server::whereNotIn(
                    'id',
                    $user->permissions
                        ->where('type', 'server')
                        ->pluck('value')
                        ->toArray()
                )->get();
                $title = ['*hidden*', 'İsim', 'Türü', 'İp Adresi'];
                $display = ['id:id', 'name', 'type', 'ip_address'];
                break;
            case 'extension':
                $data = Extension::whereNotIn(
                    'id',
                    $user->permissions
                        ->where('type', 'extension')
                        ->pluck('value')
                        ->toArray()
                )->get();
                $title = ['*hidden*', 'İsim'];
                $display = ['id:id', 'display_name'];
                break;
            case 'role':
                $data = Role::whereNotIn(
                    'id',
                    $user->roles->pluck('id')->toArray()
                )->get();
                $title = ['*hidden*', 'İsim'];
                $display = ['id:id', 'name'];
                break;
            case 'liman':
                $usedPermissions = Permission::where([
                    'type' => 'liman',
                    'morph_id' => request('user_id'),
                ])
                    ->get()
                    ->groupBy('value');

                $data = [
                    [
                        'id' => 'view_logs',
                        'name' => __('Sunucu Günlük Kayıtlarını Görüntüleme'),
                    ],
                    [
                        'id' => 'add_server',
                        'name' => __('Sunucu Ekleme'),
                    ],
                    [
                        'id' => 'server_services',
                        'name' => __('Sunucu Servislerini Görüntüleme'),
                    ],
                    [
                        'id' => 'server_details',
                        'name' => __('Sunucu Detaylarını Görüntüleme'),
                    ],
                    [
                        'id' => 'update_server',
                        'name' => __('Sunucu Detaylarını Güncelleme'),
                    ],
                ];

                foreach ($usedPermissions as $permission => $values) {
                    foreach ($data as $k => $v) {
                        if ($v['id'] == $permission) {
                            unset($data[$k]);
                        }
                    }
                }

                $title = ['*hidden*', 'İsim'];
                $display = ['id:id', 'name'];
                break;
            default:
                abort(504, 'Tip Bulunamadı');
        }

        return view('table', [
            'value' => (object) $data,
            'title' => $title,
            'display' => $display,
        ]);
    }

    public function allRoles()
    {
        $data = [];

        $permissionData =
            Permission::with('morph')
                        ->get()->each(function ($row) {
                            $row->details = $row->getRelatedObject();
                            if ($row->morph_type == 'roles') {
                                $row->users = $row->morph->users()->get();
                            }
                        });

        foreach ($permissionData as $row) {
            if ($row->details['value'] == '-' || $row->details['type'] == '-') {
                continue;
            }

            $insert = [
                'id' => $row->morph->id,
                'morph_type' => $row->morph_type,
                'perm_type' => $row->details['type'],
                'perm_value' => $row->details['value'],
            ];

            if ($row->morph_type == 'users') {
                $data[] = array_merge($insert, [
                    'username' => $row->morph->name,
                    'role_name' => __('Rol yok'),
                ]);
            } elseif ($row->morph_type == 'roles') {
                foreach ($row->users as $user) {
                    $data[] = array_merge($insert, [
                        'username' => $user->name,
                        'role_name' => $row->morph->name,
                    ]);
                }
            }
        }

        return view('table', [
            'value' => $data,
            'title' => ['*hidden*', '*hidden*', 'Kullanıcı Adı', 'Rol Adı', 'İzin Tipi', 'İzin Değeri'],
            'display' => ['id:id', 'morph_type:morph_type', 'username', 'role_name', 'perm_type', 'perm_value'],
            'onclick' => 'goToRoleItem',
        ]);
    }

    public function addList()
    {
        $arr = [];
        foreach (json_decode((string) request('ids'), true) as $id) {
            array_push($arr, $id);
            Permission::grant(request('user_id'), request('type'), 'id', $id);
        }
        $arr['type'] = request('type');
        $arr['target_user_id'] = request('user_id');
        system_log(7, 'PERMISSION_GRANT', $arr);

        return respond(__('Başarılı'), 200);
    }

    public function removeFromList()
    {
        $arr = [];
        $flag = false;
        $ids = json_decode((string) request('ids'), true);

        if ($ids == []) {
            return respond('Lütfen bir seçim yapın', 201);
        }

        foreach ($ids as $id) {
            $flag = Permission::revoke(
                request('user_id'),
                request('type'),
                'id',
                $id
            );
        }
        array_push($arr, $id);
        $arr['type'] = request('type');
        $arr['target_user_id'] = request('user_id');
        system_log(7, 'PERMISSION_REVOKE', $arr);
        if ($flag) {
            return respond(__('Başarılı'), 200);
        } else {
            return respond(__('Yetki(ler) silinemedi'), 201);
        }
    }

    public function addVariable()
    {
        if (Permission::grant(
            request('object_id'),
            'variable',
            request('key'),
            request('value'),
            null,
            request('object_type')
        )) {
            return respond('Veri başarıyla eklendi!');
        } else {
            return respond('Veri eklenemedi!', 201);
        }
    }

    public function removeVariable()
    {
        $flag = false;
        foreach (explode(',', (string) request('variables')) as $id) {
            $flag = Permission::find($id)->delete();
        }

        if ($flag) {
            return respond('Veri(ler) başarıyla silindi!');
        }

        return respond('Veri(ler) silinemedi!', 201);
    }

    public function getExtensionFunctions()
    {
        $extension = json_decode(
            file_get_contents(
                '/liman/extensions/'.
                strtolower((string) extension()->name).
                DIRECTORY_SEPARATOR.
                'db.json'
            ),
            true
        );
        $functions = array_key_exists('functions', $extension)
            ? $extension['functions']
            : [];
        $lang = session('locale');
        $file =
            '/liman/extensions/'.
            strtolower((string) extension()->name).
            '/lang/'.
            $lang.
            '.json';

        //Translate Items.
        $cleanFunctions = [];
        if (is_file($file)) {
            $json = json_decode(file_get_contents($file), true);
            for ($i = 0; $i < count($functions); $i++) {
                if (
                    array_key_exists('isActive', $functions[$i]) &&
                    $functions[$i]['isActive'] == 'false'
                ) {
                    continue;
                }
                $description = array_key_exists(
                    $functions[$i]['description'],
                    $json
                )
                    ? $json[$functions[$i]['description']]
                    : $functions[$i]['description'];
                array_push($cleanFunctions, [
                    'name' => $functions[$i]['name'],
                    'description' => $description,
                ]);
            }
        }

        return view('table', [
            'value' => $cleanFunctions,
            'title' => ['*hidden*', 'Açıklama'],
            'display' => ['name:name', 'description'],
        ]);
    }

    public function addFunctionPermissions()
    {
        foreach (explode(',', (string) request('functions')) as $function) {
            Permission::grant(
                request('user_id'),
                'function',
                'name',
                strtolower((string) extension()->name),
                $function
            );
        }

        return respond(__('Başarılı'), 200);
    }

    public function removeFunctionPermissions()
    {
        foreach (explode(',', (string) request('functions')) as $function) {
            Permission::find($function)->delete();
        }

        return respond(__('Başarılı'), 200);
    }

    public function health()
    {
        return respond(checkHealth(), 200);
    }

    public function saveLDAPConf()
    {
        $cert = Certificate::where([
            'server_hostname' => request('ldapAddress'),
            'origin' => 636,
        ])->first();
        if (! $cert) {
            [$flag, $message] = retrieveCertificate(
                request('ldapAddress'),
                636
            );
            if ($flag) {
                addCertificate(request('ldapAddress'), 636, $message['path']);
                AdminNotification::create([
                    'title' => json_encode([
                        'tr' => __('Yeni Sertifika Eklendi', [], 'tr'),
                        'en' => __('Yeni Sertifika Eklendi', [], 'en'),
                    ]),
                    'type' => 'new_cert',
                    'message' => json_encode([
                        'tr' => __('Sisteme yeni sunucu eklendi ve yeni bir sertifika eklendi.', [], 'tr'),
                        'en' => __('Sisteme yeni sunucu eklendi ve yeni bir sertifika eklendi.', [], 'en'),
                    ]),
                    'level' => 3,
                ]);
            }
        }
        if (! setBaseDn(request('ldapAddress'))) {
            return respond('Sunucuya bağlanırken bir hata oluştu!', 201);
        }
        setEnv([
            'LDAP_HOST' => request('ldapAddress'),
            'LDAP_GUID_COLUMN' => request('ldapObjectGUID'),
            'LDAP_STATUS' => request('ldapStatus'),
            'LDAP_MAIL_COLUMN' => request('ldapMail'),
        ]);

        return respond(__('Kaydedildi!'), 200);
    }

    public function getPermisssionData()
    {
        $extension = json_decode(
            file_get_contents(
                '/liman/extensions/'.
                strtolower((string) request('extension_name')).
                DIRECTORY_SEPARATOR.
                'db.json'
            ),
            true
        );
        $function = collect($extension['functions'])
            ->where('name', request('function_name'))
            ->first();

        if (! $function) {
            return respond('Fonksiyon bulunamadı!', 201);
        }

        $parameters = isset($function['parameters'])
            ? $function['parameters']
            : null;
        if (! $parameters) {
            return respond('Fonksiyon parametresi bulunamadı!', 201);
        }

        $data = PermissionData::where('permission_id', request('id'))->first();
        $data = $data ? json_decode((string) $data->data) : (object) [];
        foreach ($parameters as $key => $parameter) {
            $parameters[$key]['value'] = isset($data->{$parameter['variable']})
                ? $data->{$parameter['variable']}
                : '';
        }

        $parameters = collect(cleanArray($parameters));
        $inputs = view('inputs', [
            'inputs' => $parameters->mapWithKeys(function ($item) {
                return [
                    $item['name'] => $item['variable'].':'.$item['type'],
                ];
            }),
        ])->render();

        return respond([
            'data' => $parameters,
            'inputs' => $inputs,
        ]);
    }

    public function writePermisssionData()
    {
        $data = PermissionData::where('permission_id', request('id'))->first();
        if ($data) {
            $data->update([
                'data' => request('data'),
            ]);

            return respond('Başarıyla eklendi!');
        }

        PermissionData::create([
            'data' => request('data'),
            'permission_id' => request('id'),
        ]);

        return respond('Başarıyla eklendi!');
    }

    public function addServerGroup()
    {
        if (! request('name') || strlen((string) request('name')) < 1) {
            return respond('Lütfen bir grup ismi girin.', 201);
        }
        if (ServerGroup::where('name', request('name'))->exists()) {
            return respond('Bu isimle zaten bir grup var.', 201);
        }
        $flag = ServerGroup::create([
            'name' => request('name'),
            'servers' => request('servers'),
        ]);

        return $flag
            ? respond('Grup başarıyla eklendi!')
            : respond('Grup Eklenemedi!', 201);
    }

    public function modifyServerGroup()
    {
        $group = ServerGroup::find(request('server_group_id'));
        if (! $group) {
            return respond('Grup bulunamadı!', 201);
        }
        $flag = $group->update([
            'name' => request('name'),
            'servers' => request('servers'),
        ]);

        return $flag
            ? respond('Grup başarıyla düzenlendi!')
            : respond('Grup Düzenlenemedi!', 201);
    }

    public function deleteServerGroup()
    {
        $group = ServerGroup::find(request('server_group_id'));
        if (! $group) {
            return respond('Grup bulunamadı!', 201);
        }
        $flag = $group->delete();

        return $flag
            ? respond('Grup başarıyla silindi!')
            : respond('Grup Silinemedi!', 201);
    }

    public function saveLogSystem()
    {
        $flag = request()->validate([
            'targetHostname' => 'required|min:3',
            'targetPort' => 'required|numeric|between:1,65535',
            'logInterval' => 'required|numeric',
        ]);
        if (! $flag) {
            return respond('Girdiğiniz veriler geçerli değil!', 201);
        }

        $text =
            '
*.*     @@'.
            request('targetHostname').
            ':'.
            request('targetPort').
            '
\\$ModLoad imfile
\\$InputFilePollInterval '.
            request('logInterval').
            '
\\$PrivDropToGroup adm
\\$InputFileName '.
            env('LOG_PATH').
            '
\\$InputFileTag LimanApp
\\$InputFileStateFile Stat-APP
\\$InputFileSeverity Info
\\$InputRunFileMonitor
\\$InputFilePersistStateInterval 1000
';
        Command::runLiman('echo @{:text} > /etc/rsyslog.d/liman.conf', [
            'text' => $text,
        ]);

        shell_exec("sudo sed -i '/module(load=\"imudp\")/d' /etc/rsyslog.conf");
        shell_exec("sudo sed -i '/module(load=\"imtcp\")/d' /etc/rsyslog.conf");
        shell_exec(
            "sudo sed -i '/input(type=\"imudp\" port=\"514\")/d' /etc/rsyslog.conf"
        );
        shell_exec(
            "sudo sed -i '/input(type=\"imtcp\" port=\"514\")/d' /etc/rsyslog.conf"
        );

        $text = '
module(load="imudp")
input(type="imudp" port="514")
module(load="imtcp")
input(type="imtcp" port="514")';

        Command::runLiman('echo @{:text} | sudo tee -a /etc/rsyslog.conf', [
            'text' => $text,
        ]);
        shell_exec('sudo systemctl restart rsyslog');

        return respond('Başarıyla Kaydedildi!');
    }

    public function redirectMarket()
    {
        $auth_code = Str::random(15);
        session([
            'market_auth' => $auth_code,
        ]);

        return redirect(
            env('MARKET_URL').
            '/connect/authorize?response_type=code&scope=offline_access+user_api&redirect_uri='.
            urlencode(
                env('APP_URL').'/api/market/bagla?auth='.$auth_code
            ).
            '&client_id='.
            env('MARKET_CLIENT_ID')
        );
    }

    public function connectMarket()
    {
        if (session('market_auth') != request('auth')) {
            session([
                'market_auth' => false,
            ]);
            abort(504, 'Geçersiz istek!');
        }
        session([
            'market_auth' => false,
        ]);
        try {
            $client = new Client(['verify' => false]);

            $params = [
                'code' => request('code'),
                'grant_type' => 'authorization_code',
                'redirect_uri' => env('APP_URL').
                    '/api/market/bagla?auth='.
                    request('auth'),
                'client_id' => env('MARKET_CLIENT_ID'),
                'client_secret' => env('MARKET_CLIENT_SECRET'),
            ];
            $res = $client->request(
                'POST',
                env('MARKET_URL').'/connect/token',
                ['form_params' => $params]
            );
        } catch (BadResponseException) {
            abort(504, 'Market hesabınız bağlanırken bir hata oluştu!');
        }

        $json = json_decode((string) $res->getBody());
        $requiredScopes = ['user_api', 'offline_access'];
        $currentScopes = explode(' ', (string) $json->scope);

        if ($requiredScopes != $currentScopes) {
            abort(
                504,
                'Gerekli izinleri vermediğiniz için işleminizi gerçekleştiremiyoruz.'
            );
        }

        setEnv([
            'MARKET_ACCESS_TOKEN' => $json->access_token,
            'MARKET_REFRESH_TOKEN' => $json->refresh_token,
        ]);

        return redirect(route('settings').'#limanMarket');
    }

    public function getLogSystem()
    {
        $status =
            trim(shell_exec('systemctl is-active rsyslog.service')) == 'active'
                ? true
                : false;

        $data = trim(
            shell_exec(
                "cat /etc/rsyslog.d/liman.conf | grep 'InputFilePollInterval' | cut -d' ' -f2"
            )
        );
        $interval = $data == '' ? '10' : $data;

        $ip_address = '';
        $port = '';

        $data = trim(shell_exec("cat /etc/rsyslog.d/liman.conf | grep '@@**'"));
        if ($data != '') {
            $arr = explode('@@', $data);
            $ip_port = explode(':', $arr[1]);
            $ip_address = $ip_port[0];
            $port = $ip_port[1];
        }

        return respond([
            'status' => $status,
            'ip_address' => $ip_address != '' ? $ip_address : '',
            'port' => $port != '' ? $port : '514',
            'interval' => $interval != '' ? $interval : '10',
        ]);
    }

    public function restrictedMode()
    {
        $flag = setenv([
            'LIMAN_RESTRICTED' => request('LIMAN_RESTRICTED')
                ? 'true'
                : 'false',
            'LIMAN_RESTRICTED_SERVER' => request('LIMAN_RESTRICTED_SERVER'),
            'LIMAN_RESTRICTED_EXTENSION' => request(
                'LIMAN_RESTRICTED_EXTENSION'
            ),
        ]);
        if ($flag) {
            return respond('Kısıtlı mod ayarları başarıyla güncellendi!');
        } else {
            return respond('Kısıtlı mod ayarları güncellenemedi!', 201);
        }
    }

    public function getDNSServers()
    {
        $data = `grep nameserver /etc/resolv.conf | grep -v "#" | grep nameserver`;
        $arr = explode("\n", (string) $data);
        $arr = array_filter($arr);
        $clean = [];
        foreach ($arr as $ip) {
            if ($ip == '') {
                continue;
            }
            $foo = explode(' ', trim($ip));
            if (count($foo) == 1) {
                continue;
            }
            array_push($clean, $foo[1]);
        }

        return respond($clean);
    }

    public function setDNSServers()
    {
        $system = rootSystem();
        $flag = $system->dnsUpdate(
            request('dns1'),
            request('dns2'),
            request('dns3')
        );
        if ($flag) {
            return respond('DNS Ayarları güncellendi!');
        } else {
            return respond('DNS Ayarları güncellenemedi!', 201);
        }
    }

    public function uploadLoginLogo(Request $request)
    {
        try {
            request()->validate([
                'photo' => 'mimes:jpeg,png|max:4096|required',
            ]);
        } catch (\Throwable) {
            return respond('Dosya yükleme başarısız!', 201);
        }

        $uploadedFile = $request->file('photo');
        $filename = time().$uploadedFile->getClientOriginalName();

        try {
            Storage::disk('local')->putFileAs(
                'public/files/',
                $uploadedFile,
                $filename
            );
        } catch (\Throwable) {
            return respond('Dosya yükleme başarısız!', 201);
        }

        setEnv(['BRANDING' => '/storage/files/'.$filename]);

        return respond('Dosya yükleme başarılı!', 200);
    }
}
