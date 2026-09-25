<?php
declare(strict_types=1);

namespace Archium\Controllers;

use Archium\Repositories\UserAdminRepository;
use Archium\Support\Auth;
use Archium\Support\Csrf;
use Archium\Support\Database;

final class UserAdminController extends BaseController
{
    private function guard(): UserAdminRepository
    {
        if (Auth::role($this->config) !== 'admin') {
            http_response_code(403);
            exit('Accesso riservato agli amministratori globali.');
        }

        return new UserAdminRepository(Database::connection($this->config));
    }

    public function index(): string
    {
        $repo = $this->guard();

        return $this->view('admin/users', [
            'pageTitle' => 'Gestione utenti',
            'users' => $repo->all(),
            'roles' => $repo->roles(),
            'createErrors' => [],
            'createOld' => [
                'name' => '',
                'email' => '',
                'role_id' => '',
                'status' => 'active',
            ],
        ]);
    }

    public function create(): string
    {
        Csrf::requireValid();

        $repo = $this->guard();

        $name = trim((string) ($_POST['name'] ?? ''));
        $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirmation = (string) ($_POST['password_confirmation'] ?? '');
        $roleId = (int) ($_POST['role_id'] ?? 0);
        $status = (string) ($_POST['status'] ?? 'active');

        $old = [
            'name' => $name,
            'email' => $email,
            'role_id' => (string) $roleId,
            'status' => $status,
        ];

        $errors = [];

        if (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
            $errors['name'][] = 'Il nome deve contenere da 2 a 120 caratteri.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
            $errors['email'][] = 'Inserisci un indirizzo email valido.';
        }

        if (mb_strlen($password) < 10 || mb_strlen($password) > 72) {
            $errors['password'][] = 'La password deve contenere da 10 a 72 caratteri.';
        }

        if (!hash_equals($password, $passwordConfirmation)) {
            $errors['password'][] = 'La conferma password non corrisponde.';
        }

        if (!in_array($status, ['active', 'disabled'], true)) {
            $errors['status'][] = 'Stato utente non valido.';
        }

        $validRole = false;

        foreach ($repo->roles() as $role) {
            if ((int) $role['id'] === $roleId) {
                $validRole = true;
                break;
            }
        }

        if (!$validRole) {
            $errors['role_id'][] = 'Seleziona un ruolo valido.';
        }

        if ($email !== '' && $repo->findByEmail($email) !== null) {
            $errors['email'][] = 'Esiste già un utente con questa email.';
        }

        if ($errors !== []) {
            return $this->view('admin/users', [
                'pageTitle' => 'Gestione utenti',
                'users' => $repo->all(),
                'roles' => $repo->roles(),
                'createErrors' => $errors,
                'createOld' => $old,
            ]);
        }

        $cost = (int) ($this->config['security']['bcrypt_cost'] ?? 12);

        $passwordHash = password_hash(
            $password,
            PASSWORD_BCRYPT,
            ['cost' => $cost]
        );

        $repo->create(
            $name,
            $email,
            $passwordHash,
            $roleId,
            $status
        );

        flash(
            'success',
            'Utente creato manualmente. L’account è già verificato; l’utente può accedere se impostato come attivo.'
        );

        redirect('/admin/users');
    }

    public function updateRole(string $id): string
    {
        Csrf::requireValid();

        $repo = $this->guard();
        $target = $repo->find((int) $id);
        $roleId = (int) ($_POST['role_id'] ?? 0);

        $validRole = false;
        $newRoleName = null;

        foreach ($repo->roles() as $role) {
            if ((int) $role['id'] === $roleId) {
                $validRole = true;
                $newRoleName = (string) $role['name'];
                break;
            }
        }

        if ($target === null || !$validRole) {
            flash('error', 'Utente o ruolo non valido.');
            redirect('/admin/users');
        }

        if (
            $target['role_name'] === 'admin'
            && $newRoleName !== 'admin'
            && $target['status'] === 'active'
            && $repo->countActiveAdmins() <= 1
        ) {
            flash('error', 'Non puoi declassare l’ultimo amministratore attivo.');
            redirect('/admin/users');
        }

        $repo->updateRole((int) $id, $roleId);

        flash('success', 'Ruolo globale aggiornato.');
        redirect('/admin/users');
    }

    public function updateStatus(string $id): string
    {
        Csrf::requireValid();

        $repo = $this->guard();
        $target = $repo->find((int) $id);
        $status = (string) ($_POST['status'] ?? '');

        if ($target === null || !in_array($status, ['active', 'disabled'], true)) {
            flash('error', 'Utente o stato non valido.');
            redirect('/admin/users');
        }

        if (
            $target['role_name'] === 'admin'
            && $target['status'] === 'active'
            && $status === 'disabled'
            && $repo->countActiveAdmins() <= 1
        ) {
            flash('error', 'Non puoi disabilitare l’ultimo amministratore attivo.');
            redirect('/admin/users');
        }

        $repo->updateStatus((int) $id, $status);

        flash('success', 'Stato utente aggiornato.');
        redirect('/admin/users');
    }
}