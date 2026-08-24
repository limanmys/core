<?php

namespace App\Support;

final class SshHostKeyDecision
{
    public const TRUSTED = 'trusted';

    public const APPROVE = 'approve';

    public const REPLACE = 'replace';

    public const CHALLENGE_UNKNOWN = 'challenge_unknown';

    public const CHALLENGE_MISMATCH = 'challenge_mismatch';

    /**
     * @param  array<int, string>  $trustedPublicKeys
     */
    public static function decide(
        array $trustedPublicKeys,
        string $presentedPublicKey,
        string $presentedFingerprint,
        ?string $confirmedFingerprint,
        bool $approve,
        bool $replacementAllowed,
        bool $replace,
    ): string {
        foreach ($trustedPublicKeys as $trustedPublicKey) {
            if (hash_equals($trustedPublicKey, $presentedPublicKey)) {
                return self::TRUSTED;
            }
        }

        $isMismatch = $trustedPublicKeys !== [];
        $fingerprintMatches = is_string($confirmedFingerprint)
            && hash_equals($presentedFingerprint, $confirmedFingerprint);

        if (! $approve || ! $fingerprintMatches) {
            return $isMismatch ? self::CHALLENGE_MISMATCH : self::CHALLENGE_UNKNOWN;
        }

        if ($isMismatch) {
            return $replacementAllowed && $replace
                ? self::REPLACE
                : self::CHALLENGE_MISMATCH;
        }

        return self::APPROVE;
    }
}
