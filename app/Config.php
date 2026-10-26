<?php

class Config
{
    private static array $env = [];
    private static bool $loaded = false;

    public static function load(string $rootPath): void
    {
        if (self::$loaded) {
            return;
        }

        $envFile = $rootPath . DIRECTORY_SEPARATOR . '.env';
        if (is_file($envFile)) {
            self::$env = self::parseEnvFile($envFile);
        }

        $tz = self::get('APP_TIMEZONE', 'Asia/Jakarta');
        date_default_timezone_set($tz);
        self::$loaded = true;
    }

    private static function parseEnvFile(string $path): array
    {
        $result = [];
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return $result;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }
            $key = trim(substr($line, 0, $pos));
            $value = trim(substr($line, $pos + 1));
            if (
                (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }
            $value = str_replace(['\\n', '\\r'], ["\n", "\r"], $value);
            $result[$key] = $value;
        }

        return $result;
    }

    public static function get(string $key, $default = null)
    {
        if (array_key_exists($key, self::$env) && self::$env[$key] !== '') {
            return self::$env[$key];
        }
        $fromServer = getenv($key);
        if ($fromServer !== false && $fromServer !== '') {
            return $fromServer;
        }
        return $default;
    }

    public static function smtpConfigured(): bool
    {
        return self::get('SMTP_HOST')
            && self::get('SMTP_USER')
            && self::get('SMTP_PASS')
            && self::get('MAIL_FROM_EMAIL', self::get('SMTP_USER'));
    }

    public static function defaultPaymentGuide(): string
    {
        return (string) self::get(
            'DEFAULT_PAYMENT_GUIDE',
            "PANDUAN PEMBAYARAN:\n1. Transfer ke Virtual Account (VA) yang tertera\n2. Atau klik Link Pembayaran pada email\n3. Simpan bukti pembayaran"
        );
    }
}
