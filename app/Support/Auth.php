<?php
declare(strict_types=1);

namespace Archium\Support;

use Archium\Repositories\UserRepository;

final class Auth
{
    private static bool $loaded = false;
    private static ?array $user = null;

    /** Utente corrente (con ruolo), o null se non autenticato. */
    public static function user(array $config): ?array
    {
        if (!self::$loaded) {
            self::$loaded = true;
            $id = $_SESSION['user_id'] ?? null;
            if (is_int($id) || (is_string($id) && ctype_digit($id))) {
                $repo = new UserRepository(Database::connection($config));
                $u = $repo->findActiveWithRole((int) $id);
                if ($u !== null) {
                    self::$user = $u;
                } else {
                    unset($_SESSION['user_id']);
                }
            }
        }
        return self::$user;
    }

    public static function check(array $config): bool
    {
        return self::user($config) !== null;
    }

    public static function id(array $config): ?int
    {
        $u = self::user($config);
        return $u ? (int) $u['id'] : null;
    }

    public static function role(array $config): ?string
    {
        $u = self::user($config);
        return $u ? ($u['role_name'] ?? null) : null;
    }

    /** Login: rigenera l'id di sessione (anti-fixation) e traccia la sessione a DB. */
    public static function login(array $config, array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        self::$loaded = true;
        self::$user = null; // forza ricaricamento con ruolo

        $pdo = Database::connection($config);
        $stmt = $pdo->prepare(
            'INSERT INTO sessions (id, user_id, ip_address, user_agent, last_activity)
             VALUES (:id, :uid, :ip, :ua, NOW())
             ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), ip_address = VALUES(ip_address),
                                     user_agent = VALUES(user_agent), last_activity = NOW()'
        );
        $stmt->execute([
            'id'  => session_id(),
            'uid' => (int) $user['id'],
            'ip'  => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
            'ua'  => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        ]);
    }

    /** Logout completo: rimuove la riga sessions, distrugge e riavvia la sessione (per il flash). */
    public static function logout(array $config): void
    {
        try {
            $pdo = Database::connection($config);
            $pdo->prepare('DELETE FROM sessions WHERE id = :id')->execute(['id' => session_id()]);
        } catch (\Throwable) {
            // il logout deve comunque procedere
        }

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], (bool) $p['secure'], (bool) $p['httponly']);
        }
        session_destroy();

        // nuova sessione pulita per poter mostrare il messaggio di arrivederci
        session_start();
        session_regenerate_id(true);

        self::$loaded = false;
        self::$user = null;
    }
}