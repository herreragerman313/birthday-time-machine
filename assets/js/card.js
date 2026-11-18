// Birthday Time Machine - shareable story card drawn on a <canvas>
(function () {
  'use strict';
  const D = window.BTM;
  const canvas = document.getElementById('card');
  if (!D || !canvas) return;
  const ctx = canvas.getContext('2d');
  const W = canvas.width, H = canvas.height;

  // Colors and fonts that match each decade theme
  const looks = {
    't-60s': { bg: ['#ffd6e0', '#c1f0e3'], panel: 'rgba(255,255,255,0.75)', text: '#3d2c5e', accent: '#ff5d8f', head: 'Lobster' },
    't-70s': { bg: ['#f4a261', '#7f4f24'], panel: 'rgba(255,240,215,0.85)', text: '#4a2c13', accent: '#e76f51', date: '#5a2a0e', head: 'Righteous' },
    't-80s': { bg: ['#1a0033', '#3b0a66'], panel: 'rgba(20,0,40,0.75)', text: '#f5f5ff', accent: '#ff2bd6', head: 'Monoton' },
    't-90s': { bg: ['#008080', '#00a3a3'], panel: '#c0c0c0', text: '#000000', accent: '#000080', head: 'VT323' },
    't-00s': { bg: ['#6dd5fa', '#2980b9'], panel: 'rgba(255,255,255,0.85)', text: '#0b2545', accent: '#ff7b00', head: 'Space Grotesk' },
    't-10s': { bg: ['#f5f5f5', '#e0e0e0'], panel: '#ffffff', text: '#212121', accent: '#1e88e5', head: 'Inter' },
    't-20s': { bg: ['#0f0c29', '#302b63'], panel: 'rgba(255,255,255,0.10)', text: '#ffffff', accent: '#8be9fd', head: 'Space Grotesk' },
  };
  const L = looks[D.theme] || looks['t-20s'];

  // Draws text on up to maxLines lines, adding "..." if it doesn't fit
  function wrap(text, x, y, maxW, lineH, maxLines) {
    const words = String(text).split(' ');
    let line = '', lines = 0;
    for (let i = 0; i < words.length; i++) {
      const test = line ? line + ' ' + words[i] : words[i];
      if (ctx.measureText(test).width <= maxW || !line) { line = test; continue; }
      if (maxLines && lines === maxLines - 1) {
        let rest = words.slice(i - line.split(' ').length).join(' ');
        while (ctx.measureText(rest + '...').width > maxW && rest.length) rest = rest.slice(0, -1);
        ctx.fillText(rest.trim() + '...', x, y);
        return y + lineH;
      }
      ctx.fillText(line, x, y);
      y += lineH; lines++; line = words[i];
    }
    ctx.fillText(line, x, y);
    return y + lineH;
  }

  function roundRect(x, y, w, h, r) {
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.arcTo(x + w, y, x + w, y + h, r);
    ctx.arcTo(x + w, y + h, x, y + h, r);
    ctx.arcTo(x, y + h, x, y, r);
    ctx.arcTo(x, y, x + w, y, r);
    ctx.closePath();
  }

  function panel(y, h) {
    ctx.fillStyle = L.panel;
    roundRect(70, y, W - 140, h, D.theme === 't-90s' ? 0 : 36);
    ctx.fill();
    if (D.theme === 't-90s') {
      ctx.strokeStyle = '#ffffff'; ctx.lineWidth = 6;
      ctx.beginPath(); ctx.moveTo(70, y + h); ctx.lineTo(70, y); ctx.lineTo(W - 70, y); ctx.stroke();
      ctx.strokeStyle = '#404040';
      ctx.beginPath(); ctx.moveTo(W - 70, y); ctx.lineTo(W - 70, y + h); ctx.lineTo(70, y + h); ctx.stroke();
    }
    if (D.theme === 't-80s') {
      ctx.strokeStyle = L.accent; ctx.lineWidth = 4; ctx.shadowColor = L.accent; ctx.shadowBlur = 25;
      roundRect(70, y, W - 140, h, 36); ctx.stroke(); ctx.shadowBlur = 0;
    }
  }

  function draw() {
    // Background
    const g = ctx.createLinearGradient(0, 0, W, H);
    g.addColorStop(0, L.bg[0]); g.addColorStop(1, L.bg[1]);
    ctx.fillStyle = g; ctx.fillRect(0, 0, W, H);

    if (D.theme === 't-80s') {           // neon grid floor
      ctx.strokeStyle = 'rgba(255,43,214,0.35)'; ctx.lineWidth = 2;
      for (let i = 0; i < 20; i++) { const y = H - 380 + i * i * 1.6; ctx.beginPath(); ctx.moveTo(0, y); ctx.lineTo(W, y); ctx.stroke(); }
      for (let i = -10; i <= 10; i++) { ctx.beginPath(); ctx.moveTo(W / 2 + i * 40, H - 380); ctx.lineTo(W / 2 + i * 200, H); ctx.stroke(); }
    }
    if (D.theme === 't-70s') {           // sunburst stripes
      ['#e9c46a', '#f4a261', '#e76f51'].forEach((c, i) => { ctx.fillStyle = c; ctx.fillRect(0, 60 + i * 34, W, 22); });
    }

    ctx.textAlign = 'center';
    ctx.fillStyle = D.theme === 't-90s' ? '#ffffff' : L.text;
    if (D.theme === 't-10s') ctx.fillStyle = L.text;
    ctx.font = `700 44px "Space Grotesk", sans-serif`;
    ctx.fillText('⏱️ BIRTHDAY TIME MACHINE', W / 2, 200);

    ctx.font = `400 46px Inter, sans-serif`;
    ctx.fillText('I traveled back to', W / 2, 300);

    ctx.fillStyle = L.date || (L.accent === '#000080' ? '#ffffff' : L.accent);
    ctx.font = `${L.head === 'Monoton' ? 400 : 700} ${L.head === 'Monoton' ? 70 : 92}px "${L.head}", sans-serif`;
    const parts = D.pretty.split(', ');           // "Tuesday", "March 3", "2004"
    ctx.fillText(parts[1] || D.pretty, W / 2, 420);
    ctx.font = `${L.head === 'Monoton' ? 400 : 700} ${L.head === 'Monoton' ? 120 : 150}px "${L.head}", sans-serif`;
    ctx.fillText(parts[2] || '', W / 2, 580);

    ctx.fillStyle = D.theme === 't-90s' ? '#ffffff' : L.text;
    ctx.font = '600 40px Inter, sans-serif';
    ctx.fillText(`${D.genEmoji} ${D.generation}  ·  ${D.zodiac}  ·  ${D.chinese}`, W / 2, 660);

    // Song + movie
    let y = 720;
    panel(y, 330);
    ctx.fillStyle = L.text; ctx.textAlign = 'left';
    ctx.font = '700 36px Inter, sans-serif';
    ctx.fillText(`🎵 BIGGEST SONG OF ${D.year}`, 120, y + 70);
    ctx.font = '600 44px Inter, sans-serif';
    wrap(D.song || 'Still being decided', 120, y + 130, W - 240, 54, 2);
    ctx.font = '700 36px Inter, sans-serif';
    ctx.fillText('🎬 TOP MOVIE', 120, y + 240);
    ctx.font = '600 40px Inter, sans-serif';
    wrap(D.movie || 'Coming soon', 120, y + 295, W - 240, 50, 1);

    // Prices
    y = 1080;
    panel(y, 370);
    ctx.fillStyle = L.text;
    ctx.font = '700 36px Inter, sans-serif';
    ctx.fillText(`💸 THEN (${D.year}) VS NOW`, 120, y + 70);
    ctx.font = '500 38px Inter, sans-serif';
    (D.prices || []).slice(0, 4).forEach((p, i) => {
      const rowY = y + 140 + i * 62;
      ctx.textAlign = 'left';
      ctx.fillText(`${p[0]} ${p[1]}`, 120, rowY);
      ctx.textAlign = 'right';
      ctx.fillText(`${p[2]}  ➜  ${p[3]}`, W - 120, rowY);
    });
    ctx.textAlign = 'left';

    // Didn't exist yet
    y = 1480;
    panel(y, 280);
    ctx.fillStyle = L.text;
    ctx.font = '700 36px Inter, sans-serif';
    ctx.fillText('🚫 DIDN\'T EXIST YET', 120, y + 70);
    ctx.font = '500 36px Inter, sans-serif';
    const items = (D.notYet || []).slice(0, 6);
    if (!items.length) {
      ctx.fillText('Everything on our list was already around!', 120, y + 140);
    }
    items.forEach((t, i) => {
      const col = i % 2, row = Math.floor(i / 2);
      ctx.fillText(t.length > 24 ? t.slice(0, 23) + '...' : t, 120 + col * 440, y + 140 + row * 56);
    });

    // Footer
    ctx.textAlign = 'center';
    ctx.fillStyle = (D.theme === 't-60s' || D.theme === 't-10s') ? L.text : '#ffffff';
    ctx.font = '500 34px Inter, sans-serif';
    if (D.moon) ctx.fillText(`The moon that night: ${D.moon}`, W / 2, 1815);
    ctx.font = '600 32px "Space Grotesk", sans-serif';
    ctx.fillText('Find your day: ' + D.siteUrl.replace(/^https?:\/\//, ''), W / 2, 1870);
  }

  function filename() { return `birthday-time-machine-${D.date}.png`; }

  document.getElementById('download-card').addEventListener('click', function () {
    draw();
    const a = document.createElement('a');
    a.download = filename();
    a.href = canvas.toDataURL('image/png');
    a.click();
  });

  // Phones: use the built in share sheet when it supports files
  const shareBtn = document.getElementById('share-card');
  if (navigator.canShare && navigator.share) {
    shareBtn.hidden = false;
    shareBtn.addEventListener('click', function () {
      draw();
      canvas.toBlob(function (blob) {
        const file = new File([blob], filename(), { type: 'image/png' });
        if (navigator.canShare({ files: [file] })) {
          navigator.share({ files: [file], title: 'My Birthday Time Machine', text: 'Look what the world was like when I was born!' }).catch(() => {});
        }
      });
    });
  }

  window.BTMCard = { draw };
  (document.fonts && document.fonts.ready ? document.fonts.ready : Promise.resolve()).then(() => setTimeout(draw, 300));
})();
