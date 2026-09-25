<?php
declare(strict_types=1);

namespace Archium\Support;

final class Csrf
{
    private const KEY = '_csrf_token';

    public static function token(): string
    {
        if (empty($_SESSION[self::KEY])) {
            $_SESSION[self::KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::KEY];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(self::token()) . '">';
    }

    public static function verify(mixed $token): bool
    {
        return is_string($token)
            && $token !== ''
            && !empty($_SESSION[self::KEY])
            && hash_equals($_SESSION[self::KEY], $token);
    }

    /** Da chiamare all'inizio di OGNI handler POST/PUT/DELETE. */
    public static function requireValid(): void
    {
        if (!self::verify($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            Logger::error('CSRF non valido', ['ip' => $_SERVER['REMOTE_ADDR'] ?? '?']);
            exit('Token di sicurezza non valido. Ricarica la pagina e riprova.');
        }
    }
}