<?php

namespace App\Support;

use App\Models\SshHostKey;
use phpseclib3\Net\SSH2;
use RuntimeException;

final class SshHostKeyScanner
{
    /**
     * Perform only the SSH transport handshake and return public host-key metadata.
     *
     * @return array{host: string, port: int, key_type: string, public_key: string, fingerprint: string}
     */
    public function discover(string $host, int $port): array
    {
        $timeout = max(1, (int) ceil(intval(config('liman.server_connection_timeout')) / 1000));
        $normalizedHost = SshHostKey::normalizeHost($host);
        $ssh = new SSH2($normalizedHost, $port, $timeout);
        $publicKey = $ssh->getServerPublicHostKey();

        if (! is_string($publicKey) || trim($publicKey) === '') {
            throw new RuntimeException('SSH sunucu anahtarı alınamadı.');
        }

        return self::fromOpenSshPublicKey($host, $port, $publicKey);
    }

    /**
     * @return array{host: string, port: int, key_type: string, public_key: string, fingerprint: string}
     */
    public static function fromOpenSshPublicKey(string $host, int $port, string $publicKey): array
    {
        $parts = preg_split('/\s+/', trim($publicKey));
        if (! is_array($parts) || count($parts) < 2) {
            throw new RuntimeException('Geçersiz SSH sunucu anahtarı.');
        }

        [, $encodedKey] = $parts;
        $wireKey = base64_decode($encodedKey, true);
        if ($wireKey === false || strlen($wireKey) < 4) {
            throw new RuntimeException('Geçersiz SSH sunucu anahtarı.');
        }

        $length = unpack('Nlength', substr($wireKey, 0, 4));
        $keyTypeLength = is_array($length) ? $length['length'] : 0;
        if ($keyTypeLength < 1 || $keyTypeLength > strlen($wireKey) - 4) {
            throw new RuntimeException('Geçersiz SSH sunucu anahtarı.');
        }
        $keyType = substr($wireKey, 4, $keyTypeLength);

        return [
            'host' => SshHostKey::normalizeHost($host),
            'port' => $port,
            'key_type' => $keyType,
            'public_key' => $keyType.' '.$encodedKey,
            'fingerprint' => 'SHA256:'.rtrim(base64_encode(hash('sha256', $wireKey, true)), '='),
        ];
    }
}
