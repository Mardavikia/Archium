<?php
declare(strict_types=1);

namespace Archium\Support;

final class Logger
{
    private static string $path = '';

    public static function configure(string $path): void
    {
        self::$path = $path;
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    private static function write(string $level, string $message, array $context): void
    {
        if (self::$path === '') {
            return;
        }

        // Mascheramento preventivo: mai password/token/secret nei log.
        $safe = [];
        foreach ($context as $key => $value) {
            $safe[$key] = preg_match('/pass|pwd|token|secret|hash/i', (string) $key)
                ? '[omesso]'
                : (is_scalar($value) ? $value : '[non-scalar]');
        }

        $line = sprintf(
            "[%s] %s: %s%s\n",
            date('c'),
            $level,
            $message,
            $safe ? ' ' . (string) json_encode($safe, JSON_UNESCAPED_SLASHES) : ''
        );

        @file_put_contents(self::$path, $line, FILE_APPEND | LOCK_EX);
    }
}