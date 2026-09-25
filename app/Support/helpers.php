<?php
declare(strict_types=1);

if (!function_exists('e')) {
    /** Escaping output anti-XSS. Usare SEMPRE per stampare dati nelle view. */
    function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('redirect')) {
    /** Redirect HTTP verso un path interno. Termina la richiesta. */
    function redirect(string $path): never
    {
        header('Location: ' . $path, true, 302);
        exit;
    }
}

if (!function_exists('flash')) {
    /** Messaggio one-shot mostrato al prossimo render del layout. */
    function flash(string $type, string $message): void
    {
        $_SESSION['_flash'] = ['type' => $type, 'message' => $message];
    }
}

if (!function_exists('pull_flash')) {
    /** @return array{type:string,message:string}|null */
    function pull_flash(): ?array
    {
        $f = $_SESSION['_flash'] ?? null;
        unset($_SESSION['_flash']);
        return is_array($f) ? $f : null;
    }
}