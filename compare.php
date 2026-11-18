<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/timemachine.php';

$a = parse_birth_date($_GET['a'] ?? null);
$b = parse_birth_date($_GET['b'] ?? null);
$nameA = trim($_GET['na'] ?? '') ?: 'Person A';
$nameB = trim($_GET['nb'] ?? '') ?: 'Person B';
$nameA = mb_substr($nameA, 0, 20);
$nameB = mb_substr($nameB, 0, 20);

$ra = $a ? build_report($a) : null;
$rb = ($a && $b) ? build_report($b) : null;

$pageTitle = 'Compare Birthdays · Birthday Time Machine';
require __DIR__ . '/includes/header.php';
?>
<section class="panel narrow">
  <h1 class="section-title">⚖️ Generation Gap</h1>
  <p>Put two birthdays side by side. Try you vs your parents, or you vs your professor.</p>
  <form method="get" class="compare-form">
    <div>
      <input name="na" placeholder="Name" maxlength="20" value="<?= e($nameA === 'Person A' ? '' : $nameA) ?>">
      <input type="date" name="a" required min="1955-01-01" max="<?= date('Y-m-d') ?>" value="<?= e($_GET['a'] ?? '') ?>">
    </div>
    <span class="vs">VS</span>
    <div>
      <input name="nb" placeholder="Name" maxlength="20" value="<?= e($nameB === 'Person B' ? '' : $nameB) ?>">
      <input type="date" name="b" required min="1955-01-01" max="<?= date('Y-m-d') ?>" value="<?= e($_GET['b'] ?? '') ?>">
    </div>
    <button class="big-btn" type="submit">Compare</button>
  </form>
</section>

<?php if ($ra && $rb):
  $gap = $a->diff($b);
  $older = $a < $b ? $nameA : $nameB;
  $priceMap = fn($r) => array_column($r['prices'], null, 'name');
  $pa = $priceMap($ra); $pb = $priceMap($rb);
?>
<section class="panel">
  <p class="callout center"><b><?= e($older) ?></b> is older by
    <b><?= $gap->y ?> year<?= $gap->y == 1 ? '' : 's' ?>, <?= $gap->m ?> month<?= $gap->m == 1 ? '' : 's' ?> and <?= $gap->d ?> day<?= $gap->d == 1 ? '' : 's' ?></b>
    (<?= number_format($gap->days) ?> days).</p>

  <div class="compare-table">
    <div class="ct-head"></div>
    <div class="ct-head"><?= e($nameA) ?><small><?= e($ra['pretty']) ?></small></div>
    <div class="ct-head"><?= e($nameB) ?><small><?= e($rb['pretty']) ?></small></div>

    <div class="ct-label">Generation</div>
    <div><?= $ra['generation']['emoji'] . ' ' . e($ra['generation']['name']) ?></div>
    <div><?= $rb['generation']['emoji'] . ' ' . e($rb['generation']['name']) ?></div>

    <div class="ct-label">Biggest song</div>
    <div><?= e($ra['then']['song_title'] ?? 'TBD') ?></div>
    <div><?= e($rb['then']['song_title'] ?? 'TBD') ?></div>

    <div class="ct-label">Biggest movie</div>
    <div><?= e($ra['then']['movie_title'] ?? 'TBD') ?></div>
    <div><?= e($rb['then']['movie_title'] ?? 'TBD') ?></div>

    <?php foreach ($pa as $name => $p): if (!isset($pb[$name])) continue; ?>
      <div class="ct-label"><?= $p['emoji'] . ' ' . e($name) ?></div>
      <div><?= money($p['then']) . e($p['unit']) ?></div>
      <div><?= money($pb[$name]['then']) . e($p['unit']) ?></div>
    <?php endforeach; ?>

    <div class="ct-label">📱 Phone</div>
    <div><?= e($ra['tech_then']['phone']['label'] ?? '') ?></div>
    <div><?= e($rb['tech_then']['phone']['label'] ?? '') ?></div>

    <div class="ct-label">🎧 Music</div>
    <div><?= e($ra['tech_then']['music']['label'] ?? '') ?></div>
    <div><?= e($rb['tech_then']['music']['label'] ?? '') ?></div>

    <div class="ct-label">🚫 Things that didn't exist yet</div>
    <div><?= count($ra['inventions']['not_yet']) + count($ra['inventions']['came_and_went']) ?></div>
    <div><?= count($rb['inventions']['not_yet']) + count($rb['inventions']['came_and_went']) ?></div>
  </div>
  <p class="center">
    <a class="small-btn" href="machine.php?d=<?= e($ra['date']) ?>">Open <?= e($nameA) ?>'s trip</a>
    <a class="small-btn" href="machine.php?d=<?= e($rb['date']) ?>">Open <?= e($nameB) ?>'s trip</a>
  </p>
</section>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
