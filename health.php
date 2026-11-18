<?php
// Health check for the AWS load balancer.
// The load balancer calls this every few seconds. If it fails, that server
// stops getting visitors until it is healthy again.
require_once __DIR__ . '/includes/config.php';
header('Content-Type: text/plain');
header('Cache-Control: no-store');
try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 3,
    ]);
    $pdo->query('SELECT 1');
    echo 'OK ' . (defined('SERVER_LABEL') ? SERVER_LABEL : gethostname());
} catch (Throwable $e) {
    http_response_code(503);
    echo 'DATABASE DOWN';
}
