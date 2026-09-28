<?php

namespace App\Support;

use phpseclib3\Exception\NoSupportedAlgorithmsException;
use Throwable;

final class SshConnectionDiagnostic
{
    /** Public diagnostics must not expose raw exceptions, credentials or paths. */
    public static function fromException(Throwable $exception): array
    {
        $message = strtolower($exception->getMessage());
        $code = match (true) {
            str_contains($message, 'timed out'), str_contains($message, 'timeout') => 'SSH_CONNECTION_TIMEOUT',
            str_contains($message, 'getaddrinfo'), str_contains($message, 'php_network_getaddresses') => 'SSH_DNS_FAILED',
            str_contains($message, 'connection refused') => 'SSH_CONNECTION_REFUSED',
            $exception instanceof NoSupportedAlgorithmsException => 'SSH_ALGORITHM_UNSUPPORTED',
            str_contains($message, 'identification string'), str_contains($message, 'connection closed') => 'SSH_HANDSHAKE_FAILED',
            default => 'SSH_HOST_KEY_UNAVAILABLE',
        };

        return self::forCode($code);
    }

    public static function forCode(string $code): array
    {
        $message = match ($code) {
            'SSH_CONNECTION_TIMEOUT' => 'SSH bağlantısı veya el sıkışması zaman aşımına uğradı. Ağ erişimini, güvenlik duvarını, portu ve SSH servisini kontrol edip yeniden deneyin.',
            'SSH_DNS_FAILED' => 'Sunucu adı çözümlenemedi. DNS kaydını ve sunucu adresini kontrol edin.',
            'SSH_CONNECTION_REFUSED' => 'Sunucu SSH bağlantısını reddetti. SSH servisinin çalıştığını ve bağlantı portunu kontrol edin.',
            'SSH_ALGORITHM_UNSUPPORTED' => 'Ortak bir SSH algoritması bulunamadı. SSH servisinin desteklediği algoritmaları ve kayıtlı sunucu kimliğini kontrol edin.',
            'SSH_HANDSHAKE_FAILED' => 'SSH el sıkışması tamamlanamadı. Seçilen portta SSH servisinin çalıştığını kontrol edin.',
            'SSH_AUTHENTICATION_FAILED' => 'SSH sunucu kimliği doğrulandı ancak oturum açılamadı. Kullanıcı adını, parolayı veya özel anahtarı ve sunucunun giriş politikasını kontrol edin.',
            'SSH_PRIVATE_KEY_INVALID' => 'SSH özel anahtarı okunamadı. Anahtar biçimini ve parola korumasını kontrol edin.',
            'SSH_HOST_KEY_MISMATCH' => 'SSH sunucu kimliği kayıtlı anahtarla eşleşmiyor. Sunucular sayfasından SSH kimliğini yeniden kontrol edin; parmak izini güvenilir bir kanaldan doğrulamadan değiştirmeyin.',
            'SSH_HOST_KEY_UNKNOWN' => 'SSH sunucu kimliği henüz onaylanmamış. Sunucular sayfasından SSH kimliğini doğrulayın.',
            default => 'SSH sunucu kimliği alınamadı. Sunucu adresini, SSH portunu ve ağ erişimini kontrol edip yeniden deneyin.',
        };

        return ['code' => $code, 'message' => $message];
    }
}
