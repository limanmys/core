<?php

namespace App\Http\Controllers\Extension;

use App\Http\Controllers\Controller;
use App\Models\License;

/**
 * Class SettingsController
 * @package App\Http\Controllers\Extension
 */
class SettingsController extends Controller
{
    // Extension Management Home Page
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function settings_all()
    {
        system_log(7, "EXTENSION_LIST");
        $updateAvailable = is_file(storage_path("extension_updates"));
        $extensions = extensions()->map(function ($item) {
            if (!$item["issuer"]) {
                $item["issuer"] = __('Güvenli olmayan üretici!');
            }
            return $item;
        });

        return magicView('extension_pages.manager', [
            "updateAvailable" => $updateAvailable,
            "extensions" => $extensions,
        ]);
    }

    public function addLicense()
    {
        $license = License::updateOrCreate(
            ['extension_id' => extension()->id],
            ['data' => request('license')]
        );
        if ($license) {
            return respond("Lisans Eklendi");
        } else {
            return respond("Lisans Eklenemiyor!", 201);
        }
    }

    // Extension Management Page

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function settings_one()
    {
        system_log(7, "EXTENSION_SETTINGS_PAGE", [
            "extension_id" => extension()->_id,
        ]);
        if (extension()->language == null) {
            extension()->update([
                "language" => "php",
            ]);
            $extension = json_decode(
                file_get_contents(
                    "/liman/extensions/" .
                        strtolower(extension()->name) .
                        DIRECTORY_SEPARATOR .
                        "db.json"
                ),
                true
            );
            $extension["language"] = "php";
            file_put_contents(
                "/liman/extensions/" .
                    strtolower(extension()->name) .
                    DIRECTORY_SEPARATOR .
                    "db.json",
                json_encode($extension, JSON_PRETTY_PRINT)
            );
        }
        // Return view with required parameters.
        return magicView('extension_pages.one', [
            "extension" => getExtensionJson(extension()->name),
        ]);
    }

    public function update()
    {
        $extension = json_decode(
            file_get_contents(
                "/liman/extensions/" .
                    strtolower(extension()->name) .
                    DIRECTORY_SEPARATOR .
                    "db.json"
            ),
            true
        );

        if (request('type') == "general") {
            extension()->update(request()->only([
                'display_name',
                'icon',
                'support',
                'version',
                'service',
                'require_key',
                'sslPorts',
                'supportedLiman'
            ]));
            $extension["display_name"] = request("display_name");
            $extension["icon"] = request("icon");
            $extension["service"] = request("service");
            $extension["version"] = request("version");
            $extension["require_key"] = request("require_key");
            $extension["verification"] = request("verification");
            $extension["dependencies"] = request("dependencies");
            $extension["sslPorts"] = request("sslPorts");
            $extension["supportedLiman"] = request("supportedLiman");
        } else {
            $values = $extension[request('table')];
            foreach ($values as $key => $value) {
                if ($value["name"] == request('name_old')) {
                    switch (request('table')) {
                        case "database":
                            $values[$key]["variable"] = request('variable');
                            $values[$key]["type"] = request('type');
                            $values[$key]["name"] = request('name');
                            $values[$key]["required"] = request('required')
                                ? true
                                : false;
                            $values[$key]["global"] = request('global')
                                ? true
                                : false;
                            break;
                        case "widgets":
                            $values[$key]["target"] = request('target');
                            $values[$key]["type"] = request('type');
                            $values[$key]["name"] = request('name');
                            $values[$key]["icon"] = request('icon');
                            break;
                    }
                    break;
                }
            }
            $extension[request("table")] = $values;
        }
        if (array_key_exists("version_code", $extension)) {
            $extension["version_code"] = intval($extension["version_code"]) + 1;
        } else {
            $extension["version_code"] = 1;
        }
        file_put_contents(
            "/liman/extensions/" .
                strtolower(extension()->name) .
                DIRECTORY_SEPARATOR .
                "db.json",
            json_encode($extension, JSON_PRETTY_PRINT)
        );

        system_log(7, "EXTENSION_SETTINGS_UPDATE", [
            "extension_id" => extension()->_id,
            "settings_type" => request('table'),
        ]);

        return respond("Güncellendi.", 200);
    }

