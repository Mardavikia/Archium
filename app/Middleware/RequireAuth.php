<?php
declare(strict_types=1);

namespace Archium\Middleware;

use Archium\Support\Auth;

final class RequireAuth implements Middleware
{
    public function handle(array $config): bool
    {
        if (!Auth::check($config)) {
            flash('error', 'Devi effettuare il login per continuare.');
            redirect('/login');
        }
        return true;
    }
}