<?php
// Returns NASA's Astronomy Picture of the Day for a date (archive starts June 16, 1995).
define('NO_SESSION', true); // API calls don't need a login session
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/http.php';

$date = $_GET['date'] ?? '';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !strtotime($date)) {
    json_out(['error' => 'Bad date'], 400);
}
if ($date < '1995-06-16') {
    json_out(['available' => false, 'reason' => 'NASA started posting its daily space photo on June 16, 1995.']);
}
if ($date > date('Y-m-d')) {
    json_out(['available' => false, 'reason' => 'That day has not happened yet.']);
}

$stmt = db()->prepare('SELECT payload FROM cache_apod WHERE apod_date = ?');
$stmt->execute([$date]);
$cached = $stmt->fetchColumn();
if ($cached) {
    json_out(json_decode($cached, true));
}

$url = 'https://api.nasa.gov/planetary/apod?thumbs=true&date=' . urlencode($date) . '&api_key=' . urlencode(NASA_API_KEY);
$data = http_get_json($url, 10);
if (!$data || empty($data['url'])) {
    json_out(['available' => false, 'reason' => 'NASA\'s photo service is busy right now. Refresh to try again.'], 200);
}

$explanation = $data['explanation'] ?? '';
if (mb_strlen($explanation) > 420) {
    $explanation = mb_substr($explanation, 0, 420) . '...';
}
$out = [
    'available'  => true,
    'title'      => $data['title'] ?? 'Space photo',
    'media_type' => $data['media_type'] ?? 'image',
    'url'        => $data['url'],
    'thumb'      => $data['thumbnail_url'] ?? null,
    'explanation'=> $explanation,
    'credit'     => isset($data['copyright']) ? trim($data['copyright']) : 'NASA',
    'page'       => 'https://apod.nasa.gov/apod/ap' . date('ymd', strtotime($date)) . '.html',
];
$save = db()->prepare('REPLACE INTO cache_apod (apod_date, payload, fetched_at) VALUES (?, ?, NOW())');
$save->execute([$date, json_encode($out, JSON_UNESCAPED_UNICODE)]);
json_out($out);
