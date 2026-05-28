<?php

define('DB_PATH', __DIR__ . '/database.sqlite');

define('DB_DSN', 'sqlite:' . DB_PATH);

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

if (!file_exists(DB_PATH)) {
    file_put_contents(DB_PATH, '');
}

$pdo = new PDO(DB_DSN, null, null, $options);
$pdo->exec('PRAGMA foreign_keys = ON');

$schema = file_get_contents(__DIR__ . '/schema.sql');
$pdo->exec($schema);
