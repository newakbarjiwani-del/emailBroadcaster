<?php

/**
 * Router untuk built-in PHP server:
 *   php -S 127.0.0.1:8080 router.php
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$publicDir = __DIR__ . DIRECTORY_SEPARATOR . 'public';
$file = $publicDir . str_replace('/', DIRECTORY_SEPARATOR, $uri);

if ($uri !== '/' && is_file($file)) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $types = [
        'css' => 'text/css; charset=utf-8',
        'js' => 'application/javascript; charset=utf-8',
        'html' => 'text/html; charset=utf-8',
        'htm' => 'text/html; charset=utf-8',
        'json' => 'application/json; charset=utf-8',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'txt' => 'text/plain; charset=utf-8',
        'map' => 'application/json; charset=utf-8',
    ];
    if (isset($types[$ext])) {
        header('Content-Type: ' . $types[$ext]);
    }
    readfile($file);
    return true;
}

require $publicDir . DIRECTORY_SEPARATOR . 'index.php';
