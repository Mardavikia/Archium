<?php
declare(strict_types=1);

namespace Archium\Support;

/** Rate limiter file-based (nessuna tabella aggiuntiva): adatto a hosting condiviso. */
final class RateLimiter
{
    public function __construct(private string $dir)
    {
        if (!is_dir($this->dir)) {
            @mkdir($this->dir, 0775, true);
        }
    }

    public function tooManyAttempts(string $key, int $maxAttempts, int $windowSeconds): bool
    {
        $data = $this->read($key);
        if ($data === null) {
            return false;
        }
        if ($data['reset'] < time()) {
            $this->clear($key);
            return false;
        }
        return $data['count'] >= $maxAttempts;
    }

    public function hit(string $key, int $windowSeconds): void
    {
        $data = $this->read($key);
        if ($data === null || $data['reset'] < time()) {
            $data = ['count' => 0, 'reset' => time() + $windowSeconds];
        }
        $data['count']++;
        @file_put_contents($this->file($key), (string) json_encode($data), LOCK_EX);
    }

    public function clear(string $key): void
    {
        $f = $this->file($key);
        if (is_file($f)) {
            @unlink($f);
        }
    }

    private function file(string $key): string
    {
        return $this->dir . '/rl_' . hash('sha256', $key) . '.json';
    }

    private function read(string $key): ?array
    {
        $f = $this->file($key);
        if (!is_file($f)) {
            return null;
        }
        $data = json_decode((string) file_get_contents($f), true);
        return (is_array($data) && isset($data['count'], $data['reset'])) ? $data : null;
    }
}