<?php
declare(strict_types=1);

namespace Archium\Middleware;

interface Middleware
{
    /**
     * Restituisce true per proseguire verso l'handler.
     * In caso contrario emette direttamente la risposta (redirect/403) e restituisce false.
     */
    public function handle(array $config): bool;
}