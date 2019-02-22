<?php

use Illuminate\Support\Facades\Log;

if (!function_exists('respond')) {
    /**
     * @param $message
     * @param int $status
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    function respond($message, $status = 200)
    {
        if (\request()->wantsJson()) {
            return response()->json([
                "message" => __($message),
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
        return \App\Notification::where([
            "user_id" => \Auth::id(),
            "read" => false
        ])->orderBy('updated_at', 'desc')->get();
    }
}

if (!function_exists('liman_log')) {
    /**
     * @param $message
     */
    function liman_log($message)
    {
        Log::info(auth()->id() . ":" . $message);
    }
}
if (!function_exists('server')) {
    /**
     * @return \App\Server
     */
    function server()
    {
        $server = request('server');
        $key = \App\Key::where('server_id',$server->_id)->first();
        $server->key = $key;
        return $server;
    }
}

if (!function_exists('script')) {
    /**
     * @return mixed
     */
    function script()
    {
        return \App\Script::where('_id', request('script_id'))->first();
    }
}

if (!function_exists('servers')) {
    /**
     * @return array
     */
    function servers()
    {
        return \App\Server::getAll();
    }
}

if (!function_exists('extensions')) {

    /**
     * @param null $filter
     * @return array
     */
    function extensions($filter = [])
    {
        return \App\Extension::getAll($filter);
    }
}

if (!function_exists('extensionRoute')) {
    /**
     * @param string $route
     * @return string
     */
    function extensionRoute($route)
    {
        return route('extension_server_route',[
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
     * @return \App\Extension
     */
    function extension($id = null)
    {
        if($id == null){
            $id = request('extension_id');
        }
        return \App\Extension::one($id);
    }
}

if (!function_exists('extensionDb')) {
    /**
     * @param null $id
     * @return \App\Extension
     */
    function extensionDb($key)
    {
        $extension_id = request('extension_id');
        return server()->extensions[$extension_id][$key];
    }
}

if (!function_exists('getCertificate')) {

    /**
     * @param $server
     * @param $ip_address
     * @return string|null
     */
    function getCertificate($server, $ip_address)
    {
        $query = "openssl s_client -connect " . $server . ":" . $ip_address .
            " 2>/dev/null </dev/null |  sed -ne '/-BEGIN CERTIFICATE-/,/-END CERTIFICATE-/p'";
        return shell_exec($query);
    }
}

if (!function_exists('getObject')) {
    /**
     * @param $type
     * @param null $id
     * @return bool
     */
    function getObject($type, $id = null)
    {
        // Check for type
        switch ($type) {
            case "Script":
            case "script":
                return \App\Script::where('_id', $id)->first();
                break;
            case "Extension":
            case "extension":
                return \App\Extension::where('_id', $id)->first();
                break;
            case "Server":
            case "server":
                return \App\Server::where('_id', $id)->first();
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
        foreach($array as $item){
            if(is_array($item)){
                $combined_array[$item[$key]] = $item[$value];
            }else{
                $combined_array[$item->__get($key)] = $item->__get($value);
            }

        }
        return $combined_array;
    }
}

if(!function_exists('cities')){
    /**
     * @return array
     */
    function cities($city = null){
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
            "Düzce" => "81"
        ];
        if($city){
            return array_search($city, $cities);
        }
        return $cities;
    }
}