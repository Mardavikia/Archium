<?php
declare(strict_types=1);

namespace Archium\Controllers;

use Archium\Repositories\UserRepository;
use Archium\Services\AuthService;
use Archium\Services\Mailer;
use Archium\Support\Auth;
use Archium\Support\Csrf;
use Archium\Support\Database;
use Archium\Validation\Validator;

final class SetupController extends BaseController
{
    private function usersRepo(): UserRepository
    {
        return new UserRepository(Database::connection($this->config));
    }

    public function show(): string
    {
        if ($this->usersRepo()->hasAdmin()) {
            flash('error', 'Il setup iniziale e\' gia\' stato completato.');
            redirect('/login');
        }
        return $this->view('setup', [
            'pageTitle' => 'Setup iniziale',
            'errors'    => [],
            'old'       => ['name' => '', 'email' => ''],
        ]);
    }

    public function store(): string
    {
        $repo = $this->usersRepo();
        if ($repo->hasAdmin()) {
            flash('error', 'Il setup iniziale e\' gia\' stato completato.');
            redirect('/login');
        }

        Csrf::requireValid();

        $name  = trim((string) ($_POST['name'] ?? ''));
        $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));

        $v = Validator::make($_POST, [
            'name'     => 'required|min:2|max:120',
            'email'    => 'required|email|max:190',
            'password' => 'required|min:10|max:72|confirmed',
        ]);
        if ($v->fails()) {
            return $this->view('setup', [
                'pageTitle' => 'Setup iniziale',
                'errors'    => $v->errors(),
                'old'       => ['name' => $name, 'email' => $email],
            ]);
        }
        if ($repo->emailExists($email)) {
            return $this->view('setup', [
                'pageTitle' => 'Setup iniziale',
                'errors'    => ['email' => ['Questa email risulta gia\' registrata.']],
                'old'       => ['name' => $name, 'email' => $email],
            ]);
        }

        $pdo = Database::connection($this->config);
        $service = new AuthService($this->config, $pdo, $repo, new Mailer($this->config));
        $adminId = $service->createAdmin($name, $email, (string) $_POST['password']);

        $admin = $repo->findActiveWithRole($adminId);
        if ($admin !== null) {
            Auth::login($this->config, $admin);
        }

        flash('success', 'Amministratore creato. Benvenuto in ' . ($this->config['app']['name'] ?? 'Archium') . '.');
        redirect('/dashboard');
    }
}