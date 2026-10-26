<?php

require_once __DIR__ . '/Templates/billing.php';
require_once __DIR__ . '/Templates/payment_confirmation.php';

class EmailService
{
    public function __construct(private MessageRepository $repo)
    {
    }

    public function processPending(?int $limit = null): array
    {
        if (!Config::smtpConfigured()) {
            return [
                'skipped' => true,
                'reason' => 'SMTP not configured',
                'processed' => 0,
                'sent' => 0,
                'failed' => 0,
            ];
        }

        $limit = $limit ?? (int) Config::get('EMAIL_BATCH_SIZE', 20);
        $pending = $this->repo->findPending($limit);
        $sent = 0;
        $failed = 0;

        foreach ($pending as $row) {
            $this->repo->markSending($row['id']);
            $message = message_to_array($row);

            try {
                $rendered = $this->render($message);
                Mailer::send(
                    $message['recipientEmail'],
                    $message['subject'],
                    $rendered['html'],
                    $rendered['text']
                );
                $this->repo->markSent($row['id']);
                $sent++;
            } catch (Throwable $e) {
                $this->repo->markFailed($row['id'], $e->getMessage());
                $failed++;
            }
        }

        return [
            'skipped' => false,
            'processed' => count($pending),
            'sent' => $sent,
            'failed' => $failed,
        ];
    }

    private function render(array $message): array
    {
        if (($message['type'] ?? '') === 'PAYMENT_CONFIRMATION') {
            return render_payment_confirmation_email($message);
        }
        return render_billing_email($message);
    }
}
