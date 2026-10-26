<?php

// Jalankan tiap 2 menit via crontab (SSH):
//   which php
//   crontab -e
// Lalu tempel baris ini (satu baris):
//   */2 * * * * /usr/bin/php /var/www/html/emailBroadcaster/cron/send_emails.php >> /var/www/html/emailBroadcaster/storage/cron.log 2>&1
// Cek: crontab -l

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
