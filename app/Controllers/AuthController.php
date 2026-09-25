<?php
declare(strict_types=1);

namespace Archium\Controllers;

use Archium\Repositories\UserRepository;
use Archium\Services\AuthService;
use Archium\Services\Mailer;
use Archium\Support\Auth;
use Archium\Support\Csrf;
use Archium\Support\Database;
use Archium\Support\Logger;
use Archium\Support\RateLimiter;
use Archium\Validation\Validator;

final class AuthController extends BaseController
{
    private function usersRepo(): UserRepository
    {
        return new UserRepository(Database::connection($this->config));
    }

    private function service(): AuthService
    {
        $pdo = Database::connection($this->config);
        return new AuthService($this->config, $pdo, new UserRepository($pdo), new Mailer($this->config));
    }

    private function limiter(): RateLimiter
    {
        return new RateLimiter(BASE_PATH . '/storage/cache');
    }

    private function isLocal(): bool
    {
        return ($this->config['app']['env'] ?? 'production') === 'local';
    }

    // ----------------------------- Registrazione -----------------------------

    public function showRegister(): string
    {
        return $this->view('auth/register', [
            'pageTitle' => 'Registrati',
            'errors'    => [],
            'old'       => ['name' => '', 'email' => ''],
        ]);
    }

    public function register(): string
    {
        Csrf::requireValid();

        $name  = trim((string) ($_POST['name'] ?? ''));
        $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
        $old   = ['name' => $name, 'email' => $email];

        $v = Validator::make($_POST, [
            'name'     => 'required|min:2|max:120',
            'email'    => 'required|email|max:190',
            'password' => 'required|min:8|max:72|confirmed',
        ]);
        if ($v->fails()) {
            return $this->view('auth/register', ['pageTitle' => 'Registrati', 'errors' => $v->errors(), 'old' => $old]);
        }
        if ($this->usersRepo()->emailExists($email)) {
            return $this->view('auth/register', [
                'pageTitle' => 'Registrati',
                'errors'    => ['email' => ['Questa email risulta gia\' registrata.']],
                'old'       => $old,
            ]);
        }

        $key = 'register:' . ($_SERVER['REMOTE_ADDR'] ?? '?');
        if ($this->limiter()->tooManyAttempts($key, 5, 3600)) {
            flash('error', 'Troppe registrazioni da questo indirizzo. Riprova piu\' tardi.');
            redirect('/register');
        }
        $this->limiter()->hit($key, 3600);

        [, $url] = $this->service()->register($name, $email, (string) $_POST['password']);

        if ($this->isLocal()) {
            $_SESSION['_dev_verify_url'] = $url; // solo sviluppo
        }

        flash('success', 'Registrazione completata. Controlla la tua email per verificare l\'account.');
        redirect('/verify-email/notice');
    }

    public function verifyNotice(): string
    {
        $devUrl = $_SESSION['_dev_verify_url'] ?? null;
        unset($_SESSION['_dev_verify_url']);
        return $this->view('auth/verify_notice', [
            'pageTitle' => 'Verifica email',
            'devUrl'    => $this->isLocal() ? $devUrl : null,
        ]);
    }

    public function verifyEmail(): string
    {
        $token = (string) ($_GET['token'] ?? '');
        if ($token !== '' && $this->service()->verifyEmail($token)) {
            flash('success', 'Email verificata. Ora puoi accedere.');
        } else {
            flash('error', 'Link di verifica non valido o scaduto. Registrati di nuovo o richiedi un nuovo link.');
        }
        redirect('/login');
    }

    // ----------------------------- Login / Logout -----------------------------

    public function showLogin(): string
    {
        return $this->view('auth/login', [
            'pageTitle' => 'Accedi',
            'errors'    => [],
            'old'       => ['email' => ''],
        ]);
    }

