<?php
/**
 * config/db.php
 * Database connection using PDO (PostgreSQL).
 * Terhubung otomatis dengan konfigurasi Docker & GCP.
 */

define('DB_HOST', getenv('DB_HOST') ?: 'db');
define('DB_PORT', getenv('DB_PORT') ?: '5432');
define('DB_NAME', getenv('DB_NAME') ?: 'thriftzone_db');
define('DB_USER', getenv('DB_USER') ?: 'thrift_user');
define('DB_PASS', getenv('DB_PASS') ?: 'Rahasia123!');

/**
 * Returns a PDO connection (singleton pattern).
 * Throws a PDOException on failure (caught in each page).
 */
function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        // DSN Format khusus PostgreSQL (pgsql)
        $dsn = 'pgsql:host=' . DB_HOST
             . ';port=' . DB_PORT
             . ';dbname=' . DB_NAME . ';';

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }

    return $pdo;
}