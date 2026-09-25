<?php
declare(strict_types=1);

namespace Archium\Controllers;

use Archium\Policies\WorkspacePolicy;
use Archium\Repositories\WorkspaceRepository;
use Archium\Support\Auth;
use Archium\Support\Csrf;
use Archium\Support\Database;
use Archium\Validation\Validator;

final class WorkspaceController extends BaseController
{
    private function repo(): WorkspaceRepository
    {
        return new WorkspaceRepository(Database::connection($this->config));
    }

    public function index(): string
    {
        $user = Auth::user($this->config);
        return $this->view('workspaces/index', [
            'pageTitle'   => 'Workspace',
            'workspaces'  => $this->repo()->forUser((int) $user['id']),
            'currentId'   => (int) ($_SESSION['workspace_id'] ?? 0),
            'errors'      => [],
        ]);
    }

    public function create(): string
    {
        return $this->view('workspaces/create', [
            'pageTitle' => 'Nuovo workspace',
            'errors'    => [],
            'old'       => ['name' => '', 'description' => ''],
        ]);
    }

    public function store(): string
    {
        Csrf::requireValid();
        $user = Auth::user($this->config);

        $v = Validator::make($_POST, [
            'name'        => 'required|min:2|max:150',
            'description' => 'max:500',
        ]);
        $name = trim((string) ($_POST['name'] ?? ''));
        $desc = trim((string) ($_POST['description'] ?? ''));

        if ($v->fails()) {
            return $this->view('workspaces/create', [
                'pageTitle' => 'Nuovo workspace',
                'errors'    => $v->errors(),
                'old'       => ['name' => $name, 'description' => $desc],
            ]);
        }

        $repo = $this->repo();
        $id = $repo->create($name, $repo->uniqueSlug($name), (int) $user['id'], $desc !== '' ? $desc : null);
        $_SESSION['workspace_id'] = $id;

        flash('success', 'Workspace creato e attivato.');
        redirect('/workspaces');
    }

    public function select(string $id): string
    {
        Csrf::requireValid();
        $user = Auth::user($this->config);
        $repo = $this->repo();

        $role = $repo->roleOf((int) $id, (int) $user['id']);
        if ($role === null) {
            http_response_code(403);
            return 'Non fai parte di questo workspace.';
        }
        $_SESSION['workspace_id'] = (int) $id;
        flash('success', 'Workspace attivo cambiato.');
        redirect('/dashboard');
    }

    public function edit(string $id): string
    {
        $user = Auth::user($this->config);
        $repo = $this->repo();
        $ws = $repo->findActive((int) $id);

        if (!$ws || !WorkspacePolicy::canManage($repo->roleOf((int) $id, (int) $user['id']))) {
            http_response_code(403);
            return 'Solo il proprietario puo\' modificare il workspace.';
        }
        return $this->view('workspaces/edit', [
            'pageTitle' => 'Modifica workspace',
            'ws'        => $ws,
            'errors'    => [],
        ]);
    }

    public function update(string $id): string
    {
        Csrf::requireValid();
        $user = Auth::user($this->config);
        $repo = $this->repo();
        $ws = $repo->findActive((int) $id);

        if (!$ws || !WorkspacePolicy::canManage($repo->roleOf((int) $id, (int) $user['id']))) {
            http_response_code(403);
            return 'Solo il proprietario puo\' modificare il workspace.';
        }

        $v = Validator::make($_POST, ['name' => 'required|min:2|max:150', 'description' => 'max:500']);
        if ($v->fails()) {
            return $this->view('workspaces/edit', [
                'pageTitle' => 'Modifica workspace',
                'ws'        => array_merge($ws, [
                    'name'        => trim((string) ($_POST['name'] ?? '')),
                    'description' => trim((string) ($_POST['description'] ?? '')),
                ]),
                'errors'    => $v->errors(),
            ]);
        }

        $repo->update(
            (int) $id,
            trim((string) $_POST['name']),
            trim((string) ($_POST['description'] ?? '')) ?: null
        );
        flash('success', 'Workspace aggiornato.');
        redirect('/workspaces');
    }
}