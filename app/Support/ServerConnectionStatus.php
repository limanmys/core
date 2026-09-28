<?php

namespace App\Support;

use App\Models\Server;
use App\Models\ServerKey;

class ServerConnectionStatus
{
    /** Metadata only: credential presence does not prove a successful login. */
    public static function describe(string $serverType, ?string $personalType, ?string $sharedType): array
    {
        $sshTypes = ['ssh', 'ssh_certificate'];
        $requiresKey = in_array($serverType, $sshTypes, true);
        $sharedEnabled = $sharedType !== null && $sharedType !== 'no_key';
        if (! $requiresKey) {
            return ['requires_key' => false, 'shared_enabled' => $sharedEnabled, 'source' => 'not_required', 'reason' => null];
        }

        // Match core/engine precedence: an incompatible personal record also wins.
        $selectedType = $personalType ?? $sharedType;
        $source = in_array($selectedType, $sshTypes, true)
            ? ($personalType !== null ? 'personal' : 'shared')
            : 'missing';
        $reason = $source !== 'missing' ? null : ($personalType !== null
            ? 'personal_key_incompatible'
            : ($sharedType !== null ? 'shared_key_incompatible' : 'not_shared'));

        return ['requires_key' => true, 'shared_enabled' => $sharedEnabled, 'source' => $source, 'reason' => $reason];
    }

    public static function forServer(Server $server, string $userId): array
    {
        $personal = ServerKey::where('server_id', $server->id)->where('user_id', $userId)
            ->orderByDesc('updated_at')->orderBy('id')->value('type');
        $shared = ServerKey::where('server_id', $server->id)->where('shared', true)
            ->orderByDesc('updated_at')->orderBy('id')->value('type');

        return self::describe($server->type, $personal, $shared);
    }
}
