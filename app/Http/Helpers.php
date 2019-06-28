<?php

use App\Extension;
use App\Notification;
use App\Script;
use App\Server;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        return Notification::where([
            "user_id" => auth()->id(),
            "read" => false
        ])->orderBy('updated_at', 'desc')->get();
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

        switch ($level){
            case 1:
                Log::emergency($message,$array);
                break;
            case 2:
                Log::alert($message,$array);
                break;
            case 3:
                Log::critical($message,$array);
                break;
            case 4:
                Log::error($message,$array);
                break;
            case 5:
                Log::warning($message,$array);
                break;
            case 6:
                Log::notice($message,$array);
                break;
            case 7:
                Log::info($message,$array);
                break;
            default:
                Log::debug($message,$array);
                break;
        }
    }
}

if (!function_exists('server')) {
    /**
     * @return array|Request|string
     */
    function server()
    {
        if(!request('server')){
            dd(request()->all());
            abort(504,"Sunucu Bulunamadi");
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
     * @return Extension
     */
    function extension($id = null)
    {
        if($id == null){
            $id = request('extension_id');
        }
        return Extension::one($id);
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
            "extension_id" => extension()->id,
            "name" => $key
        ])->first();
        if($target){
            return $target->value;
        }
        return null;
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
            case "Script":
            case "script":
                return Script::find($id);
                break;
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

if (!function_exists('serverKey')) {
    /**
     * @return App\Key
     */
    function serverKey()
    {
        return App\Key::where('server_id',server()->id)->first();
    }
}

if(!function_exists('cities')){
    /**
     * @param null $city
     * @return array|false|int|string
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



