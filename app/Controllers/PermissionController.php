<?php
declare(strict_types=1);

namespace Archium\Controllers;

use Archium\Repositories\CollectionRepository;
use Archium\Repositories\DocumentRepository;
use Archium\Repositories\UserRepository;
use Archium\Repositories\WorkspaceRepository;
use Archium\Services\PermissionResolver;
use Archium\Support\Auth;
use Archium\Support\Csrf;
use Archium\Support\Database;

final class PermissionController extends BaseController
{
    private function resolver(): PermissionResolver
    {
        $pdo = Database::connection($this->config);
        return new PermissionResolver(new WorkspaceRepository($pdo), new CollectionRepository($pdo), new DocumentRepository($pdo));
    }

    public function collectionIndex(string $id): string
    {
        $ws = $this->currentWorkspace();
        $user = Auth::user($this->config);
        $pdo = Database::connection($this->config);
        $collection = (new CollectionRepository($pdo))->findInWorkspace((int) $id, (int) $ws['id']);
        if ($collection === null) {
            http_response_code(404);
            return 'Raccolta non trovata.';
        }
        if (!$this->resolver()->canCollection((int) $collection['id'], (int) $ws['id'], (int) $user['id'], 'admin')) {
            http_response_code(403);
            return 'Non hai i permessi amministrativi sulla raccolta.';
        }
        return $this->view('permissions/collection', [
            'pageTitle' => 'Permessi raccolta', 'ws' => $ws, 'collection' => $collection,
            'permissions' => (new CollectionRepository($pdo))->permissions((int) $collection['id']),
        ]);
    }

    public function documentIndex(string $id): string
    {
        $ws = $this->currentWorkspace();
        $user = Auth::user($this->config);
        $pdo = Database::connection($this->config);
        $document = (new DocumentRepository($pdo))->findInWorkspace((int) $id, (int) $ws['id']);
        if ($document === null) {
            http_response_code(404);
            return 'Documento non trovato.';
        }
        if (!$this->resolver()->canDocument((int) $document['id'], (int) $ws['id'], $document['collection_id'] !== null ? (int) $document['collection_id'] : null, (int) $user['id'], 'admin')) {
            http_response_code(403);
            return 'Non hai i permessi amministrativi sul documento.';
        }
        return $this->view('permissions/document', [
            'pageTitle' => 'Permessi documento', 'ws' => $ws, 'document' => $document,
            'permissions' => (new DocumentRepository($pdo))->permissions((int) $document['id']),
        ]);
    }

    public function grantCollection(string $id): string
    {
        Csrf::requireValid();
        $ws = $this->currentWorkspace(); $user = Auth::user($this->config); $pdo = Database::connection($this->config);
        $repo = new CollectionRepository($pdo); $collection = $repo->findInWorkspace((int) $id, (int) $ws['id']);
        if ($collection === null || !$this->resolver()->canCollection((int) $id, (int) $ws['id'], (int) $user['id'], 'admin')) { http_response_code(403); return 'Accesso negato.'; }
        $target = (new UserRepository($pdo))->findByEmail(mb_strtolower(trim((string) ($_POST['email'] ?? ''))));
        $permission = (string) ($_POST['permission'] ?? 'read');
        if ($target === null || !in_array($permission, ['read','write','admin'], true)) { flash('error', 'Utente o permesso non valido.'); redirect('/collections/' . (int) $id . '/permissions'); }
        if ((new WorkspaceRepository($pdo))->roleOf((int) $ws['id'], (int) $target['id']) === null) { flash('error', 'L’utente deve essere prima membro del workspace.'); redirect('/collections/' . (int) $id . '/permissions'); }
        $repo->grantPermission((int) $id, (int) $target['id'], $permission);
        flash('success', 'Permesso raccolta aggiornato.'); redirect('/collections/' . (int) $id . '/permissions');
    }

    public function grantDocument(string $id): string
    {
        Csrf::requireValid();
        $ws = $this->currentWorkspace(); $user = Auth::user($this->config); $pdo = Database::connection($this->config);
        $repo = new DocumentRepository($pdo); $document = $repo->findInWorkspace((int) $id, (int) $ws['id']);
        if ($document === null || !$this->resolver()->canDocument((int) $id, (int) $ws['id'], $document['collection_id'] !== null ? (int) $document['collection_id'] : null, (int) $user['id'], 'admin')) { http_response_code(403); return 'Accesso negato.'; }
        $target = (new UserRepository($pdo))->findByEmail(mb_strtolower(trim((string) ($_POST['email'] ?? ''))));
        $permission = (string) ($_POST['permission'] ?? 'read');
        if ($target === null || !in_array($permission, ['read','write','admin'], true)) { flash('error', 'Utente o permesso non valido.'); redirect('/documents/' . (int) $id . '/permissions'); }
        if ((new WorkspaceRepository($pdo))->roleOf((int) $ws['id'], (int) $target['id']) === null) { flash('error', 'L’utente deve essere prima membro del workspace.'); redirect('/documents/' . (int) $id . '/permissions'); }
        $repo->grantPermission((int) $id, (int) $target['id'], $permission);
        flash('success', 'Permesso documento aggiornato.'); redirect('/documents/' . (int) $id . '/permissions');
    }

    public function revokeCollection(string $id, string $userId): string
    {
        Csrf::requireValid(); $ws = $this->currentWorkspace(); $user = Auth::user($this->config); $pdo = Database::connection($this->config);
        $collection = (new CollectionRepository($pdo))->findInWorkspace((int) $id, (int) $ws['id']);
        if ($collection === null || !$this->resolver()->canCollection((int) $id, (int) $ws['id'], (int) $user['id'], 'admin')) { http_response_code(403); return 'Accesso negato.'; }
        (new CollectionRepository($pdo))->revokePermission((int) $id, (int) $userId);
        flash('success', 'Override raccolta rimosso.'); redirect('/collections/' . (int) $id . '/permissions');
    }

    public function revokeDocument(string $id, string $userId): string
    {
        Csrf::requireValid(); $ws = $this->currentWorkspace(); $user = Auth::user($this->config); $pdo = Database::connection($this->config);
        $document = (new DocumentRepository($pdo))->findInWorkspace((int) $id, (int) $ws['id']);
        if ($document === null || !$this->resolver()->canDocument((int) $id, (int) $ws['id'], $document['collection_id'] !== null ? (int) $document['collection_id'] : null, (int) $user['id'], 'admin')) { http_response_code(403); return 'Accesso negato.'; }
        (new DocumentRepository($pdo))->revokePermission((int) $id, (int) $userId);
        flash('success', 'Override documento rimosso.'); redirect('/documents/' . (int) $id . '/permissions');
    }
}