<?php
declare(strict_types=1);

namespace Archium\Support;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?PDO $pdo = null;

    /** Prepared statements NATIVI (no emulazione), errori come eccezioni, utf8mb4. */
    public static function connection(array $config): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $db  = $config['db'];
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $db['host'],
            (int) $db['port'],
            $db['name'],
            $db['charset']
        );

        try {
            self::$pdo = new PDO($dsn, $db['user'], $db['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            // Mai loggare la password: si loggano solo host e database.
            Logger::error('Connessione DB fallita', ['host' => $db['host'], 'db' => $db['name']]);
            throw new RuntimeException('Database non raggiungibile.');
        }

        return self::$pdo;
    }
}