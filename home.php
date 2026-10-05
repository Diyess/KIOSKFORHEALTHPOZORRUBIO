<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>HealthKiosk Dashboard</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>

  :root{
    --pink: #ec4899;
    --pink-light: #fce7f3;
    --pink-dark: #db2777;
    --text-dark: #1e2d3d;
    --text-muted: #6b7280;
    --bg: #fdf2f8;
    --line: #dfe3e8;
  }

  *{ box-sizing:border-box; margin:0; padding:0; }

  html, body{
    height:100%;
  }

  body{
    font-family:'Poppins', sans-serif;
    background: var(--bg);
    display:flex;
    flex-direction:column;
  }

  /* HEADER */
  .header{
    display:flex;
    align-items:center;
    justify-content:space-between;
    background: var(--pink);
    padding:18px 32px;
    color:#fff;
    flex-shrink:0;
  }

  .logo{
    display:flex;
    align-items:center;
    gap:16px;
  }

  .logo-img{
    width:56px;
    height:56px;
    border-radius:14px;
    background:#fff;
    object-fit:cover;
  }

  .logo-name{
    font-size:26px;
    font-weight:800;
    color:#fff;
    letter-spacing:0.5px;
    line-height:1.1;
  }

  .logo-sub{
    font-size:12px;
    font-weight:700;
    color:#fff;
    opacity:0.9;
    letter-spacing:1px;
  }

  .header-right{
    display:flex;
    align-items:center;
    gap:18px;
  }

  .clock-block{
    text-align:right;
  }

  .clock{
    font-size:28px;
    font-weight:800;
    color:#fff;
    letter-spacing:1px;
  }

  .clock-date{
    font-size:13px;
    font-weight:500;
    color:#fff;
    opacity:0.9;
  }

  .status-badge{
    display:flex;
    align-items:center;
    gap:8px;
    background: rgba(255,255,255,0.15);
    border:1px solid rgba(255,255,255,0.5);
    border-radius:999px;
    padding:8px 16px;
    font-size:13px;
    font-weight:700;
    color:#fff;
    letter-spacing:0.5px;
  }

  .status-dot{
    width:8px;
    height:8px;
    border-radius:50%;
    background:#fff;
  }

  /* DASHBOARD GRID */
  .dashboard{
    flex:1;
    display:grid;
    grid-template-columns: repeat(4, 1fr);
    gap:28px;
    padding:32px;
    align-content:center;
  }

  /* CARD */
  .card{
    position:relative;
    background:#fff;
    border-radius:22px;
    padding:38px 26px 30px;
    display:flex;
    flex-direction:column;
    align-items:center;
    text-align:center;
    cursor:pointer;
    box-shadow: 0 6px 18px -6px rgba(236,72,153,0.18);
    border: 1px solid rgba(236,72,153,0.08);
    overflow:hidden;
    opacity:0;
    transform:translateY(14px);
    animation: cardIn 0.5s ease forwards;
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
  }

  .card:nth-child(1){ animation-delay:0.05s; }
  .card:nth-child(2){ animation-delay:0.1s; }
  .card:nth-child(3){ animation-delay:0.15s; }
  .card:nth-child(4){ animation-delay:0.2s; }
  .card:nth-child(5){ animation-delay:0.25s; }
  .card:nth-child(6){ animation-delay:0.3s; }
  .card:nth-child(7){ animation-delay:0.35s; }
  .card:nth-child(8){ animation-delay:0.4s; }

  @keyframes cardIn{
    to{ opacity:1; transform:translateY(0); }
  }

  .card:hover{
    transform: translateY(-6px);
    box-shadow: 0 16px 28px -8px rgba(236,72,153,0.3);
    border-color: rgba(236,72,153,0.25);
  }

  .card:active{
    transform: translateY(-2px) scale(0.98);
  }

  /* numbered corner tag */
  .card-badge{
    position:absolute;
    top:18px;
    right:20px;
    width:34px;
    height:34px;
    border-radius:50%;
    background: var(--pink);
    color:#fff;
    font-size:14px;
    font-weight:700;
    display:flex;
    align-items:center;
    justify-content:center;
    box-shadow:0 2px 5px rgba(219,39,119,0.4);
  }

  .badge-dot{
    position:absolute;
    top:54px;
    right:25px;
    width:5px;
    height:5px;
    border-radius:50%;
    background: var(--pink);
    opacity:0.55;
  }

  /* decorative corner flourishes */
  .deco{
    position:absolute;
    width:40px;
    height:40px;
    opacity:0.55;
    pointer-events:none;
  }
  .deco-tl{ top:10px; left:10px; }
  .deco-br{ bottom:10px; right:10px; transform:rotate(180deg); }

  /* icon */
  .card-icon{
    width:84px;
    height:84px;
    margin-bottom:20px;
  }
  .card-icon svg{
    width:100%;
    height:100%;
    fill:none;
    stroke: var(--pink);
    stroke-width:2;
    stroke-linecap:round;
    stroke-linejoin:round;
  }

  .card .title{
    font-size:25px;
    font-weight:700;
    color:var(--text-dark);
    margin-bottom:9px;
  }

  .card .desc{
    font-size:16px;
    font-weight:500;
    color:var(--text-muted);
    line-height:1.5;
    min-height:46px;
    margin-bottom:22px;
  }

  .cta{
    margin-top:auto;
    display:inline-flex;
    align-items:center;
    gap:6px;
    background: var(--pink);
    color:#fff;
    font-size:16px;
    font-weight:600;
    padding:12px 24px;
    border-radius:999px;
    box-shadow:0 5px 12px rgba(236,72,153,0.35);
    transition: background 0.2s ease, transform 0.2s ease;
  }

  .card:hover .cta{
    background: var(--pink-dark);
    transform: translateX(2px);
  }

  /* SUMMARY CARD (dark, closing CTA) */
  .summary-card{
    position:relative;
    background: var(--text-dark);
    color:#fff;
    border-radius:22px;
    padding:38px 26px 30px;
    display:flex;
    flex-direction:column;
    align-items:center;
    text-align:center;
    cursor:pointer;
    box-shadow: 0 6px 18px -6px rgba(30,45,61,0.3);
    overflow:hidden;
    opacity:0;
    transform:translateY(14px);
    animation: cardIn 0.5s ease forwards;
    animation-delay:0.45s;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }

  .summary-card:hover{
    transform: translateY(-6px);
    box-shadow: 0 16px 28px -8px rgba(30,45,61,0.45);
  }

  .summary-card:active{
    transform: translateY(-2px) scale(0.98);
  }

  .summary-card .card-icon svg{
    stroke:#fff;
  }

  .summary-card .title{
    font-size:25px;
    font-weight:700;
    margin-bottom:9px;
  }

  .summary-card .desc{
    font-size:16px;
    font-weight:500;
    opacity:0.75;
    line-height:1.5;
    min-height:46px;
    margin-bottom:22px;
  }

  .summary-card .cta{
    background:#fff;
    color: var(--text-dark);
    box-shadow:0 5px 12px rgba(0,0,0,0.25);
  }

  .summary-card:hover .cta{
    background: var(--pink-light);
    color: var(--pink-dark);
    transform: translateX(2px);
  }

  @media (max-width:700px){
    .dashboard{ grid-template-columns: repeat(2, 1fr); }
  }

  @media (prefers-reduced-motion: reduce){
    .card, .summary-card{ animation:none; opacity:1; transform:none; }
    .card:hover, .summary-card:hover{ transform:translateY(-4px); }
  }

