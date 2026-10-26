<?php

class HealthController
{
    public function show(): void
    {
        json_response([
            'ok' => true,
            'smtpConfigured' => Config::smtpConfigured(),
            'cron' => '*/2 * * * * (via cron/send_emails.php)',
            'batchSize' => (int) Config::get('EMAIL_BATCH_SIZE', 20),
        ]);
    }
}
