<?php

namespace App\Http\Controllers\Module;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Module;
use App\ModuleHook;

class MainController extends Controller
{
    public function index()
    {
        $modules = Module::all();
        $modules->map(function($module){
            $module->enabled_text = $module->enabled ? "Aktif" : "İzin Verilmemiş";
            $module->hook_count = $module->hooks->count();
        });
        return view('modules.index',[
            "modules" => $modules
        ]);
    }

    public function getHooks()
    {
        $module = Module::findOrFail(request("module_id"));
        $hooks = $module->hooks;
        $hooks->map(function($hook){
            $hook->enabled_text = $hook->enabled ? "Aktif" : "İzin Verilmemiş";
        });
        return view('l.table',[
            "id" => "moduleHooks",
            "value" => $hooks,
            "title" => [
                "Adı" , "Durumu", "*hidden*","*hidden*"
            ],
            "display" => [
                "hook" , "enabled_text", "id:module_hook_id", "enabled:enabled"
            ]
        ]);
    }

    public function modifyHookStatus()
    {
        $idList = json_decode(request('target_ids'));

        $flag = ModuleHook::whereIn("id",$idList)->update([
            "enabled" => request('target_status') == "allow" ? true : false
        ]);

        if($flag){
            return respond("Tetikleyiciler güncellendi.");
        }else{
            return respond("Bir hata oluştu.$flag",201);
        }
    }

    public function modifyModuleStatus()
    {
        $module = Module::findOrFail(request('module_id'))->first();

        $flag = $module->update([
            "enabled" => request('moduleStatus') == "true" ? true : false
        ]);

        if($flag){
            return respond("Modül güncellendi.");
        }else{
            return respond("Bir hata oluştu.$flag",201);
        }
    }

    public function getModuleSettings()
    {
        $module = Module::findOrFail(request('module_id'))->first();

        $template = file_get_contents("/liman/modules/" . $module->name . "/template.json");
        $template = json_decode($template,true);
        if(json_last_error() != JSON_ERROR_NONE){
            return respond("Modul ayarlari okunamiyor.",201);
        }

        $inputs = $template["settings"];
        
        return view('l.inputs',[
            "inputs" => $inputs
        ]);
    }
}
