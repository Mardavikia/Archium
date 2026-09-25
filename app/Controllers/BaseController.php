<?php
declare(strict_types=1);

namespace Archium\Controllers;

use Archium\Repositories\WorkspaceRepository;
use Archium\Support\Auth;
use Archium\Support\Database;

abstract class BaseController
{
    public function __construct(protected array $config)
    {
    }

    protected function view(string $template, array $data = []): string
    {
        $config      = $this->config;
        $currentUser = $data['currentUser'] ?? Auth::user($config);
        extract($data, EXTR_SKIP);

        ob_start();
        require BASE_PATH . '/app/Views/' . $template . '.php';
        $content = (string) ob_get_clean();

        ob_start();
        require BASE_PATH . '/app/Views/layout.php';
        return (string) ob_get_clean();
    }

    /**
     * Workspace attivo dell'utente: quello in sessione se valido,
     * altrimenti il primo disponibile (creato automaticamente se assente).
     * Restituisce la riga del workspace con chiave 'my_role'.
     */
    protected function currentWorkspace(): array
    {
        $user = Auth::user($this->config);
        $repo = new WorkspaceRepository(Database::connection($this->config));

        $wsId = $_SESSION['workspace_id'] ?? null;
        if (is_scalar($wsId) && (int) $wsId > 0) {
            $ws   = $repo->findActive((int) $wsId);
            $role = $ws ? $repo->roleOf((int) $ws['id'], (int) $user['id']) : null;
            if ($ws && $role !== null) {
                $ws['my_role'] = $role;
                return $ws;
            }
            unset($_SESSION['workspace_id']);
        }

        $ws = $repo->ensureDefaultFor((int) $user['id'], (string) $user['name']);
        $_SESSION['workspace_id'] = (int) $ws['id'];
        return $ws;
    }
}