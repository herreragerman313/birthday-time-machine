<!-- Time travel intro overlay (animated by assets/js/app.js) -->
<div id="warp" class="warp" aria-hidden="true">
  <div class="warp-tunnel"></div>
  <div class="warp-center">
    <svg class="warp-clock" viewBox="0 0 100 100" width="150" height="150">
      <circle cx="50" cy="50" r="46" fill="none" stroke="currentColor" stroke-width="4"/>
      <g stroke="currentColor" stroke-width="3" stroke-linecap="round">
        <line x1="50" y1="8" x2="50" y2="16"/><line x1="92" y1="50" x2="84" y2="50"/>
        <line x1="50" y1="92" x2="50" y2="84"/><line x1="8" y1="50" x2="16" y2="50"/>
      </g>
      <line class="hand-hour" x1="50" y1="50" x2="50" y2="28" stroke="currentColor" stroke-width="5" stroke-linecap="round"/>
      <line class="hand-min" x1="50" y1="50" x2="50" y2="14" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
      <circle cx="50" cy="50" r="4" fill="currentColor"/>
    </svg>
    <div id="warp-year" class="warp-year">2026</div>
    <div id="warp-text" class="warp-text">Spinning up the time machine...</div>
  </div>
</div>
