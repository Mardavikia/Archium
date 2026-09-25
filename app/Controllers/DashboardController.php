<?php
declare(strict_types=1);

namespace Archium\Controllers;

use Archium\Repositories\CollectionRepository;
use Archium\Repositories\DocumentRepository;
use Archium\Support\Database;

final class DashboardController extends BaseController
{
    public function index(): string
    {
        $ws   = $this->currentWorkspace();
        $docs = new DocumentRepository(Database::connection($this->config));
        $cols = new CollectionRepository(Database::connection($this->config));

        return $this->view('dashboard', [
            'pageTitle'  => 'Dashboard',
            'ws'         => $ws,
            'totalDocs'  => $docs->countForWorkspace((int) $ws['id']),
            'totalCols'  => count($cols->forWorkspace((int) $ws['id'])),
        ]);
    }
}