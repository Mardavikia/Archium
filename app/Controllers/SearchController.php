<?php
declare(strict_types=1);

namespace Archium\Controllers;

use Archium\Repositories\SearchRepository;
use Archium\Support\Auth;
use Archium\Support\Database;

final class SearchController extends BaseController
{
    public function index(): string
    {
        $user = Auth::user($this->config);
        $q    = trim((string) ($_GET['q'] ?? ''));

        $results = [];
        if ($q !== '' && mb_strlen($q) >= 2) {
            $rows = (new SearchRepository(Database::connection($this->config)))
                ->search((int) $user['id'], $q);
            foreach ($rows as $row) {
                $row['excerpt'] = $this->excerpt($row['content_markdown'] ?? '', $q);
                $results[] = $row;
            }
        }

        return $this->view('search/index', [
            'pageTitle' => 'Ricerca',
            'q'         => $q,
            'results'   => $results,
            'searched'  => $q !== '',
        ]);
    }

    private function excerpt(string $markdown, string $query): string
    {
        $plain = trim((string) preg_replace('/\s+/', ' ', $markdown));
        if ($plain === '') {
            return '';
        }
        $pos = mb_stripos($plain, $query);
        if ($pos === false) {
            return mb_substr($plain, 0, 160) . (mb_strlen($plain) > 160 ? '…' : '');
        }
        $start = max(0, $pos - 70);
        return ($start > 0 ? '…' : '') . mb_substr($plain, $start, 170) . '…';
    }
}