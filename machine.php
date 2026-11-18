<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/timemachine.php';

$shared = null;
if (!empty($_GET['s'])) {
    // Shared link: look up a saved page by its slug
    $stmt = db()->prepare(
        'SELECT sp.*, u.username FROM saved_pages sp JOIN users u ON u.id = sp.user_id WHERE sp.slug = ?'
    );
    $stmt->execute([$_GET['s']]);
    $shared = $stmt->fetch();
    if (!$shared) {
        flash('That time machine link does not exist anymore.');
        header('Location: index.php');
        exit;
    }
    if (empty($_SESSION['viewed_' . $shared['slug']])) {
        db()->prepare('UPDATE saved_pages SET views = views + 1 WHERE id = ?')->execute([$shared['id']]);
        $_SESSION['viewed_' . $shared['slug']] = true;
    }
    $birth = parse_birth_date($shared['birth_date']);
} else {
    $birth = parse_birth_date($_GET['d'] ?? null);
}

if (!$birth) {
    header('Location: index.php?bad=1');
    exit;
}

$r = build_report($birth);
$user = current_user();
log_lookup($r['date'], $user['id'] ?? null);
$siteTwins = birthday_twins_on_site($r['month_day']);
$arrived = isset($_GET['arrived']);

$title = $shared ? $shared['title'] : 'Your Time Machine';
$pageTitle = $title . ' · ' . $r['pretty'];
$bodyClass = $r['theme']['class'] . ' machine';
$extraScripts = ['assets/js/machine.js', 'assets/js/card.js'];

// Data handed to JavaScript (counters, moon, APOD, history, share card)
$then = $r['then'];
$jsData = [
    'date'       => $r['date'],
    'year'       => $r['year'],
    'monthDay'   => $r['month_day'],
    'pretty'     => $r['pretty'],
    'weekday'    => $r['weekday'],
    'theme'      => $r['theme']['class'],
    'themeLabel' => $r['theme']['label'],
    'generation' => $r['generation']['name'],
    'genEmoji'   => $r['generation']['emoji'],
    'zodiac'     => $r['zodiac']['symbol'] . ' ' . $r['zodiac']['name'],
    'chinese'    => $r['chinese']['emoji'] . ' ' . $r['chinese']['name'],
    'song'       => $then && $then['song_title'] ? $then['song_title'] . ' by ' . $then['song_artist'] : null,
    'movie'      => $then['movie_title'] ?? null,
    'prices'     => array_map(fn($p) => [$p['emoji'], $p['name'], money($p['then']) . $p['unit'], money($p['now']) . $p['unit']], array_slice($r['prices'], 0, 4)),
    'notYet'     => array_map(fn($i) => $i['emoji'] . ' ' . $i['name'], array_slice(array_reverse($r['inventions']['not_yet']), 0, 6)),
    'arrived'    => $arrived,
    'siteUrl'    => SITE_URL,
    'title'      => $title,
];
require __DIR__ . '/includes/header.php';
?>
<?php require __DIR__ . '/includes/intro.php'; ?>
<?php if (!$arrived): ?>
<div id="tap-to-travel" class="tap-to-travel">
  <div>
    <p class="eyebrow"><?= $shared ? e($shared['username']) . ' sent you a trip to' : 'Destination locked in' ?></p>
    <h2><?= e($r['pretty']) ?></h2>
    <button id="start-travel" class="big-btn">🚀 Start the Time Machine</button>
    <p><button id="skip-travel" class="link-btn">Skip the ride</button></p>
  </div>
</div>
<?php endif; ?>

