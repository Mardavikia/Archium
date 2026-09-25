<?php
declare(strict_types=1);

namespace Archium\Controllers;

use Archium\Policies\DocumentPolicy;
use Archium\Repositories\DocumentRepository;
use Archium\Repositories\RevisionRepository;
use Archium\Services\MarkdownService;
use Archium\Support\Auth;
use Archium\Support\Csrf;
use Archium\Support\Database;

final class RevisionController extends BaseController
{
    private function loadDoc(string $id): array
    {
        $ws  = $this->currentWorkspace();
        $doc = (new DocumentRepository(Database::connection($this->config)))
            ->findInWorkspace((int) $id, (int) $ws['id']);
        if (!$doc) {
            http_response_code(404);
            exit('Documento non trovato.');
        }
        if (!DocumentPolicy::canView($ws['my_role'])) {
            http_response_code(403);
            exit('Accesso negato.');
        }
        return [$ws, $doc];
    }

    public function index(string $id): string
    {
        [$ws, $doc] = $this->loadDoc($id);
        return $this->view('documents/revisions', [
            'pageTitle' => 'Revisioni: ' . $doc['title'],
            'ws'        => $ws,
            'doc'       => $doc,
            'revisions' => (new RevisionRepository(Database::connection($this->config)))->forDocument((int) $doc['id']),
            'canWrite'  => DocumentPolicy::canWrite($ws['my_role']),
        ]);
    }

    public function show(string $id, string $num): string
    {
        [$ws, $doc] = $this->loadDoc($id);
        $rev = (new RevisionRepository(Database::connection($this->config)))
            ->findByNumber((int) $doc['id'], (int) $num);
        if (!$rev) {
            http_response_code(404);
            return 'Revisione non trovata.';
        }
        return $this->view('documents/revision_view', [
            'pageTitle' => 'Revisione #' . $rev['revision_number'] . ' — ' . $doc['title'],
            'ws'        => $ws,
            'doc'       => $doc,
            'rev'       => $rev,
            'html'      => (new MarkdownService())->toHtml((string) ($rev['content_markdown'] ?? '')),
            'canWrite'  => DocumentPolicy::canWrite($ws['my_role']),
        ]);
    }

    public function restore(string $id, string $num): string
    {
        Csrf::requireValid();
        [$ws, $doc] = $this->loadDoc($id);
        if (!DocumentPolicy::canWrite($ws['my_role'])) {
            http_response_code(403);
            return 'Non puoi ripristinare questo documento.';
        }

        $pdo  = Database::connection($this->config);
        $revs = new RevisionRepository($pdo);
        $rev  = $revs->findByNumber((int) $doc['id'], (int) $num);
        if (!$rev) {
            http_response_code(404);
            return 'Revisione non trovata.';
        }

        $user = Auth::user($this->config);

        // 1. snapshot dello stato CORRENTE: il ripristino resta annullabile
        $revs->add((int) $doc['id'], (string) $doc['title'], $doc['content_markdown'], (int) $user['id']);

        // 2. applica la revisione scelta
        $html = (new MarkdownService())->toHtml((string) ($rev['content_markdown'] ?? ''));
        (new DocumentRepository($pdo))->update(
            (int) $doc['id'],
            $doc['collection_id'] !== null ? (int) $doc['collection_id'] : null,
            $doc['parent_id'] !== null ? (int) $doc['parent_id'] : null,
            (string) $rev['title'],
            $rev['content_markdown'],
            $html,
            (int) $user['id']
        );

        flash('success', 'Ripristinata la revisione #' . (int) $rev['revision_number'] . ' (lo stato precedente e\' salvato come nuova revisione).');
        redirect('/documents/' . (int) $doc['id']);
    }
}