</main>
<footer class="site-footer">
  <p>⏱️ Birthday Time Machine · CIT 384 Final Project · Built with HTML, CSS, JavaScript, PHP and MySQL</p>
  <p class="small server-badge">🖥️ Served by <b><?= e(defined('SERVER_LABEL') ? SERVER_LABEL : gethostname()) ?></b> through the AWS load balancer</p>
  <p class="small">
    Data: Wikipedia "On This Day", NASA Astronomy Picture of the Day, US BLS (CPI), US EIA (gas),
    USPS (stamps), US Dept. of Labor (minimum wage), Billboard year end charts.
    Prices in between data years are estimates.
  </p>
</footer>
<script src="assets/js/app.js?v=1"></script>
<?php if (!empty($extraScripts)) foreach ($extraScripts as $s): ?>
<script src="<?= e($s) ?>?v=1"></script>
<?php endforeach; ?>
</body>
</html>
