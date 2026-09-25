<?php
declare(strict_types=1);

namespace Archium\Middleware;

use Archium\Support\Auth;

final class RequireAdmin implements Middleware
{
    public function handle(array $config): bool
    {
        if (!Auth::check($config)) {
            flash('error', 'Devi effettuare il login per continuare.');
            redirect('/login');
        }
        if (Auth::role($config) !== 'admin') {
            http_response_code(403);
            echo 'Accesso riservato agli amministratori.';
            return false;
        }
        return true;
    }
}