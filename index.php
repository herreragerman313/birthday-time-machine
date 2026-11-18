<?php
require_once __DIR__ . '/includes/auth.php';

$error = '';
$prefill = $_GET['d'] ?? '';
if (isset($_GET['bad'])) {
    $error = 'Pick a real date between January 1, 1955 and today.';
}

$totalTrips = (int)db()->query('SELECT COUNT(*) FROM lookups')->fetchColumn();

$pageTitle = 'Birthday Time Machine · Travel back to the day you were born';
$bodyClass = 't-home';
require __DIR__ . '/includes/header.php';
?>
<?php require __DIR__ . '/includes/intro.php'; ?>

<section class="hero">
  <div class="hero-glow"></div>
  <p class="eyebrow">Ever wonder what the world was like the day you showed up?</p>
  <h1>Birthday <span>Time Machine</span></h1>
  <p class="lead">Enter your birthday and travel back in time. See the song everyone was playing,
    what gas cost, what didn't exist yet, the moon that night and a lot more.</p>

  <form id="travel-form" class="travel-form" action="machine.php" method="get">
    <label for="d" class="sr-only">Your birthday</label>
    <input type="date" id="d" name="d" required min="1955-01-01" max="<?= date('Y-m-d') ?>" value="<?= e($prefill) ?>">
    <button type="submit" class="big-btn">🚀 Go Back in Time</button>
  </form>
  <?php if ($error): ?><p class="form-error"><?= e($error) ?></p><?php endif; ?>
  <p class="tiny">Works for any date from 1955 to today. <?= number_format($totalTrips) ?> trips taken so far.</p>
</section>

<section class="features">
  <div class="feature"><span>🎵</span><h3>The Soundtrack</h3><p>The biggest song and movie of the year you were born.</p></div>
  <div class="feature"><span>💸</span><h3>What Things Cost</h3><p>Gas, stamps, Big Macs and minimum wage, then vs now.</p></div>
  <div class="feature"><span>🚫</span><h3>Didn't Exist Yet</h3><p>Everything that showed up after you did, and what's gone forever.</p></div>
  <div class="feature"><span>🌕</span><h3>The Sky That Night</h3><p>Your birth moon and NASA's space photo from your birthday.</p></div>
  <div class="feature"><span>🎨</span><h3>Your Decade's Style</h3><p>The whole page changes to look like the era you were born in.</p></div>
  <div class="feature"><span>📸</span><h3>Share Card</h3><p>Turn your trip into a story sized card to post.</p></div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
