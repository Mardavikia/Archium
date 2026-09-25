<?php
declare(strict_types=1);

namespace Archium\Controllers;

use Archium\Policies\CollectionPolicy;
use Archium\Repositories\CollectionRepository;
use Archium\Support\Csrf;
use Archium\Support\Database;
use Archium\Support\Str;
use Archium\Validation\Validator;

final class CollectionController extends BaseController
{
    private function repo(): CollectionRepository
    {
        return new CollectionRepository(Database::connection($this->config));
    }

    public function index(): string
    {
        $ws = $this->currentWorkspace();
        if (!CollectionPolicy::canView($ws['my_role'])) {
            http_response_code(403);
            return 'Accesso negato.';
        }
        return $this->view('collections/index', [
            'pageTitle'   => 'Raccolte',
            'ws'          => $ws,
            'collections' => $this->repo()->forWorkspace((int) $ws['id']),
            'canWrite'    => CollectionPolicy::canWrite($ws['my_role']),
        ]);
    }

    public function create(): string
    {
        $ws = $this->currentWorkspace();
        if (!CollectionPolicy::canWrite($ws['my_role'])) {
            http_response_code(403);
            return 'Non hai i permessi di scrittura su questo workspace.';
        }
        return $this->view('collections/create', [
            'pageTitle'   => 'Nuova raccolta',
            'ws'          => $ws,
            'collections' => $this->repo()->forWorkspace((int) $ws['id']),
            'errors'      => [],
            'old'         => ['name' => '', 'description' => '', 'parent_id' => ''],
        ]);
    }

    public function store(): string
    {
        Csrf::requireValid();
        $ws = $this->currentWorkspace();
        if (!CollectionPolicy::canWrite($ws['my_role'])) {
            http_response_code(403);
            return 'Non hai i permessi di scrittura su questo workspace.';
        }

        $repo  = $this->repo();
        $name  = trim((string) ($_POST['name'] ?? ''));
        $desc  = trim((string) ($_POST['description'] ?? ''));
        $parentId = ($raw = trim((string) ($_POST['parent_id'] ?? ''))) !== '' ? (int) $raw : null;

        $v = Validator::make($_POST, ['name' => 'required|min:2|max:150', 'description' => 'max:500']);

        if ($parentId !== null && !$repo->findInWorkspace($parentId, (int) $ws['id'])) {
            $v2 = ['parent_id' => ['Raccolta genitore non valida.']];
        } else {
            $v2 = [];
        }

        if ($v->fails() || $v2 !== []) {
            return $this->view('collections/create', [
                'pageTitle'   => 'Nuova raccolta',
                'ws'          => $ws,
                'collections' => $repo->forWorkspace((int) $ws['id']),
                'errors'      => array_merge($v->errors(), $v2),
                'old'         => ['name' => $name, 'description' => $desc, 'parent_id' => $parentId],
            ]);
        }

        $slug = $this->uniqueSlug($repo, (int) $ws['id'], $name);
        $repo->create((int) $ws['id'], $parentId, $name, $slug, $desc !== '' ? $desc : null);

        flash('success', 'Raccolta creata.');
        redirect('/collections');
    }

    public function edit(string $id): string
    {
        $ws = $this->currentWorkspace();
        if (!CollectionPolicy::canWrite($ws['my_role'])) {
            http_response_code(403);
            return 'Non hai i permessi di scrittura su questo workspace.';
        }
        $repo = $this->repo();
        $col  = $repo->findInWorkspace((int) $id, (int) $ws['id']);
        if (!$col) {
            http_response_code(404);
            return 'Raccolta non trovata.';
        }
        return $this->view('collections/edit', [
            'pageTitle'   => 'Modifica raccolta',
            'ws'          => $ws,
            'col'         => $col,
            'collections' => $repo->forWorkspace((int) $ws['id']),
            'errors'      => [],
        ]);
    }

    public function update(string $id): string
    {
        Csrf::requireValid();
        $ws = $this->currentWorkspace();
        if (!CollectionPolicy::canWrite($ws['my_role'])) {
            http_response_code(403);
            return 'Non hai i permessi di scrittura su questo workspace.';
        }

        $repo = $this->repo();
        $col  = $repo->findInWorkspace((int) $id, (int) $ws['id']);
        if (!$col) {
            http_response_code(404);
            return 'Raccolta non trovata.';
        }

        $name = trim((string) ($_POST['name'] ?? ''));
        $desc = trim((string) ($_POST['description'] ?? ''));
        $parentId = ($raw = trim((string) ($_POST['parent_id'] ?? ''))) !== '' ? (int) $raw : null;

        $v = Validator::make($_POST, ['name' => 'required|min:2|max:150', 'description' => 'max:500']);
        $extra = [];

        if ($parentId !== null) {
            if (!$repo->findInWorkspace($parentId, (int) $ws['id'])) {
                $extra['parent_id'] = ['Raccolta genitore non valida.'];
            } elseif ($repo->isSelfOrDescendant((int) $col['id'], $parentId)) {
                $extra['parent_id'] = ['Non puoi spostare una raccolta dentro se stessa o in una sua discendente.'];
            }
        }

        if ($v->fails() || $extra !== []) {
            $col['name'] = $name;
            $col['description'] = $desc;
            $col['parent_id'] = $parentId;
            return $this->view('collections/edit', [
                'pageTitle'   => 'Modifica raccolta',
                'ws'          => $ws,
                'col'         => $col,
                'collections' => $repo->forWorkspace((int) $ws['id']),
                'errors'      => array_merge($v->errors(), $extra),
            ]);
        }

        $slug = $this->uniqueSlug($repo, (int) $ws['id'], $name, (int) $col['id']);
        $repo->update((int) $col['id'], $parentId, $name, $slug, $desc !== '' ? $desc : null);

        flash('success', 'Raccolta aggiornata.');
        redirect('/collections');
    }

    public function destroy(string $id): string
    {
        Csrf::requireValid();
        $ws = $this->currentWorkspace();
        if (!CollectionPolicy::canWrite($ws['my_role'])) {
            http_response_code(403);
            return 'Non hai i permessi di scrittura su questo workspace.';
        }
        $repo = $this->repo();
        $col  = $repo->findInWorkspace((int) $id, (int) $ws['id']);
        if (!$col) {
            http_response_code(404);
            return 'Raccolta non trovata.';
        }
        $repo->softDelete((int) $col['id']);
        flash('success', 'Raccolta eliminata. Documenti e sotto-raccolte sono stati spostati alla radice.');
        redirect('/collections');
    }

    private function uniqueSlug(CollectionRepository $repo, int $wsId, string $base, ?int $excludeId = null): string
    {
        $slug = Str::slug($base);
        $candidate = $slug;
        $i = 2;
        while ($repo->slugTaken($wsId, $candidate, $excludeId)) {
            $candidate = $slug . '-' . $i++;
        }
        return $candidate;
    }
}