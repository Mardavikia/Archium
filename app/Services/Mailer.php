<?php
declare(strict_types=1);

namespace Archium\Services;

final class Mailer
{
    public function __construct(private array $config)
    {
    }

    /**
     * Driver:
     *  - 'log'  (default, sviluppo): scrive in storage/logs/mail.log
     *  - 'mail' (produzione Aruba): usa mail() nativa PHP
     */
    public function send(string $to, string $subject, string $body): bool
    {
        $driver = (string) ($this->config['mail']['driver'] ?? 'log');
        $app    = (string) ($this->config['app']['name'] ?? 'Archium');

        if ($driver === 'mail') {
            $host    = parse_url((string) ($this->config['app']['base_url'] ?? ''), PHP_URL_HOST) ?: 'localhost';
            $headers = 'From: no-reply@' . $host . "\r\n"
                     . "Content-Type: text/plain; charset=utf-8\r\n";
            return @mail($to, '[' . $app . '] ' . $subject, $body, $headers);
        }

        $logPath = (string) ($this->config['security']['log_path'] ?? BASE_PATH . '/storage/logs/app.log');
        $line = sprintf(
            "[%s] TO: %s | SUBJ: %s | BODY: %s\n---\n",
            date('c'),
            $to,
            $subject,
            str_replace(["\r", "\n"], ' ', $body)
        );
        return is_string($logPath)
            && @file_put_contents(dirname($logPath) . '/mail.log', $line, FILE_APPEND | LOCK_EX) !== false;
    }
}