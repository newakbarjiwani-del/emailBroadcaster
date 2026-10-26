<?php

/**
 * Minimal SMTP client (AUTH LOGIN + STARTTLS/SSL) — tanpa Composer.
 */
class Mailer
{
    public static function send(string $to, string $subject, string $html, string $text = ''): void
    {
        if (!Config::smtpConfigured()) {
            throw new RuntimeException('SMTP belum dikonfigurasi. Isi SMTP_* dan MAIL_FROM_* di file .env');
        }

        $host = (string) Config::get('SMTP_HOST');
        $port = (int) Config::get('SMTP_PORT', 587);
        $secure = strtolower((string) Config::get('SMTP_SECURE', 'tls'));
        $user = (string) Config::get('SMTP_USER');
        $pass = (string) Config::get('SMTP_PASS');
        $fromEmail = (string) Config::get('MAIL_FROM_EMAIL', $user);
        $fromName = (string) Config::get('MAIL_FROM_NAME', 'Email Broadcaster');

        $remote = ($secure === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
        $errno = 0;
        $errstr = '';
        $fp = @stream_socket_client($remote, $errno, $errstr, 30, STREAM_CLIENT_CONNECT);
        if (!$fp) {
            throw new RuntimeException("Gagal konek SMTP: {$errstr} ({$errno})");
        }
        stream_set_timeout($fp, 30);

        try {
            self::expect($fp, [220]);
            self::command($fp, 'EHLO localhost', [250]);

            if ($secure === 'tls') {
                self::command($fp, 'STARTTLS', [220]);
                if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException('STARTTLS gagal');
                }
                self::command($fp, 'EHLO localhost', [250]);
            }

            self::command($fp, 'AUTH LOGIN', [334]);
            self::command($fp, base64_encode($user), [334]);
            self::command($fp, base64_encode($pass), [235]);

            self::command($fp, 'MAIL FROM:<' . $fromEmail . '>', [250]);
            self::command($fp, 'RCPT TO:<' . $to . '>', [250, 251]);
            self::command($fp, 'DATA', [354]);

            $boundary = 'b_' . bin2hex(random_bytes(8));
            $headers = [];
            $headers[] = 'Date: ' . date('r');
            $headers[] = 'From: ' . self::encodeAddress($fromName, $fromEmail);
            $headers[] = 'To: <' . $to . '>';
            $headers[] = 'Subject: ' . self::encodeHeader($subject);
            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';
            $headers[] = 'X-Mailer: EmailBroadcaster-PHP';

            if ($text === '') {
                $text = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], ["\n", "\n", "\n", "\n"], $html));
            }

            $body = '';
            $body .= '--' . $boundary . "\r\n";
            $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $body .= chunk_split(base64_encode($text)) . "\r\n";
            $body .= '--' . $boundary . "\r\n";
            $body .= "Content-Type: text/html; charset=UTF-8\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $body .= chunk_split(base64_encode($html)) . "\r\n";
            $body .= '--' . $boundary . "--\r\n";

            $data = implode("\r\n", $headers) . "\r\n\r\n" . $body;
            $data = preg_replace("/\r\n\./", "\r\n..", $data);
            fwrite($fp, $data . "\r\n.\r\n");
            self::expect($fp, [250]);
            self::command($fp, 'QUIT', [221]);
        } finally {
            fclose($fp);
        }
    }

    private static function encodeAddress(string $name, string $email): string
    {
        return self::encodeHeader($name) . ' <' . $email . '>';
    }

    private static function encodeHeader(string $value): string
    {
        if (preg_match('/[^\x20-\x7E]/', $value)) {
            return '=?UTF-8?B?' . base64_encode($value) . '?=';
        }
        return $value;
    }

    private static function command($fp, string $cmd, array $okCodes): void
    {
        fwrite($fp, $cmd . "\r\n");
        self::expect($fp, $okCodes);
    }

    private static function expect($fp, array $okCodes): string
    {
        $response = '';
        while (($line = fgets($fp, 515)) !== false) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        $code = (int) substr($response, 0, 3);
        if (!in_array($code, $okCodes, true)) {
            throw new RuntimeException('SMTP error: ' . trim($response));
        }
        return $response;
    }
}
