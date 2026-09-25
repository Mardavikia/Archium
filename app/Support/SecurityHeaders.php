<?php
declare(strict_types=1);

namespace Archium\Support;

final class SecurityHeaders
{
    public static function apply(array $config): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

        if (($config['app']['env'] ?? 'production') !== 'local') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }

        // 'unsafe-inline' su style solo per piccoli stili inline MVP;
        // verra' rimosso quando il frontend sara' stabilizzato (Fase 8).
        header(
            "Content-Security-Policy: default-src 'self'; "
            . "img-src 'self' data:; "
            . "style-src 'self' 'unsafe-inline'; "
            . "script-src 'self'; "
            . "base-uri 'self'; form-action 'self'; frame-ancestors 'self'"
        );
    }
}