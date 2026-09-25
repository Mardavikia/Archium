<?php
declare(strict_types=1);

namespace Archium\Controllers;

use Archium\Repositories\UserRepository;
use Archium\Repositories\WorkspaceMemberRepository;
use Archium\Repositories\WorkspaceRepository;
use Archium\Support\Auth;
use Archium\Support\Csrf;
use Archium\Support\Database;

final class MemberController extends BaseController
{
    private function workspace(int $id): array
    {
        $workspace = (new WorkspaceRepository(Database::connection($this->config)))->findActive($id);
        if ($workspace === null) {
            http_response_code(404);
            exit('Workspace non trovato.');
        }
        $user = Auth::user($this->config);
        $role = (new WorkspaceRepository(Database::connection($this->config)))->roleOf($id, (int) $user['id']);
        if ($role !== 'owner') {
            http_response_code(403);
            exit('Solo il proprietario del workspace può gestire i membri.');
        }
        return $workspace;
    }

    public function index(string $id): string
    {
        $workspace = $this->workspace((int) $id);
        return $this->view('members/index', [
            'pageTitle' => 'Membri — ' . $workspace['name'],
            'workspace' => $workspace,
            'members' => (new WorkspaceMemberRepository(Database::connection($this->config)))->forWorkspace((int) $id),
            'errors' => [],
        ]);
    }

    public function add(string $id): string
    {
        Csrf::requireValid();
        $workspace = $this->workspace((int) $id);
        $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
        $role = (string) ($_POST['role'] ?? 'viewer');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($role, ['owner', 'editor', 'viewer'], true)) {
            flash('error', 'Email o ruolo non valido.');
            redirect('/workspaces/' . (int) $id . '/members');
        }

        $pdo = Database::connection($this->config);
        $user = (new UserRepository($pdo))->findByEmail($email);
        if ($user === null || $user['status'] !== 'active') {
            flash('error', 'Utente non trovato o non attivo. L’utente deve prima registrarsi e verificare l’email.');
            redirect('/workspaces/' . (int) $id . '/members');
        }

        (new WorkspaceMemberRepository($pdo))->add((int) $workspace['id'], (int) $user['id'], $role);
        flash('success', 'Membro aggiunto o aggiornato.');
        redirect('/workspaces/' . (int) $id . '/members');
    }

    public function update(string $id, string $memberId): string
    {
        Csrf::requireValid();
        $workspace = $this->workspace((int) $id);
        $role = (string) ($_POST['role'] ?? 'viewer');
        if (!in_array($role, ['owner', 'editor', 'viewer'], true)) {
            flash('error', 'Ruolo non valido.');
            redirect('/workspaces/' . (int) $id . '/members');
        }

        $repo = new WorkspaceMemberRepository(Database::connection($this->config));
        $member = $repo->find((int) $workspace['id'], (int) $memberId);
        if ($member === null) {
            http_response_code(404);
            return 'Membro non trovato.';
        }
        if ($member['role'] === 'owner' && $role !== 'owner' && $repo->countOwners((int) $workspace['id']) <= 1) {
            flash('error', 'Non puoi declassare l’ultimo owner del workspace.');
            redirect('/workspaces/' . (int) $id . '/members');
        }

        $repo->updateRole((int) $workspace['id'], (int) $memberId, $role);
        flash('success', 'Ruolo del membro aggiornato.');
        redirect('/workspaces/' . (int) $id . '/members');
    }

    public function remove(string $id, string $memberId): string
    {
        Csrf::requireValid();
        $workspace = $this->workspace((int) $id);
        $repo = new WorkspaceMemberRepository(Database::connection($this->config));
        $member = $repo->find((int) $workspace['id'], (int) $memberId);
        if ($member === null) {
            http_response_code(404);
            return 'Membro non trovato.';
        }
        if ($member['role'] === 'owner' && $repo->countOwners((int) $workspace['id']) <= 1) {
            flash('error', 'Non puoi rimuovere l’ultimo owner del workspace.');
            redirect('/workspaces/' . (int) $id . '/members');
        }

        $repo->remove((int) $workspace['id'], (int) $memberId);
        flash('success', 'Membro rimosso dal workspace.');
        redirect('/workspaces/' . (int) $id . '/members');
    }
}