    public function add()
    {
        $extension = json_decode(
            file_get_contents(
                "/liman/extensions/" .
                    strtolower(extension()->name) .
                    DIRECTORY_SEPARATOR .
                    "db.json"
            ),
            true
        );

        $values = $extension[request('table')];
        switch (request('table')) {
            case "database":
                array_push($values, [
                    "variable" => request('variable'),
                    "type" => request('type'),
                    "name" => request('name'),
                    "required" => request('required') ? true : false,
                    "global" => request('global') ? true : false,
                ]);
                break;
            case "widgets":
                array_push($values, [
                    "target" => request('target'),
                    "type" => request('type'),
                    "name" => request('name'),
                    "icon" => request('icon'),
                ]);
                break;
        }
        $extension[request('table')] = $values;

        if (array_key_exists("version_code", $extension)) {
            $extension["version_code"] = intval($extension["version_code"]) + 1;
        } else {
            $extension["version_code"] = 1;
        }

        file_put_contents(
            "/liman/extensions/" .
                strtolower(extension()->name) .
                DIRECTORY_SEPARATOR .
                "db.json",
            json_encode($extension, JSON_PRETTY_PRINT)
        );

        system_log(7, "EXTENSION_SETTINGS_ADD", [
            "extension_id" => extension()->id,
            "settings_type" => request('table'),
        ]);

        return respond("Eklendi", 200);
    }

    public function remove()
    {
        $extension = json_decode(
            file_get_contents(
                "/liman/extensions/" .
                    strtolower(extension()->name) .
                    DIRECTORY_SEPARATOR .
                    "db.json"
            ),
            true
        );

        $values = $extension[request('table')];
        foreach ($values as $key => $value) {
            if ($value["name"] == request('name')) {
                unset($values[$key]);
                break;
            }
        }

        $extension[request('table')] = $values;

        if (array_key_exists("version_code", $extension)) {
            $extension["version_code"] = intval($extension["version_code"]) + 1;
        } else {
            $extension["version_code"] = 1;
        }

        file_put_contents(
            "/liman/extensions/" .
                strtolower(extension()->name) .
                DIRECTORY_SEPARATOR .
                "db.json",
            json_encode($extension, JSON_PRETTY_PRINT)
        );

        system_log(7, "EXTENSION_SETTINGS_REMOVE", [
            "extension_id" => extension()->id,
            "settings_type" => request('table'),
        ]);

        return respond("Sayfa Silindi.", 200);
    }

    public function getFunctionParameters()
    {
        $function_name = request('function_name');
        $extension = json_decode(
            file_get_contents(
                "/liman/extensions/" .
                    strtolower(extension()->name) .
                    DIRECTORY_SEPARATOR .
                    "db.json"
            ),
            true
        );
        $function = collect($extension['functions'])
            ->where('name', $function_name)
            ->first();
        $parameters = isset($function['parameters'])
            ? $function['parameters']
            : [];

        return magicView('table', [
            "value" => $parameters,
            "title" => ["Parametre Adı", "Değişken Adı", "Tipi"],
            "display" => ["name", "variable", "type"],
            "menu" => [
                "Sil" => [
                    "target" => "deleteFunctionParameters",
                    "icon" => "fa-trash",
                ],
            ],
        ]);
    }

