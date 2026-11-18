<?php
// Database connection (PDO) and session setup shared by every page.

$configFile = __DIR__ . '/config.php';
if (!file_exists($configFile)) {
    die('Missing includes/config.php. Copy includes/config.sample.php to includes/config.php and fill it in.');
}
require_once $configFile;

if (DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

date_default_timezone_set('America/Los_Angeles');

// Behind the AWS load balancer, HTTPS ends at the load balancer and it talks to
// this server over plain HTTP. It tells us the visitor used HTTPS with this header.
if (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') {
    $_SERVER['HTTPS'] = 'on';
}

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

// Sessions are saved in MySQL instead of files on one server.
// That way you stay logged in no matter which server the load balancer sends you to.
class DbSessionHandler implements SessionHandlerInterface {
    public function open($path, $name): bool { return true; }
    public function close(): bool { return true; }

    public function read($id): string|false {
        $stmt = db()->prepare('SELECT data FROM sessions WHERE id = ? AND expires_at > NOW()');
        $stmt->execute([$id]);
        $data = $stmt->fetchColumn();
        return $data === false ? '' : $data;
    }

    public function write($id, $data): bool {
        $stmt = db()->prepare(
            'REPLACE INTO sessions (id, data, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 DAY))'
        );
        return $stmt->execute([$id, $data]);
    }

    public function destroy($id): bool {
        return db()->prepare('DELETE FROM sessions WHERE id = ?')->execute([$id]);
    }

    public function gc($max_lifetime): int|false {
        return db()->exec('DELETE FROM sessions WHERE expires_at < NOW()');
    }
}

// Safer session cookies
if (session_status() === PHP_SESSION_NONE && PHP_SAPI !== 'cli' && !defined('NO_SESSION')) {
    session_set_save_handler(new DbSessionHandler(), true);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
