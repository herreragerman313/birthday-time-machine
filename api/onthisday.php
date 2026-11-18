<?php
// Returns historical events and famous birthdays for a month/day.
// Data: Wikipedia "On this day" feed. Cached in MySQL for 30 days.
define('NO_SESSION', true); // API calls don't need a login session
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/http.php';

$md = $_GET['md'] ?? '';
$year = (int)($_GET['year'] ?? 0);
if (!preg_match('/^(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/', $md)) {
    json_out(['error' => 'Bad date'], 400);
}

$raw = null;
$stmt = db()->prepare('SELECT payload, fetched_at FROM cache_onthisday WHERE month_day = ?');
$stmt->execute([$md]);
$row = $stmt->fetch();
if ($row && strtotime($row['fetched_at']) > strtotime('-30 days')) {
    $raw = json_decode($row['payload'], true);
}

if (!$raw) {
    [$m, $d] = explode('-', $md);
    $feed = http_get_json("https://en.wikipedia.org/api/rest_v1/feed/onthisday/all/$m/$d");
    if (!$feed) {
        // Use old cache if the API is down, otherwise report the problem
        if ($row) {
            $raw = json_decode($row['payload'], true);
        } else {
            json_out(['error' => 'History service is busy. Try again in a minute.'], 503);
        }
    } else {
        // Keep only what we need so the cache stays small
        $slim = function (array $items): array {
            $out = [];
            foreach ($items as $it) {
                $page = $it['pages'][0] ?? [];
                $out[] = [
                    'year' => $it['year'] ?? null,
                    'text' => $it['text'] ?? '',
                    'desc' => $page['description'] ?? '',
                    'url'  => $page['content_urls']['desktop']['page'] ?? null,
                    'name' => $page['titles']['normalized'] ?? null,
                ];
            }
            return $out;
        };
        $raw = [
            'events' => $slim(array_merge($feed['selected'] ?? [], $feed['events'] ?? [])),
            'births' => $slim($feed['births'] ?? []),
        ];
        $save = db()->prepare(
            'REPLACE INTO cache_onthisday (month_day, payload, fetched_at) VALUES (?, ?, NOW())'
        );
        $save->execute([$md, json_encode($raw, JSON_UNESCAPED_UNICODE)]);
    }
}

// Remove duplicate events (the "selected" list repeats some events)
$seen = [];
$events = [];
foreach ($raw['events'] as $ev) {
    $k = $ev['year'] . substr($ev['text'], 0, 40);
    if (isset($seen[$k]) || $ev['year'] === null) continue;
    $seen[$k] = true;
    $events[] = $ev;
}

$sameYear = array_values(array_filter($events, fn($e) => (int)$e['year'] === $year));
$others = array_values(array_filter($events, fn($e) => (int)$e['year'] !== $year));

// Pick a spread of years so it feels like a trip through history
usort($others, fn($a, $b) => $a['year'] <=> $b['year']);
$picked = [];
$count = count($others);
$want = min(8, $count);
for ($i = 0; $i < $want; $i++) {
    $picked[] = $others[(int)floor($i * $count / max($want, 1))];
}

// Birthday twins: people with a short description, newest first, exact year matches on top
$births = array_values(array_filter($raw['births'], fn($b) => $b['name'] && $b['desc']));
usort($births, function ($a, $b) use ($year) {
    $ae = (int)$a['year'] === $year ? 1 : 0;
    $be = (int)$b['year'] === $year ? 1 : 0;
    if ($ae !== $be) return $be <=> $ae;
    return (int)$b['year'] <=> (int)$a['year'];
});
$births = array_slice($births, 0, 10);

json_out([
    'same_year' => array_slice($sameYear, 0, 5),
    'events'    => $picked,
    'births'    => $births,
    'source'    => 'Wikipedia',
]);
