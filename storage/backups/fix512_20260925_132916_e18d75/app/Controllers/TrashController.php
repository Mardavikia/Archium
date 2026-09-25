<?php
declare(strict_types=1);

namespace Archium\Controllers;

use Archium\Policies\CollectionPolicy;
use Archium\Policies\DocumentPolicy;
use Archium\Repositories\CollectionRepository;
use Archium\Repositories\DocumentRepository;
use Archium\Support\Csrf;
use Archium\Support\Database;

final class TrashController extends BaseController
{
    public function index(): string
    {
        $ws = $this->currentWorkspace();
        if (!DocumentPolicy::canWrite($ws['my_role'])) {
            http_response_code(403);
            return 'Il cestino e\' accessibile solo a chi puo\' scrivere nel workspace.';
        }
        $pdo = Database::connection($this->config);
        return $this->view('trash/index', [
            'pageTitle'   => 'Cestino',
            'ws'          => $ws,
            'documents'   => (new \Archium\Repositories\TrashRepository($pdo))->documents((int) $ws['id']),
            'collections' => (new CollectionRepository($pdo))->trashedForWorkspace((int) $ws['id']),
            'isOwner'     => $ws['my_role'] === 'owner',
        ]);
    }

    public function restoreDocument(string $id): string
    {
        Csrf::requireValid();
        $ws   = $this->currentWorkspace();
        $docs = new DocumentRepository(Database::connection($this->config));
        $doc  = $docs->findTrashedInWorkspace((int) $id, (int) $ws['id']);
        if (!$doc) {
            http_response_code(404);
            return 'Documento non trovato nel cestino.';
        }
        if (!DocumentPolicy::canWrite($ws['my_role'])) {
            http_response_code(403);
            return 'Non puoi ripristinare.';
        }
        $docs->restore((int) $doc['id']);
        flash('success', 'Documento ripristinato: "' . $doc['title'] . '".');
        redirect('/trash');
    }

    public function forceDeleteDocument(string $id): string
    {
        Csrf::requireValid();
        $ws   = $this->currentWorkspace();
        if ($ws['my_role'] !== 'owner') {
            http_response_code(403);
            return 'Solo il proprietario del workspace puo\' eliminare definitivamente.';
        }
        $docs = new DocumentRepository(Database::connection($this->config));
        $doc  = $docs->findTrashedInWorkspace((int) $id, (int) $ws['id']);
        if (!$doc) {
            http_response_code(404);
            return 'Documento non trovato nel cestino.';
        }
        $docs->forceDelete((int) $doc['id']);
        flash('success', 'Documento eliminato definitivamente (revisioni, tag e preferiti rimossi).');
        redirect('/trash');
    }

    public function restoreCollection(string $id): string
    {
        Csrf::requireValid();
        $ws   = $this->currentWorkspace();
        $cols = new CollectionRepository(Database::connection($this->config));
        $col  = $cols->findTrashedInWorkspace((int) $id, (int) $ws['id']);
        if (!$col) {
            http_response_code(404);
            return 'Raccolta non trovata nel cestino.';
        }
        if (!CollectionPolicy::canWrite($ws['my_role'])) {
            http_response_code(403);
            return 'Non puoi ripristinare.';
        }
        $cols->restore((int) $col['id']);
        flash('success', 'Raccolta ripristinata: "' . $col['name'] . '".');
        redirect('/trash');
    }
}