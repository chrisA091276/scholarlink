<?php

// Creates only a dedicated test database. Never migrate or truncate the runtime DB here.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (! $app->environment('local')) throw new RuntimeException('Run this helper only in local development.');
$config = config('database.connections.mysql');
$database = 'scholarlink_qa_20261027_test';
if ($config['database'] === $database) throw new RuntimeException('The application runtime must not point at the test database.');
$pdo = new PDO('mysql:host='.$config['host'].';port='.$config['port'].';charset=utf8mb4', $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->exec('CREATE DATABASE IF NOT EXISTS `'.$database.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
echo 'Dedicated test database ready: '.$database.PHP_EOL;
