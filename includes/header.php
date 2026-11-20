<?php
// Shared top of every page. Set $pageTitle and $bodyClass before including.
require_once __DIR__ . '/auth.php';
$pageTitle = $pageTitle ?? SITE_NAME;
$bodyClass = $bodyClass ?? 't-home';
$user = current_user();
$flashMsg = flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?></title>
  <meta name="description" content="Travel back to the day you were born: the music, the prices, the news and the sky that night.">
  <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg">
  <link rel="icon" type="image/png" sizes="32x32" href="assets/img/favicon-32.png">
  <link rel="apple-touch-icon" href="assets/img/apple-touch-icon.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=Lobster&family=Monoton&family=Righteous&family=VT323&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css?v=1">
  <link rel="stylesheet" href="assets/css/themes.css?v=1">
  <?php if (!empty($extraStyles)) foreach ($extraStyles as $href): ?>
  <link rel="stylesheet" href="<?= e($href) ?>">
  <?php endforeach; ?>
</head>
<body class="<?= e($bodyClass) ?>">
<header class="site-header">
  <a class="brand" href="index.php">Birthday <b>Time Machine</b></a>
  <nav>
    <a href="index.php">Home</a>
    <a href="compare.php">Compare</a>
    <a href="stats.php">Stats</a>
    <?php if ($user): ?>
      <a href="dashboard.php">My Trips</a>
      <form method="post" action="logout.php" class="inline">
        <?= csrf_field() ?>
        <button type="submit" class="link-btn">Log out</button>
      </form>
    <?php else: ?>
      <a href="login.php">Log in</a>
      <a class="nav-cta" href="register.php">Sign up</a>
    <?php endif; ?>
  </nav>
</header>
<?php if ($flashMsg): ?>
  <div class="flash"><?= e($flashMsg) ?></div>
<?php endif; ?>
<main>
