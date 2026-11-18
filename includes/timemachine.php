<?php
// The "engine" of the site: takes a birth date and builds everything the page shows.
require_once __DIR__ . '/db.php';

const MIN_DATE = '1955-01-01';

function parse_birth_date(?string $value): ?DateTimeImmutable {
    if (!$value || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return null;
    }
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    if (!$d || $d->format('Y-m-d') !== $value) {
        return null;           // rejects things like 2023-02-30
    }
    $today = new DateTimeImmutable('today');
    if ($value < MIN_DATE || $d > $today) {
        return null;
    }
    return $d;
}

function year_facts(int $year): ?array {
    $stmt = db()->prepare('SELECT * FROM year_facts WHERE year = ?');
    $stmt->execute([$year]);
    return $stmt->fetch() ?: null;
}

// Price of a stamp or the minimum wage on an exact date
function dated_price(string $item, string $date): ?float {
    $stmt = db()->prepare(
        'SELECT price FROM dated_prices WHERE item = ? AND start_date <= ? ORDER BY start_date DESC LIMIT 1'
    );
    $stmt->execute([$item, $date]);
    $p = $stmt->fetchColumn();
    return $p === false ? null : (float)$p;
}

function generation_for(int $year): array {
    $gens = [
        [1928, 1945, 'Silent Generation', '🎙️'],
        [1946, 1964, 'Baby Boomer', '🌼'],
        [1965, 1980, 'Gen X', '📼'],
        [1981, 1996, 'Millennial', '💾'],
        [1997, 2012, 'Gen Z', '📱'],
        [2013, 2024, 'Gen Alpha', '🧒'],
        [2025, 2039, 'Gen Beta', '🚀'],
    ];
    foreach ($gens as [$a, $b, $name, $emoji]) {
        if ($year >= $a && $year <= $b) {
            return ['name' => $name, 'emoji' => $emoji, 'range' => "$a to $b"];
        }
    }
    return ['name' => 'Time Traveler', 'emoji' => '⏳', 'range' => ''];
}

function zodiac_for(DateTimeImmutable $d): array {
    $md = (int)$d->format('md');
    $signs = [
        [120, 'Capricorn', '♑'], [219, 'Aquarius', '♒'], [320, 'Pisces', '♓'],
        [420, 'Aries', '♈'], [521, 'Taurus', '♉'], [621, 'Gemini', '♊'],
        [722, 'Cancer', '♋'], [823, 'Leo', '♌'], [923, 'Virgo', '♍'],
        [1023, 'Libra', '♎'], [1122, 'Scorpio', '♏'], [1222, 'Sagittarius', '♐'],
        [1231, 'Capricorn', '♑'],
    ];
    foreach ($signs as [$end, $name, $symbol]) {
        if ($md < $end || ($end === 1231)) {
            return ['name' => $name, 'symbol' => $symbol];
        }
    }
    return ['name' => 'Capricorn', 'symbol' => '♑'];
}

// Simple version: uses the calendar year (Lunar New Year falls in Jan/Feb)
function chinese_zodiac_for(int $year): array {
    $animals = [
        ['Rat', '🐀'], ['Ox', '🐂'], ['Tiger', '🐅'], ['Rabbit', '🐇'], ['Dragon', '🐉'], ['Snake', '🐍'],
        ['Horse', '🐎'], ['Goat', '🐐'], ['Monkey', '🐒'], ['Rooster', '🐓'], ['Dog', '🐕'], ['Pig', '🐖'],
    ];
    $a = $animals[(($year - 1900) % 12 + 12) % 12];
    return ['name' => $a[0], 'emoji' => $a[1]];
}

function decade_theme(int $year): array {
    if ($year < 1970) return ['class' => 't-60s', 'label' => 'Groovy 60s'];
    if ($year < 1980) return ['class' => 't-70s', 'label' => 'Far Out 70s'];
    if ($year < 1990) return ['class' => 't-80s', 'label' => 'Neon 80s'];
    if ($year < 2000) return ['class' => 't-90s', 'label' => 'Totally 90s'];
    if ($year < 2010) return ['class' => 't-00s', 'label' => 'Y2K 2000s'];
    if ($year < 2020) return ['class' => 't-10s', 'label' => 'Flat 2010s'];
    return ['class' => 't-20s', 'label' => 'Glassy 2020s'];
}

