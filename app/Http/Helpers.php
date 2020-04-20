<?php

use App\AdminNotification;
use App\Extension;
use App\Notification;
use App\Permission;
use App\Server;
use App\Certificate;
use App\Module;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Token;

if (!function_exists('respond')) {
    /**
     * @param $message
     * @param int $status
     * @return JsonResponse|Response
     */
    function respond($message, $status = 200)
    {
        if (request()->wantsJson()) {
            return response()->json([
                "message" => is_array($message) ? $message : __($message),
                "status" => $status
            ], $status);
        } else {
            return response()->view('general.error', [
                "message" => __($message),
                "status" => $status
            ], $status);
        }
    }
}

if (!function_exists('notifications')) {
    /**
     * @return mixed
     */
    function notifications()
    {
        return Notification::where([
            "user_id" => auth()->id(),
            "read" => false
        ])->orderBy('updated_at', 'desc')->get();
    }
}

if (!function_exists('knownPorts')) {
    /**
     * @return mixed
     */
    function knownPorts()
    {
        return $knownPorts = [
            "5986" ,"636"
        ];
    }
}

if (!function_exists('retrieveCertificate')) {
    /**
     * @return mixed
     */
    function retrieveCertificate($hostname, $port)
    {
        $get = stream_context_create(array("ssl" => array(
            "capture_peer_cert" => TRUE,
            "allow_self_signed" => TRUE, "verify_peer" => FALSE, "verify_peer_name" => FALSE,
        )));
        $flag = false;
        try {
            $read = stream_socket_client("ssl://" .
                $hostname . ":" . $port, $errno, $errstr, intval(env('SERVER_CONNECTION_TIMEOUT')), STREAM_CLIENT_CONNECT, $get);
            $flag = true;
        } catch (\Exception $exception) {           
        }
        
        if(!$flag){
            try {
                $read = stream_socket_client("tlsv1.1://" .
                    $hostname . ":" . $port, $errno, $errstr, intval(env('SERVER_CONNECTION_TIMEOUT')), STREAM_CLIENT_CONNECT, $get);
                $flag = true;
            } catch (\Exception $exception) {  
                return [false,"Sertifika alınamıyor!"];         
            }
        }

        $cert = stream_context_get_params($read);
        $certinfo = openssl_x509_parse($cert['options']['ssl']['peer_certificate']);
        openssl_x509_export($cert["options"]["ssl"]["peer_certificate"],$publicKey);
        $certinfo["subjectKeyIdentifier"] = array_key_exists("subjectKeyIdentifier",$certinfo["extensions"]) ? $certinfo["extensions"]["subjectKeyIdentifier"]: "";
        $certinfo["authorityKeyIdentifier"] = array_key_exists("authorityKeyIdentifier",$certinfo["extensions"]) ? substr($certinfo["extensions"]["authorityKeyIdentifier"],6): "";
        $certinfo["validFrom_time_t"] = Carbon::createFromTimestamp($certinfo["validFrom_time_t"])->format('H:i d/m/Y');
        $certinfo["validTo_time_t"] = Carbon::createFromTimestamp($certinfo["validTo_time_t"])->format('H:i d/m/Y');
        unset($certinfo["extensions"]);
        $path = Str::random(10);
        $certinfo["path"] = $path;
        file_put_contents("/tmp/" . $path,$publicKey);
        return [true,$certinfo];
    }
}

if (!function_exists('parseCertificate')) {
    /**
     * @return mixed
     */
    function parseCertificate($hostname, $port)
    {
        
        return [true,$certinfo];
    }
}

if (!function_exists('adminNotifications')) {
    /**
     * @return mixed
     */
    function adminNotifications()
    {
        return AdminNotification::where([
            "read" => "false"
        ])->orderBy('updated_at', 'desc')->get();
    }
}

