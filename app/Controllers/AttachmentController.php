<?php
declare(strict_types=1);

namespace Archium\Controllers;

use Archium\Policies\DocumentPolicy;
use Archium\Repositories\AttachmentRepository;
use Archium\Repositories\DocumentRepository;
use Archium\Repositories\WorkspaceRepository;
use Archium\Services\UploadService;
use Archium\Support\Auth;
use Archium\Support\Csrf;
use Archium\Support\Database;
use Archium\Support\Logger;
use Throwable;

final class AttachmentController extends BaseController
{
    private function atts(): AttachmentRepository
    {
        return new AttachmentRepository(Database::connection($this->config));
    }

    public function upload(string $id): string
    {
        Csrf::requireValid();
        $ws  = $this->currentWorkspace();
        $doc = (new DocumentRepository(Database::connection($this->config)))->findInWorkspace((int) $id, (int) $ws['id']);
        if (!$doc || !DocumentPolicy::canWrite($ws['my_role'])) {
            http_response_code(403);
            return 'Non puoi caricare allegati su questo documento.';
        }

        $user = Auth::user($this->config);
        try {
            $service = new UploadService($this->config, Database::connection($this->config));
            $service->store($_FILES['attachment'] ?? [], (int) $ws['id'], (int) $doc['id'], (int) $user['id']);
            flash('success', 'Allegato caricato.');
        } catch (Throwable $e) {
            Logger::error('Upload allegato rifiutato', ['err' => $e->getMessage()]);
            flash('error', $e->getMessage());
        }
        redirect('/documents/' . (int) $doc['id'] . '/edit');
    }

    /** Streaming con controllo permessi: mai accesso diretto al filesystem. */
    public function stream(string $id): string
    {
        $this->serve($id, false);
        return '';
    }

    public function download(string $id): string
    {
        $this->serve($id, true);
        return '';
    }

    private function serve(string $id, bool $forceDownload): void
    {
        $user = Auth::user($this->config);
        $att  = $this->atts()->find((int) $id);
        if (!$att) {
            http_response_code(404);
            exit('Allegato non trovato.');
        }

        $role = (new WorkspaceRepository(Database::connection($this->config)))
            ->roleOf((int) $att['workspace_id'], (int) $user['id']);
        if (!DocumentPolicy::canView($role)) {
            http_response_code(403);
            exit('Accesso negato.');
        }

        $uploadDir = realpath(BASE_PATH . '/storage/uploads');
        $path      = realpath(BASE_PATH . '/storage/uploads/' . (string) $att['stored_name']);
        if ($uploadDir === false || $path === false || !str_starts_with($path, $uploadDir)) {
            http_response_code(410);
            exit('File non piu\' disponibile.');
        }

        $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', (string) $att['original_name']);
        $isImage  = UploadService::isInlineImage((string) $att['mime_type']);

        header('X-Content-Type-Options: nosniff');
        header('Content-Length: ' . filesize($path));
        if ($isImage && !$forceDownload) {
            header('Content-Type: ' . $att['mime_type']);
            header('Content-Disposition: inline; filename="' . $safeName . '"');
        } else {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $safeName . '"');
        }
        readfile($path);
        exit;
    }

    public function delete(string $id): string
    {
        Csrf::requireValid();
        $user = Auth::user($this->config);
        $att  = $this->atts()->find((int) $id);
        if (!$att) {
            http_response_code(404);
            return 'Allegato non trovato.';
        }

        $wsRepo = new WorkspaceRepository(Database::connection($this->config));
        $role   = $wsRepo->roleOf((int) $att['workspace_id'], (int) $user['id']);

        $allowed = false;
        $redirect = '/documents';
        if (!empty($att['document_id'])) {
            $doc = (new DocumentRepository(Database::connection($this->config)))
                ->findInWorkspace((int) $att['document_id'], (int) $att['workspace_id']);
            $allowed = $doc !== null && DocumentPolicy::canWrite($role);
            if ($doc) {
                $redirect = '/documents/' . (int) $doc['id'] . '/edit';
            }
        } else {
            // allegato orfano: solo autore o owner del workspace
            $allowed = ($role === 'owner') || ((int) $att['uploaded_by'] === (int) $user['id']);
        }

        if (!$allowed) {
            http_response_code(403);
            return 'Non puoi eliminare questo allegato.';
        }

        $this->atts()->softDelete((int) $att['id']);
        flash('success', 'Allegato eliminato.');
        redirect($redirect);
    }
}