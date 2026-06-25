<?php

namespace App\Classes\Authentication\OIDC;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * OIDC verified claim'lerinden Liman kullanıcısını bulur veya oluşturur.
 */
class OIDCUserProvisioner
{
    /**
     * @param  object  $claims  Doğrulanmış ID token claim'leri (stdClass).
     */
    public function findOrCreate(object $claims): ?User
    {
        $userInfo = $this->normalize($claims);

        try {
            $user = User::where('oidc_sub', $userInfo['sub'])->first();

            if (! $user) {
                if (trim($userInfo['email']) === '') {
                    throw new \Exception('User creation failed, email value is required');
                }

                $user = User::where('email', $userInfo['email'])->first();

                if ($user) {
                    $user->update([
                        'oidc_sub' => $userInfo['sub'],
                        'auth_type' => 'oidc',
                        'name' => $userInfo['display_name'],
                    ]);
                } else {
                    $user = $this->createUser($userInfo);
                }
            } else {
                $user->update([
                    'name' => $userInfo['display_name'],
                    'email' => $userInfo['email'],
                    'auth_type' => 'oidc',
                ]);
            }

            if (! $user->getJWTIdentifier()) {
                Log::error('User JWT identifier is null', [
                    'user_id' => $user->id,
                    'user_exists' => $user->exists,
                ]);

                return null;
            }

            return $user;
        } catch (\Exception $e) {
            Log::error('User creation/update failed: '.$e->getMessage());

            return null;
        }
    }

    /**
     * @return array{sub: string, email: string, display_name: string, username: string, external_token: ?string}
     */
    private function normalize(object $claims): array
    {
        $array = json_decode(json_encode($claims), true) ?: [];

        $email = strtolower((string) ($array['email'] ?? ''));
        $displayName = $array['name']
            ?? $array['preferred_username']
            ?? $array['nickname']
            ?? $array['email']
            ?? '';

        return [
            'sub' => (string) ($array['sub'] ?? ''),
            'email' => $email,
            'display_name' => $displayName,
            'username' => $array['preferred_username']
                ?? (str_contains($email, '@') ? explode('@', $email)[0] : $email),
            'external_token' => $array['external_token'] ?? null,
        ];
    }

    private function createUser(array $userInfo): User
    {
        return DB::transaction(function () use ($userInfo): User {
            $newUser = User::create([
                'oidc_sub' => $userInfo['sub'],
                'name' => $userInfo['display_name'],
                'email' => $userInfo['email'],
                'username' => $userInfo['username'],
                'auth_type' => 'oidc',
                'password' => Hash::make(Str::random(32)),
                'forceChange' => false,
            ]);

            $newUser->refresh();
            if (! $newUser->id) {
                throw new \Exception('User creation failed - ID is null');
            }

            return $newUser;
        });
    }
}
