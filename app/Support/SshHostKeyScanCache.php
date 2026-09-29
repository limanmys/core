<?php

namespace App\Support;

use App\Models\SshHostKey;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\LockTimeoutException;
use RuntimeException;

final class SshHostKeyScanCache
{
    private const TTL_SECONDS = 900;

    public function __construct(
        private readonly Repository $cache,
        private readonly int $timeoutSeconds = 15,
    ) {}

    /**
     * Share only successful scans whose public key is still approved in the DB.
     *
     * @param  array<string>  $trustedPublicKeys
     * @param  callable(): array{host: string, port: int, key_type: string, public_key: string, fingerprint: string}  $scan
     * @return array{host: string, port: int, key_type: string, public_key: string, fingerprint: string}
     */
    public function discover(string $host, int $port, array $trustedPublicKeys, callable $scan, bool $forceRefresh = false): array
    {
        $host = SshHostKey::normalizeHost($host);
        $key = $this->key($host, $port);
        if (! $forceRefresh && ($cached = $this->cached($key, $host, $port, $trustedPublicKeys)) !== null) {
            return $cached;
        }

        try {
            return $this->cache->lock($key.':lock', max(30, $this->timeoutSeconds + 10))
                ->block($this->timeoutSeconds + 2, function () use ($key, $host, $port, $trustedPublicKeys, $scan, $forceRefresh): array {
                    // Another worker may have completed the scan while we waited.
                    if (! $forceRefresh && ($cached = $this->cached($key, $host, $port, $trustedPublicKeys)) !== null) {
                        return $cached;
                    }

                    // A failed fresh scan must never leave an earlier success usable.
                    $this->cache->forget($key);
                    $discovered = $scan();
                    if (in_array($discovered['public_key'], $trustedPublicKeys, true)) {
                        $this->rememberTrusted($discovered);
                    }

                    return $discovered;
                });
        } catch (LockTimeoutException $exception) {
            throw new RuntimeException('SSH host-key scan timed out waiting for another scan.', 0, $exception);
        }
    }

    /**
     * Called only for a matched key or after explicit approval has committed.
     *
     * @param  array{host: string, port: int, key_type: string, public_key: string, fingerprint: string}  $discovered
     */
    public function rememberTrusted(array $discovered): void
    {
        $this->cache->put($this->key($discovered['host'], $discovered['port']), $discovered, self::TTL_SECONDS);
    }

    private function key(string $host, int $port): string
    {
        return 'ssh-host-key:scan:v1:'.hash('sha256', SshHostKey::normalizeHost($host)."\0".$port);
    }

    private function cached(string $key, string $host, int $port, array $trustedPublicKeys): ?array
    {
        $cached = $this->cache->get($key);
        if (
            ! is_array($cached)
            || ($cached['host'] ?? null) !== $host
            || ($cached['port'] ?? null) !== $port
            || ! is_string($cached['key_type'] ?? null)
            || ! is_string($cached['fingerprint'] ?? null)
            || ! in_array($cached['public_key'] ?? null, $trustedPublicKeys, true)
        ) {
            return null;
        }

        return $cached;
    }
}