<script>window.BTM = <?= json_encode($jsData, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>

<!-- ============ ARRIVAL ============ -->
<section class="arrival panel">
  <p class="eyebrow">📍 Destination: <?= e($r['theme']['label']) ?></p>
  <h1 class="arrival-title"><?= $shared ? e($shared['title']) : 'You arrived on' ?></h1>
  <h2 class="arrival-date"><?= e($r['pretty']) ?></h2>
  <div class="badges">
    <span class="badge"><?= $r['generation']['emoji'] ?> <?= e($r['generation']['name']) ?></span>
    <span class="badge"><?= $r['zodiac']['symbol'] ?> <?= e($r['zodiac']['name']) ?></span>
    <span class="badge"><?= $r['chinese']['emoji'] ?> Year of the <?= e($r['chinese']['name']) ?></span>
    <span class="badge">📅 Born on a <?= e($r['weekday']) ?></span>
  </div>
  <p class="arrival-sub">That was <b><?= number_format($r['days_alive']) ?></b> days ago.
    <?php if ($siteTwins > 1): ?>This birthday has been visited <b><?= number_format($siteTwins) ?></b> times on this site.<?php endif; ?></p>
  <a href="#share" class="small-btn">📸 Jump to your share card</a>
</section>

<!-- ============ SOUNDTRACK ============ -->
<section class="panel" id="soundtrack">
  <h2 class="section-title">🎵 The Soundtrack</h2>
  <div class="two-col">
    <div class="tile big-tile">
      <p class="tile-label">Biggest song of <?= $r['year'] ?></p>
      <?php if ($then && $then['song_title']): ?>
        <p class="tile-main">"<?= e($then['song_title']) ?>"</p>
        <p class="tile-sub"><?= e($then['song_artist']) ?></p>
      <?php else: ?>
        <p class="tile-main">Still being decided</p>
        <p class="tile-sub">The year end chart isn't out yet</p>
      <?php endif; ?>
      <p class="tiny">Billboard year end #1 single</p>
    </div>
    <div class="tile big-tile">
      <p class="tile-label">Biggest movie of <?= $r['year'] ?></p>
      <?php if ($then && $then['movie_title']): ?>
        <p class="tile-main"><?= e($then['movie_title']) ?></p>
        <p class="tile-sub">Top US box office for movies released that year</p>
      <?php else: ?>
        <p class="tile-main">🎬 Coming soon</p>
        <p class="tile-sub">We don't have this year yet</p>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ============ PRICES ============ -->
<section class="panel" id="prices">
  <h2 class="section-title">💸 What Things Cost</h2>
  <?php if ($r['inflation']): ?>
    <p class="callout">💵 $20 in <?= $r['year'] ?> buys about as much as
      <b><?= money(20 * $r['inflation']) ?></b> today.</p>
  <?php endif; ?>
  <div class="price-grid">
    <?php foreach ($r['prices'] as $p): ?>
      <div class="price-card">
        <div class="price-emoji"><?= $p['emoji'] ?></div>
        <div class="price-name"><?= e($p['name']) ?></div>
        <div class="price-row">
          <div><span class="tiny"><?= $r['year'] ?></span><b><?= money($p['then']) ?><?= e($p['unit']) ?></b></div>
          <div class="arrow">➜</div>
          <div><span class="tiny">Now</span><b><?= money($p['now']) ?><?= e($p['unit']) ?></b></div>
        </div>
        <?php if ($p['times'] && $p['times'] >= 1.05): ?>
          <div class="price-change">📈 <?= number_format($p['times'], 1) ?>x more</div>
        <?php elseif ($p['times'] && $p['times'] < 0.95): ?>
          <div class="price-change down">📉 Cheaper now</div>
        <?php else: ?>
          <div class="price-change">About the same</div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- ============ TECH THEN VS NOW ============ -->
<section class="panel" id="tech">
  <h2 class="section-title">📟 Then vs Now Tech</h2>
  <?php
  $techIcons = ['phone' => '📱 Phone', 'music' => '🎧 Music', 'internet' => '🌐 Internet', 'gaming' => '🎮 Gaming', 'tv' => '📺 TV'];
  ?>
  <div class="tech-list">
    <?php foreach ($techIcons as $cat => $label):
      $t = $r['tech_then'][$cat] ?? null; $n = $r['tech_now'][$cat] ?? null;
      if (!$t || !$n) continue; ?>
      <div class="tech-row">
        <div class="tech-cat"><?= $label ?></div>
        <div class="tech-then">
          <b><?= e($t['label']) ?></b>
          <?php if ($t['detail']): ?><span><?= e($t['detail']) ?></span><?php endif; ?>
        </div>
        <div class="arrow">➜</div>
        <div class="tech-now"><b><?= e($n['label']) ?></b><span>Today</span></div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- ============ DIDN'T EXIST / GONE ============ -->
<section class="panel" id="exist">
  <h2 class="section-title">🚫 Didn't Exist Yet vs 🪦 Gone Forever</h2>
  <div class="three-col">
    <div class="list-box">
      <h3>Didn't exist when you were born</h3>
      <?php if (!$r['inventions']['not_yet']): ?><p class="tiny">Wow, everything on our list already existed!</p><?php endif; ?>
      <ul>
        <?php foreach (array_reverse($r['inventions']['not_yet']) as $i): ?>
          <li><span class="li-emoji"><?= $i['emoji'] ?></span> <?= e($i['name']) ?>
            <small>arrived <?= date('Y', strtotime($i['start_date'])) ?></small></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div class="list-box">
      <h3>Around when you were born, gone now</h3>
      <?php if (!$r['inventions']['gone']): ?><p class="tiny">Nothing on our list has died since you were born. Yet.</p><?php endif; ?>
      <ul>
        <?php foreach ($r['inventions']['gone'] as $i): ?>
          <li><span class="li-emoji"><?= $i['emoji'] ?></span> <?= e($i['name']) ?>
            <small>gone <?= date('Y', strtotime($i['end_date'])) ?></small></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div class="list-box">
      <h3>Came AND went in your lifetime</h3>
      <?php if (!$r['inventions']['came_and_went']): ?><p class="tiny">None yet. Give it time.</p><?php endif; ?>
      <ul>
        <?php foreach ($r['inventions']['came_and_went'] as $i): ?>
          <li><span class="li-emoji"><?= $i['emoji'] ?></span> <?= e($i['name']) ?>
            <small><?= date('Y', strtotime($i['start_date'])) ?> to <?= date('Y', strtotime($i['end_date'])) ?></small></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</section>

<!-- ============ ON THIS DAY ============ -->
<section class="panel" id="history">
  <h2 class="section-title">📰 What Was Happening</h2>
  <div class="two-col">
    <div>
      <h3>Big moments on <?= e($birth->format('F j')) ?> through history</h3>
      <div id="history-same" class="history-same"></div>
      <ul id="history-events" class="timeline"><li class="loading">Digging through the history books...</li></ul>
    </div>
    <div>
      <h3>👯 Famous birthday twins</h3>
      <ul id="history-births" class="twins"><li class="loading">Finding people born on your birthday...</li></ul>
    </div>
  </div>
  <p class="tiny">From Wikipedia's "On This Day".</p>
</section>

<!-- ============ SKY ============ -->
<section class="panel" id="sky">
  <h2 class="section-title">🌌 The Sky That Night</h2>
  <div class="two-col">
    <div class="tile moon-tile">
      <canvas id="moon" width="220" height="220" aria-label="Moon phase on your birthday"></canvas>
      <p class="tile-main" id="moon-name">...</p>
      <p class="tile-sub" id="moon-detail"></p>
    </div>
    <div class="tile" id="apod">
      <p class="tile-label">NASA's space photo from your birthday</p>
      <p class="loading">Calling NASA...</p>
    </div>
  </div>
</section>

<!-- ============ NUMBERS ============ -->
<section class="panel" id="numbers">
  <h2 class="section-title">🎂 You by the Numbers <span class="live-dot">LIVE</span></h2>
  <div class="counter-grid">
    <div class="counter"><b data-counter="seconds">0</b><span>seconds alive</span></div>
    <div class="counter"><b data-counter="heartbeats">0</b><span>heartbeats (about)</span></div>
    <div class="counter"><b data-counter="breaths">0</b><span>breaths (about)</span></div>
    <div class="counter"><b data-counter="sleep">0</b><span>hours asleep (about)</span></div>
    <div class="counter"><b data-counter="miles">0</b><span>miles traveled around the sun</span></div>
    <div class="counter"><b data-counter="moons">0</b><span>full moons you've lived through</span></div>
  </div>
  <h3>Your age on other planets</h3>
  <div class="planet-grid" id="planets"></div>
  <div class="two-col">
    <div class="tile"><p class="tile-label">🎉 Next birthday in</p><p class="tile-main" id="next-bday">...</p></div>
    <div class="tile"><p class="tile-label">⏳ Your 1 billionth second</p><p class="tile-main" id="billion">...</p></div>
  </div>
</section>

<!-- ============ SHARE / SAVE ============ -->
<section class="panel" id="share">
  <h2 class="section-title">📸 Share Your Trip</h2>
  <div class="share-wrap">
    <canvas id="card" width="1080" height="1920" class="card-preview" aria-label="Shareable birthday card"></canvas>
    <div class="share-actions">
      <p>Your card is sized for an Instagram or TikTok story.</p>
      <button id="download-card" class="big-btn">⬇️ Download Card</button>
      <button id="share-card" class="small-btn" hidden>📤 Share</button>

      <hr>
      <?php if ($user && !$shared): ?>
        <form method="post" action="save.php" class="save-form">
          <?= csrf_field() ?>
          <input type="hidden" name="birth_date" value="<?= e($r['date']) ?>">
          <label>Name this trip
            <input type="text" name="title" maxlength="60" required value="<?= e($user['username']) ?>'s Time Machine">
          </label>
          <button type="submit" class="small-btn">💾 Save &amp; get a share link</button>
        </form>
      <?php elseif (!$user): ?>
        <p><a href="login.php?next=<?= urlencode('machine.php?d=' . $r['date'] . '&arrived=1') ?>">Log in</a> or
          <a href="register.php">sign up</a> to save this trip and get a share link.</p>
      <?php else: ?>
        <p>🔗 Share link: <input class="copy-link" readonly value="<?= e(SITE_URL . '/machine.php?s=' . $shared['slug']) ?>"></p>
        <p class="tiny">Viewed <?= (int)$shared['views'] ?> times.</p>
      <?php endif; ?>
      <p><a class="small-btn" href="compare.php?a=<?= e($r['date']) ?>">⚖️ Compare with another birthday</a></p>
      <p><a class="link-btn" href="index.php">↩ Try another date</a></p>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
