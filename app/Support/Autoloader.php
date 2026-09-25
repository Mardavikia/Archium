<?php
declare(strict_types=1);

namespace Archium\Support;

final class Autoloader
{
    public static function register(string $baseDir, string $prefix): void
    {
        spl_autoload_register(function (string $class) use ($baseDir, $prefix): void {
            if (!str_starts_with($class, $prefix . '\\')) {
                return;
            }
            $relative = substr($class, strlen($prefix) + 1);
            $file = $baseDir . '/' . str_replace('\\', '/', $relative) . '.php';
            if (is_file($file)) {
                require $file;
            }
        });
    }
}