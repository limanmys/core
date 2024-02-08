<?php

namespace App\Http\Controllers\API\Server;

use App\Exceptions\JsonResponseException;
use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\System\Command;
use Illuminate\Http\Response;

/**
 * Server User Controller
 */
class UserController extends Controller
{
    public function __construct()
    {
        if (!Permission::can(auth('api')->user()->id, 'liman', 'id', 'server_details')) {
            throw new JsonResponseException([
                'message' => 'Bu işlemi yapmak için yetkiniz yok!'
            ], '', Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Get local users on system
     *
     * @return JsonResponse|Response
     *
     * @throws GuzzleException
     * @throws GuzzleException
     */
    public function getLocalUsers()
    {
        $users = [];
        if (server()->isLinux()) {
            $output = Command::runSudo(
                "cut -d: -f1,3 /etc/passwd | egrep ':[0-9]{4}$' | cut -d: -f1"
            );
            $output = trim($output);
            if (empty($output)) {
                $users = [];
            } else {
                $output = explode("\n", $output);
                foreach ($output as $user) {
                    $users[] = [
                        'user' => $user,
                    ];
                }
            }
        }

        if (server()->isWindows() && server()->canRunCommand()) {
            $output = Command::run(
                'Get-LocalUser | Where { $_.Enabled -eq $True} | Select-Object Name'
            );
            $output = trim($output);
            if (empty($output)) {
                $users = [];
            } else {
                $output = explode("\r\n", $output);
                foreach ($output as $key => $user) {
                    if ($key == 0 || $key == 1) {
                        continue;
                    }
                    $users[] = [
                        'user' => $user,
                    ];
                }
            }
        }

        return response()->json($users);
    }

    /**
     * Create local user on server
     *
     * @return JsonResponse|Response
     *
     * @throws GuzzleException
     */
    public function addLocalUser()
    {
        $user_name = request('username');
        $user_password = request('password');
        $user_password_confirmation = request('password_confirmation');
        if ($user_password !== $user_password_confirmation) {
            return response()->json([
                'password' => 'Şifreler eşleşmiyor.'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $output = Command::runSudo('useradd --no-user-group -p $(openssl passwd -1 {:user_password}) {:user_name} -s "/bin/bash" &> /dev/null && echo 1 || echo 0', [
            'user_password' => $user_password,
            'user_name' => $user_name,
        ]);
        if ($output == '0') {
            return response()->json([
                'message' => 'Kullanıcı oluşturulurken hata oluştu.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'message' => 'Kullanıcı başarıyla oluşturuldu.'
        ]);
    }


    public function getLocalGroups()
    {
        $groups = [];
        if (server()->isLinux()) {
            $output = Command::runSudo("getent group | cut -d ':' -f1");
            $output = trim($output);
            if (empty($output)) {
                $groups = [];
            } else {
                $output = explode("\n", $output);
                foreach ($output as $group) {
                    $groups[] = [
                        'group' => $group,
                    ];
                }
                $groups = array_reverse($groups);
            }
        }

        if (server()->isWindows() && server()->canRunCommand()) {
            $output = Command::run(
                'Get-LocalGroup | Select-Object Name'
            );
            $output = trim($output);
            if (empty($output)) {
                $groups = [];
            } else {
                $output = explode("\r\n", $output);
                foreach ($output as $key => $group) {
                    if ($key == 0 || $key == 1) {
                        continue;
                    }
                    $groups[] = [
                        'group' => $group,
                    ];
                }
            }
        }

        return response()->json($groups);
    }

    public function deleteGroup($groupName)
    {
        if (server()->isLinux()) {
            $output = Command::runSudo("groupdel $groupName");
            if ($output) {
                return true;
            } else {
                return false;
            }
        } elseif (server()->isWindows() && server()->canRunCommand()) {
            $output = Command::run("Remove-LocalGroup -Name $groupName");
            if ($output) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }




    /**
     * Get local group details
     *
     * @return JsonResponse|Response
     *
     * @throws GuzzleException
     */
    public function getLocalGroupDetails()
    {
        $group = request('group');
        $output = Command::runSudo("getent group @{:group} | cut -d ':' -f4", [
            'group' => $group,
        ]);

        $users = [];
        if (!empty($output)) {
            $users = array_map(function ($value) {
                return ['name' => $value];
            }, explode(',', (string) $output));
        }

        return response()->json($users);
    }

    /**
     * Create local group on server
     *
     * @return JsonResponse|Response
     *
     * @throws GuzzleException
     */
    public function addLocalGroup()
    {
        $group_name = request('group_name');
        $output = Command::runSudo('groupadd @{:group_name} &> /dev/null && echo 1 || echo 0', [
            'group_name' => $group_name,
        ]);
        if ($output == '0') {
            return response()->json([
                'message' => 'Grup oluşturulurken hata oluştu.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'message' => 'Grup başarıyla oluşturuldu.'
        ], 200);
    }

    /**
     * Add user to group
     *
     * @return JsonResponse|Response
     *
     * @throws GuzzleException
     */
    public function addLocalGroupUser()
    {
        $group = request('group');
        $user = request('user');
        $output = Command::runSudo('usermod -a -G @{:group} @{:user} &> /dev/null && echo 1 || echo 0', [
            'group' => $group,
            'user' => $user,
        ]);
        if ($output != '1') {
            return response()->json('An error occured while adding user to group.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json('User added to group successfully.');
    }

    /**
     * Get sudoers list
     *
     * @return JsonResponse|Response
     *
     * @throws GuzzleException
     * @throws GuzzleException
     */
    public function getSudoers()
    {
        $command = <<<'EOT'
        sh -c "cat /etc/sudoers /etc/sudoers.d/* | grep -v '^#\|^Defaults' | sed '/^$/d'"
        EOT;

        $output = Command::runSudo($command);

        $sudoers = [];
        if (!empty($output)) {
            $sudoers = array_map(function ($value) {
                $val = strtr($value, "\t\n\r ", ' ');
                $fetch = explode(' ', $val);
                $name = array_shift($fetch);

                return ['name' => $name, 'access' => implode(' ', $fetch)];
            }, explode("\n", $output));
        }

        return response()->json($sudoers);
    }

    /**
     * Create sudoer on server
     *
     * @return JsonResponse|Response
     *
     * @throws GuzzleException
     */
    public function addSudoers()
    {
        $name = request('name');
        $name = str_replace(' ', '\\x20', (string) $name);
        $checkFile = Command::runSudo("[ -f '/etc/sudoers.d/{:name}' ] && echo 1 || echo 0", [
            'name' => $name,
        ]);
        if ($checkFile == '1') {
            return response()->json(['name' => 'Bu isimde başka bir kullanıcı mevcut.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $output = Command::runSudo(
            'echo "{:name} ALL=(ALL:ALL) ALL" | ' . sudo() . ' tee /etc/sudoers.d/{:name} &> /dev/null && echo 1 || echo 0',
            [
                'name' => $name,
            ]
        );
        if ($output == '0') {
            return response()->json(['message' => 'An error occured while creating sudoer'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json('Sudoer created successfully.');
    }

    /**
     * Delete sudoer
     *
     * @return JsonResponse|Response
     *
     * @throws GuzzleException
     */
    public function deleteSudoers()
    {
        $names = request('names');
        $names = array_map(function ($value) {
            return str_replace(' ', '\\x20', (string) $value);
        }, $names);

        foreach ($names as $name) {
            $output = Command::runSudo(
                'bash -c "if [ -f \"/etc/sudoers.d/{:name}\" ]; then rm /etc/sudoers.d/{:name} && echo 1 || echo 0; else echo 0; fi"',
                [
                    'name' => $name,
                ]
            );
            if ($output == '0') {
                return response()->json('An error occured while deleting sudoer.', Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        return response()->json('Sudoer deleted successfully.');
    }
}
