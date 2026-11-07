<?php

// Creates a private SQL snapshot. Does not change the running database.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

try {
    $connection = DB::connection();
    $config = $connection->getConfig();
    if (! in_array($config['driver'], ['mysql', 'mariadb'], true)) {
        throw new RuntimeException('The configured database is not MySQL/MariaDB.');
    }
    $connection->getPdo();
    $binary = getenv('MYSQLDUMP_PATH') ?: (new ExecutableFinder)->find('mysqldump');
    if (! $binary && is_file('C:/xampp/mysql/bin/mysqldump.exe')) {
        $binary = 'C:/xampp/mysql/bin/mysqldump.exe';
    }
    if (! $binary) throw new RuntimeException('Set MYSQLDUMP_PATH to your mysqldump executable.');
    $directory = storage_path('app/private/backups/mysql');
    if (! is_dir($directory)) mkdir($directory, 0700, true);
    $name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $config['database']);
    $target = $directory.'/'.$name.'-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.sql';
    $temporary = $target.'.partial';
    $arguments = [$binary, '--single-transaction', '--quick', '--hex-blob', '--routines', '--events',
        '--triggers', '--default-character-set=utf8mb4', '--host='.$config['host'],
        '--port='.($config['port'] ?? 3306), '--user='.$config['username'], '--result-file='.$temporary];
    if (! empty($config['unix_socket'])) $arguments[] = '--socket='.$config['unix_socket'];
    $arguments[] = $config['database'];
    $process = new Process($arguments, base_path(), ['MYSQL_PWD' => $config['password'] ?? '']);
    $process->setTimeout(180);
    $process->run();
    if (! $process->isSuccessful() || ! is_file($temporary) || filesize($temporary) === 0) {
        if (is_file($temporary)) unlink($temporary);
        throw new RuntimeException('mysqldump failed; no completed backup was saved. Check database availability and export permissions.');
    }
    rename($temporary, $target);
    $documents = $connection->table('application_documents')->select('id', 'storage_disk', 'file_path')->get();
    $missingDocuments = $documents->filter(fn ($document) => $document->storage_disk === 'local'
        && ! is_file(storage_path('app/private/'.$document->file_path)))->pluck('id')->values();
    $avatars = $connection->table('users')->whereNotNull('avatar_path')->select('id', 'avatar_path')->get();
    $missingAvatars = $avatars->filter(fn ($user) => ! is_file(storage_path('app/public/'.$user->avatar_path)))->pluck('id')->values();
    $report = [
        'backup' => str_replace(base_path().DIRECTORY_SEPARATOR, '', $target),
        'bytes' => filesize($target), 'sha256' => hash_file('sha256', $target),
        'document_storage_counts' => $documents->countBy('storage_disk'),
        'missing_local_document_ids' => $missingDocuments,
        'avatar_count' => $avatars->count(), 'missing_avatar_user_ids' => $missingAvatars,
    ];
    file_put_contents($target.'.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
} catch (PDOException $exception) {
    fwrite(STDERR, 'Database unavailable. Start the configured MySQL service and run php tools/backup-local-database.php again.'.PHP_EOL);
    exit(1);
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage().PHP_EOL);
    exit(1);
}