    public function addFunctionParameter()
    {
        $function_name = request('function_name');
        $extension = json_decode(
            file_get_contents(
                "/liman/extensions/" .
                    strtolower(extension()->name) .
                    DIRECTORY_SEPARATOR .
                    "db.json"
            ),
            true
        );

        if (isset($extension['functions'])) {
            foreach ($extension['functions'] as $key => $function) {
                if ($function['name'] == $function_name) {
                    $extension['functions'][$key]['parameters'] = isset(
                        $extension['functions'][$key]['parameters']
                    )
                        ? $extension['functions'][$key]['parameters']
                        : [];
                    array_push($extension['functions'][$key]['parameters'], [
                        "variable" => request("variable"),
                        "type" => request("type"),
                        "name" => request("name"),
                    ]);

                    if (array_key_exists("version_code", $extension)) {
                        $extension["version_code"] =
                            intval($extension["version_code"]) + 1;
                    } else {
                        $extension["version_code"] = 1;
                    }

                    file_put_contents(
                        "/liman/extensions/" .
                            strtolower(extension()->name) .
                            DIRECTORY_SEPARATOR .
                            "db.json",
                        json_encode($extension, JSON_PRETTY_PRINT)
                    );
                    return respond("Parametre başarıyla eklendi!");
                }
                break;
            }
        }
        return respond("Fonksiyon bulunamadı!", 201);
    }

    public function deleteFunctionParameters()
    {
        $function_name = request('function_name');
        $parameter_variable = request('parameter_variable');

        $extension = json_decode(
            file_get_contents(
                "/liman/extensions/" .
                    strtolower(extension()->name) .
                    DIRECTORY_SEPARATOR .
                    "db.json"
            ),
            true
        );

        if (isset($extension['functions'])) {
            foreach ($extension['functions'] as $key => $function) {
                if ($function['name'] == $function_name) {
                    if (isset($function['parameters'])) {
                        foreach (
                            $function['parameters']
                            as $parameter_key => $parameter
                        ) {
                            if ($parameter['variable'] == $parameter_variable) {
                                unset(
                                    $extension['functions'][$key]['parameters'][
                                        $parameter_key
                                    ]
                                );
                                if (
                                    array_key_exists("version_code", $extension)
                                ) {
                                    $extension["version_code"] =
                                        intval($extension["version_code"]) + 1;
                                } else {
                                    $extension["version_code"] = 1;
                                }
                                file_put_contents(
                                    "/liman/extensions/" .
                                        strtolower(extension()->name) .
                                        DIRECTORY_SEPARATOR .
                                        "db.json",
                                    json_encode($extension, JSON_PRETTY_PRINT)
                                );
                                return respond("Parametre başarıyla silindi!");
                            }
                        }
                        return respond("Parametre bulunamadı!", 201);
                    }
                }
                break;
            }
        }
        return respond("Fonksiyon bulunamadı!", 201);
    }

    public function addFunction()
    {
        $extension = json_decode(
            file_get_contents(
                "/liman/extensions/" .
                    strtolower(extension()->name) .
                    DIRECTORY_SEPARATOR .
                    "db.json"
            ),
            true
        );

        $functions = [];

        if (array_key_exists("functions", $extension)) {
            $functions = $extension["functions"];
        }

        array_push($functions, [
            "name" => request("name"),
            "description" => request("description"),
            "isActive" => request()->has("isActive") ? "true" : "false",
            "displayLog" => request()->has("displayLog") ? "true" : "false",
        ]);

        $extensionSQL = extension();
        if (request()->has("displayLog")) {
            if ($extensionSQL->displays == null) {
                $extensionSQL->update([
                    "displays" => [request('name')],
                ]);
            } else {
                $current = $extensionSQL->displays;
                array_push($current, request('name'));
                $extensionSQL->update([
                    "displays" => $current,
                ]);
            }
        }

        $extension["functions"] = $functions;
        if (array_key_exists("version_code", $extension)) {
            $extension["version_code"] = intval($extension["version_code"]) + 1;
        } else {
            $extension["version_code"] = 1;
        }
        file_put_contents(
            "/liman/extensions/" .
                strtolower(extension()->name) .
                DIRECTORY_SEPARATOR .
                "db.json",
            json_encode($extension, JSON_PRETTY_PRINT)
        );

        system_log(7, "EXTENSION_SETTINGS_ADD_FUNCTION", [
            "extension_id" => extension()->id,
            "function" => request('name'),
        ]);

        return respond("Fonksiyon Eklendi.", 200);
    }

