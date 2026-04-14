<?php
/**
 * PublicPulse AI — Database Configuration & Connection
 * Version 2.0 — Production Ready
 */

// ── Database ────────────────────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_NAME',    'publicpulse_ai');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

// ── Application ─────────────────────────────────────────────────────
define('APP_NAME',    'PublicPulse AI');
define('APP_VERSION', '2.0.0');
define('APP_URL',     'http://localhost/publicpulse');
define('APP_ROOT',    dirname(__DIR__));

// ── File Upload ─────────────────────────────────────────────────────
define('UPLOAD_DIR',    APP_ROOT . '/uploads/complaints/');
define('UPLOAD_URL',    APP_URL  . '/uploads/complaints/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024);
define('ALLOWED_TYPES', ['image/jpeg','image/png','image/gif','image/webp']);

// ── Security ────────────────────────────────────────────────────────
define('SESSION_LIFETIME', 7200);
define('BCRYPT_COST',      12);

// ── AI Thresholds ────────────────────────────────────────────────────
define('AI_HOTSPOT_THRESHOLD', 3);
define('AI_TREND_THRESHOLD',   0.25);
define('AI_PREDICTION_CONF',   0.70);

/**
 * Database — PDO singleton with full error handling
 */
class Database
{
    private static ?PDO $instance = null;

    private function __construct() {}
    private function __clone()    {}

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
            $opts = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci, time_zone='+00:00'",
            ];
            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $opts);
            } catch (PDOException $e) {
                error_log('[PublicPulse] DB Error: ' . $e->getMessage());
                http_response_code(503);
                die('Service temporarily unavailable. Please try again later.');
            }
        }
        return self::$instance;
    }

    public static function fetchAll(string $sql, array $p = []): array
    {
        $s = self::getInstance()->prepare($sql);
        $s->execute($p);
        return $s->fetchAll();
    }

    public static function fetchOne(string $sql, array $p = []): ?array
    {
        $s = self::getInstance()->prepare($sql);
        $s->execute($p);
        $r = $s->fetch();
        return $r ?: null;
    }

    public static function execute(string $sql, array $p = []): int
    {
        $s = self::getInstance()->prepare($sql);
        $s->execute($p);
        $id = (int)self::getInstance()->lastInsertId();
        return $id ?: $s->rowCount();
    }

    public static function fetchScalar(string $sql, array $p = []): mixed
    {
        $s = self::getInstance()->prepare($sql);
        $s->execute($p);
        return $s->fetchColumn();
    }

    public static function beginTransaction(): void  { self::getInstance()->beginTransaction(); }
    public static function commit(): void            { self::getInstance()->commit(); }
    public static function rollback(): void          { self::getInstance()->rollBack(); }
}
