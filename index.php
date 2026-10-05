<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<title>RHU Kiosk — Patient Vital Signs</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet" />
<style>
  /* ============ DESIGN TOKENS ============
     --pink-deep  #E0559B  primary pink (header, swipe handle, sensor bar)
     --pink       #F472B6  main pink accent
     --pink-mid   #FBC4DE  soft pink for borders/badges
     --pink-soft  #FDF1F8  very light pink tint for backgrounds
     --white      #FFFFFF  base background
     --ink        #4A1942  deep plum-pink text (reads on white, not pure black)
     --success    #2E9E5B
     --danger     #ef1212  used for swipe-complete state (border/checkmark/text)
  ==========================================*/
  :root{
    --pink-deep:#E0559B;
    --pink-deeper:#C43D80;
    --pink:#F472B6;
    --pink-bright:#FF7AC2;
    --pink-mid:#FBC4DE;
    --pink-soft:#FDF1F8;
    --cream:#FFFFFF;
    --ink:#4A1942;
    --white:#FFFFFF;
    --success:#2E9E5B;
    --danger:#ef1212;
    --danger-deep:#C0392B;
    --err:#C0392B;

    --font-display:'Poppins', system-ui, sans-serif;
    --font-mono:'Space Mono', monospace;

    /* fluid type scale tuned for 14" (~1366–1920px) kiosk screens, still responsive below */
    --fs-hero: clamp(2.6rem, 5vw, 5rem);
    --fs-sub:  clamp(1.15rem, 1.6vw, 1.6rem);
    --fs-logo: clamp(1.4rem, 2vw, 1.9rem);
    --fs-clock: clamp(1.6rem, 2.2vw, 2.3rem);
    --fs-date: clamp(0.95rem, 1.2vw, 1.15rem);
    --fs-swipe: clamp(1.4rem, 2.1vw, 2rem);
    --fs-sensor: clamp(0.95rem, 1.2vw, 1.2rem);
    --fs-badge: clamp(0.85rem, 1.1vw, 1.05rem);
  }

  *{ box-sizing:border-box; }
  html,body{
    margin:0; padding:0; height:100%;
    background:var(--cream);
    font-family:var(--font-display);
    color:var(--ink);
    overflow:hidden;
  }

  /* subtle arched-window motif in the background, echoing the RHU/municipal facade */
  body{
    background-image:
      radial-gradient(circle at 12% 8%, var(--pink-soft) 0%, transparent 45%),
      radial-gradient(circle at 88% 92%, var(--pink-soft) 0%, transparent 45%);
    background-color:var(--cream);
  }

  /* ============ HEADER ============ */
  .header{
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding: clamp(0.9rem, 1.6vh, 1.6rem) clamp(1.4rem, 3vw, 3rem);
    background:linear-gradient(135deg, var(--pink) 0%, var(--pink-deep) 100%);
    box-shadow: 0 4px 18px rgba(224,85,155,0.25);
    position:relative;
    z-index:5;
  }

  .logo{
    display:flex;
    align-items:center;
    gap: clamp(0.7rem, 1.2vw, 1.1rem);
  }

  /* Logo is now an IMAGE slot — drop in your PNG/SVG at assets/patients/logo.png.
     Until then it shows a soft placeholder card so layout stays intact. */
  .logo-img-wrap{
    width: clamp(52px, 4.5vw, 68px);
    height: clamp(52px, 4.5vw, 68px);
    border-radius: 16px;
    background: var(--white);
    display:flex;
    align-items:center;
    justify-content:center;
    overflow:hidden;
    flex-shrink:0;
    box-shadow: 0 2px 10px rgba(0,0,0,0.15);
  }
  .logo-img-wrap img{
    width:120px;
    height:120px;
    object-fit:contain;
    display:block;
  }
  .logo-img-wrap .logo-fallback{
    font-family:var(--font-display);
    font-weight:800;
    font-size: clamp(1.1rem, 1.6vw, 1.5rem);
    color:var(--pink-deep);
  }
  .logo-name{
    font-family:var(--font-display);
    font-weight:800;
    font-size:var(--fs-logo);
    letter-spacing:0.3px;
    color:var(--white);
    line-height:1.15;
  }
  .logo-name small{
    display:block;
    font-weight:500;
    font-size: 0.5em;
    color: var(--pink-soft);
    letter-spacing:1.5px;
    text-transform:uppercase;
    margin-top:2px;
  }

  .header-right{
    display:flex;
    align-items:center;
    gap: clamp(1rem, 2vw, 2.2rem);
  }

  .clock-block{ text-align:right; }
  .clock{
    font-family:var(--font-mono);
    font-weight:700;
    font-size:var(--fs-clock);
    color:var(--white);
    line-height:1.1;
    letter-spacing:1px;
  }
  .clock-date{
    font-family:var(--font-display);
    font-weight:500;
    font-size:var(--fs-date);
    color:var(--pink-soft);
    margin-top:2px;
  }

  .ws-badge{
    display:flex;
    align-items:center;
    gap:8px;
    padding: 0.5em 1em;
    border-radius:999px;
    font-weight:600;
    font-size:var(--fs-badge);
    background:rgba(255,255,255,0.12);
    color:var(--white);
    border:1.5px solid rgba(255,255,255,0.35);
  }
  .ws-badge.ok{ background:rgba(46,158,91,0.18); border-color:var(--success); color:#eafff2; }
  .ws-badge.err{ background:rgba(192,57,43,0.2); border-color:var(--err); color:#ffe9e6; }
  .ws-dot{
    width:10px; height:10px; border-radius:50%;
    background: currentColor;
    box-shadow:0 0 0 3px rgba(255,255,255,0.15);
  }

  /* ============ MAIN / HERO ============ */
  .main{
    display:flex;
    align-items:center;
    justify-content:center;
    height: calc(100vh - 98px); /* header approx, sensor bar removed */
    flex:1;
    padding: 2vh 4vw;
  }

  .start-hero{
    text-align:center;
    max-width: 900px;
  }

  .start-title{
    font-family:var(--font-display);
    font-weight:800;
    font-size:var(--fs-hero);
    color:var(--pink-deeper);
    letter-spacing:-1px;
    line-height:1.05;
    margin-bottom: clamp(0.8rem, 1.6vh, 1.4rem);
  }
  .start-title .accent{ color:var(--pink-deep); }

  .start-subtitle{
    font-family:var(--font-display);
    font-weight:500;
    font-size:var(--fs-sub);
    color: #7A4356;
    line-height:1.5;
    margin-bottom: clamp(2.2rem, 5vh, 3.6rem);
  }

  /* ============ SWIPE CONTROL ============ */
  .swipe-wrapper{
    position:relative;
    width: min(680px, 88vw);
    height: clamp(84px, 11vh, 108px);
    margin: 0 auto;
    border-radius:999px;
    background: var(--pink-soft);
    border: 2px solid var(--pink);
    display:flex;
    align-items:center;
    overflow:hidden;
    box-shadow: inset 0 2px 6px rgba(224,85,155,0.08);
  }
  .swipe-wrapper.complete{
    background: linear-gradient(90deg, var(--pink-soft), var(--danger));
    border-color: var(--danger);
  }

  .swipe-track-text{
    position:absolute;
    top:50%;
    left:0;
    width:100%;
    text-align:center;
    transform: translateY(-50%);
    font-family:var(--font-display);
    font-weight:600;
    font-size:var(--fs-swipe);
    color: var(--pink-deeper);
    transition: opacity 0.15s linear, color 0.2s ease;
    pointer-events:none;
  }

  .swipe-arrows{
    position:absolute;
    right: clamp(1.4rem, 3.4vw, 2.4rem);
    display:flex;
    gap:2px;
    color: var(--pink);
    font-size: clamp(1.6rem, 2.4vw, 2.2rem);
    font-weight:700;
    pointer-events:none;
    animation: arrowPulse 1.2s ease-in-out infinite;
  }
  .swipe-arrows span:nth-child(2){ opacity:0.6; }
  .swipe-arrows span:nth-child(3){ opacity:0.3; }
  @keyframes arrowPulse{
    0%,100%{ transform:translateX(0); opacity:1;}
    50%{ transform:translateX(6px); opacity:0.6;}
  }

  .swipe-handle{
    position:absolute;
    left:4px;
    width: clamp(68px, 8.5vh, 96px);
    height: clamp(68px, 8.5vh, 96px);
    border-radius:50%;
    background: linear-gradient(135deg, var(--pink-bright), var(--pink-deeper));
    color:var(--white);
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:1.9rem;
    font-weight:800;
    cursor:grab;
    box-shadow: 0 4px 14px rgba(224,85,155,0.4);
    z-index:2;
    touch-action:none;
    user-select:none;
  }
  .swipe-handle.released{
    transition: left 0.3s cubic-bezier(.2,.8,.2,1);
  }
  .swipe-wrapper.complete .swipe-handle{
    background: linear-gradient(135deg, var(--danger), var(--danger-deep));
  }

  /* ============ TOAST ============ */
  .db-save-toast{
    position:fixed;
    top: clamp(90px, 12vh, 120px);
    left:50%;
    transform: translateX(-50%) translateY(-14px);
    background: var(--pink-deep);
    color:var(--white);
    font-family:var(--font-display);
    font-weight:600;
    font-size: clamp(0.95rem, 1.2vw, 1.15rem);
    padding: 0.8em 1.6em;
    border-radius:999px;
    box-shadow: 0 8px 24px rgba(224,85,155,0.35);
    opacity:0;
    pointer-events:none;
    transition: opacity 0.25s ease, transform 0.25s ease;
    z-index:50;
  }
  .db-save-toast.show{
    opacity:1;
    transform: translateX(-50%) translateY(0);
  }

  /* ============ RESPONSIVE ============ */
  @media (max-width: 720px){
    .header{ flex-wrap:wrap; gap:0.8rem; }
    .header-right{ gap:0.8rem; }
  }
</style>
</head>
<body>

<!-- HEADER -->
<header class="header">
  <div class="logo">
    <!-- LOGO IMAGE SLOT: drop your RHU/municipal logo file here.
         If the file is missing, a placeholder initial is shown automatically. -->
    <div class="logo-img-wrap">
      <img src="logo.jpg" alt="Rural Health Unit Pozorrubio Logo">
    </div>
    <div class="logo-name">RHU Kiosk<small>Rural Health Unit Pozorrubio</small></div>
  </div>
  <div class="header-right">
    <div class="clock-block">
      <div class="clock" id="clock">--:-- --</div>
      <div class="clock-date" id="clockDate">---</div>
    </div>
    <div class="ws-badge conn" id="wsBadge">
      <span class="ws-dot"></span>
      <span id="wsLabel">CONNECTING</span>
    </div>
  </div>
</header>

<!-- MAIN -->
<main class="main">
  <div id="screen-start">
    <div class="start-hero">
      <div class="start-title">Patient <span class="accent">Vital Signs</span> Station</div>
      <div class="start-subtitle">Welcome to the Rural Health Unit kiosk. Swipe below to begin your health assessment.</div>
    </div>

    <!-- Swipe button -->
    <div class="swipe-wrapper" id="swipeWrapper">
      <div class="swipe-track-text" id="swipeText">Swipe to begin</div>
      <div class="swipe-arrows">
        <span>›</span><span>›</span><span>›</span>
      </div>
      <div class="swipe-handle" id="swipeHandle">›</div>
    </div>
  </div>
</main>

<!-- Toast -->
<div class="db-save-toast" id="toast">✓ Connected</div>

<script>
/* ── CONFIG ── */
const WS_URL    = 'ws://localhost:8765';
const NEXT_PAGE = 'home.php';   // ← Directs to home.php on swipe complete

/* ── CLOCK (12-hour format with AM/PM) ── */
const DAYS   = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
const MONTHS = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
function updateClock() {
  const n = new Date();
  let hours = n.getHours();
  const meridiem = hours >= 12 ? 'PM' : 'AM';
  hours = hours % 12;
  if (hours === 0) hours = 12;
  const h = String(hours).padStart(2,'0');
  const m = String(n.getMinutes()).padStart(2,'0');
  document.getElementById('clock').textContent = `${h}:${m} ${meridiem}`;
  document.getElementById('clockDate').textContent =
    `${DAYS[n.getDay()]} ${MONTHS[n.getMonth()]} ${n.getDate()}, ${n.getFullYear()}`;
}
updateClock();
setInterval(updateClock, 1000);

/* ── TOAST ── */
let toastTimer;
function showToast(msg) {
  clearTimeout(toastTimer);
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.classList.add('show');
  toastTimer = setTimeout(() => t.classList.remove('show'), 2800);
}

/* ── NAVIGATE to home.php ── */
function goNext() {
  setTimeout(() => { window.location.href = NEXT_PAGE; }, 800);
}

/* ── SWIPE HANDLE ── */
(function () {
  const wrapper = document.getElementById('swipeWrapper');
  const handle  = document.getElementById('swipeHandle');
  const text    = document.getElementById('swipeText');

  const PAD = 4, THUMB = 68, THRESHOLD = 0.85;
  let dragging = false, startX = 0, done = false;

  function maxX() { return wrapper.offsetWidth - THUMB - PAD * 2; }

  function setPos(rawDx) {
    if (done) return;
    const x   = Math.max(0, Math.min(rawDx, maxX()));
    const pct = x / maxX();
    handle.style.left  = (PAD + x) + 'px';
    text.style.opacity = Math.max(0, 1 - pct * 2.5);
    if (pct >= THRESHOLD) complete();
  }

  function complete() {
    if (done) return;
    done = true;
    dragging = false;
    handle.style.left    = (PAD + maxX()) + 'px';
    handle.textContent   = '✓';
    handle.style.fontSize = '26px';
    wrapper.classList.add('complete');
    text.style.opacity = '1';
    text.textContent   = 'Loading…';
    text.style.color   = 'var(--danger)';
    goNext();   // ← navigates to home.php
  }

  function reset() {
    if (done) return;
    dragging = false;
    handle.classList.add('released');
    handle.style.left  = PAD + 'px';
    text.style.opacity = '1';
    setTimeout(() => { handle.classList.remove('released'); }, 300);
  }

  /* Touch */
  handle.addEventListener('touchstart', e => {
    if (done) return;
    dragging = true; startX = e.touches[0].clientX;
    handle.style.transition = ''; e.preventDefault();
  }, { passive: false });
  window.addEventListener('touchmove', e => {
    if (!dragging) return;
    setPos(e.touches[0].clientX - startX);
  }, { passive: true });
  window.addEventListener('touchend', () => {
    if (!dragging) return;
    const cur = parseFloat(handle.style.left || PAD) - PAD;
    if (cur / maxX() < THRESHOLD) reset(); else complete();
    dragging = false;
  });

  /* Mouse */
  handle.addEventListener('mousedown', e => {
    if (done) return;
    dragging = true; startX = e.clientX;
    handle.style.transition = ''; e.preventDefault();
  });
  window.addEventListener('mousemove', e => {
    if (!dragging) return;
    setPos(e.clientX - startX);
  });
  window.addEventListener('mouseup', () => {
    if (!dragging) return;
    const cur = parseFloat(handle.style.left || PAD) - PAD;
    if (cur / maxX() < THRESHOLD) reset(); else complete();
    dragging = false;
  });

  /* Keyboard fallback */
  document.addEventListener('keydown', e => {
    if (['Enter', ' ', 'ArrowRight'].includes(e.key)) complete();
  });
})();

/* ── WS BADGE HELPER (header only, sensor bar removed) ── */
function setWsBadge(state) {
  /* state: 'ok' | 'err' | 'conn' */
  const badge = document.getElementById('wsBadge');
  const label = document.getElementById('wsLabel');
  badge.className = 'ws-badge ' + state;
  label.textContent = state === 'ok' ? 'ONLINE' : state === 'conn' ? 'CONNECTING' : 'OFFLINE';
}

/* ── WEBSOCKET ── */
let ws = null, reconnectDelay = 2000, reconnectTimer = null;

function handleMsg(raw) {
  try {
    const msg = JSON.parse(raw);
    if (msg.trigger || msg.type === 'trigger') {
      showToast('👤 Presence detected — opening…');
      goNext();
    }
  } catch { /* non-JSON */ }
}

function connectWS() {
  clearTimeout(reconnectTimer);
  setWsBadge('conn');
  try { ws = new WebSocket(WS_URL); } catch { setWsBadge('err'); scheduleReconnect(); return; }

  ws.addEventListener('open', () => {
    reconnectDelay = 2000;
    setWsBadge('ok');
    showToast('✓ Sensor connected');
    ws.send(JSON.stringify({ type: 'identify', role: 'kiosk-start' }));
  });
  ws.addEventListener('message', e => handleMsg(e.data));
  ws.addEventListener('close', () => { setWsBadge('err'); scheduleReconnect(); });
  ws.addEventListener('error', () => { setWsBadge('err'); ws.close(); });
}

function scheduleReconnect() {
  reconnectTimer = setTimeout(() => {
    reconnectDelay = Math.min(reconnectDelay * 1.5, 15000);
    connectWS();
  }, reconnectDelay);
}

connectWS();
document.addEventListener('visibilitychange', () => {
  if (!document.hidden && (!ws || ws.readyState > 1)) connectWS();
});


</script>
</body>
</html>