if (!function_exists('addCertificate')) {
    /**
     * @return mixed
     */
    function addCertificate($hostname,$port,$path)
    {
        $file = "liman-" . $hostname . "_" . $port . ".crt";
        $cert = file_get_contents('/tmp/' . $path);
        $query = "echo '$cert'| sudo tee /usr/local/share/ca-certificates/" . $file;
        shell_exec($query);
        shell_exec("sudo update-ca-certificates");

        // Create Certificate Object.
        $cert = new Certificate([
            "server_hostname" => $hostname,
            "origin" => $port
        ]);
        return $cert->save();
    }
}


if (!function_exists('system_log')) {

    /**
     * @param $level
     * @param $message
     * @param array $array
     */
    function system_log($level, $message, $array = [])
    {
        $array["user_id"] = auth()->id();
        $array["ip_address"] = request()->ip();

        switch ($level) {
            case 1:
                Log::emergency($message, $array);
                break;
            case 2:
                Log::alert($message, $array);
                break;
            case 3:
                Log::critical($message, $array);
                break;
            case 4:
                Log::error($message, $array);
                break;
            case 5:
                Log::warning($message, $array);
                break;
            case 6:
                Log::notice($message, $array);
                break;
            case 7:
                Log::info($message, $array);
                break;
            default:
                Log::debug($message, $array);
                break;
        }
    }
}

if (!function_exists('server')) {
    /**
     * @return \App\Server
     */
    function server()
    {
        if (!request('server')) {
            abort(504, "Sunucu Bulunamadı");
        }
        return request('server');
    }
}

if (!function_exists('script')) {
    /**
     * @return mixed
     */
    function script()
    {
        return request('script');
    }
}

if (!function_exists('servers')) {
    /**
     * @return Server|Builder
     */
    function servers()
    {
        return auth()->user()->servers();
    }
}

if (!function_exists('extensions')) {

    /**
     * @param array $filter
     * @return array
     */
    function extensions($filter = [])
    {
        return Extension::getAll($filter);
    }
}

if (!function_exists('extensionRoute')) {
    /**
     * @param string $route
     * @return string
     */
    function extensionRoute($route)
    {
        return route('extension_server_route', [
            "extension_id" => request()->route('extension_id'),
            "server_id" => request()->route('server_id'),
            "city" => request()->route('city'),
            "unique_code" => $route
        ]);
    }
}

if (!function_exists('extension')) {
    /**
     * @param null $id
     * @return Extension
     */
    function extension($id = null)
    {
        if ($id == null) {
            $id = request('extension_id');
        }
        return Extension::one($id);
    }
}

if (!function_exists('user')) {
    /**
     * @param null $id
     * @return User
     */
    function user()
    {
        return auth()->user();
    }
}

if (!function_exists('sandbox')) {
    /**
     * @param null $id
     * @return App\Classes\Sandbox\Sandbox
     */
    function sandbox($language = null)
    {
        if($language == null){
            $language = extension()->language;
        }
        switch($language){
            case "python":
                return new App\Classes\Sandbox\PythonSandbox;
            break;
            case "php":
            default:
                return new App\Classes\Sandbox\PHPSandbox;
            break;
        }
    }
}

if (!function_exists('hook')) {
    /**
     * @param $name
     * @param array $data
     * @return void
     */
    function hook($name, $data = [])
    {
        $hooks = App\ModuleHook::where('hook',$name)->get();

        array_key_exists("user", $data) ? $data["user"] = user() : null;
        array_key_exists("extension", $data) ? $data["extension"] = extension() : null;
        array_key_exists("server", $data) ? $data["server"] = server() : null;

        $data = base64_encode(json_encode($data));
        $modellist = [];
        foreach($hooks as $hook){
            if(!array_key_exists($hook->module_name,$modellist)){
                $foo = Module::where("name",$hook->module_name)->first();
                if(!$foo){
                    continue;
                }
                $modellist[$hook->module_name] = $foo->enabled;
                unset($foo);
            }

            if($modellist[$hook->module_name] == false){
                continue;
            }
            
            $command = "/liman/modules/" . strtolower($hook->module_name) . "/main $name $data";
            shell_exec("bash -c '$command & disown'");
        }
    }
}

