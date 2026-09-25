<?php
declare(strict_types=1);

namespace Archium\Services;

use PDO;
use RuntimeException;

final class UploadService
{
    /** Whitelist estensioni => MIME atteso (verificato con finfo sul contenuto reale). */
    private const ALLOWED = [
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'webp' => 'image/webp',
        'pdf'  => 'application/pdf',
        'txt'  => 'text/plain',
        'md'   => 'text/plain',
        'zip'  => 'application/zip',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    private const IMAGES_INLINE = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];

    public function __construct(private array $config, private PDO $pdo)
    {
    }

    /**
     * Salva un file caricato in storage/uploads con nome random e registra la riga.
     * @param array $file array $_FILES['attachment']
     * @throws RuntimeException con messaggio mostrabile all'utente
     */
    public function store(array $file, int $workspaceId, ?int $documentId, int $userId): int
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload non riuscito (codice ' . (int) ($file['error'] ?? -1) . ').');
        }

        $maxBytes = (int) ($this->config['uploads']['max_bytes'] ?? 20 * 1024 * 1024);
        $size     = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > $maxBytes) {
            throw new RuntimeException('File non valido o supera il limite di ' . (int) ($maxBytes / 1048576) . ' MB.');
        }

        $original = basename((string) ($file['name'] ?? 'file'));
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        if (!isset(self::ALLOWED[$ext])) {
            throw new RuntimeException('Estensione non consentita.');
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new RuntimeException('File temporaneo non valido.');
        }

        // MIME reale dal contenuto, non dall'intestazione del client
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = (string) $finfo->file($tmp);

        $expected = self::ALLOWED[$ext];
        $mimeOk = ($mime === $expected)
            // tolleranze note: i file Office sono zip; txt/md possono essere rilevati diversamente
            || ($ext === 'docx' && in_array($mime, ['application/zip', 'application/octet-stream'], true))
            || ($ext === 'xlsx' && in_array($mime, ['application/zip', 'application/octet-stream'], true))
            || (in_array($ext, ['txt', 'md'], true) && str_starts_with($mime, 'text/'));
        if (!$mimeOk) {
            throw new RuntimeException('Il contenuto del file non corrisponde all\'estensione dichiarata.');
        }

        $storedName = bin2hex(random_bytes(16)) . '.' . $ext;
        $target     = BASE_PATH . '/storage/uploads/' . $storedName;
        if (!move_uploaded_file($tmp, $target)) {
            throw new RuntimeException('Impossibile salvare il file sul server.');
        }
        @chmod($target, 0644);

        $stmt = $this->pdo->prepare(
            'INSERT INTO attachments (workspace_id, document_id, uploaded_by, original_name, stored_name, mime_type, size_bytes)
             VALUES (:w, :d, :u, :o, :s, :m, :z)'
        );
        $stmt->execute([
            'w' => $workspaceId,
            'd' => $documentId,
            'u' => $userId,
            'o' => mb_substr($original, 0, 255),
            's' => $storedName,
            'm' => $mime === 'application/zip' && in_array($ext, ['docx', 'xlsx'], true) ? $expected : $mime,
            'z' => $size,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public static function isInlineImage(string $mime): bool
    {
        return in_array($mime, self::IMAGES_INLINE, true);
    }
}