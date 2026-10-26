<?php

declare(strict_types=1);

$app = require dirname(__DIR__) . '/app/bootstrap.php';

/** @var PDO $pdo */
$pdo = $app['pdo'];

$repo = new MessageRepository($pdo);
$emailService = new EmailService($repo);
$billing = new BillingController($repo);
$payment = new PaymentController($repo);
$messages = new MessageController($repo, $emailService);
$health = new HealthController();

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$uri = rawurldecode($uri);

// Strip base path only when SCRIPT_NAME is an actual PHP entry file
// (avoids broken strip on `php -S` router where SCRIPT_NAME == request path)
$scriptFile = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
if (str_ends_with(strtolower($scriptFile), '.php')) {
    $base = rtrim(str_replace('\\', '/', dirname($scriptFile)), '/');
    if ($base !== '' && $base !== '/' && str_starts_with($uri, $base . '/')) {
        $uri = substr($uri, strlen($base)) ?: '/';
    } elseif ($base !== '' && $base !== '/' && $uri === $base) {
        $uri = '/';
    }
}

if ($uri !== '/' && str_ends_with($uri, '/')) {
    $uri = rtrim($uri, '/') ?: '/';
}

try {
    if ($method === 'GET' && ($uri === '/' || $uri === '/index.php')) {
        header('Location: /dashboard.html');
        exit;
    }

    if ($method === 'GET' && $uri === '/health') {
        $health->show();
    }

    if ($method === 'POST' && $uri === '/api/billing') {
        $billing->store();
    }

    if ($method === 'POST' && $uri === '/api/payment-confirmation') {
        $payment->store();
    }

    if ($method === 'GET' && $uri === '/api/messages') {
        $messages->index();
    }

    if ($method === 'GET' && preg_match('#^/api/messages/([^/]+)$#', $uri, $m)) {
        $messages->show($m[1]);
    }

    if ($method === 'POST' && preg_match('#^/api/messages/([^/]+)/retry$#', $uri, $m)) {
        $messages->retry($m[1]);
    }

    if ($method === 'POST' && $uri === '/api/process-now') {
        $messages->processNow();
    }

    json_response(['ok' => false, 'error' => 'Not found', 'path' => $uri], 404);
} catch (Throwable $e) {
    error_log($e->getMessage());
    json_response(['ok' => false, 'error' => $e->getMessage() ?: 'Internal error'], 500);
}
