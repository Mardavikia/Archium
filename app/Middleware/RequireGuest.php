<?php
declare(strict_types=1);

namespace Archium\Middleware;

use Archium\Support\Auth;

final class RequireGuest implements Middleware
{
    public function handle(array $config): bool
    {
        if (Auth::check($config)) {
            redirect('/dashboard');
        }
        return true;
    }
}