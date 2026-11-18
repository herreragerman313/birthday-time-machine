// Birthday Time Machine - shared JavaScript (intro animation, sound, small helpers)
(function () {
  'use strict';

  // ---------- Whoosh sound made with the Web Audio API (no audio files needed) ----------
  function playWhoosh(duration) {
    try {
      const AudioCtx = window.AudioContext || window.webkitAudioContext;
      if (!AudioCtx) return;
      const ctx = new AudioCtx();
      const len = Math.floor(ctx.sampleRate * duration);
      const buffer = ctx.createBuffer(1, len, ctx.sampleRate);
      const data = buffer.getChannelData(0);
      for (let i = 0; i < len; i++) data[i] = Math.random() * 2 - 1;

      const noise = ctx.createBufferSource();
      noise.buffer = buffer;
      const filter = ctx.createBiquadFilter();
      filter.type = 'bandpass';
      filter.Q.value = 6;
      filter.frequency.setValueAtTime(3000, ctx.currentTime);
      filter.frequency.exponentialRampToValueAtTime(180, ctx.currentTime + duration);

      const gain = ctx.createGain();
      gain.gain.setValueAtTime(0.0001, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.5, ctx.currentTime + 0.4);
      gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + duration);

      // A falling tone under the noise
      const osc = ctx.createOscillator();
      osc.type = 'sawtooth';
      osc.frequency.setValueAtTime(880, ctx.currentTime);
      osc.frequency.exponentialRampToValueAtTime(55, ctx.currentTime + duration);
      const oscGain = ctx.createGain();
      oscGain.gain.setValueAtTime(0.0001, ctx.currentTime);
      oscGain.gain.exponentialRampToValueAtTime(0.06, ctx.currentTime + 0.3);
      oscGain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + duration);

      noise.connect(filter).connect(gain).connect(ctx.destination);
      osc.connect(oscGain).connect(ctx.destination);
      noise.start();
      osc.start();
      noise.stop(ctx.currentTime + duration);
      osc.stop(ctx.currentTime + duration);
      // A little "ding" when we land
      setTimeout(() => {
        const ding = ctx.createOscillator();
        const dg = ctx.createGain();
        ding.type = 'sine';
        ding.frequency.value = 1320;
        dg.gain.setValueAtTime(0.2, ctx.currentTime);
        dg.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.8);
        ding.connect(dg).connect(ctx.destination);
        ding.start();
        ding.stop(ctx.currentTime + 0.8);
      }, duration * 1000);
    } catch (e) { /* sound is optional */ }
  }

  // ---------- Time travel animation ----------
  // targetYear: the year to land on, done: function to run when finished
  function timeTravel(targetYear, done) {
    const warp = document.getElementById('warp');
    if (!warp) { done && done(); return; }
    const yearEl = document.getElementById('warp-year');
    const textEl = document.getElementById('warp-text');
    const startYear = new Date().getFullYear();
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const duration = reduceMotion ? 800 : 3000;

    warp.classList.add('active');
    warp.setAttribute('aria-hidden', 'false');
    if (!reduceMotion) playWhoosh(duration / 1000);

    const messages = ['Spinning up the time machine...', 'Bending space and time...', 'Passing through the decades...', 'Almost there...'];
    const t0 = performance.now();
    function frame(now) {
      const p = Math.min((now - t0) / duration, 1);
      const eased = 1 - Math.pow(1 - p, 3);
      const y = Math.round(startYear - (startYear - targetYear) * eased);
      yearEl.textContent = y;
      textEl.textContent = messages[Math.min(Math.floor(p * messages.length), messages.length - 1)];
      if (p < 1) {
        requestAnimationFrame(frame);
      } else {
        yearEl.textContent = targetYear;
        textEl.textContent = 'Arrived!';
        warp.classList.add('glitch');
        setTimeout(() => {
          done && done();
        }, 450);
      }
    }
    requestAnimationFrame(frame);
  }

  function hideWarp() {
    const warp = document.getElementById('warp');
    if (!warp) return;
    warp.classList.add('fade-out');
    setTimeout(() => {
      warp.classList.remove('active', 'glitch', 'fade-out');
      warp.setAttribute('aria-hidden', 'true');
    }, 500);
  }

  window.BTMFX = { timeTravel, hideWarp };

  // ---------- Home page: play the intro, then go to the results page ----------
  const form = document.getElementById('travel-form');
  if (form) {
    form.addEventListener('submit', function (ev) {
      const input = document.getElementById('d');
      if (!input.value) return;
      ev.preventDefault();
      const year = parseInt(input.value.slice(0, 4), 10);
      timeTravel(year, function () {
        window.location.href = 'machine.php?d=' + encodeURIComponent(input.value) + '&arrived=1';
      });
    });
  }

  // ---------- Click a share link box to copy it ----------
  document.querySelectorAll('.copy-link').forEach(function (box) {
    box.addEventListener('click', function () {
      box.select();
      if (navigator.clipboard) {
        navigator.clipboard.writeText(box.value).then(function () {
          const old = box.value;
          box.value = 'Copied! ✅';
          setTimeout(() => { box.value = old; }, 1200);
        });
      }
    });
  });
})();
