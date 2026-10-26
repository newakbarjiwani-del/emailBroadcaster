<?php

class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(string $rootPath): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $driver = strtolower((string) Config::get('DB_DRIVER', 'sqlite'));

        if ($driver === 'mysql') {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                Config::get('DB_HOST', '127.0.0.1'),
                Config::get('DB_PORT', '3306'),
                Config::get('DB_NAME', 'email_broadcaster')
            );
            self::$pdo = new PDO($dsn, Config::get('DB_USER', 'root'), Config::get('DB_PASS', ''), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            self::ensureMysqlSchema(self::$pdo, $rootPath);
            return self::$pdo;
        }

        $relative = Config::get('DB_PATH', 'storage/database.sqlite');
        $dbPath = $relative;
        if (!preg_match('/^[A-Za-z]:[\\\\\\/]/', $relative) && !str_starts_with($relative, '/')) {
            $dbPath = $rootPath . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative);
        }

        $dir = dirname($dbPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $isNew = !is_file($dbPath);
        self::$pdo = new PDO('sqlite:' . $dbPath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        self::$pdo->exec('PRAGMA foreign_keys = ON');

        if ($isNew) {
            self::runSqlFile(self::$pdo, $rootPath . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'schema.sql');
        } else {
            self::ensureSqliteSchema(self::$pdo, $rootPath);
        }

        return self::$pdo;
    }

    private static function ensureSqliteSchema(PDO $pdo, string $rootPath): void
    {
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='email_messages'");
        $exists = $stmt->fetchColumn();
        if (!$exists) {
            self::runSqlFile($pdo, $rootPath . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'schema.sql');
        }
    }

    private static function ensureMysqlSchema(PDO $pdo, string $rootPath): void
    {
        $stmt = $pdo->query("SHOW TABLES LIKE 'email_messages'");
        $exists = $stmt->fetchColumn();
        if (!$exists) {
            self::runSqlFile($pdo, $rootPath . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'schema.mysql.sql');
        }
    }

    private static function runSqlFile(PDO $pdo, string $path): void
    {
        if (!is_file($path)) {
            throw new RuntimeException('Schema file tidak ditemukan: ' . $path);
        }
        $sql = file_get_contents($path);
        $pdo->exec($sql);
    }
}
