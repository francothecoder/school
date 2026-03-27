<?php
declare(strict_types=1);

$config = [
    'app' => [
        'name' => 'Moomba Academic Management System',
        'base_url' => 'http://localhost/school/',
        'timezone' => 'Africa/Lusaka',
    ],
    'db' => [
        'host' => '127.0.0.1',
        'port' => '3306',
        'dbname' => 'mileston_moombasec',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ],
];

date_default_timezone_set($config['app']['timezone']);
