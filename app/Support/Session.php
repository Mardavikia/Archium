<?php
declare(strict_types=1);

namespace Archium\Support;

final class Session
{
    public static function start(array $config): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

        session_name($config['security']['session_name'] ?? 'kb_sid');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps, // in prod HTTPS e' forzato, quindi sempre true
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}