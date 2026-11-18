<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/timemachine.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}
check_csrf();
$user = require_login();

$birth = parse_birth_date($_POST['birth_date'] ?? null);
$title = trim($_POST['title'] ?? '');
if (!$birth || $title === '' || mb_strlen($title) > 60) {
    flash('Give your trip a name (60 characters max).');
    header('Location: index.php');
    exit;
}

// Limit each account to 50 saved trips
$count = db()->prepare('SELECT COUNT(*) FROM saved_pages WHERE user_id = ?');
$count->execute([$user['id']]);
if ((int)$count->fetchColumn() >= 50) {
    flash('You have 50 saved trips. Delete one first.');
    header('Location: dashboard.php');
    exit;
}

// Random 10 character slug for the share link
$alphabet = 'abcdefghijkmnpqrstuvwxyz23456789';
do {
    $slug = '';
    for ($i = 0; $i < 10; $i++) {
        $slug .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }
    $check = db()->prepare('SELECT 1 FROM saved_pages WHERE slug = ?');
    $check->execute([$slug]);
} while ($check->fetchColumn());

$stmt = db()->prepare('INSERT INTO saved_pages (user_id, slug, title, birth_date) VALUES (?, ?, ?, ?)');
$stmt->execute([$user['id'], $slug, $title, $birth->format('Y-m-d')]);

flash('Saved! Copy your share link below.');
header('Location: machine.php?s=' . $slug . '&arrived=1#share');