if (!function_exists('redirect_now')) {
    function redirect_now($url, $code = 302)
    {
        try {
            \App::abort($code, '', ['Location' => $url]);
        } catch (\Exception $exception) {
            // the blade compiler catches exceptions and rethrows them
            // as ErrorExceptions :(
            //
            // also the __toString() magic method cannot throw exceptions
            // in that case also we need to manually call the exception
            // handler
            $previousErrorHandler = set_exception_handler(function () {
            });
            restore_error_handler();
            call_user_func($previousErrorHandler, $exception);
            die;
        }
    }
}
if (!function_exists('extensionDb')) {
    /**
     * @param $key
     * @return String
     */
    function extensionDb($key)
    {
        $target = DB::table("user_settings")->where([
            "user_id" => auth()->user()->id,
            "server_id" => server()->id,
            "name" => $key
        ])->first();
        if ($target) {
            $key = env('APP_KEY') . auth()->user()->id . server()->id;
            $decrypted = openssl_decrypt($target->value, 'aes-256-cfb8', $key);
            $stringToDecode = substr($decrypted, 16);
            return base64_decode($stringToDecode);
        }
        return null;
    }
}

if (!function_exists('sudo')) {

    function sudo()
    {
        if(server()->type == "linux_certificate"){
            return "sudo ";
        }
        $pass64 = base64_encode(extensionDb("clientPassword")."\n");
        return 'echo ' . $pass64 .' | base64 -d | sudo -S -p " " id 2>/dev/null 1>/dev/null; sudo ';
    }
}

if (!function_exists('getObject')) {
    /**
     * @param $type
     * @param null $id
     * @return Extension|bool|Model|Builder|object
     */
    function getObject($type, $id = null)
    {
        // Check for type
        switch ($type) {
            case "Extension":
            case "extension":
                return Extension::find($id);
                break;
            case "Server":
            case "server":
                return Server::find($id);
                break;
            default:
                return false;
        }
    }
}

if (!function_exists('objectToArray')) {
    /**
     * @param $array
     * @param $key
     * @param $value
     * @return array
     */
    function objectToArray($array, $key, $value)
    {
        $combined_array = [];
        foreach ($array as $item) {
            if (is_array($item)) {
                $combined_array[$item[$key]] = $item[$value];
            } else {
                $combined_array[$item->__get($key)] = $item->__get($value);
            }
        }
        return $combined_array;
    }
}

if (!function_exists('cleanArray')) {
    function cleanArray($array)
    {
        $newArray = [];
        foreach($array as $row){
            $newArray[] = $row;
        }
        return $newArray;
    }
}

if (!function_exists('serverKey')) {
    /**
     * @return App\Key
     */
    function serverKey()
    {
        return App\Key::where('server_id', server()->id)->first();
    }
}

