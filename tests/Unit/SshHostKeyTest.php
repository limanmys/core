<?php

namespace Tests\Unit;

use App\Models\SshHostKey;
use App\Support\SshHostKeyDecision;
use App\Support\SshHostKeyScanner;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SshHostKeyTest extends TestCase
{
    #[DataProvider('hostnames')]
    public function test_it_normalizes_endpoint_hosts(string $input, string $expected): void
    {
        $this->assertSame($expected, SshHostKey::normalizeHost($input));
    }

    public static function hostnames(): array
    {
        return [
            'dns names are case insensitive' => [' EXAMPLE.COM. ', 'example.com'],
            'ipv4 is preserved' => ['192.0.2.10', '192.0.2.10'],
            'ipv6 is canonicalized' => ['[2001:0db8:0:0:0:0:0:1]', '2001:db8::1'],
        ];
    }

    public function test_unknown_keys_require_explicit_matching_fingerprint_approval(): void
    {
        $this->assertSame(
            SshHostKeyDecision::CHALLENGE_UNKNOWN,
            SshHostKeyDecision::decide([], 'ssh-ed25519 AAAA', 'SHA256:new', null, false, false, false),
        );
        $this->assertSame(
            SshHostKeyDecision::CHALLENGE_UNKNOWN,
            SshHostKeyDecision::decide([], 'ssh-ed25519 AAAA', 'SHA256:new', 'SHA256:other', true, false, false),
        );
        $this->assertSame(
            SshHostKeyDecision::APPROVE,
            SshHostKeyDecision::decide([], 'ssh-ed25519 AAAA', 'SHA256:new', 'SHA256:new', true, false, false),
        );
    }

    public function test_changed_keys_cannot_be_silently_replaced(): void
    {
        $trusted = ['ssh-ed25519 OLD'];

        $this->assertSame(
            SshHostKeyDecision::CHALLENGE_MISMATCH,
            SshHostKeyDecision::decide($trusted, 'ssh-ed25519 NEW', 'SHA256:new', 'SHA256:new', true, false, true),
        );
        $this->assertSame(
            SshHostKeyDecision::CHALLENGE_MISMATCH,
            SshHostKeyDecision::decide($trusted, 'ssh-ed25519 NEW', 'SHA256:new', 'SHA256:new', true, true, false),
        );
        $this->assertSame(
            SshHostKeyDecision::REPLACE,
            SshHostKeyDecision::decide($trusted, 'ssh-ed25519 NEW', 'SHA256:new', 'SHA256:new', true, true, true),
        );
    }

    public function test_matching_key_needs_no_new_approval(): void
    {
        $this->assertSame(
            SshHostKeyDecision::TRUSTED,
            SshHostKeyDecision::decide(['ssh-ed25519 SAME'], 'ssh-ed25519 SAME', 'SHA256:same', null, false, false, false),
        );
    }

    public function test_it_calculates_openssh_sha256_fingerprints(): void
    {
        $wireKey = "\x00\x00\x00\x0bssh-ed25519test-key";
        $encoded = base64_encode($wireKey);
        $result = SshHostKeyScanner::fromOpenSshPublicKey(
            'EXAMPLE.COM.',
            2222,
            'ssh-ed25519 '.$encoded.' comment',
        );

        $this->assertSame('example.com', $result['host']);
        $this->assertSame(2222, $result['port']);
        $this->assertSame('ssh-ed25519 '.$encoded, $result['public_key']);
        $this->assertSame(
            'SHA256:'.rtrim(base64_encode(hash('sha256', $wireKey, true)), '='),
            $result['fingerprint'],
        );
    }

    public function test_it_rejects_malformed_openssh_keys(): void
    {
        $this->expectException(\RuntimeException::class);
        SshHostKeyScanner::fromOpenSshPublicKey('example.com', 22, 'not-a-key');
    }

    public function test_it_uses_the_wire_key_type_instead_of_the_rsa_signature_algorithm(): void
    {
        $wireKey = pack('N', 7).'ssh-rsa'.'test-key';
        $encoded = base64_encode($wireKey);

        $result = SshHostKeyScanner::fromOpenSshPublicKey(
            'example.com',
            22,
            'rsa-sha2-512 '.$encoded,
        );

        $this->assertSame('ssh-rsa', $result['key_type']);
        $this->assertSame('ssh-rsa '.$encoded, $result['public_key']);
    }
}
