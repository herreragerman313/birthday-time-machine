<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/timemachine.php';

$pdo = db();
$total = (int)$pdo->query('SELECT COUNT(*) FROM lookups')->fetchColumn();
$users = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$saved = (int)$pdo->query('SELECT COUNT(*) FROM saved_pages')->fetchColumn();

$topDays = $pdo->query(
    "SELECT DATE_FORMAT(birth_date, '%M %e') AS day, COUNT(*) AS n
     FROM lookups GROUP BY DATE_FORMAT(birth_date, '%m-%d'), DATE_FORMAT(birth_date, '%M %e')
     ORDER BY n DESC LIMIT 5"
)->fetchAll();

$topYears = $pdo->query(
    'SELECT YEAR(birth_date) AS y, COUNT(*) AS n FROM lookups GROUP BY YEAR(birth_date) ORDER BY n DESC LIMIT 5'
)->fetchAll();

$decades = $pdo->query(
    'SELECT FLOOR(YEAR(birth_date) / 10) * 10 AS decade, COUNT(*) AS n FROM lookups GROUP BY decade ORDER BY decade'
)->fetchAll();

$months = $pdo->query(
    'SELECT MONTH(birth_date) AS m, COUNT(*) AS n FROM lookups GROUP BY m ORDER BY m'
)->fetchAll(PDO::FETCH_KEY_PAIR);

$popularShared = $pdo->query(
    'SELECT title, views FROM saved_pages ORDER BY views DESC LIMIT 5'
)->fetchAll();

$maxDecade = max(array_column($decades, 'n') ?: [1]);
$maxMonth = max($months ?: [1]);

$pageTitle = 'Site Stats · Birthday Time Machine';
require __DIR__ . '/includes/header.php';
?>
<section class="panel">
  <h1 class="section-title">📊 Site Stats</h1>
  <div class="counter-grid">
    <div class="counter"><b><?= number_format($total) ?></b><span>trips taken</span></div>
    <div class="counter"><b><?= number_format($users) ?></b><span>time travelers signed up</span></div>
    <div class="counter"><b><?= number_format($saved) ?></b><span>trips saved and shared</span></div>
  </div>
</section>

<section class="panel">
  <div class="two-col">
    <div>
      <h2>🎂 Most visited birthdays</h2>
      <?php if (!$topDays): ?><p class="tiny">No trips yet. Be the first!</p><?php endif; ?>
      <ol class="rank">
        <?php foreach ($topDays as $row): ?>
          <li><?= e($row['day']) ?> <span><?= (int)$row['n'] ?> trips</span></li>
        <?php endforeach; ?>
      </ol>
    </div>
    <div>
      <h2>📅 Most popular birth years</h2>
      <ol class="rank">
        <?php foreach ($topYears as $row): ?>
          <li><?= (int)$row['y'] ?> <span><?= (int)$row['n'] ?> trips</span></li>
        <?php endforeach; ?>
      </ol>
    </div>
  </div>
</section>

<section class="panel">
  <h2>🕰️ Trips by decade</h2>
  <div class="bars">
    <?php foreach ($decades as $row): $pct = round($row['n'] / $maxDecade * 100); ?>
      <div class="bar-row">
        <span class="bar-label"><?= (int)$row['decade'] ?>s</span>
        <span class="bar"><span style="width: <?= $pct ?>%"></span></span>
        <span class="bar-num"><?= (int)$row['n'] ?></span>
      </div>
    <?php endforeach; ?>
  </div>

  <h2>🗓️ Birthdays by month</h2>
  <div class="month-chart">
    <?php for ($m = 1; $m <= 12; $m++): $n = (int)($months[$m] ?? 0); $h = $maxMonth ? round($n / $maxMonth * 100) : 0; ?>
      <div class="month-col" title="<?= $n ?> trips">
        <span class="month-bar" style="height: <?= max($h, 2) ?>%"></span>
        <span class="month-name"><?= date('M', mktime(0, 0, 0, $m, 1)) ?></span>
      </div>
    <?php endfor; ?>
  </div>
</section>

<?php if ($popularShared): ?>
<section class="panel">
  <h2>🔥 Most viewed shared trips</h2>
  <ol class="rank">
    <?php foreach ($popularShared as $row): ?>
      <li><?= e($row['title']) ?> <span><?= (int)$row['views'] ?> views</span></li>
    <?php endforeach; ?>
  </ol>
</section>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