if (!function_exists('cities')) {
    /**
     * @param null $city
     * @return array|false|int|string
     */
    function cities($city = null)
    {
        $cities = [
            "Adana" => "01",
            "Adıyaman" => "02",
            "Afyonkarahisar" => "03",
            "Ağrı" => "04",
            "Amasya" => "05",
            "Ankara" => "06",
            "Antalya" => "07",
            "Artvin" => "08",
            "Aydın" => "09",
            "Balıkesir" => "10",
            "Bilecik" => "11",
            "Bingöl" => "12",
            "Bitlis" => "13",
            "Bolu" => "14",
            "Burdur" => "15",
            "Bursa" => "16",
            "Çanakkale" => "17",
            "Çankırı" => "18",
            "Çorum" => "19",
            "Denizli" => "20",
            "Diyarbakır" => "21",
            "Edirne" => "22",
            "Elazığ" => "23",
            "Erzincan" => "24",
            "Erzurum" => "25",
            "Eskişehir" => "26",
            "Gaziantep" => "27",
            "Giresun" => "28",
            "Gümüşhane" => "29",
            "Hakkâri" => "30",
            "Hatay" => "31",
            "Isparta" => "32",
            "Mersin" => "33",
            "İstanbul" => "34",
            "İzmir" => "35",
            "Kars" => "36",
            "Kastamonu" => "37",
            "Kayseri" => "38",
            "Kırklareli" => "39",
            "Kırşehir" => "40",
            "Kocaeli" => "41",
            "Konya" => "42",
            "Kütahya" => "43",
            "Malatya" => "44",
            "Manisa" => "45",
            "Kahramanmaraş" => "46",
            "Mardin" => "47",
            "Muğla" => "48",
            "Muş" => "49",
            "Nevşehir" => "50",
            "Niğde" => "51",
            "Ordu" => "52",
            "Rize" => "53",
            "Sakarya" => "54",
            "Samsun" => "55",
            "Siirt" => "56",
            "Sinop" => "57",
            "Sivas" => "58",
            "Tekirdağ" => "59",
            "Tokat" => "60",
            "Trabzon" => "61",
            "Tunceli" => "62",
            "Şanlıurfa" => "63",
            "Uşak" => "64",
            "Van" => "65",
            "Yozgat" => "66",
            "Zonguldak" => "67",
            "Aksaray" => "68",
            "Bayburt" => "69",
            "Karaman" => "70",
            "Kırıkkale" => "71",
            "Batman" => "72",
            "Şırnak" => "73",
            "Bartın" => "74",
            "Ardahan" => "75",
            "Iğdır" => "76",
            "Yalova" => "77",
            "Karabük" => "78",
            "Kilis" => "79",
            "Osmaniye" => "80",
            "Düzce" => "81",
            "Kuzey Kıbrıs" => "82"
        ];
        if ($city) {
            return array_search($city, $cities);
        }
        return $cities;
    }
}

if (!function_exists('clean_score')) {
    /**
     * @return array|Request|string
     */
    function clean_score($text)
    {
        return str_replace('-', '', $text);
    }
}
if (!function_exists('is_json')) {
    function is_json($string, $return_data = false)
    {
        $data = json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE) ? ($return_data ? $data : TRUE) : FALSE;
    }
}

if (!function_exists('getPermissions')) {
    function getPermissions($path)
    {
        return substr(sprintf("%o", fileperms($path)), -4);
    }
}
if (!function_exists('setEnv')) {
    function setEnv(array $values)
    {

        $envFile = app()->environmentFilePath();
        $str = file_get_contents($envFile);

        if (count($values) > 0) {
            foreach ($values as $envKey => $envValue) {

                $str .= "\n"; // In case the searched variable is in the last line without \n
                $keyPosition = strpos($str, "{$envKey}=");
                $endOfLinePosition = strpos($str, "\n", $keyPosition);
                $oldLine = substr($str, $keyPosition, $endOfLinePosition - $keyPosition);

                // If key does not exist, add it
                if (!$keyPosition || !$endOfLinePosition || !$oldLine) {
                    $str .= "{$envKey}={$envValue}\n";
                } else {
                    $str = str_replace($oldLine, "{$envKey}={$envValue}", $str);
                }
            }
        }

        $str = substr($str, 0, -1);
        if (!file_put_contents($envFile, $str)) return false;
        return true;
    }
}

