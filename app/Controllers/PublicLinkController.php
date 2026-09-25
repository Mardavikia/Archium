<?php
declare(strict_types=1);

namespace Archium\Controllers;

use Archium\Repositories\CollectionRepository;
use Archium\Repositories\DocumentRepository;
use Archium\Repositories\PublicLinkRepository;
use Archium\Repositories\WorkspaceRepository;
use Archium\Services\MarkdownService;
use Archium\Services\PermissionResolver;
use Archium\Support\Auth;
use Archium\Support\Csrf;
use Archium\Support\Database;

final class PublicLinkController extends BaseController
{
    private function resolver(): PermissionResolver
    {
        $pdo = Database::connection($this->config);
        return new PermissionResolver(new WorkspaceRepository($pdo), new CollectionRepository($pdo), new DocumentRepository($pdo));
    }

    private function loadDocument(string $id): array
    {
        $ws = $this->currentWorkspace();
        $user = Auth::user($this->config);
        $pdo = Database::connection($this->config);
        $doc = (new DocumentRepository($pdo))->findInWorkspace((int) $id, (int) $ws['id']);
        if ($doc === null) { http_response_code(404); exit('Documento non trovato.'); }
        if (!$this->resolver()->canDocument((int) $doc['id'], (int) $ws['id'], $doc['collection_id'] !== null ? (int) $doc['collection_id'] : null, (int) $user['id'], 'admin')) {
            http_response_code(403); exit('Non hai permessi amministrativi per gestire i link pubblici.');
        }
        return [$ws, $doc];
    }

    public function index(string $id): string
    {
        [$ws, $doc] = $this->loadDocument($id);
        return $this->view('public_links/index', [
            'pageTitle' => 'Link pubblici — ' . $doc['title'], 'ws' => $ws, 'document' => $doc,
            'links' => (new PublicLinkRepository(Database::connection($this->config)))->forDocument((int) $doc['id']),
            'newPublicUrl' => $_SESSION['_new_public_url'] ?? null,
        ]);
    }

    public function create(string $id): string
    {
        Csrf::requireValid();
        [$ws, $doc] = $this->loadDocument($id);
        $input = trim((string) ($_POST['expires_at'] ?? ''));
        $expiresAt = null;
        if ($input !== '') {
            $date = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $input);
            if ($date === false || $date <= new \DateTimeImmutable('now')) {
                flash('error', 'La scadenza deve essere una data futura valida.');
                redirect('/documents/' . (int) $doc['id'] . '/shares');
            }
            $expiresAt = $date->format('Y-m-d H:i:s');
        }
        $user = Auth::user($this->config);
        $token = (new PublicLinkRepository(Database::connection($this->config)))->create((int) $doc['id'], $expiresAt, (int) $user['id']);
        $_SESSION['_new_public_url'] = rtrim((string) $this->config['app']['base_url'], '/') . '/public/' . $token;
        flash('success', 'Link pubblico creato. Copialo ora: il token non viene salvato in chiaro.');
        redirect('/documents/' . (int) $doc['id'] . '/shares');
    }

    public function revoke(string $id, string $linkId): string
    {
        Csrf::requireValid();
        [, $doc] = $this->loadDocument($id);
        $repo = new PublicLinkRepository(Database::connection($this->config));
        if ($repo->findForDocument((int) $doc['id'], (int) $linkId) === null) { http_response_code(404); return 'Link non trovato.'; }
        $repo->revoke((int) $linkId);
        flash('success', 'Link pubblico revocato.');
        redirect('/documents/' . (int) $doc['id'] . '/shares');
    }

    /** Pagina anonima: espone SOLO documento renderizzato; non allegati, membri, tag o metadata privati. */
    public function showPublic(string $token): string
    {
        $link = (new PublicLinkRepository(Database::connection($this->config)))->findActiveByToken($token);
        if ($link === null) { http_response_code(404); return 'Pagina non trovata.'; }
        $html = $link['content_html'] ?? (new MarkdownService())->toHtml((string) ($link['content_markdown'] ?? ''));
        $document = $link;
        $pageTitle = (string) $link['title'];
        $config = $this->config;
        ob_start();
        require BASE_PATH . '/app/Views/public/document.php';
        return (string) ob_get_clean();
    }
}