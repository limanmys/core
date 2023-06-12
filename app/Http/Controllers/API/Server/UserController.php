<?php

namespace App\Http\Controllers\API\Server;

use App\Http\Controllers\Controller;
use App\System\Command;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UserController extends Controller
{
    /**
     * Get local users on system
     *
     * @return JsonResponse|Response
     * @throws GuzzleException
     * @throws GuzzleException
     */
    public function getLocalUsers()
    {
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
            $output = Command::runSudo(
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
     * @throws GuzzleException
     */
    public function addLocalUser()
    {
        $user_name = request('username');
        $user_password = request('password');
        $user_password_confirmation = request('password_confirmation');
        if ($user_password !== $user_password_confirmation) {
            return response()->json('Provided passwords doesn\'t match.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $output = Command::runSudo('useradd --no-user-group -p $(openssl passwd -1 {:user_password}) {:user_name} -s "/bin/bash" &> /dev/null && echo 1 || echo 0', [
            'user_password' => $user_password,
            'user_name' => $user_name,
        ]);
        if ($output == '0') {
            return response()->json('An error occured while creating user.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json('User created successfully.');
    }

    /**
     * Get local groups
     *
     * @return JsonResponse|Response
     * @throws GuzzleException
     */
    public function getLocalGroups()
    {
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

            return response()->json($groups);
        }

        if (server()->isWindows() && server()->canRunCommand()) {
            $output = Command::runSudo(
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

            return response()->json($groups);
        }
    }

    /**
     * Get local group details
     *
     * @return JsonResponse|Response
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
     * @throws GuzzleException
     */
    public function addLocalGroup()
    {
        $group_name = request('group_name');
        $output = Command::runSudo('groupadd @{:group_name} &> /dev/null && echo 1 || echo 0', [
            'group_name' => $group_name,
        ]);
        if ($output == '0') {
            return response()->json('An error occured while creating group.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json('Created group successfully.', 200);
    }

    /**
     * Add user to group
     *
     * @return JsonResponse|Response
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
     * @throws GuzzleException
     * @throws GuzzleException
     */
    public function getSudoers()
    {
        $output = Command::runSudo(
            "cat /etc/sudoers /etc/sudoers.d/* | grep -v '^#\|^Defaults' | sed '/^$/d' | awk '{ print $1 \"*-*\" $2 \" \" $3 }'"
        );

        $sudoers = [];
        if (!empty($output)) {
            $sudoers = array_map(function ($value) {
                $fetch = explode('*-*', $value);

                return ['name' => $fetch[0], 'access' => $fetch[1]];
            }, explode("\n", $output));
        }

        return response()->json($sudoers);
    }

    /**
     * Create sudoer on server
     *
     * @return JsonResponse|Response
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
            return response()->json('Another user exists with this name.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $output = Command::runSudo(
            'echo "{:name} ALL=(ALL:ALL) ALL" | sudo -p "liman-pass-sudo" tee /etc/sudoers.d/{:name} &> /dev/null && echo 1 || echo 0',
            [
                'name' => $name,
            ]
        );
        if ($output == '0') {
            return response()->json('An error occured while creating sudoer', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json('Sudoer created successfully.');
    }

    /**
     * Delete sudoer
     *
     * @return JsonResponse|Response
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
                return response()->json('An error occured while deleting sudoer.', Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }
        
        return response()->json('Sudoer deleted successfully.');
    }
}
