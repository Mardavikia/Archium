<?php
declare(strict_types=1);

namespace Archium\Controllers;

use Archium\Support\Database;
use Throwable;

final class HomeController extends BaseController
{
    public function index(): string
    {
        $dbOk = false;
        try {
            $pdo  = Database::connection($this->config);
            $dbOk = (bool) $pdo->query('SELECT 1');
        } catch (Throwable) {
            $dbOk = false;
        }

        return $this->view('home', [
            'pageTitle' => 'Stato installazione',
            'appName'   => $this->config['app']['name'],
            'env'       => $this->config['app']['env'],
            'dbOk'      => $dbOk,
            'phpVer'    => PHP_VERSION,
        ]);
    }
}