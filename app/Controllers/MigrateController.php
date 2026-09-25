<?php
declare(strict_types=1);

namespace Archium\Controllers;

use Archium\Support\Csrf;
use Archium\Support\Database;
use Archium\Support\Logger;
use PDO;
use Throwable;

final class MigrateController extends BaseController
{
    public function show(): string
    {
        $this->authorize();

        $pdo = Database::connection($this->config);
        $this->ensureMigrationsTable($pdo);

        return $this->view('migrate', [
            'pageTitle' => 'Migrazioni database',
            'applied'   => $this->appliedMigrations($pdo),
            'pending'   => $this->pendingMigrations($pdo),
            'csrfField' => Csrf::field(),
            'token'     => $_GET['token'] ?? '',
        ]);
    }

    public function run(): string
    {
        $this->authorize();
        Csrf::requireValid();

        $pdo = Database::connection($this->config);
        $this->ensureMigrationsTable($pdo);

        $applied = $this->appliedMigrations($pdo);
        $batch   = $this->nextBatch($pdo);
        $results = [];

        foreach ($this->migrationFiles() as $file) {
            $name = basename($file);
            if (in_array($name, $applied, true)) {
                continue;
            }

            $sql = (string) file_get_contents($file);
            try {
                foreach ($this->splitStatements($sql) as $statement) {
                    $pdo->exec($statement);
                }
                $stmt = $pdo->prepare('INSERT INTO migrations (migration, batch) VALUES (:m, :b)');
                $stmt->execute(['m' => $name, 'b' => $batch]);
                $results[] = ['ok' => true, 'msg' => "Applicata: {$name}"];
            } catch (Throwable $e) {
                $results[] = ['ok' => false, 'msg' => "ERRORE in {$name}. Dettagli nel log applicativo."];
                Logger::error('Migrazione fallita', ['file' => $name, 'err' => $e->getMessage()]);
                break; // stop alla prima migrazione fallita, in ordine
            }
        }

        return $this->view('migrate_result', [
            'pageTitle' => 'Esito migrazioni',
            'results'   => $results,
            'token'     => $_GET['token'] ?? '',
        ]);
    }

    private function authorize(): void
    {
        $token    = $_GET['token'] ?? '';
        $expected = (string) ($this->config['app']['migrate_token'] ?? '');

        if ($expected === '' ) {
            http_response_code(403);
            exit('Token di migrazione non configurato in config.php.');
        }
        if (!is_string($token) || !hash_equals($expected, $token)) {
            http_response_code(403);
            Logger::error('Accesso migrazioni negato', ['ip' => $_SERVER['REMOTE_ADDR'] ?? '?']);
            exit('Accesso negato.');
        }
    }

    private function ensureMigrationsTable(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS migrations (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                batch INT UNSIGNED NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_migrations_migration (migration)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    /** @return string[] */
    private function appliedMigrations(PDO $pdo): array
    {
        return $pdo->query('SELECT migration FROM migrations ORDER BY id')->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    /** @return string[] */
    private function pendingMigrations(PDO $pdo): array
    {
        $applied = $this->appliedMigrations($pdo);
        $all     = array_map('basename', $this->migrationFiles());
        return array_values(array_diff($all, $applied));
    }

    private function nextBatch(PDO $pdo): int
    {
        return ((int) $pdo->query('SELECT COALESCE(MAX(batch), 0) FROM migrations')->fetchColumn()) + 1;
    }

    /** @return string[] */
    private function migrationFiles(): array
    {
        $files = glob(BASE_PATH . '/database/migrations/*.sql') ?: [];
        sort($files, SORT_STRING);
        return $files;
    }

    /** Statement separati da ";" a fine riga. Niente procedure/trigger nelle migrazioni. */
    private function splitStatements(string $sql): array
    {
        $parts = preg_split('/;\s*\r?\n/', $sql) ?: [];
        return array_values(array_filter(array_map('trim', $parts), static fn (string $s) => $s !== ''));
    }
}