function tech_for_year(int $year): array {
    $stmt = db()->prepare(
        'SELECT category, label, detail FROM tech_eras WHERE ? BETWEEN start_year AND end_year ORDER BY FIELD(category,"phone","music","internet","gaming","tv")'
    );
    $stmt->execute([$year]);
    $out = [];
    foreach ($stmt->fetchAll() as $row) {
        $out[$row['category']] = $row;
    }
    return $out;
}

function invention_lists(string $birth): array {
    $today = date('Y-m-d');
    $rows = db()->query('SELECT * FROM inventions ORDER BY start_date')->fetchAll();
    $lists = ['not_yet' => [], 'gone' => [], 'came_and_went' => []];
    foreach ($rows as $r) {
        $ended = $r['end_date'] !== null && $r['end_date'] <= $today;
        if ($r['start_date'] > $birth) {
            if ($ended) {
                $lists['came_and_went'][] = $r;   // born after you, already dead
            } elseif (!$ended) {
                $lists['not_yet'][] = $r;
            }
        } elseif ($ended && $r['end_date'] > $birth) {
            $lists['gone'][] = $r;                // existed when you were born, gone now
        }
    }
    return $lists;
}

// Builds the full report for one birth date
function build_report(DateTimeImmutable $birth): array {
    $year = (int)$birth->format('Y');
    $now = new DateTimeImmutable('today');
    $thisYear = (int)$now->format('Y');
    $birthStr = $birth->format('Y-m-d');

    $then = year_facts($year);
    $current = year_facts($thisYear) ?? year_facts($thisYear - 1);
    $inflation = ($then && $current) ? (float)$current['cpi'] / (float)$then['cpi'] : null;

    $prices = [];
    $add = function ($emoji, $name, $thenVal, $nowVal, $unit = '') use (&$prices) {
        if ($thenVal === null || $nowVal === null) return;
        $prices[] = [
            'emoji' => $emoji, 'name' => $name, 'unit' => $unit,
            'then' => (float)$thenVal, 'now' => (float)$nowVal,
            'times' => $thenVal > 0 ? $nowVal / $thenVal : null,
        ];
    };
    if ($then && $current) {
        $add('⛽', 'Gallon of gas', $then['gas_gallon'], $current['gas_gallon']);
        $add('🎟️', 'Movie ticket', $then['movie_ticket'], $current['movie_ticket']);
        $add('🍔', 'Big Mac', $then['big_mac'], $current['big_mac']);
        $add('🚙', 'New car', $then['new_car'], $current['new_car']);
    }
    $add('✉️', 'Postage stamp', dated_price('stamp', $birthStr), dated_price('stamp', $now->format('Y-m-d')));
    $add('💵', 'Federal minimum wage', dated_price('min_wage', $birthStr), dated_price('min_wage', $now->format('Y-m-d')), '/hr');

    $age = $birth->diff($now);

    return [
        'date'        => $birthStr,
        'year'        => $year,
        'pretty'      => $birth->format('l, F j, Y'),
        'weekday'     => $birth->format('l'),
        'month_day'   => $birth->format('m-d'),
        'age_years'   => $age->y,
        'days_alive'  => (int)$age->days,
        'then'        => $then,
        'current'     => $current,
        'current_year'=> $thisYear,
        'inflation'   => $inflation,
        'prices'      => $prices,
        'generation'  => generation_for($year),
        'zodiac'      => zodiac_for($birth),
        'chinese'     => chinese_zodiac_for($year),
        'theme'       => decade_theme($year),
        'tech_then'   => tech_for_year($year),
        'tech_now'    => tech_for_year($thisYear),
        'inventions'  => invention_lists($birthStr),
    ];
}

function log_lookup(string $birth, ?int $userId): void {
    // Only count once per date per visit so refreshing doesn't inflate stats
    $key = 'logged_' . $birth;
    if (!empty($_SESSION[$key])) return;
    $stmt = db()->prepare('INSERT INTO lookups (birth_date, user_id) VALUES (?, ?)');
    $stmt->execute([$birth, $userId]);
    $_SESSION[$key] = true;
}

function birthday_twins_on_site(string $monthDay): int {
    $stmt = db()->prepare("SELECT COUNT(*) FROM lookups WHERE DATE_FORMAT(birth_date, '%m-%d') = ?");
    $stmt->execute([$monthDay]);
    return (int)$stmt->fetchColumn();
}

function money($v, bool $whole = false): string {
    if ($whole || $v >= 1000) return '$' . number_format($v, 0);
    if ($v < 1) return number_format($v * 100, 0) . '¢';
    return '$' . number_format($v, 2);
}