if (!function_exists('checkHealth')) {
    function checkHealth()
    {
        $allowed = [
            "certs" => "0700",
            "database" => "0700",
            "extensions" => "0755",
            "keys" => "0755",
            "logs" => "0700",
            "sandbox" => "0755",
            "server" => "0700",
            "webssh" => "0700"
        ];
        $messages = [];

        // Check Permissions and Owners
        foreach ($allowed as $name => $permission) {
            // Permission Check
            $file = "/liman/" . $name;
            if (!file_exists($file)) {
                array_push($messages, [
                    "type" => "danger",
                    "message" => "'/liman/$name' isimli sistem dosyası bulunamadı"
                ]);
                continue;
            }

            if (getPermissions('/liman/' . $name) != $permission) {
                array_push($messages, [
                    "type" => "danger",
                    "message" => "'/liman/$name' izni hatalı (" . getPermissions('/liman/' . $name) . ")."
                ]);
            }

            // Owners Check
            $owner = posix_getpwuid(fileowner($file))["name"];
            $group = posix_getgrgid(filegroup($file))["name"];
            if ($owner != "liman" || $group != "liman") {
                array_push($messages, [
                    "type" => "danger",
                    "message" => "'/liman/$name' dosyasının sahibi hatalı ($owner : $group)."
                ]);
            }
        }




        // Check Extra Files
        $extra = array_diff(array_diff(scandir("/liman"), array('..', '.')), array_keys($allowed));
        foreach ($extra as $item) {
            array_push($messages, [
                "type" => "warning",
                "message" => "'/liman/$item' dosyasina izin verilmiyor."
            ]);
        }
        if (empty($messages)) {
            array_push($messages, [
                "type" => "success",
                "message" => "Herşey Yolunda, sıkıntı yok!"
            ]);
        }

        return $messages;
    }
}

if (!function_exists('lDecrypt')) {
    function lDecrypt($data)
    {
        $key = env('APP_KEY') . user()->id . server()->id;
        $decrypted = openssl_decrypt($data, 'aes-256-cfb8', $key);
        $stringToDecode = substr($decrypted, 16);
        return base64_decode($stringToDecode);
    }
}
if (!function_exists('setBaseDn')) {

    function setBaseDn($ldap_host=null)
    {
        $ldap_host = $ldap_host ? $ldap_host : config('ldap.ldap_host');
        $flag = false;
        $connection = ldap_connect($ldap_host,389);
        ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($connection, LDAP_OPT_NETWORK_TIMEOUT, 10);
        ldap_set_option($connection, LDAP_OPT_TIMELIMIT, 10);
        $flag = ldap_bind($connection);
        $outputs = ldap_read($connection,'','objectclass=*');
        $entries = ldap_get_entries($connection,$outputs)[0];
        $domain = str_replace("dc=","",strtolower($entries["rootdomainnamingcontext"][0]));
        $domain = str_replace(",", ".", $domain);
        setEnv([
            "LDAP_BASE_DN" => $entries["rootdomainnamingcontext"][0],
            "LDAP_DOMAIN" => $domain
        ]);
        return $flag;
    }

}

if (!function_exists('checkPort')) {
    function checkPort($ip, $port) {
        $fp = @fsockopen($ip, $port, $errno, $errstr, 0.1);
        if (!$fp) {
            return false;
        } else {
            fclose($fp);
            return true;
        }
    }
}
if (!function_exists('endsWith')) {
    function endsWith($string, $endString) 
    { 
        $len = strlen($endString); 
        if ($len == 0) { 
            return true; 
        } 
        return (substr($string, -$len) === $endString); 
    } 
} 

if (!function_exists('scanTranslations')) {
    function scanTranslations($directory) {
        $pattern =
        '[^\w]' . 
        '(?<!->)' . 
        '(' . implode('|', ['__']) . ')' . 
        "\(" . 
        "[\'\"]" . 
        '(' . 
        '.+' . 
        ')' .
        "[\'\"]" . 
        "[\),]"  
        ;
        $allMatches = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
        foreach ($iterator as $file) {
            if ($file->isDir()) continue;
            if(endsWith($file->getPathname(), ".php")){
                $content = file_get_contents($file->getPathname());
                if (preg_match_all("/$pattern/siU", $content, $matches)) {
                    foreach($matches[2] as $row){
                        $allMatches[$row] = $row;
                    }
                }
            }
        }
        return $allMatches;
    }
}