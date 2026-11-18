<?php
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}
check_csrf();
$user = require_login();

// The user_id check makes sure people can only delete their own trips
$stmt = db()->prepare('DELETE FROM saved_pages WHERE id = ? AND user_id = ?');
$stmt->execute([(int)($_POST['id'] ?? 0), $user['id']]);

flash($stmt->rowCount() ? 'Trip deleted.' : 'Could not find that trip.');
header('Location: dashboard.php');
