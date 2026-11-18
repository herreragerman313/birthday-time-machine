<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_login();

$stmt = db()->prepare('SELECT * FROM saved_pages WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$user['id']]);
$trips = $stmt->fetchAll();

$pageTitle = 'My Trips · Birthday Time Machine';
require __DIR__ . '/includes/header.php';
?>
<section class="panel narrow">
  <h1 class="section-title">🧳 My Saved Trips</h1>
  <p>Hey <b><?= e($user['username']) ?></b>! Every trip you save gets its own share link.</p>
  <?php if (!$trips): ?>
    <p class="callout">No saved trips yet. <a href="index.php">Take a trip</a> and hit "Save" at the bottom of the page.</p>
  <?php else: ?>
    <div class="trip-list">
      <?php foreach ($trips as $t): ?>
        <div class="trip">
          <div>
            <b><?= e($t['title']) ?></b>
            <span><?= e(date('F j, Y', strtotime($t['birth_date']))) ?> · 👀 <?= (int)$t['views'] ?> views</span>
            <input class="copy-link" readonly value="<?= e(SITE_URL . '/machine.php?s=' . $t['slug']) ?>">
          </div>
          <div class="trip-actions">
            <a class="small-btn" href="machine.php?s=<?= e($t['slug']) ?>&arrived=1">Open</a>
            <form method="post" action="delete.php" onsubmit="return confirm('Delete this trip?');">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
              <button class="link-btn danger" type="submit">Delete</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
