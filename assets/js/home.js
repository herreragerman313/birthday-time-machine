// Home page: LED destination readout, Tesla coil charge meter and electric arcs
(function () {
  'use strict';
  const form = document.getElementById('travel-form');
  if (!form) return;
  const lab = document.querySelector('.lab');
  const month = document.getElementById('f-month');
  const monthShow = document.getElementById('m-show');
  const day = document.getElementById('f-day');
  const year = document.getElementById('f-year');
  const hidden = document.getElementById('d');
  const note = document.getElementById('board-note');
  const status = document.getElementById('coil-status');
  const cells = Array.from(document.querySelectorAll('#charge i'));
  const depart = document.getElementById('depart');
    const canvas = document.getElementById('arcs');
  const ctx = canvas.getContext('2d');
  const defaultNote = note.textContent;
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  let level = 0;

  cells.forEach((c, i) => c.style.setProperty('--n', i));

  // ---------- Reading the readout ----------
  function blink(el) {
    el.classList.remove('blink');
    void el.offsetWidth;
    el.classList.add('blink');
  }
  [monthShow, day, year].forEach((el) => el.addEventListener('animationend', () => el.classList.remove('blink')));

  function currentDate() {
    const m = month.value, y = year.value;
    if (!m || !/^\d{1,2}$/.test(day.value) || !/^\d{4}$/.test(y)) return '';
    const d = day.value.padStart(2, '0');
    const dt = new Date(+y, +m - 1, +d);
    if (dt.getFullYear() !== +y || dt.getMonth() !== +m - 1 || dt.getDate() !== +d) return '';
    const today = new Date(); today.setHours(0, 0, 0, 0);
    const iso = `${y}-${m}-${d}`;
    if (iso < '1955-01-01' || dt > today) return '';
    return iso;
  }

  function update() {
    hidden.value = currentDate();
    const thisYear = new Date().getFullYear();
    let parts = 0;
    if (month.value) parts++;
    if (+day.value >= 1 && +day.value <= 31) parts++;
    if (/^\d{4}$/.test(year.value) && +year.value >= 1955 && +year.value <= thisYear) parts++;
    const newLevel = hidden.value ? 3 : Math.min(parts, 2);

    cells.forEach((c, i) => c.classList.toggle('on', i < newLevel * 4));
    lab.dataset.charge = newLevel;
    status.textContent = newLevel === 3 ? 'Fully charged. Ready to depart.'
      : parts === 3 ? 'That day is not on the calendar. Check the date.'
      : 'Needs a month, day and year to charge';
    if (newLevel > level) spark(newLevel);
    level = newLevel;

    if (note.classList.contains('is-error') && hidden.value) {
      note.classList.remove('is-error');
      note.textContent = defaultNote;
    }
  }

  month.addEventListener('change', () => {
    monthShow.textContent = month.value ? month.options[month.selectedIndex].text : '';
    blink(monthShow); update(); day.focus();
  });
  day.addEventListener('input', () => {
    day.value = day.value.replace(/\D/g, '').slice(0, 2);
    blink(day); update();
    if (day.value.length === 2) year.focus();
  });
  year.addEventListener('input', () => {
    year.value = year.value.replace(/\D/g, '').slice(0, 4);
    blink(year); update();
  });

  // ---------- Electric arcs ----------
  let bolts = [];
  let running = false;

  function sizeCanvas() {
    const r = lab.getBoundingClientRect();
    const dpr = window.devicePixelRatio || 1;
    canvas.width = r.width * dpr;
    canvas.height = r.height * dpr;
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
  }
  window.addEventListener('resize', sizeCanvas);
  sizeCanvas();

  // Where the arcs start: sparks jump off the end of the charge meter,
  // and the big strike comes down from above the Depart button
  function origin(kind) {
    const L = lab.getBoundingClientRect();
    if (kind === 'strike') {
      const d = depart.getBoundingClientRect();
      return { x: d.left - L.left + d.width * (0.3 + Math.random() * 0.4), y: 0 };
    }
    const m = document.getElementById('charge').getBoundingClientRect();
    return { x: m.right - L.left - 4, y: m.top - L.top + m.height / 2 };
  }
  function target(el) {
    const L = lab.getBoundingClientRect();
    const r = el.getBoundingClientRect();
    return { x: r.left - L.left + r.width * (0.2 + Math.random() * 0.6), y: r.top - L.top + r.height * 0.3 };
  }

  // Jagged lightning path between two points (midpoint displacement)
  function path(a, b, spread, depth) {
    if (depth === 0) return [a, b];
    const mid = {
      x: (a.x + b.x) / 2 + (Math.random() - 0.5) * spread,
      y: (a.y + b.y) / 2 + (Math.random() - 0.5) * spread,
    };
    const left = path(a, mid, spread / 2, depth - 1);
    return left.concat(path(mid, b, spread / 2, depth - 1).slice(1));
  }

  function drawBolt(pts, alpha, width) {
    ctx.beginPath();
    pts.forEach((p, i) => (i ? ctx.lineTo(p.x, p.y) : ctx.moveTo(p.x, p.y)));
    ctx.strokeStyle = `rgba(143, 220, 255, ${alpha * 0.35})`;
    ctx.lineWidth = width * 5;
    ctx.stroke();
    ctx.strokeStyle = `rgba(235, 250, 255, ${alpha})`;
    ctx.lineWidth = width;
    ctx.stroke();
  }

  function loop(now) {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    bolts = bolts.filter((b) => now < b.end);
    bolts.forEach((b) => {
      const life = (b.end - now) / (b.end - b.start);
      const to = typeof b.to === 'function' ? b.to() : b.to;
      const dist = Math.hypot(to.x - b.from.x, to.y - b.from.y);
      drawBolt(path(b.from, to, dist * 0.35, 6), Math.min(1, life * 1.6), b.width);
    });
    if (bolts.length) requestAnimationFrame(loop);
    else { running = false; ctx.clearRect(0, 0, canvas.width, canvas.height); }
  }
  function addBolt(from, to, ms, width) {
    const now = performance.now();
    bolts.push({ from, to, start: now, end: now + ms, width });
    if (!running) { running = true; requestAnimationFrame(loop); }
  }

  // Small sparks around the coil each time the charge goes up
  function spark(n) {
    if (reduceMotion) return;
    const o = origin();
    for (let i = 0; i < n + 1; i++) {
      const ang = Math.random() * Math.PI * 2;
      const len = 40 + n * 22 + Math.random() * 30;
      setTimeout(() => addBolt(o, { x: o.x + Math.cos(ang) * len, y: o.y + Math.sin(ang) * len * 0.5 }, 160 + Math.random() * 120, 1.4), i * 70);
    }
  }

  // Short electric zap made with the Web Audio API
  function zap() {
    try {
      const A = window.AudioContext || window.webkitAudioContext;
      const a = new A();
      const len = Math.floor(a.sampleRate * 0.6);
      const buf = a.createBuffer(1, len, a.sampleRate);
      const data = buf.getChannelData(0);
      for (let i = 0; i < len; i++) data[i] = (Math.random() * 2 - 1) * (Math.random() < 0.3 ? 1 : 0.2);
      const src = a.createBufferSource();
      src.buffer = buf;
      const hp = a.createBiquadFilter();
      hp.type = 'highpass';
      hp.frequency.value = 1800;
      const g = a.createGain();
      g.gain.setValueAtTime(0.35, a.currentTime);
      g.gain.exponentialRampToValueAtTime(0.001, a.currentTime + 0.6);
      src.connect(hp).connect(g).connect(a.destination);
      src.start();
    } catch (e) { /* sound is optional */ }
  }

  // The big moment: the coil fires into the Depart button, then the time travel starts
  function fire(done) {
    if (reduceMotion) { done(); return; }
    zap();
    lab.classList.add('firing');
    for (let i = 0; i < 9; i++) {
      setTimeout(() => addBolt(origin('strike'), () => target(depart), 220 + Math.random() * 200, 2.2), i * 70);
    }
    setTimeout(() => { lab.classList.remove('firing'); done(); }, 800);
  }

  // Runs before app.js so we control the timing
  form.addEventListener('submit', function (ev) {
    update();
    ev.preventDefault();
    ev.stopImmediatePropagation();
    if (!hidden.value) {
      note.classList.add('is-error');
      note.textContent = 'Pick a month, a day and a 4 digit year between 1955 and today.';
      (!month.value ? month : (!day.value ? day : year)).focus();
      return;
    }
    const iso = hidden.value;
    fire(() => {
      window.BTMFX.timeTravel(parseInt(iso.slice(0, 4), 10), () => {
        window.location.href = 'machine.php?d=' + encodeURIComponent(iso) + '&arrived=1';
      });
    });
  }, true);

  update();
})();
