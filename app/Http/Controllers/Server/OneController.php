<?php

namespace App\Http\Controllers\Server;

use App\Models\ConnectorToken;
use App\Models\Extension;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\User;
use App\Models\Permission;
use App\System\Command;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OneController extends Controller
{
    public function one()
    {
        $server = server();
        if (!$server) {
            abort(504, "Sunucu Bulunamadı.");
        }

        try {
            if ($server->isWindows()) {
                preg_match('/\d+/', $server->getUptime(), $output);
                $uptime = $output[0];
            } else {
                $uptime = $server->getUptime();
            }
            $uptime = Carbon::parse($uptime)->diffForHumans();
        } catch (\Throwable $e) {
            $uptime = __("Uptime parse edemiyorum.");
        }        

        $outputs = [
            "hostname" => $server->getHostname(),
            "version" => $server->getVersion(),
            "nofservices" => $server->getNoOfServices(),
            "nofprocesses" => $server->getNoOfProcesses(),
            "uptime" => $uptime,
        ];

        $input_extensions = [];
        $available_extensions = $this->availableExtensions();

        foreach ($available_extensions as $extension) {
            $arr = [];
            if (isset($extension->install)) {
                foreach ($extension->install as $key => $parameter) {
                    $arr[$parameter["name"]] = $key . ":" . $parameter["type"];
                }
            }
            $arr[$extension->display_name . ":" . $extension->id] =
                "extension_id:hidden";
            $input_extensions[] = [
                "name" => $extension->display_name,
                "id" => $extension->id,
            ];
        }

        return view('server.one.main', [
            "server" => $server,
            "favorite" => $server->isFavorite(),
            "outputs" => $outputs,
            "installed_extensions" => $this->installedExtensions(),
            "available_extensions" => $available_extensions,
            "input_extensions" => $input_extensions,
        ]);
    }

    public function remove()
    {
        hook('server_delete', [
            "server" => server(),
        ]);

        // Check if authenticated user is owner or admin.
        if (
            server()->user_id != auth()->id() &&
            !auth()
                ->user()
                ->isAdmin()
        ) {
            // Throw error
            return respond("Yalnızca kendi sunucunuzu silebilirsiniz.", 202);
        }
        $server = server();
        // Delete the Server Object.
        server()->delete();
        Notification::new(
            __("Bir sunucu silindi."),
            "notify",
            __(":server (:ip) isimli sunucu silindi.", [
                "server" => $server->name,
                "ip" => $server->ip_address,
            ])
        );
        // Redirect user to servers home page.
        return respond(route('servers'), 300);
    }

    public function serviceCheck()
    {
        if (is_numeric(extension()->service)) {
            if (extension()->service == -1) {
                $flag = true;
            } else {
                $status = @fsockopen(
                    server()->ip_address,
                    extension()->service,
                    $errno,
                    $errstr,
                    intval(config('liman.server_connection_timeout')) / 1000
                );
                $flag = is_resource($status);
            }
        } else {
            $flag = server()->isRunning(extension()->service);
        }
        // Return the button class name ~ color to update client.
        if ($flag) {
            return respond('btn-success');
        } else {
            return respond('btn-danger');
        }
    }

    public function service()
    {
        // Retrieve Service name from extension.
        $service = Extension::where(
            'name',
            'like',
            request('extension')
        )->first()->service;

        $output = Command::runSudo('systemctl @{:action} @{:service}', [
            'action' => request('action'),
            'service' => $service
        ]);
        return [
            "result" => 200,
            "data" => $output,
        ];
    }

    public function enableExtension()
    {
        hook('server_extension_add', [
            "server" => server(),
            "request" => request()->all(),
        ]);

        if (
            !auth()->user()->id == server()->user_id &&
            !auth()
                ->user()
                ->isAdmin()
        ) {
            return respond(
                "Bu islemi yalnizca sunucu sahibi ya da bir yonetici yapabilir."
            );
        }
        $extensions = json_decode(request('extensions'));

        foreach ($extensions as $extension) {
            $data = [
                "server_id" => server()->id,
                "extension_id" => $extension,
            ];
            if (
                DB::table("server_extensions")
                    ->where($data)
                    ->doesntExist()
            ) {
                $data["id"] = Str::uuid();
                DB::table("server_extensions")->insert($data);
            }
        }
        return respond('Eklenti başarıyla eklendi.');
    }

    public function update()
    {
        if (strlen(request('name')) > 24) {
            return respond("Lütfen daha kısa bir sunucu adı girin.", 201);
        }

        if (server()->name !== request('name')) {
            Notification::new(
                __("Server Adı Güncellemesi"),
                "notify",
                __(":old isimli sunucunun adı :new olarak değiştirildi.", [
                    "old" => server()->name,
                    "new" => request('name'),
                ])
            );
        }

        hook('server_update', [
            "request" => request()->all(),
        ]);

        $output = server()->update([
            "name" => request('name'),
            "control_port" => request('control_port'),
            "ip_address" => request('ip_address'),
            "city" => request('city'),
        ]);

        ConnectorToken::clear();

        return [
            "result" => 200,
            "data" => $output,
        ];
    }

    public function terminal()
    {
    }

    public function upload()
    {
        // Store file in /tmp directory.
        request()
            ->file('file')
            ->move(
                '/tmp/',
                request()
                    ->file('file')
                    ->getClientOriginalName()
            );

        // Send file to the server.
        server()->putFile(
            '/tmp/' .
                request()
                    ->file('file')
                    ->getClientOriginalName(),
            \request('path')
        );

        // Build query to check if file exists in server to validate.
        $query =
            "(ls @{:path} >> /dev/null 2>&1 && echo 1) || echo 0";

        $flag = Command::runSudo($query, [
            'path' => request('path')
        ], false);

        // Respond according to the flag.
        if ($flag == "1") {
            return respond("Dosya başarıyla yüklendi.");
        }
        return respond('Dosya yüklenemedi.', 201);
    }

    public function download()
    {
        // Generate random file name
        $file = Str::random();
        server()->getFile(request('path'), '/tmp/' . $file);

        // Extract file name from path.
        $file_name = explode("/", request('path'));

        // Send file to the user then delete it.
        return response()
            ->download('/tmp/' . $file, $file_name[count($file_name) - 1])
            ->deleteFileAfterSend();
    }

    private function availableExtensions()
    {
        return Extension::getAll()->whereNotIn(
            "id",
            DB::table("server_extensions")
                ->where([
                    "server_id" => server()->id,
                ])
                ->pluck("extension_id")
                ->toArray()
        );
    }

    private function installedExtensions()
    {
        return server()->extensions();
    }

    public function favorite()
    {
        $current = DB::table("user_favorites")
            ->where([
                "user_id" => auth()->user()->id,
                "server_id" => server()->id,
            ])
            ->first();

        if ($current && request("action") != "true") {
            DB::table("user_favorites")
                ->where([
                    "user_id" => auth()->user()->id,
                    "server_id" => server()->id,
                ])
                ->delete();
        } elseif (!$current) {
            DB::table("user_favorites")->insert([
                "id" => Str::uuid(),
                "server_id" => server()->id,
                "user_id" => auth()->user()->id,
            ]);
        }

        return respond("Düzenlendi.", 200);
    }

    public function stats()
    {
        if (server()->isLinux()) {
            $cpuPercent = server()->run(
                "ps -eo %cpu --no-headers | grep -v 0.0 | awk '{s+=$1} END {print s/NR*10}'"
            );
            $ramPercent = server()->run(
                "free | grep Mem | awk '{print $3/$2 * 100.0}'"
            );
            $ioPercent = server()->run(
                "iostat -d | tail -n +4 | head -n -1 | awk '{s+=$2} END {print s}'"
            );
            $firstDown = $this->calculateNetworkBytes();
            $firstUp = $this->calculateNetworkBytes(false);
            sleep(1);
            $secondDown = $this->calculateNetworkBytes();
            $secondUp = $this->calculateNetworkBytes(false);
            return [
                'cpu' => round($cpuPercent, 2),
                'ram' => round($ramPercent, 2),
                'io' => round($ioPercent, 2),
                'network' => [
                    'down' => round(($secondDown - $firstDown) / 1024 / 2, 2),
                    'up' => round(($secondUp - $firstUp) / 1024 / 2, 2),
                ],
                'time' => \Carbon\Carbon::now()->format('H:i:s'),
            ];
        } elseif (server()->isWindows()) {
            /*
            $cpu = substr(
                server()->run(
                    "Get-WmiObject win32_processor | Measure-Object -property LoadPercentage -Average | Select Average"
                ),
                23,
                -3
            );
            echo $cpu;
            $disk = round(
                floatval(
                    server()->run(
                        "(1 - (Get-WmiObject -Class Win32_logicalDisk | ? {\$_.DriveType -eq '3'}).FreeSpace / (Get-WmiObject -Class Win32_logicalDisk | ? {\$_.DriveType -eq '3'}).Size) * 100"
                    )
                ),
                2
            );
            try {
                $usedRam = intval(
                    substr(
                        server()->run("Get-Counter '\Memory\Available MBytes'"),
                        390,
                        -335
                    )
                );
                $totalRam = intval(
                    server()->run(
                        "[math]::Round((Get-WmiObject Win32_ComputerSystem).totalphysicalmemory / (1024 * 1024))"
                    )
                );
                $ram = round(($usedRam / $totalRam) * 100, 2);
            } catch (\Exception $exception) {
                $ram = "0";
            }
            */
        }
        return [
            "disk" => 0,
            "ram" => 0,
            "cpu" => 0,
            "time" => 0,
        ];
    }

    private function parsePsOutput($output)
    {
        $data = [];
        foreach (explode("\n", $output) as $row) {
            $row = explode('*-*', $row);
            $row[3] = str_replace('\\', '/', $row[3]);
            $fetch = explode('/', $row[3]);
            $data[] = [
                'pid' => $row[0],
                'percent' => $row[1],
                'user' => $row[2],
                'cmd' => end($fetch),
            ];
        }
        return $data;
    }

    private function parseDfOutput($output)
    {
        $data = [];
        foreach (explode("\n", $output) as $row) {
            $row = explode('*-*', $row);
            $row[1] = str_replace('\\', '/', $row[1]);
            $fetch = explode('/', $row[1]);
            $data[] = [
                'percent' => $row[0],
                'source' => end($fetch),
                'size' => $row[2],
                'used' => $row[3],
            ];
        }
        return $data;
    }

    public function topMemoryProcesses()
    {
        $output = trim(
            server()->run(
                "ps -eo pid,%mem,user,cmd --sort=-%mem --no-headers | head -n 5 | awk '{print $1\"*-*\"$2\"*-*\"$3\"*-*\"$4}'"
            )
        );
        return view('table', [
            'value' => $this->parsePsOutput($output),
            'title' => [__('Kullanıcı'), __('İşlem'), '%'],
            'display' => ['user', 'cmd', 'percent'],
        ]);
    }

    public function topCpuProcesses()
    {
        $output = trim(
            server()->run(
                "ps -eo pid,%cpu,user,cmd --sort=-%cpu --no-headers | head -n 5 | awk '{print $1\"*-*\"$2\"*-*\"$3\"*-*\"$4}'"
            )
        );
        return view('table', [
            'value' => $this->parsePsOutput($output),
            'title' => [__('Kullanıcı'), __('İşlem'), '%'],
            'display' => ['user', 'cmd', 'percent'],
        ]);
    }

    public function topDiskUsage()
    {
        $output = trim(
            server()->run(
                "df --output=pcent,source,size,used -hl -x squashfs -x tmpfs -x devtmpfs | sed -n '1!p' | head -n 5 | sort -hr | awk '{print $1\"*-*\"$2\"*-*\"$3\"*-*\"$4}'"
            )
        );
        return view('table', [
            'value' => $this->parseDfOutput($output),
            'title' => [__('Disk'), __('Boyut'), __('Dolu'), '%'],
            'display' => ['source', 'size', 'used', 'percent'],
        ]);
    }

    private function calculateNetworkBytes($download = true)
    {
        $text = $download ? 'rx_bytes' : 'tx_bytes';
        $count = 0;
        $raw = Command::runSudo('cat /sys/class/net/*/statistics/:text:', [
            'text' => $text
        ]);
        foreach (explode("\n", trim($raw)) as $data) {
            $count += intval($data);
        }
        return $count;
    }

    public function getLocalUsers()
    {
        $output = server()->run(
            "cut -d: -f1,3 /etc/passwd | egrep ':[0-9]{4}$' | cut -d: -f1"
        );
        $output = trim($output);
        if (empty($output)) {
            $users = [];
        } else {
            $output = explode("\n", $output);
            foreach ($output as $user) {
                $users[] = [
                    "user" => $user,
                ];
            }
        }
        return magicView('table', [
            "value" => $users,
            "title" => ["Kullanıcı Adı"],
            "display" => ["user"],
        ]);
    }

    public function addLocalUser()
    {
        $user_name = request("user_name");
        $user_password = request("user_password");
        $user_password_confirmation = request("user_password_confirmation");
        if ($user_password !== $user_password_confirmation) {
            return respond("Şifreler uyuşmuyor!", 201);
        }
        $output = Command::runSudo("useradd --no-user-group -p $(openssl passwd -1 {:user_password}) {:user_name} -s \"/bin/bash\" &> /dev/null && echo 1 || echo 0", [
            'user_password' => $user_password,
            'user_name' => $user_name
        ]);
        if ($output == "0") {
            return respond("Kullanıcı eklenemedi!", 201);
        }
        return respond("Kullanıcı başarıyla eklendi!", 200);
    }

    public function getLocalGroups()
    {
        $output = server()->run("getent group | cut -d ':' -f1");
        $output = trim($output);
        if (empty($output)) {
            $groups = [];
        } else {
            $output = explode("\n", $output);
            foreach ($output as $group) {
                $groups[] = [
                    "group" => $group,
                ];
            }
            $groups = array_reverse($groups);
        }
        return magicView('table', [
            "value" => $groups,
            "title" => ["Grup Adı"],
            "display" => ["group"],
            "onclick" => "localGroupDetails",
        ]);
    }

    public function getLocalGroupDetails()
    {
        $group = request("group");
        $output = Command::runSudo("getent group @{:group} | cut -d ':' -f4", [
            'group' => $group
        ]);

        $users = [];
        if (!empty($output)) {
            $users = array_map(function ($value) {
                return ["name" => $value];
            }, explode(",", $output));
        }
        return magicView('table', [
            "value" => $users,
            "title" => ["Kullanıcı Adı"],
            "display" => ["name"],
        ]);
    }

    public function addLocalGroup()
    {
        $group_name = request("group_name");
        $output = Command::runSudo('groupadd @{:group_name} &> /dev/null && echo 1 || echo 0', [
            'group_name' => $group_name
        ]);
        if ($output == "0") {
            return respond("Grup eklenemedi!", 201);
        }
        return respond("Grup başarıyla eklendi!", 200);
    }

    public function addLocalGroupUser()
    {
        $group = request("group");
        $user = request("user");
        $output = Command::runSudo('usermod -a -G @{:group} @{:user} &> /dev/null && echo 1 || echo 0', [
            'group' => $group,
            'user' => $user
        ]);
        if ($output != "1") {
            return respond("Kullanıcı gruba eklenemedi!", 201);
        }
        return respond("Kullanıcı gruba başarıyla eklendi!");
    }

    public function getSudoers()
    {
        $output = trim(
            server()->run(
                sudo() .
                    "cat /etc/sudoers /etc/sudoers.d/* | grep -v '^#\|^Defaults' | sed '/^$/d' | awk '{ print $1 \"*-*\" $2 \" \" $3 }'"
            )
        );

        $sudoers = [];
        if (!empty($output)) {
            $sudoers = array_map(function ($value) {
                $fetch = explode("*-*", $value);
                return ["name" => $fetch[0], "access" => $fetch[1]];
            }, explode("\n", $output));
        }
        return magicView('table', [
            "value" => $sudoers,
            "title" => ["İsim", "Yetki"],
            "display" => ["name", "access"],
            "menu" => [
                "Sil" => [
                    "target" => "deleteSudoers",
                    "icon" => "fa-trash",
                ],
            ],
        ]);
    }

    public function addSudoers()
    {
        $name = request("name");
        $name = str_replace(" ", "\\x20", $name);
        $checkFile = Command::runSudo("[ -f '/etc/sudoers.d/{:name}' ] && echo 1 || echo 0", [
            'name' => $name
        ]);
        if ($checkFile == "1") {
            return respond("Bu isimde bir kullanıcı zaten ekli!", 201);
        }
        $output = Command::runSudo(
            "echo \"{:name} ALL=(ALL:ALL) ALL\" | tee /etc/sudoers.d/{:name} &> /dev/null && echo 1 || echo 0",
            [
                'name' => $name
            ]
        );
        if ($output == "0") {
            return respond("Tam yetkili kullanıcı eklenemedi!", 201);
        }
        return respond("Tam yetkili kullanıcı başarıyla eklendi!", 200);
    }

    public function deleteSudoers()
    {
        $name = request("name");
        $name = str_replace(" ", "\\x20", $name);
        $output = Command::runSudo(
            "if [ -f \"/etc/sudoers.d/{:name}\" ]; then rm /etc/sudoers.d/{:name} && echo 1 || echo 0; else echo 0; fi",
            [
                'name' => $name
            ]
        );
        if ($output == "0") {
            return respond("Tam yetkili kullanıcı silinemedi!", 201);
        }
        return respond("Tam yetkili kullanıcı başarıyla silindi!", 200);
    }

    public function serviceList()
    {
        if (!Permission::can(user()->id, 'liman', 'id', 'server_services')) {
            return respond("Bu işlemi yapmak için yetkiniz yok!", 201);
        }
        $services = [];
        if (server()->isLinux()) {
            $raw = server()->run(
                "systemctl list-units | grep service | awk '{print $1 \":\"$2\" \"$3\" \"$4\":\"$5\" \"$6\" \"$7\" \"$8\" \"$9\" \"$10}'",
                false
            );
            foreach (explode("\n", $raw) as $package) {
                if ($package == "") {
                    continue;
                }
                $row = explode(":", trim($package));
                try {
                    array_push($services, [
                        "name" => $row[0],
                        "description" => $row[2],
                        "status" => $row[1],
                    ]);
                } catch (Exception $exception) {
                }
            }
        } else {
            $rawServices = server()->run(
                "(Get-WmiObject win32_service | select Name, DisplayName, State, StartMode) -replace '\s\s+',':'"
            );
            $services = [];
            foreach (explode('}', $rawServices) as $service) {
                $row = explode(";", substr($service, 2));
                if ($row[0] == "") {
                    continue;
                }
                try {
                    array_push($services, [
                        "name" => trim(explode('=', $row[0])[1]),
                        "description" => trim(explode('=', $row[1])[1]),
                        "status" => trim(explode('=', $row[2])[1]),
                    ]);
                } catch (Exception $exception) {
                }
            }
        }

        return magicView('table', [
            "id" => "servicesTable",
            "value" => $services,
            "title" => ["Servis Adı", "Açıklama", "Durumu"],
            "display" => ["name", "description", "status"],
            "menu" => [
                "Detaylar" => [
                    "target" => "statusService",
                    "icon" => "fa-info-circle",
                ],
                "Başlat" => [
                    "target" => "startService",
                    "icon" => "fa-play",
                ],
                "Durdur" => [
                    "target" => "stopService",
                    "icon" => "fa-stop",
                ],
                "Yeniden Başlat" => [
                    "target" => "restartService",
                    "icon" => "fa-sync-alt",
                ],
            ],
        ]);
    }

    public function getLogs()
    {
        if (!Permission::can(user()->id, 'liman', 'id', 'view_logs')) {
            return respond(
                "Sunucu Günlük Kayıtlarını görüntülemek için yetkiniz yok",
                201
            );
        }

        $page = request('page') * 10;
        $query = request('query') ? request('query') : "";
        $server_id = request('server_id');
        $count = intval(
            Command::runLiman(
                'grep --text EXTENSION_RENDER_PAGE /liman/logs/liman.log | grep \'"display":"true"\'| grep @{:query} | grep @{:server_id} | wc -l',
                [
                    'query' => $query,
                    'server_id' => $server_id
                ]
            )
        );
        $head = $page > $count ? $count % 10 : 10;
        $data = Command::runLiman(
            'grep --text EXTENSION_RENDER_PAGE /liman/logs/liman.log | grep \'"display":"true"\'| grep @{:query} | grep @{:server_id} | tail -{:page} | head -{:head} | tac',
            [
                'query' => $query,
                'server_id' => $server_id,
                'page' => $page,
                'head' => $head,
            ]
        );
        $clean = [];

        $knownUsers = [];
        $knownExtensions = [];

        if ($data == "") {
            return respond([
                "table" => __("Bu aramaya göre bir sonuç bulunamadı."),
            ]);
        }

        foreach (explode("\n", $data) as $row) {
            $dateEndPos = strposX($row, " ", 2);
            $date = substr($row, 1, $dateEndPos - 2);
            $json = substr($row, strpos($row, "{"));
            $parsed = json_decode($json, true);
            $parsed["date"] = $date;
            if (!array_key_exists($parsed["extension_id"], $knownExtensions)) {
                $extension = Extension::find($parsed["extension_id"]);
                if ($extension) {
                    $knownExtensions[$parsed["extension_id"]] =
                        $extension->display_name;
                } else {
                    $knownExtensions[$parsed["extension_id"]] =
                        $parsed["extension_id"];
                }
            }

            $parsed["extension_id"] = $knownExtensions[$parsed["extension_id"]];
            if (!array_key_exists("log_id", $parsed)) {
                $parsed["log_id"] = null;
            }
            if (!array_key_exists($parsed["user_id"], $knownUsers)) {
                $user = User::find($parsed["user_id"]);
                if ($user) {
                    $knownUsers[$parsed["user_id"]] = $user->name;
                } else {
                    $knownUsers[$parsed["user_id"]] = $parsed["user_id"];
                }
            }
            $parsed["user_id"] = $knownUsers[$parsed["user_id"]];

            array_push($clean, $parsed);
        }

        $table = view('table', [
            "value" => (array) $clean,
            "startingNumber" => (intval(request('page')) - 1) * 10,
            "title" => [
                "Eklenti",
                "Fonksiyon",
                "Kullanıcı",
                "İşlem Tarihi",
                "*hidden*",
            ],
            "display" => [
                "extension_id",
                "view",
                "user_id",
                "date",
                "log_id:id",
            ],
            "onclick" => "getLogDetails",
        ])->render();

        $pagination = view('pagination', [
            "current" => request('page') ? intval(request('page')) : 1,
            "count" => floor($count / 10) + 1,
            "onclick" => "getLogs",
        ])->render();

        return respond([
            "table" => $table . "<br>" . $pagination,
        ]);
    }

    public function getLogDetails()
    {
        $query = request('log_id');
        $data = Command::runLiman('grep @{:query} /liman/logs/extension.log', [
            'query' => $query
        ]);
        if ($data == "") {
            return respond(__("Bu loga ait detay bulunamadı"), 201);
        }
        $logs = [];
        foreach (explode("\n", $data) as $row) {
            $dateEndPos = strposX($row, " ", 2);
            $date = substr($row, 1, $dateEndPos - 2);
            $json = substr($row, strpos($row, "{"));
            $parsed = json_decode($json, true);
            $parsed["title"] = base64_decode($parsed["title"]);
            $parsed["message"] = base64_decode($parsed["message"]);
            array_push($logs, $parsed);
        }

        return respond($logs);
    }

    public function installPackage()
    {
        if (server()->isLinux()) {
            $package = request("package_name");
            $raw = Command::runSudo(
                "DEBIAN_FRONTEND=noninteractive apt install @{:package} -qqy >\"/tmp/{:packageBase}.txt\" 2>&1 & disown && echo \$!", 
                [
                    'packageBase' => basename($package),
                    'package' => $package
                ]
            );
            system_log(7, "Paket Güncelleme", [
                'package_name' => request("package_name"),
            ]);
        } else {
            $raw = "";
        }
        return $raw;
    }

    public function checkPackage()
    {
        $mode = request("mode") ? request("mode") : 'update';
        $output = trim(
            server()->run(
                "ps aux | grep \"apt \|dpkg \" | grep -v grep 2>/dev/null 1>/dev/null && echo '1' || echo '0'"
            )
        );
        $command_output = Command::runSudo('cat "/tmp/{:packageBase}.txt" 2> /dev/null | base64', [
            'packageBase' => basename(request("package_name")),
        ]);
        $command_output = base64_decode($command_output);
        Command::runSudo('truncate -s 0 "/tmp/{:packageBase}.txt"', [
            'packageBase' => basename(request("package_name")),
        ]);
        if ($output === "0") {
            $list_method = $mode == "install" ? "--installed" : "--upgradable";
            $package = request("package_name");
            if (endsWith($package, ".deb")) {
                $package = Command::runSudo('dpkg -I @{:package} | grep Package: | cut -d\':\' -f2 | tr -d \'[:space:]\'', [
                    'package' => $package,
                ]);
            }
            $package = Command::runSudo(
                'apt list ' .
                $list_method .
                ' 2>/dev/null | grep ' .
                '@{:package}' .
                ' && echo 1 || echo 0', 
                [
                    'package' => $package,
                ]
            );
            if (
                ($mode == "update" && $output == "0") ||
                ($mode == "install" && $output != "0")
            ) {
                system_log(7, "Paket Güncelleme Başarılı", [
                    'package_name' => request("package_name"),
                ]);
                return respond([
                    "status" => __(":package_name paketi başarıyla kuruldu.", [
                        'package_name' => request("package_name"),
                    ]),
                    "output" => trim($command_output),
                ]);
            } else {
                system_log(7, "Paket Güncelleme Başarısız", [
                    'package_name' => request("package_name"),
                ]);
                return respond([
                    "status" => __(":package_name paketi kurulamadı.", [
                        'package_name' => request("package_name"),
                    ]),
                    "output" => trim($command_output),
                ]);
            }
        } else {
            return respond(
                [
                    "status" => __(
                        ":package_name paketinin kurulum işlemi henüz bitmedi.",
                        ['package_name' => request("package_name")]
                    ),
                    "output" => trim($command_output),
                ],
                400
            );
        }
        return $output;
    }

    public function uploadDebFile()
    {
        if (server()->isLinux()) {
            $filePath = request('filePath');
            if (!$filePath) {
                return respond("Dosya yolu zorunludur.", 403);
            }
            server()->putFile($filePath, "/tmp/" . basename($filePath));
            unlink($filePath);
            return respond("/tmp/" . basename($filePath), 200);
        } else {
            return respond("Bu sunucuya deb paketi kuramazsınız.", 403);
        }
    }

    public function updateList()
    {
        $updates = [];
        $raw = server()->run(
            sudo() .
                "apt-get -qq update 2> /dev/null > /dev/null; " .
                sudo() .
                "apt list --upgradable 2>/dev/null | sed '1,1d'"
        );
        foreach (explode("\n", $raw) as $package) {
            if ($package == "" || strpos($package, 'List') !== false) {
                continue;
            }
            $row = explode(" ", $package, 4);
            try {
                array_push($updates, [
                    "name" => $row[0],
                    "version" => $row[1],
                    "type" => $row[2],
                    "status" => $row[3],
                ]);
            } catch (\Exception $exception) {
            }
        }
        return [
            "count" => count($updates),
            "list" => $updates,
            "table" => view('table', [
                "id" => "updateListTable",
                "value" => $updates,
                "title" => ["Paket Adı", "Versiyon", "Tip", "Durumu"],
                "display" => ["name", "version", "type", "status"],
                "menu" => [
                    "Güncelle" => [
                        "target" => "updateSinglePackage",
                        "icon" => "fa-sync",
                    ],
                ],
            ])->render(),
        ];
    }

    public function packageList()
    {
        $raw = server()->run(
            sudo() . "apt list --installed 2>/dev/null | sed '1,1d'",
            false
        );
        $packages = [];
        foreach (explode("\n", $raw) as $package) {
            if ($package == "") {
                continue;
            }
            $row = explode(" ", $package);
            try {
                array_push($packages, [
                    "name" => $row[0],
                    "version" => $row[1],
                    "type" => $row[2],
                    "status" => $row[3],
                ]);
            } catch (Exception $exception) {
            }
        }
        return magicView('table', [
            "value" => $packages,
            "title" => ["Paket Adı", "Versiyon", "Tip", "Durumu"],
            "display" => ["name", "version", "type", "status"],
        ]);
    }

    public function removeExtension()
    {
        hook('server_extension_remove', [
            "server" => server(),
            "request" => request()->all(),
        ]);

        if (
            server()->user_id != auth()->user()->id &&
            !auth()
                ->user()
                ->isAdmin()
        ) {
            return respond(
                "Yalnızca sunucu sahibi ya da yönetici bir eklentiyi silebilir.",
                201
            );
        }

        foreach (json_decode(request('extensions')) as $key => $value) {
            DB::table("server_extensions")
                ->where([
                    "server_id" => server()->id,
                    "extension_id" => $value,
                ])
                ->delete();
        }
        return respond("Eklentiler Başarıyla Silindi");
    }

    public function startService()
    {
        if (server()->isLinux()) {
            $command = sudo() . "systemctl start @{:name}";
        } else {
            $command = "Start-Service @{:name}";
        }
        Command::run($command, [
            'name' => request("name")
        ]);
        return respond("Servis Baslatildi", 200);
    }

    public function stopService()
    {
        if (server()->isLinux()) {
            $command = sudo() . "systemctl stop @{:name}";
        } else {
            $command = "Stop-Service @{:name}";
        }
        Command::run($command, [
            'name' => request("name")
        ]);
        return respond("Servis Durduruldu", 200);
    }

    public function restartService()
    {
        if (server()->isLinux()) {
            $command = sudo() . "systemctl restart @{:name}";
        } else {
            $command = "Restart-Service @{:name}";
        }
        Command::run($command, [
            'name' => request("name")
        ]);
        return respond("Servis Yeniden Başlatıldı", 200);
    }

    public function statusService()
    {
        if (server()->isLinux()) {
            $command = sudo() . "systemctl status @{:name}";
        } else {
            return respond(
                "Windows Sunucularda yalnızca servis durumu görüntülenmektedir.",
                201
            );
        }
        $output = Command::run($command, [
            'name' => request("name")
        ]);
        return respond($output, 200);
    }

    public function getOpenPorts()
    {
        if (server()->os != "linux") {
            return respond("Bu sunucuda portları kontrol edemezsiniz!", 201);
        }

        $output = trim(
            server()->run(
                sudo() .
                    "lsof -i -P -n | grep -v '\-'| awk -F' ' '{print $1,$3,$5,$8,$9}' | sed 1,1d"
            )
        );

        if (empty($output)) {
            return respond(view(
                "alert",
                [
                    "type" => "info",
                    "title" => "Bilgilendirme",
                    "message" => "Açık portları görüntüleyebilmek için sunucunuza <b>lsof</b> paketini kurmanız gerekmektedir."
                ]
            )->render() . 
            "<button class='w-100 btn btn-info' onclick='installLsof()'><i class='fas fa-download mr-1'></i> 
            " . __("Lsof paketini yükle") . "</button>"
            , 201);
        }

        $arr = [];
        foreach (explode("\n", $output) as $line) {
            $row = explode(" ", $line);
            array_push($arr, [
                "name" => $row[0],
                "username" => $row[1],
                "ip_type" => $row[2],
                "packet_type" => $row[3],
                "port" => $row[4],
            ]);
        }

        return respond(
            view('table', [
                "id" => "openPortsTable",
                "value" => $arr,
                "title" => [
                    "Program Adı",
                    "Kullanıcı",
                    "İp Türü",
                    "Paket Türü",
                    "Port",
                ],
                "display" => [
                    "name",
                    "username",
                    "ip_type",
                    "packet_type",
                    "port",
                ],
            ])->render()
        );
    }
}
