<?php

namespace App\Http\Controllers\Module;

use App\Http\Controllers\Controller;
use App\Models\Module;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Module Controller
 *
 * @extends Controller
 */
class MainController extends Controller
{
    /**
     * Returns modules view
     *
     * @return JsonResponse|Response
     */
    public function index()
    {
        $modules = Module::all();
        $modules->map(function ($module) {
            $module->enabled_text = $module->enabled
                ? 'Aktif'
                : 'İzin Verilmemiş';
        });

        return magicView('modules.index', [
            'modules' => $modules,
        ]);
    }

    /**
     * Enable or disable module
     *
     * @return JsonResponse|Response
     */
    public function modifyModuleStatus()
    {
        $module = Module::findOrFail(request('module_id'))->first();

        $flag = $module->update([
            'enabled' => request('moduleStatus') == 'true' ? true : false,
        ]);

        if ($flag) {
            return respond('Modül güncellendi.');
        } else {
            return respond(__('Bir hata oluştu. ') . $flag, 201);
        }
    }

    /**
     * Returns module settings
     *
     * @return JsonResponse|Response
     */
    public function getModuleSettings()
    {
        $module = Module::findOrFail(request('module_id'))->first();

        $template = file_get_contents(
            '/liman/modules/' . $module->name . '/template.json'
        );
        $template = json_decode($template, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            return respond('Modul ayarlari okunamiyor.', 201);
        }

        $inputs = $template['settings'];

        $view = view('inputs', [
            'inputs' => $inputs,
        ])->render();

        $data = [];

        $settingsPath = '/liman/modules/' . $module->name . '/settings.json';
        if (is_file($settingsPath)) {
            $data = file_get_contents($settingsPath);
            $data = json_decode($data, true);
            if (json_last_error() == JSON_ERROR_NONE) {
                $data = $data['variables'];
            }
        }

        return respond([
            'view' => $view,
            'data' => $data,
        ]);
    }

    /**
     * Save module settings
     *
     * @return JsonResponse|Response
     */
    public function saveModuleSettings()
    {
        $module = Module::findOrFail(request('module_id'))->first();

        $filePath = '/liman/modules/' . $module->name . '/settings.json';
        $data = [
            'variables' => [],
        ];

        if (is_file($filePath)) {
            $dataJson = file_get_contents($filePath);
            $dataJson = json_decode($dataJson, true);
            if (json_last_error() == JSON_ERROR_NONE) {
                $data = $dataJson;
            }
        }

        foreach (request()->all() as $key => $value) {
            if (substr((string) $key, 0, 4) == 'mod-') {
                $data['variables'][substr((string) $key, 4)] = $value;
            }
        }

        $flag = file_put_contents(
            $filePath,
            json_encode($data, JSON_PRETTY_PRINT)
        );

        if ($flag) {
            return respond('Ayarlar başarıyla kaydedildi.');
        } else {
            return respond('Ayarlar kaydedilemedi!', 201);
        }
    }
}