    public function login(): string
    {
        Csrf::requireValid();

        $email    = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');

        $v = Validator::make($_POST, ['email' => 'required|email|max:190', 'password' => 'required|max:72']);
        if ($v->fails()) {
            return $this->view('auth/login', ['pageTitle' => 'Accedi', 'errors' => $v->errors(), 'old' => ['email' => $email]]);
        }

        $key = 'login:' . $email . '|' . ($_SERVER['REMOTE_ADDR'] ?? '?');
        if ($this->limiter()->tooManyAttempts($key, 5, 600)) {
            flash('error', 'Troppi tentativi di accesso. Riprova tra qualche minuto.');
            redirect('/login');
        }

        $user = $this->usersRepo()->findByEmail($email);
        if (!$user || !password_verify($password, (string) $user['password_hash'])) {
            $this->limiter()->hit($key, 600);
            Logger::error('Login fallito', ['ip' => $_SERVER['REMOTE_ADDR'] ?? '?']); // niente email, niente password
            flash('error', 'Credenziali non valide.');
            redirect('/login');
        }

        if ($user['status'] !== 'active') {
            flash('error', 'Account non attivo: verifica la tua email o contatta un amministratore.');
            redirect('/login');
        }

        $this->limiter()->clear($key);
        $this->usersRepo()->touchLastLogin((int) $user['id']);
        Auth::login($this->config, $user);

        flash('success', 'Bentornato, ' . $user['name'] . '.');
        redirect('/dashboard');
    }

    public function logout(): string
    {
        Csrf::requireValid();
        Auth::logout($this->config);
        flash('success', 'Sei uscito dall\'account.');
        redirect('/login');
    }

    // ----------------------------- Reset password -----------------------------

    public function showForgot(): string
    {
        return $this->view('auth/forgot', ['pageTitle' => 'Password dimenticata', 'errors' => []]);
    }

    public function sendReset(): string
    {
        Csrf::requireValid();

        $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
        $v = Validator::make($_POST, ['email' => 'required|email|max:190']);
        if ($v->fails()) {
            return $this->view('auth/forgot', ['pageTitle' => 'Password dimenticata', 'errors' => $v->errors()]);
        }

        $key = 'reset:' . $email . '|' . ($_SERVER['REMOTE_ADDR'] ?? '?');
        if ($this->limiter()->tooManyAttempts($key, 3, 3600)) {
            flash('error', 'Troppe richieste. Riprova piu\' tardi.');
            redirect('/forgot-password');
        }
        $this->limiter()->hit($key, 3600);

        $url = $this->service()->requestPasswordReset($email);
        if ($this->isLocal() && $url !== null) {
            $_SESSION['_dev_reset_url'] = $url; // solo sviluppo
        }

        // messaggio identico sia se l'email esiste sia se non esiste (no user enumeration)
        flash('success', 'Se l\'email e\' registrata, riceverai un link per reimpostare la password.');
        $devUrl = $_SESSION['_dev_reset_url'] ?? null;
        unset($_SESSION['_dev_reset_url']);
        return $this->view('auth/forgot', [
            'pageTitle' => 'Password dimenticata',
            'errors'    => [],
            'devUrl'    => $this->isLocal() ? $devUrl : null,
        ]);
    }

    public function showReset(): string
    {
        return $this->view('auth/reset', [
            'pageTitle' => 'Nuova password',
            'errors'    => [],
            'token'     => (string) ($_GET['token'] ?? ''),
            'email'     => (string) ($_GET['email'] ?? ''),
        ]);
    }

    public function resetPassword(): string
    {
        Csrf::requireValid();

        $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
        $token = (string) ($_POST['token'] ?? '');

        $v = Validator::make($_POST, [
            'email'    => 'required|email|max:190',
            'password' => 'required|min:8|max:72|confirmed',
        ]);
        if ($v->fails() || $token === '') {
            return $this->view('auth/reset', [
                'pageTitle' => 'Nuova password',
                'errors'    => $v->fails() ? $v->errors() : ['token' => ['Link non valido.']],
                'token'     => $token,
                'email'     => $email,
            ]);
        }

        if ($this->service()->resetPassword($email, $token, (string) $_POST['password'])) {
            flash('success', 'Password aggiornata. Accedi con la nuova password.');
        } else {
            flash('error', 'Link di reset non valido o scaduto. Richiedine uno nuovo.');
        }
        redirect('/login');
    }
}