    public function updateFunction()
    {
        $extension = json_decode(
            file_get_contents(
                "/liman/extensions/" .
                    strtolower(extension()->name) .
                    DIRECTORY_SEPARATOR .
                    "db.json"
            ),
            true
        );

        $functions = [];

        if (array_key_exists("functions", $extension)) {
            $functions = $extension["functions"];
        }

        if (empty($functions)) {
            return respond("Bir hata oluştu!", 201);
        }

        for ($i = 0; $i < count($functions); $i++) {
            if (request("old") == $functions[$i]["name"]) {
                $functions[$i] = [
                    "name" => request("name"),
                    "description" => request("description"),
                    "isActive" => request()->has("isActive") ? "true" : "false",
                    "displayLog" => request()->has("displayLog")
                        ? "true"
                        : "false",
                ];
            }
        }

        $extensionSQL = extension();
        if ($extensionSQL->displays != null) {
            $current = $extensionSQL->displays;
            if (request()->has('displayLog')) {
                if (!in_array(request('name'), $current)) {
                    array_push($current, request('name'));
                }
            } else {
                if (in_array(request('name'), $current)) {
                    unset($current[array_search(request('name'), $current)]);
                }
            }
            if (empty($current)) {
                $current = null;
            }
            extension()->update([
                "displays" => $current,
            ]);
        }

        $extension["functions"] = $functions;
        if (array_key_exists("version_code", $extension)) {
            $extension["version_code"] = intval($extension["version_code"]) + 1;
        } else {
            $extension["version_code"] = 1;
        }
        file_put_contents(
            "/liman/extensions/" .
                strtolower(extension()->name) .
                DIRECTORY_SEPARATOR .
                "db.json",
            json_encode($extension, JSON_PRETTY_PRINT)
        );

        system_log(7, "EXTENSION_SETTINGS_UPDATE_FUNCTION", [
            "extension_id" => extension()->id,
            "function" => request('name'),
        ]);

        return respond("Fonksiyon güncellendi.", 200);
    }

    public function removeFunction()
    {
        $extension = json_decode(
            file_get_contents(
                "/liman/extensions/" .
                    strtolower(extension()->name) .
                    DIRECTORY_SEPARATOR .
                    "db.json"
            ),
            true
        );

        $functions = [];

        if (array_key_exists("functions", $extension)) {
            $functions = $extension["functions"];
        }

        if (empty($functions)) {
            return respond("Bir hata oluştu!", 201);
        }

        for ($i = 0; $i < count($functions); $i++) {
            if (request("name") == $functions[$i]["name"]) {
                unset($functions[$i]);
            }
        }

        $extension["functions"] = $functions;
        if (array_key_exists("version_code", $extension)) {
            $extension["version_code"] = intval($extension["version_code"]) + 1;
        } else {
            $extension["version_code"] = 1;
        }
        file_put_contents(
            "/liman/extensions/" .
                strtolower(extension()->name) .
                DIRECTORY_SEPARATOR .
                "db.json",
            json_encode($extension, JSON_PRETTY_PRINT)
        );

        system_log(7, "EXTENSION_SETTINGS_REMOVE_FUNCTION", [
            "extension_id" => extension()->id,
            "function" => request('name'),
        ]);

        return respond("Fonksiyon Silindi.", 200);
    }

    public function getExtensionUpdates()
    {
        return respond(
            array_values(json_decode(file_get_contents(storage_path('extension_updates')),true))
        );
    }
}
