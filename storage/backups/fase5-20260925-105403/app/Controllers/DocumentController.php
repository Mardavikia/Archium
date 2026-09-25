<?php
declare(strict_types=1);

namespace Archium\Controllers;

use Archium\Policies\DocumentPolicy;
use Archium\Repositories\AttachmentRepository;
use Archium\Repositories\CollectionRepository;
use Archium\Repositories\DocumentRepository;
use Archium\Repositories\RevisionRepository;
use Archium\Repositories\TagRepository;
use Archium\Services\MarkdownService;
use Archium\Support\Auth;
use Archium\Support\Csrf;
use Archium\Support\Database;
use Archium\Validation\Validator;

final class DocumentController extends BaseController
{
    private function docs(): DocumentRepository
    {
        return new DocumentRepository(Database::connection($this->config));
    }

    private function cols(): CollectionRepository
    {
        return new CollectionRepository(Database::connection($this->config));
    }

    private function tags(): TagRepository
    {
        return new TagRepository(Database::connection($this->config));
    }

    private function md(): MarkdownService
    {
        return new MarkdownService();
    }

    public function index(): string
    {
        $ws = $this->currentWorkspace();
        if (!DocumentPolicy::canView($ws['my_role'])) {
            http_response_code(403);
            return 'Accesso negato.';
        }
        return $this->view('documents/index', [
            'pageTitle' => 'Documenti',
            'ws'        => $ws,
            'documents' => $this->docs()->forWorkspace((int) $ws['id']),
            'canWrite'  => DocumentPolicy::canWrite($ws['my_role']),
        ]);
    }

    public function create(): string
    {
        $ws = $this->currentWorkspace();
        if (!DocumentPolicy::canWrite($ws['my_role'])) {
            http_response_code(403);
            return 'Non hai i permessi di scrittura su questo workspace.';
        }
        return $this->view('documents/create', [
            'pageTitle'     => 'Nuovo documento',
            'ws'            => $ws,
            'collections'   => $this->cols()->forWorkspace((int) $ws['id']),
            'parents'       => $this->docs()->forWorkspace((int) $ws['id']),
            'availableTags' => $this->tags()->forWorkspace((int) $ws['id']),
            'errors'        => [],
            'old'           => ['title' => '', 'content_markdown' => '', 'collection_id' => '', 'parent_id' => '', 'tags' => ''],
        ]);
    }

    public function store(): string
    {
        Csrf::requireValid();
        $ws = $this->currentWorkspace();
        if (!DocumentPolicy::canWrite($ws['my_role'])) {
            http_response_code(403);
            return 'Non hai i permessi di scrittura su questo workspace.';
        }

        $docs = $this->docs();
        [$collectionId, $parentId, $refErrors] = $this->validateRefs((int) $ws['id'], null);

        $title   = trim((string) ($_POST['title'] ?? ''));
        $content = (string) ($_POST['content_markdown'] ?? '');
        $tagsIn  = (string) ($_POST['tags'] ?? '');

        $v = Validator::make($_POST, ['title' => 'required|min:2|max:255', 'tags' => 'max:520']);
        if ($v->fails() || $refErrors !== []) {
            return $this->view('documents/create', [
                'pageTitle'     => 'Nuovo documento',
                'ws'            => $ws,
                'collections'   => $this->cols()->forWorkspace((int) $ws['id']),
                'parents'       => $docs->forWorkspace((int) $ws['id']),
                'availableTags' => $this->tags()->forWorkspace((int) $ws['id']),
                'errors'        => array_merge($v->errors(), $refErrors),
                'old'           => ['title' => $title, 'content_markdown' => $content, 'collection_id' => $collectionId, 'parent_id' => $parentId, 'tags' => $tagsIn],
            ]);
        }

        $user = Auth::user($this->config);
        $slug = $docs->uniqueSlug((int) $ws['id'], $title);
        $html = $this->md()->toHtml($content);
        $id   = $docs->create((int) $ws['id'], $collectionId, $parentId, $title, $slug, $content !== '' ? $content : null, $html, (int) $user['id']);

        $this->tags()->syncDocument((int) $ws['id'], $id, TagRepository::parse($tagsIn));

        flash('success', 'Documento creato.');
        redirect('/documents/' . $id);
    }

    public function show(string $id): string
    {
        $ws  = $this->currentWorkspace();
        $doc = $this->docs()->findInWorkspace((int) $id, (int) $ws['id']);
        if (!$doc) {
            http_response_code(404);
            return 'Documento non trovato.';
        }
        if (!DocumentPolicy::canView($ws['my_role'])) {
            http_response_code(403);
            return 'Accesso negato.';
        }

        $pdo = Database::connection($this->config);
        $children = array_values(array_filter(
            $this->docs()->forWorkspace((int) $ws['id']),
            static fn (array $d): bool => (int) ($d['parent_id'] ?? 0) === (int) $doc['id']
        ));

        $html = $doc['content_html'] ?? $this->md()->toHtml((string) ($doc['content_markdown'] ?? ''));

        return $this->view('documents/show', [
            'pageTitle'   => $doc['title'],
            'ws'          => $ws,
            'doc'         => $doc,
            'children'    => $children,
            'html'        => $html,
            'tags'        => $this->tags()->forDocument((int) $doc['id']),
            'attachments' => (new AttachmentRepository($pdo))->forDocument((int) $doc['id']),
            'canWrite'    => DocumentPolicy::canWrite($ws['my_role']),
        ]);
    }

