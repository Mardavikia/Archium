<?php
declare(strict_types=1);

namespace Archium\Controllers;

use Archium\Policies\DocumentPolicy;
use Archium\Repositories\DocumentRepository;
use Archium\Repositories\TagRepository;
use Archium\Support\Csrf;
use Archium\Support\Database;

final class TagController extends BaseController
{
    private function tags(): TagRepository
    {
        return new TagRepository(Database::connection($this->config));
    }

    private function docs(): DocumentRepository
    {
        return new DocumentRepository(Database::connection($this->config));
    }

    public function index(): string
    {
        $ws = $this->currentWorkspace();
        return $this->view('tags/index', [
            'pageTitle' => 'Tag',
            'ws'        => $ws,
            'tags'      => $this->tags()->forWorkspace((int) $ws['id']),
        ]);
    }

    public function show(string $id): string
    {
        $ws  = $this->currentWorkspace();
        $tag = $this->tags()->findInWorkspace((int) $id, (int) $ws['id']);
        if (!$tag) {
            http_response_code(404);
            return 'Tag non trovato.';
        }
        return $this->view('tags/show', [
            'pageTitle' => 'Tag: ' . $tag['name'],
            'ws'        => $ws,
            'tag'       => $tag,
            'documents' => $this->tags()->documentsForTag((int) $tag['id']),
        ]);
    }

    public function add(string $id): string
    {
        Csrf::requireValid();
        $ws  = $this->currentWorkspace();
        $doc = $this->docs()->findInWorkspace((int) $id, (int) $ws['id']);
        if (!$doc) {
            http_response_code(404);
            return 'Documento non trovato.';
        }
        if (!DocumentPolicy::canWrite($ws['my_role'])) {
            http_response_code(403);
            return 'Sola lettura: non puoi modificare i tag.';
        }
        $this->tags()->addToDocument((int) $ws['id'], (int) $doc['id'], (string) ($_POST['tag'] ?? ''));
        flash('success', 'Tag aggiunto.');
        redirect('/documents/' . (int) $doc['id']);
    }

    public function remove(string $id, string $tagId): string
    {
        Csrf::requireValid();
        $ws  = $this->currentWorkspace();
        $doc = $this->docs()->findInWorkspace((int) $id, (int) $ws['id']);
        if (!$doc) {
            http_response_code(404);
            return 'Documento non trovato.';
        }
        if (!DocumentPolicy::canWrite($ws['my_role'])) {
            http_response_code(403);
            return 'Sola lettura: non puoi modificare i tag.';
        }
        // il tag deve appartenere al workspace corrente
        $tag = $this->tags()->findInWorkspace((int) $tagId, (int) $ws['id']);
        if ($tag) {
            $this->tags()->removeFromDocument((int) $doc['id'], (int) $tag['id']);
        }
        flash('success', 'Tag rimosso.');
        redirect('/documents/' . (int) $doc['id']);
    }
}