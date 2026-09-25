<?php
declare(strict_types=1);

return [
    'app' => [
        'name'     => 'Archium',
        'env'      => 'local', // impostare 'production' sul deploy Aruba
        'base_url' => 'http://127.0.0.1:8080',
        'timezone' => 'Europe/Rome',
        'migrate_token' => '13c1afc000b2f1d8078c09f2f6f6aa779cd747d008fd235b68fb7caad2b7cd18',
    ],
    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'archium',
        'user'    => 'root',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],
    'security' => [
        'session_name' => 'archium_sid',
        'bcrypt_cost'  => 12,
        'log_path'     => __DIR__ . '/../storage/logs/app.log',
    ],
];
