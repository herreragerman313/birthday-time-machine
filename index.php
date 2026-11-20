<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/timemachine.php';

$error = '';
if (isset($_GET['bad'])) {
    $error = 'That date didn\'t work. Pick a real day between January 1, 1955 and today.';
}

$totalTrips = (int)db()->query('SELECT COUNT(*) FROM lookups')->fetchColumn();

// Famous days for the departures board. Song and gas price come from the database.
$departures = [
    ['1969-07-20', 'Moon landing'],
    ['1981-08-01', 'MTV goes on the air'],
    ['1989-11-09', 'Berlin Wall falls'],
    ['2000-01-01', 'Y2K'],
    ['2005-02-14', 'YouTube starts'],
    ['2007-06-29', 'First iPhone'],
];
$years = array_map(fn($d) => (int)substr($d[0], 0, 4), $departures);
$in = implode(',', array_fill(0, count($years), '?'));
$stmt = db()->prepare("SELECT year, song_title, song_artist, gas_gallon FROM year_facts WHERE year IN ($in)");
$stmt->execute($years);
$facts = [];
foreach ($stmt->fetchAll() as $row) {
    $facts[(int)$row['year']] = $row;
}

$months = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];

$pageTitle = 'Birthday Time Machine · Travel back to the day you were born';
$bodyClass = 't-home station lab-page';
$extraStyles = [
    'https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600&family=Chakra+Petch:wght@500;600;700&display=swap',
    'assets/css/home.css?v=2',
];
$extraScripts = ['assets/js/home.js'];
require __DIR__ . '/includes/header.php';
?>
<?php require __DIR__ . '/includes/intro.php'; ?>

<section class="lab">
  <canvas id="arcs" class="arcs" aria-hidden="true"></canvas>
  <div class="lab-top">
    <div class="lab-copy">
      <h1 class="lab-title">Set a date.<br>Throw the switch.</h1>
      <p class="lab-sub">Type in your birthday to see the song on the radio, what gas cost, what didn't exist yet and what the sky looked like that night.</p>
    </div>
  </div>

  <form id="travel-form" class="console" action="machine.php" method="get" novalidate>
    <input type="hidden" name="d" id="d" value="">
    <div class="console-row">
      <div class="readout" role="group" aria-labelledby="dest-label">
        <span class="plate plate-main" id="dest-label">Destination</span>
        <div class="segs">
          <label class="seg seg-month" data-ghost="~~~">
            <span class="seg-show" id="m-show" aria-hidden="true"></span>
            <select id="f-month" aria-label="Month">
              <option value="">Month</option>
              <?php foreach ($months as $i => $m): ?>
                <option value="<?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?>"><?= $m ?></option>
              <?php endforeach; ?>
            </select>
            <span class="plate">Month</span>
          </label>
          <label class="seg seg-day" data-ghost="~~">
            <input id="f-day" inputmode="numeric" maxlength="2" autocomplete="bday-day" aria-label="Day">
            <span class="plate">Day</span>
          </label>
          <label class="seg seg-year" data-ghost="~~~~">
            <input id="f-year" inputmode="numeric" maxlength="4" autocomplete="bday-year" aria-label="Year">
            <span class="plate">Year</span>
          </label>
        </div>
      </div>
      <div class="power">
        <div class="charge" id="charge" aria-hidden="true"><?php for ($c = 0; $c < 12; $c++): ?><i></i><?php endfor; ?></div>
        <p class="coil-status" id="coil-status">Needs a month, day and year to charge</p>
        <button type="submit" class="depart" id="depart">Depart</button>
      </div>
    </div>
    <p class="console-note" id="board-note" aria-live="polite"><?= $error ? e($error) : 'Any day from 1955 to today. ' . number_format($totalTrips) . ' trips taken so far.' ?></p>
  </form>
</section>

<section class="departures" aria-labelledby="dep-title">
  <h2 id="dep-title">Next departures</h2>
  <p class="departures-sub">Not ready to use your own birthday? Pick a famous day.</p>
  <div class="dep-board">
    <div class="dep-head" aria-hidden="true">
      <span>Date</span><span>Destination</span><span>On the radio</span><span>Gas</span><span>Status</span>
    </div>
    <?php foreach ($departures as $i => [$date, $what]):
      $y = (int)substr($date, 0, 4);
      $f = $facts[$y] ?? null;
      $label = strtoupper(date('M j Y', strtotime($date)));
      $led = strtoupper(date('M!d!Y', strtotime($date))); // ! is a full width blank in the LED font
    ?>
      <a class="dep-row" href="machine.php?d=<?= e($date) ?>" style="--i: <?= $i ?>">
        <span class="dep-date" aria-label="<?= e($label) ?>"><?= e($led) ?></span>
        <span class="dep-what"><?= e($what) ?></span>
        <span class="dep-song"><?= $f && $f['song_title'] ? e($f['song_title']) . ' <small>' . e($f['song_artist']) . '</small>' : '' ?></span>
        <span class="dep-gas"><?= $f ? e(money((float)$f['gas_gallon'])) : '' ?></span>
        <span class="dep-status <?= $i === 0 ? 'boarding' : '' ?>"><?= $i === 0 ? 'Boarding' : 'On time' ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<section class="itinerary" aria-label="What you'll see">
  <p><b>The soundtrack.</b> The biggest song and movie the year you were born.</p>
  <p><b>The receipts.</b> Gas, stamps, Big Macs and minimum wage, then and now.</p>
  <p><b>The sky.</b> Your birth moon and NASA's space photo from that exact day.</p>
  <p><b>The look.</b> The whole page restyles itself to match your decade.</p>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
