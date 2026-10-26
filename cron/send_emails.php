<?php

/**
 * Jalankan tiap 2 menit via Windows Task Scheduler / crontab.
 *
 * Windows (Task Scheduler):
 *   Program: php.exe
 *   Arguments: "D:\PROJECT\ICT\emailBroadcaster\cron\send_emails.php"
 *   Trigger: setiap 2 menit
 *
 * Linux crontab:
 *   */2 * * * * php /path/to/emailBroadcaster/cron/send_emails.php >> /path/to/storage/cron.log 2>&1
 */

declare(strict_types=1);

$app = require dirname(__DIR__) . '/app/bootstrap.php';

$repo = new MessageRepository($app['pdo']);
$service = new EmailService($repo);
$result = $service->processPending();

$ts = date('Y-m-d H:i:s');
if (!empty($result['skipped'])) {
    echo "[{$ts}] skip: {$result['reason']}\n";
    exit(0);
}

if (($result['processed'] ?? 0) > 0) {
    echo "[{$ts}] processed={$result['processed']} sent={$result['sent']} failed={$result['failed']}\n";
} else {
    echo "[{$ts}] idle\n";
}