    public function edit(string $id): string
    {
        $ws  = $this->currentWorkspace();
        $doc = $this->docs()->findInWorkspace((int) $id, (int) $ws['id']);
        if (!$doc) {
            http_response_code(404);
            return 'Documento non trovato.';
        }
        if (!DocumentPolicy::canWrite($ws['my_role'])) {
            http_response_code(403);
            return 'Sola lettura: non puoi modificare questo documento.';
        }
        $docTags = $this->tags()->forDocument((int) $doc['id']);
        return $this->view('documents/edit', [
            'pageTitle'     => 'Modifica: ' . $doc['title'],
            'ws'            => $ws,
            'doc'           => $doc,
            'collections'   => $this->cols()->forWorkspace((int) $ws['id']),
            'parents'       => $this->docs()->forWorkspace((int) $ws['id']),
            'availableTags' => $this->tags()->forWorkspace((int) $ws['id']),
            'docTagsCsv'    => TagRepository::toCsv($docTags),
            'attachments'   => (new AttachmentRepository(Database::connection($this->config)))->forDocument((int) $doc['id']),
            'errors'        => [],
        ]);
    }

    public function update(string $id): string
    {
        Csrf::requireValid();
        $ws   = $this->currentWorkspace();
        $docs = $this->docs();
        $doc  = $docs->findInWorkspace((int) $id, (int) $ws['id']);
        if (!$doc) {
            http_response_code(404);
            return 'Documento non trovato.';
        }
        if (!DocumentPolicy::canWrite($ws['my_role'])) {
            http_response_code(403);
            return 'Sola lettura: non puoi modificare questo documento.';
        }

        [$collectionId, $parentId, $refErrors] = $this->validateRefs((int) $ws['id'], (int) $doc['id']);

        $title   = trim((string) ($_POST['title'] ?? ''));
        $content = (string) ($_POST['content_markdown'] ?? '');
        $tagsIn  = (string) ($_POST['tags'] ?? '');

        $v = Validator::make($_POST, ['title' => 'required|min:2|max:255', 'tags' => 'max:520']);
        if ($v->fails() || $refErrors !== []) {
            $doc['title'] = $title;
            $doc['content_markdown'] = $content;
            $doc['collection_id'] = $collectionId;
            $doc['parent_id'] = $parentId;
            return $this->view('documents/edit', [
                'pageTitle'     => 'Modifica: ' . $title,
                'ws'            => $ws,
                'doc'           => $doc,
                'collections'   => $this->cols()->forWorkspace((int) $ws['id']),
                'parents'       => $docs->forWorkspace((int) $ws['id']),
                'availableTags' => $this->tags()->forWorkspace((int) $ws['id']),
                'docTagsCsv'    => $tagsIn,
                'attachments'   => (new AttachmentRepository(Database::connection($this->config)))->forDocument((int) $doc['id']),
                'errors'        => array_merge($v->errors(), $refErrors),
            ]);
        }

        $pdo  = Database::connection($this->config);
        $user = Auth::user($this->config);

        (new RevisionRepository($pdo))->add((int) $doc['id'], (string) $doc['title'], $doc['content_markdown'], (int) $user['id']);

        $html = $this->md()->toHtml($content);
        $docs->update((int) $doc['id'], $collectionId, $parentId, $title, $content !== '' ? $content : null, $html, (int) $user['id']);

        $this->tags()->syncDocument((int) $ws['id'], (int) $doc['id'], TagRepository::parse($tagsIn));

        flash('success', 'Documento aggiornato (revisione precedente salvata).');
        redirect('/documents/' . (int) $doc['id']);
    }

    public function destroy(string $id): string
    {
        Csrf::requireValid();
        $ws   = $this->currentWorkspace();
        $docs = $this->docs();
        $doc  = $docs->findInWorkspace((int) $id, (int) $ws['id']);
        if (!$doc) {
            http_response_code(404);
            return 'Documento non trovato.';
        }
        if (!DocumentPolicy::canDelete($ws['my_role'])) {
            http_response_code(403);
            return 'Non puoi eliminare questo documento.';
        }
        $docs->softDelete((int) $doc['id']);
        flash('success', 'Documento spostato nel cestino.');
        redirect('/documents');
    }

    /** @return array{0:?int,1:?int,2:array<string,string[]>} */
    private function validateRefs(int $wsId, ?int $selfDocId): array
    {
        $errors = [];

        $collectionId = ($raw = trim((string) ($_POST['collection_id'] ?? ''))) !== '' ? (int) $raw : null;
        if ($collectionId !== null && !$this->cols()->findInWorkspace($collectionId, $wsId)) {
            $errors['collection_id'] = ['Raccolta non valida per questo workspace.'];
            $collectionId = null;
        }

        $parentId = ($raw = trim((string) ($_POST['parent_id'] ?? ''))) !== '' ? (int) $raw : null;
        if ($parentId !== null) {
            $parent = $this->docs()->findInWorkspace($parentId, $wsId);
            if (!$parent) {
                $errors['parent_id'] = ['Documento genitore non valido per questo workspace.'];
                $parentId = null;
            } elseif ($selfDocId !== null && $this->docs()->isSelfOrDescendant($selfDocId, $parentId)) {
                $errors['parent_id'] = ['Non puoi spostare un documento dentro se stesso o in un suo discendente.'];
            }
        }

        return [$collectionId, $parentId, $errors];
    }
}