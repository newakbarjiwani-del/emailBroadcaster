<?php

class HealthController
{
    public function show(): void
    {
        $driver = strtolower((string) Config::get('DB_DRIVER', 'sqlite'));

        json_response([
            'ok' => true,
            'smtpConfigured' => Config::smtpConfigured(),
            'dbDriver' => $driver,
            'dbHost' => $driver === 'mysql' ? (string) Config::get('DB_HOST', '') : null,
            'dbName' => $driver === 'mysql' ? (string) Config::get('DB_NAME', '') : null,
            'cron' => '*/2 * * * * (via cron/send_emails.php)',
            'batchSize' => (int) Config::get('EMAIL_BATCH_SIZE', 20),
        ]);
    }
}
