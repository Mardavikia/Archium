<?php
declare(strict_types=1);

namespace Archium\Services;

use Archium\Repositories\UserRepository;
use PDO;
use RuntimeException;

final class AuthService
{
    public function __construct(
        private array $config,
        private PDO $pdo,
        private UserRepository $users,
        private Mailer $mailer
    ) {
    }

    public function hashPassword(string $plain): string
    {
        return password_hash($plain, PASSWORD_BCRYPT, [
            'cost' => (int) ($this->config['security']['bcrypt_cost'] ?? 12),
        ]);
    }

    /** Registra un utente (reader, pending) e invia il link di verifica. @return array{0:int,1:string} [userId, verifyUrl] */
    public function register(string $name, string $email, string $password): array
    {
        $roleId = $this->users->roleId('reader')
            ?? throw new RuntimeException('Ruolo "reader" mancante: esegui il seed della Fase 0.');

        $id  = $this->users->create($name, $email, $this->hashPassword($password), $roleId, 'pending');
        $url = $this->createVerificationUrl($id);
        $this->mailer->send(
            $email,
            'Verifica il tuo account',
            "Ciao {$name},\n\nclicca questo link per verificare il tuo account (valido 24 ore):\n{$url}\n"
        );
        return [$id, $url];
    }

    public function createAdmin(string $name, string $email, string $password): int
    {
        $roleId = $this->users->roleId('admin')
            ?? throw new RuntimeException('Ruolo "admin" mancante: esegui il seed della Fase 0.');

        return $this->users->create($name, $email, $this->hashPassword($password), $roleId, 'active', true);
    }

    public function createVerificationUrl(int $userId): string
    {
        $raw = bin2hex(random_bytes(32));
        $stmt = $this->pdo->prepare(
            'INSERT INTO email_verification_tokens (user_id, token_hash, expires_at)
             VALUES (:u, :t, DATE_ADD(NOW(), INTERVAL 24 HOUR))'
        );
        $stmt->execute(['u' => $userId, 't' => hash('sha256', $raw)]);
        return rtrim((string) $this->config['app']['base_url'], '/') . '/verify-email?token=' . $raw;
    }

    /** Attiva l'account se il token e' valido e non scaduto. */
    public function verifyEmail(string $rawToken): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM email_verification_tokens
             WHERE token_hash = :t AND expires_at > NOW()
             ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute(['t' => hash('sha256', $rawToken)]);
        $row = $stmt->fetch();
        if (!$row) {
            return false;
        }
        $this->users->markVerified((int) $row['user_id']);
        $this->pdo->prepare('DELETE FROM email_verification_tokens WHERE user_id = :u')
            ->execute(['u' => (int) $row['user_id']]);
        return true;
    }

    /** Crea il token di reset e invia la mail. Restituisce l'URL (utile in locale) o null se utente inesistente/non attivo. */
    public function requestPasswordReset(string $email): ?string
    {
        $user = $this->users->findByEmail($email);
        if (!$user || $user['status'] !== 'active') {
            return null;
        }

        $this->pdo->prepare('DELETE FROM password_reset_tokens WHERE email = :e')->execute(['e' => $email]);

        $raw = bin2hex(random_bytes(32));
        $this->pdo->prepare(
            'INSERT INTO password_reset_tokens (email, token_hash, expires_at)
             VALUES (:e, :t, DATE_ADD(NOW(), INTERVAL 1 HOUR))'
        )->execute(['e' => $email, 't' => hash('sha256', $raw)]);

        $url = rtrim((string) $this->config['app']['base_url'], '/')
             . '/reset-password?token=' . $raw . '&email=' . urlencode($email);

        $this->mailer->send(
            $email,
            'Reimposta la password',
            "Per reimpostare la tua password apri questo link (valido 1 ora):\n{$url}\n\nSe non l'hai richiesto tu, ignora questa email.\n"
        );
        return $url;
    }

    public function resetPassword(string $email, string $rawToken, string $newPassword): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM password_reset_tokens
             WHERE email = :e AND token_hash = :t AND expires_at > NOW()
             LIMIT 1'
        );
        $stmt->execute(['e' => $email, 't' => hash('sha256', $rawToken)]);
        if (!$stmt->fetch()) {
            return false;
        }

        $user = $this->users->findByEmail($email);
        if (!$user) {
            return false;
        }

        $this->users->updatePassword((int) $user['id'], $this->hashPassword($newPassword));
        $this->pdo->prepare('DELETE FROM password_reset_tokens WHERE email = :e')->execute(['e' => $email]);

        // invalida le sessioni attive dell'utente: al prossimo accesso dovra' rifare il login
        $this->pdo->prepare('DELETE FROM sessions WHERE user_id = :u')->execute(['u' => (int) $user['id']]);
        return true;
    }
}