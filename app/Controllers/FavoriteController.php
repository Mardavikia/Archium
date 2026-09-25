<?php
declare(strict_types=1);

namespace Archium\Controllers;

use Archium\Repositories\DocumentRepository;
use Archium\Repositories\FavoriteRepository;
use Archium\Support\Auth;
use Archium\Support\Csrf;
use Archium\Support\Database;

final class FavoriteController extends BaseController
{
    public function index(): string
    {
        $user = Auth::user($this->config);
        return $this->view('favorites/index', [
            'pageTitle' => 'Preferiti',
            'favorites' => (new FavoriteRepository(Database::connection($this->config)))->forUser((int) $user['id']),
        ]);
    }

    public function toggle(string $id): string
    {
        Csrf::requireValid();
        $ws  = $this->currentWorkspace();
        $doc = (new DocumentRepository(Database::connection($this->config)))
            ->findInWorkspace((int) $id, (int) $ws['id']);
        if (!$doc) {
            http_response_code(404);
            return 'Documento non trovato.';
        }

        $user  = Auth::user($this->config);
        $added = (new FavoriteRepository(Database::connection($this->config)))
            ->toggle((int) $user['id'], (int) $doc['id']);

        flash('success', $added ? 'Aggiunto ai preferiti.' : 'Rimosso dai preferiti.');
        redirect('/documents/' . (int) $doc['id']);
    }
}