// Birthday Time Machine - results page (live counters, moon, planets, history, NASA photo)
(function () {
  'use strict';
  const D = window.BTM;
  if (!D) return;

  const [by, bm, bd] = D.date.split('-').map(Number);
  const birth = new Date(by, bm - 1, bd, 0, 0, 0);
  const fmt = (n) => Math.floor(n).toLocaleString('en-US');
  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  // ---------- Intro (tap to travel) ----------
  const tap = document.getElementById('tap-to-travel');
  if (tap) {
    document.body.classList.add('locked');
    document.getElementById('start-travel').addEventListener('click', function () {
      tap.remove();
      window.BTMFX.timeTravel(D.year, function () {
        window.BTMFX.hideWarp();
        document.body.classList.remove('locked');
        document.body.classList.add('arrived-flash');
      });
    });
    document.getElementById('skip-travel').addEventListener('click', function () {
      tap.remove();
      document.body.classList.remove('locked');
    });
  } else {
    document.body.classList.add('arrived-flash');
  }

  // ---------- Fade sections in while scrolling ----------
  const panels = document.querySelectorAll('.machine .panel');
  if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach((e) => { if (e.isIntersecting) { e.target.classList.add('visible'); io.unobserve(e.target); } });
    }, { threshold: 0.08 });
    panels.forEach((p) => io.observe(p));
  } else {
    panels.forEach((p) => p.classList.add('visible'));
  }

  // ---------- Live counters ----------
  const counters = {
    seconds: (s) => s,
    heartbeats: (s) => s * 80 / 60,          // about 80 beats per minute
    breaths: (s) => s * 16 / 60,             // about 16 breaths per minute
    sleep: (s) => s / 3600 / 3,              // about a third of your life
    miles: (s) => s * 18.5,                  // Earth moves about 18.5 miles per second
    moons: (s) => s / 86400 / 29.530588853,  // one full moon every 29.5 days
  };
  const counterEls = document.querySelectorAll('[data-counter]');
  function tick() {
    const s = (Date.now() - birth.getTime()) / 1000;
    counterEls.forEach((el) => { el.textContent = fmt(counters[el.dataset.counter](s)); });
  }
  tick();
  setInterval(tick, 1000);

  // ---------- Age on other planets ----------
  const planets = [
    ['☿️', 'Mercury', 0.2408467], ['♀️', 'Venus', 0.61519726], ['♂️', 'Mars', 1.8808158],
    ['♃', 'Jupiter', 11.862615], ['♄', 'Saturn', 29.447498],
  ];
  const earthYears = (Date.now() - birth.getTime()) / (365.25 * 86400000);
  const planetBox = document.getElementById('planets');
  if (planetBox) {
    planetBox.innerHTML = planets.map(([icon, name, period]) => {
      const age = earthYears / period;
      return `<div class="planet"><span>${icon}</span><b>${age < 10 ? age.toFixed(1) : fmt(age)}</b><small>years old on ${name}</small></div>`;
    }).join('');
  }

  // ---------- Next birthday countdown ----------
  function nextBirthday() {
    const now = new Date();
    let y = now.getFullYear();
    const make = (yr) => {
      // Leap day babies celebrate on Feb 28 in normal years
      const isLeap = (yr % 4 === 0 && yr % 100 !== 0) || yr % 400 === 0;
      return (bm === 2 && bd === 29 && !isLeap) ? new Date(yr, 1, 28) : new Date(yr, bm - 1, bd);
    };
    let nb = make(y);
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    if (nb < today) nb = make(y + 1);
    return nb;
  }
  const nbEl = document.getElementById('next-bday');
  function updateBday() {
    const nb = nextBirthday();
    const today = new Date(); today.setHours(0, 0, 0, 0);
    const days = Math.round((nb - today) / 86400000);
    if (days === 0) { nbEl.textContent = '🎉 It\'s TODAY! Happy birthday!'; return; }
    const ms = nb - Date.now();
    const h = Math.floor(ms / 3600000) % 24, m = Math.floor(ms / 60000) % 60, s = Math.floor(ms / 1000) % 60;
    nbEl.textContent = `${days} days · ${h}h ${m}m ${s}s`;
  }
  if (nbEl) { updateBday(); setInterval(updateBday, 1000); }

  // ---------- 1 billionth second ----------
  const billionEl = document.getElementById('billion');
  if (billionEl) {
    const bdate = new Date(birth.getTime() + 1e9 * 1000);
    const nice = bdate.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
    if (bdate < new Date()) {
      billionEl.innerHTML = `You hit it on <b>${nice}</b> ✅`;
    } else {
      const days = Math.ceil((bdate - new Date()) / 86400000);
      billionEl.innerHTML = `<b>${nice}</b><br><small>${fmt(days)} days to go</small>`;
    }
  }

  // ---------- Moon phase on the night you were born ----------
  function moonPhase(date) {
    const synodic = 29.530588853;
    const knownNew = Date.UTC(2000, 0, 6, 18, 14); // a known new moon
    let age = ((date.getTime() - knownNew) / 86400000) % synodic;
    if (age < 0) age += synodic;
    const illum = (1 - Math.cos(2 * Math.PI * age / synodic)) / 2;
    const names = [
      [1.84566, 'New Moon', '🌑'], [5.53699, 'Waxing Crescent', '🌒'], [9.22831, 'First Quarter', '🌓'],
      [12.91963, 'Waxing Gibbous', '🌔'], [16.61096, 'Full Moon', '🌕'], [20.30228, 'Waning Gibbous', '🌖'],
      [23.99361, 'Last Quarter', '🌗'], [27.68493, 'Waning Crescent', '🌘'], [99, 'New Moon', '🌑'],
    ];
    const found = names.find(([limit]) => age < limit);
    return { age, illum, name: found[1], emoji: found[2], synodic };
  }

  function drawMoon(canvas, phase) {
    const ctx = canvas.getContext('2d');
    const w = canvas.width, r = w * 0.42, cx = w / 2, cy = w / 2;
    ctx.clearRect(0, 0, w, w);
    // glow
    const glow = ctx.createRadialGradient(cx, cy, r * 0.8, cx, cy, r * 1.25);
    glow.addColorStop(0, 'rgba(255,255,230,0.25)');
    glow.addColorStop(1, 'rgba(255,255,230,0)');
    ctx.fillStyle = glow;
    ctx.beginPath(); ctx.arc(cx, cy, r * 1.25, 0, Math.PI * 2); ctx.fill();
    // dark side
    ctx.fillStyle = '#1c1f2b';
    ctx.beginPath(); ctx.arc(cx, cy, r, 0, Math.PI * 2); ctx.fill();

    const theta = 2 * Math.PI * phase.age / phase.synodic;
    const waxing = theta < Math.PI;
    const k = Math.cos(theta);
    const lit = '#f4f1de';
    // lit half
    ctx.fillStyle = lit;
    ctx.beginPath();
    ctx.arc(cx, cy, r, -Math.PI / 2, Math.PI / 2, !waxing);
    ctx.closePath(); ctx.fill();
    // ellipse that turns the half into a crescent or gibbous shape
    ctx.fillStyle = (Math.abs(theta - Math.PI) < Math.PI / 2) ? lit : '#1c1f2b';
    ctx.beginPath();
    ctx.ellipse(cx, cy, Math.abs(k) * r, r, 0, 0, Math.PI * 2);
    ctx.fill();
    // a few craters for fun
    ctx.globalAlpha = 0.12; ctx.fillStyle = '#000';
    [[-0.3, -0.25, 0.14], [0.25, 0.1, 0.1], [-0.05, 0.35, 0.08], [0.35, -0.35, 0.06]].forEach(([x, y, s]) => {
      ctx.beginPath(); ctx.arc(cx + x * r, cy + y * r, s * r, 0, Math.PI * 2); ctx.fill();
    });
    ctx.globalAlpha = 1;
  }

  const moonCanvas = document.getElementById('moon');
  if (moonCanvas) {
    const night = new Date(by, bm - 1, bd, 21, 0, 0); // 9pm that night
    const phase = moonPhase(night);
    drawMoon(moonCanvas, phase);
    document.getElementById('moon-name').textContent = `${phase.emoji} ${phase.name}`;
    document.getElementById('moon-detail').textContent =
      `${Math.round(phase.illum * 100)}% of the moon was lit up the night you were born.`;
    D.moon = `${phase.emoji} ${phase.name}`;
  }

  // ---------- On this day (Wikipedia, through our PHP API) ----------
  fetch(`api/onthisday.php?md=${D.monthDay}&year=${D.year}`)
    .then((r) => r.json())
    .then((data) => {
      const evBox = document.getElementById('history-events');
      const sameBox = document.getElementById('history-same');
      const birthBox = document.getElementById('history-births');
      if (data.error) {
        evBox.innerHTML = `<li class="loading">${esc(data.error)}</li>`;
        birthBox.innerHTML = '';
        return;
      }
      if (data.same_year && data.same_year.length) {
        sameBox.innerHTML = `<div class="same-year"><b>🔥 The exact day you were born (${D.year}):</b><ul>` +
          data.same_year.map((e) => `<li>${esc(e.text)}</li>`).join('') + '</ul></div>';
      }
      evBox.innerHTML = data.events.map((e) =>
        `<li><span class="tl-year">${esc(e.year)}</span><span>${esc(e.text)}${e.url ? ` <a href="${esc(e.url)}" target="_blank" rel="noopener">more</a>` : ''}</span></li>`
      ).join('') || '<li class="loading">No events found.</li>';
      birthBox.innerHTML = data.births.map((b) =>
        `<li><b>${esc(b.name)}</b> <span class="twin-year">${esc(b.year)}${Number(b.year) === D.year ? ' · 🎯 same year!' : ''}</span><small>${esc(b.desc)}</small></li>`
      ).join('') || '<li class="loading">No birthday twins found.</li>';
      if (data.births[0]) D.twin = data.births[0].name;
      if (window.BTMCard) window.BTMCard.draw();
    })
    .catch(() => {
      document.getElementById('history-events').innerHTML = '<li class="loading">Could not reach the history books. Refresh to try again.</li>';
      document.getElementById('history-births').innerHTML = '';
    });

  // ---------- NASA space photo ----------
  fetch(`api/apod.php?date=${D.date}`)
    .then((r) => r.json())
    .then((a) => {
      const box = document.getElementById('apod');
      if (!a.available) {
        box.innerHTML = `<p class="tile-label">NASA's space photo from your birthday</p><p class="tile-main">🔭 Not available</p><p class="tile-sub">${esc(a.reason || '')}</p>`;
        return;
      }
      const img = a.media_type === 'image' ? a.url : (a.thumb || null);
      box.innerHTML = `<p class="tile-label">NASA's space photo from your birthday</p>
        ${img ? `<a href="${esc(a.page)}" target="_blank" rel="noopener"><img class="apod-img" src="${esc(img)}" alt="${esc(a.title)}" loading="lazy"></a>` :
          `<p><a href="${esc(a.page)}" target="_blank" rel="noopener">▶ Watch NASA's video for that day</a></p>`}
        <p class="tile-main">${esc(a.title)}</p>
        <p class="tile-sub">${esc(a.explanation)}</p>
        <p class="tiny">Credit: ${esc(a.credit)} · <a href="${esc(a.page)}" target="_blank" rel="noopener">See it on NASA's site</a></p>`;
    })
    .catch(() => {
      document.getElementById('apod').innerHTML = '<p class="tile-main">🔭 NASA is busy</p><p class="tile-sub">Refresh to try again.</p>';
    });
})();