</style>
</head>

<body>

<header class="header">
  <div class="logo">
    <img src="logo.jpg" alt="RHU Logo" class="logo-img">
    <div>
      <div class="logo-name">RHU Kiosk</div>
      <div class="logo-sub">RURAL HEALTH UNIT POZORRUBIO</div>
    </div>
  </div>

  <div class="clock-block">
    <div class="clock" id="clock">--:--</div>
    <div class="clock-date" id="date">---</div>
  </div>
</header>

<!-- DASHBOARD -->
<div class="dashboard">

  <!-- 1. Scan ID -->
  <div class="card" onclick="go('patient/scanid.php')">
    <svg class="deco deco-tl" viewBox="0 0 40 40"><path d="M4 36C4 18 18 4 36 4" stroke="#d8dbe0" stroke-width="1.3" fill="none" stroke-linecap="round"/><circle cx="5" cy="33" r="1.5" fill="#d8dbe0"/><circle cx="9" cy="27" r="1.1" fill="#d8dbe0"/></svg>
    <svg class="deco deco-br" viewBox="0 0 40 40"><path d="M4 36C4 18 18 4 36 4" stroke="#d8dbe0" stroke-width="1.3" fill="none" stroke-linecap="round"/><circle cx="5" cy="33" r="1.5" fill="#d8dbe0"/><circle cx="9" cy="27" r="1.1" fill="#d8dbe0"/></svg>
    <div class="card-badge">01</div>
    <span class="badge-dot"></span>
    <div class="card-icon">
      <svg viewBox="0 0 64 64"><rect x="8" y="16" width="48" height="32" rx="5"/><circle cx="21" cy="32" r="6"/><line x1="33" y1="26" x2="48" y2="26"/><line x1="33" y1="34" x2="48" y2="34"/><line x1="14" y1="43" x2="26" y2="43"/></svg>
    </div>
    <div class="title">Scan ID</div>
    <div class="desc">Insert or scan your health ID card</div>
    <div class="cta">Start <span>&rarr;</span></div>
  </div>

  <!-- 2. Face Capture -->
  <div class="card" onclick="go('patient/facecapture.php')">
    <svg class="deco deco-tl" viewBox="0 0 40 40"><path d="M4 36C4 18 18 4 36 4" stroke="#d8dbe0" stroke-width="1.3" fill="none" stroke-linecap="round"/><circle cx="5" cy="33" r="1.5" fill="#d8dbe0"/><circle cx="9" cy="27" r="1.1" fill="#d8dbe0"/></svg>
    <svg class="deco deco-br" viewBox="0 0 40 40"><path d="M4 36C4 18 18 4 36 4" stroke="#d8dbe0" stroke-width="1.3" fill="none" stroke-linecap="round"/><circle cx="5" cy="33" r="1.5" fill="#d8dbe0"/><circle cx="9" cy="27" r="1.1" fill="#d8dbe0"/></svg>
    <div class="card-badge">02</div>
    <span class="badge-dot"></span>
    <div class="card-icon">
      <svg viewBox="0 0 64 64"><circle cx="32" cy="25" r="10"/><path d="M16 50c2-11 10-15 16-15s14 4 16 15"/><path d="M8 8v8M8 8h8"/><path d="M56 8v8M56 8h-8"/><path d="M8 56v-8M8 56h8"/><path d="M56 56v-8M56 56h-8"/></svg>
    </div>
    <div class="title">Face Capture</div>
    <div class="desc">Look at the camera to continue</div>
    <div class="cta">Start <span>&rarr;</span></div>
  </div>

  <!-- 3. Height -->
  <div class="card" onclick="go('measurements/height.php')">
    <svg class="deco deco-tl" viewBox="0 0 40 40"><path d="M4 36C4 18 18 4 36 4" stroke="#d8dbe0" stroke-width="1.3" fill="none" stroke-linecap="round"/><circle cx="5" cy="33" r="1.5" fill="#d8dbe0"/><circle cx="9" cy="27" r="1.1" fill="#d8dbe0"/></svg>
    <svg class="deco deco-br" viewBox="0 0 40 40"><path d="M4 36C4 18 18 4 36 4" stroke="#d8dbe0" stroke-width="1.3" fill="none" stroke-linecap="round"/><circle cx="5" cy="33" r="1.5" fill="#d8dbe0"/><circle cx="9" cy="27" r="1.1" fill="#d8dbe0"/></svg>
    <div class="card-badge">03</div>
    <span class="badge-dot"></span>
    <div class="card-icon">
      <svg viewBox="0 0 64 64"><line x1="32" y1="6" x2="32" y2="58"/><path d="M26 13l6-7 6 7"/><path d="M26 51l6 7 6-7"/><line x1="18" y1="16" x2="26" y2="16"/><line x1="18" y1="27" x2="26" y2="27"/><line x1="18" y1="38" x2="26" y2="38"/><line x1="18" y1="48" x2="26" y2="48"/></svg>
    </div>
    <div class="title">Height</div>
    <div class="desc">Stand still for measurement</div>
    <div class="cta">Start <span>&rarr;</span></div>
  </div>

  <!-- 4. Weight -->
  <div class="card" onclick="go('measurements/weight.php')">
    <svg class="deco deco-tl" viewBox="0 0 40 40"><path d="M4 36C4 18 18 4 36 4" stroke="#d8dbe0" stroke-width="1.3" fill="none" stroke-linecap="round"/><circle cx="5" cy="33" r="1.5" fill="#d8dbe0"/><circle cx="9" cy="27" r="1.1" fill="#d8dbe0"/></svg>
    <svg class="deco deco-br" viewBox="0 0 40 40"><path d="M4 36C4 18 18 4 36 4" stroke="#d8dbe0" stroke-width="1.3" fill="none" stroke-linecap="round"/><circle cx="5" cy="33" r="1.5" fill="#d8dbe0"/><circle cx="9" cy="27" r="1.1" fill="#d8dbe0"/></svg>
    <div class="card-badge">04</div>
    <span class="badge-dot"></span>
    <div class="card-icon">
      <svg viewBox="0 0 64 64"><rect x="8" y="32" width="48" height="22" rx="6"/><circle cx="32" cy="32" r="15"/><line x1="32" y1="32" x2="39" y2="23"/></svg>
    </div>
    <div class="title">Weight</div>
    <div class="desc">Step on the scale gently</div>
    <div class="cta">Start <span>&rarr;</span></div>
  </div>

  <!-- 5. SpO2 -->
  <div class="card" onclick="go('measurements/oximeter.php')">
    <svg class="deco deco-tl" viewBox="0 0 40 40"><path d="M4 36C4 18 18 4 36 4" stroke="#d8dbe0" stroke-width="1.3" fill="none" stroke-linecap="round"/><circle cx="5" cy="33" r="1.5" fill="#d8dbe0"/><circle cx="9" cy="27" r="1.1" fill="#d8dbe0"/></svg>
    <svg class="deco deco-br" viewBox="0 0 40 40"><path d="M4 36C4 18 18 4 36 4" stroke="#d8dbe0" stroke-width="1.3" fill="none" stroke-linecap="round"/><circle cx="5" cy="33" r="1.5" fill="#d8dbe0"/><circle cx="9" cy="27" r="1.1" fill="#d8dbe0"/></svg>
    <div class="card-badge">05</div>
    <span class="badge-dot"></span>
    <div class="card-icon">
      <svg viewBox="0 0 64 64"><rect x="9" y="9" width="46" height="46" rx="10"/><path d="M15 34h7l4-11 6 22 4-11h9"/></svg>
    </div>
    <div class="title">Oxygen (SpO2)</div>
    <div class="desc">Place your finger on the sensor</div>
    <div class="cta">Start <span>&rarr;</span></div>
  </div>

  <!-- 6. Temperature -->
  <div class="card" onclick="go('measurements/temperature.php')">
    <svg class="deco deco-tl" viewBox="0 0 40 40"><path d="M4 36C4 18 18 4 36 4" stroke="#d8dbe0" stroke-width="1.3" fill="none" stroke-linecap="round"/><circle cx="5" cy="33" r="1.5" fill="#d8dbe0"/><circle cx="9" cy="27" r="1.1" fill="#d8dbe0"/></svg>
    <svg class="deco deco-br" viewBox="0 0 40 40"><path d="M4 36C4 18 18 4 36 4" stroke="#d8dbe0" stroke-width="1.3" fill="none" stroke-linecap="round"/><circle cx="5" cy="33" r="1.5" fill="#d8dbe0"/><circle cx="9" cy="27" r="1.1" fill="#d8dbe0"/></svg>
    <div class="card-badge">06</div>
    <span class="badge-dot"></span>
    <div class="card-icon">
      <svg viewBox="0 0 64 64"><rect x="26" y="7" width="12" height="35" rx="6"/><circle cx="32" cy="49" r="10"/><line x1="32" y1="18" x2="32" y2="41"/></svg>
    </div>
    <div class="title">Temperature</div>
    <div class="desc">Hold steady near the sensor</div>
    <div class="cta">Start <span>&rarr;</span></div>
  </div>

  <!-- 7. Blood Pressure -->
  <div class="card" onclick="go('measurements/bloodpressure.php')">
    <svg class="deco deco-tl" viewBox="0 0 40 40"><path d="M4 36C4 18 18 4 36 4" stroke="#d8dbe0" stroke-width="1.3" fill="none" stroke-linecap="round"/><circle cx="5" cy="33" r="1.5" fill="#d8dbe0"/><circle cx="9" cy="27" r="1.1" fill="#d8dbe0"/></svg>
    <svg class="deco deco-br" viewBox="0 0 40 40"><path d="M4 36C4 18 18 4 36 4" stroke="#d8dbe0" stroke-width="1.3" fill="none" stroke-linecap="round"/><circle cx="5" cy="33" r="1.5" fill="#d8dbe0"/><circle cx="9" cy="27" r="1.1" fill="#d8dbe0"/></svg>
    <div class="card-badge">07</div>
    <span class="badge-dot"></span>
    <div class="card-icon">
      <svg viewBox="0 0 64 64"><path d="M32 53C15 42 9 30 15 21c5-7 15-6 17 3 2-9 12-10 17-3 6 9 0 21-17 32z"/></svg>
    </div>
    <div class="title">Blood Pressure</div>
    <div class="desc">Rest your arm in the cuff</div>
    <div class="cta">Start <span>&rarr;</span></div>
  </div>

  <!-- 8. Summary -->
  <div class="summary-card" onclick="go('results/summary.php')">
    <svg class="deco deco-tl" viewBox="0 0 40 40"><path d="M4 36C4 18 18 4 36 4" stroke="#5a6a7a" stroke-width="1.3" fill="none" stroke-linecap="round"/><circle cx="5" cy="33" r="1.5" fill="#5a6a7a"/><circle cx="9" cy="27" r="1.1" fill="#5a6a7a"/></svg>
    <svg class="deco deco-br" viewBox="0 0 40 40"><path d="M4 36C4 18 18 4 36 4" stroke="#5a6a7a" stroke-width="1.3" fill="none" stroke-linecap="round"/><circle cx="5" cy="33" r="1.5" fill="#5a6a7a"/><circle cx="9" cy="27" r="1.1" fill="#5a6a7a"/></svg>
    <div class="card-icon">
      <svg viewBox="0 0 64 64"><rect x="14" y="10" width="36" height="46" rx="4"/><rect x="24" y="6" width="16" height="8" rx="2"/><line x1="20" y1="27" x2="44" y2="27"/><line x1="20" y1="35" x2="44" y2="35"/><line x1="20" y1="43" x2="36" y2="43"/></svg>
    </div>
    <div class="title">Show Summary</div>
    <div class="desc">View your complete results report</div>
    <div class="cta">View <span>&rarr;</span></div>
  </div>

</div>

<script>

/* CLOCK */
function updateClock(){
  const now = new Date();

  document.getElementById("clock").textContent =
    now.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit', hour12:true});

  document.getElementById("date").textContent =
    now.toLocaleDateString('en-US',{
      weekday:'short',
      month:'short',
      day:'numeric',
      year:'numeric'
    });
}
setInterval(updateClock, 1000);
updateClock();

/* ROUTE FUNCTION */
function go(page){
  window.location.href = page;
}

</script>

</body>
</html>