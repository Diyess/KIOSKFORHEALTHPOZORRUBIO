<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>Blood Pressure</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#fdf2f8;--surface:#ffffff;--border:#f6d3e6;
  --accent:#ec4899;--accent-dark:#db2777;--accent-soft:#fce7f3;
  --text:#1e2d3d;--text3:#8fa4b8;
  --mono:'Space Mono',monospace;--font:'Poppins',sans-serif;
  --green:#16a34a;--red:#dc2626;

  /* fluid type scale tuned for 14" (~1366-1920px) kiosk screens, still responsive below */
  --fs-logo:      clamp(16px, 1.6vw, 22px);
  --fs-logo-sub:  clamp(9px, 0.8vw, 11px);
  --fs-clock:     clamp(16px, 1.4vw, 20px);
  --fs-clock-date:clamp(9px, 0.8vw, 11px);
  --fs-step-icon: clamp(40px, 5vw, 60px);
  --fs-h3:        clamp(18px, 2vw, 26px);
  --fs-p:         clamp(14px, 1.4vw, 18px);
  --fs-li:        clamp(13px, 1.2vw, 16px);
  --fs-btn:       clamp(15px, 1.5vw, 19px);
  --fs-bp-icon:   clamp(48px, 5.6vw, 68px);
  --fs-bp-val:    clamp(38px, 4.6vw, 56px);
  --fs-unit:      clamp(13px, 1.2vw, 16px);
  --fs-status:    clamp(14px, 1.4vw, 18px);
  --fs-label:     clamp(12px, 1.1vw, 14.5px);
}
*{margin:0;padding:0;box-sizing:border-box;font-family:var(--font);}
body{background:var(--bg);min-height:100vh;display:flex;flex-direction:column;}

/* ── HEADER / NAVIGATION (matches other kiosk pages) ── */
.header{
  display:flex;
  align-items:center;
  justify-content:space-between;
  background:var(--accent);
  padding:clamp(12px, 1.6vh, 18px) clamp(16px, 2.5vw, 32px);
  color:#fff;
  flex-shrink:0;
  box-shadow:0 2px 10px rgba(236,72,153,0.22);
}
.logo{display:flex;align-items:center;gap:14px;}
.logo-img{width:clamp(40px, 3.5vw, 52px);height:clamp(40px, 3.5vw, 52px);border-radius:12px;background:#fff;object-fit:cover;flex-shrink:0;}
.logo-name{font-size:var(--fs-logo);font-weight:800;color:#fff;letter-spacing:0.4px;line-height:1.1;}
.logo-sub{font-size:var(--fs-logo-sub);font-weight:700;color:#fff;opacity:0.9;letter-spacing:1px;}
.header-right{display:flex;align-items:center;gap:clamp(10px, 1.6vw, 18px);}
.clock-block{text-align:right;display:flex;flex-direction:column;}
.clock{font-family:var(--font);font-size:var(--fs-clock);font-weight:800;color:#fff;letter-spacing:0.5px;}
.clock-date{font-size:var(--fs-clock-date);color:#fff;opacity:0.9;}
.header-step{font-size:var(--fs-clock-date);color:#fff;opacity:0.85;font-weight:600;margin-top:2px;}

.main{flex:1;display:flex;justify-content:center;align-items:center;padding:clamp(16px, 2.5vh, 28px) clamp(14px, 2vw, 20px);}
.card{width:100%;max-width:600px;background:var(--surface);border-radius:16px;padding:clamp(18px, 2.4vw, 32px);position:relative;box-shadow:0 8px 24px rgba(236,72,153,0.14);}

.dot{position:absolute;right:14px;top:14px;width:11px;height:11px;border-radius:50%;background:var(--red);display:none;transition:background 0.3s;}

.step-progress-top{width:100%;height:7px;background:var(--accent-soft);border-radius:10px;overflow:hidden;margin-bottom:16px;}
.step-bar-top{height:100%;width:0%;background:var(--accent);transition:width 0.4s ease;}

.step{display:none;text-align:center;}
.step.active{display:block;}
.step-icon{font-size:var(--fs-step-icon);margin-bottom:12px;display:block;}
.step h3{margin-bottom:12px;font-size:var(--fs-h3);font-weight:700;color:var(--text);}
.step p{font-size:var(--fs-p);color:#555;margin-bottom:18px;line-height:1.55;}
.instruction-box{background:var(--accent-soft);border-radius:10px;padding:clamp(12px, 1.8vw, 18px);margin-bottom:18px;text-align:left;}
.instruction-box li{font-size:var(--fs-li);color:#5c3a4c;line-height:1.9;margin-left:18px;}

/* ── BUTTONS + CLICK ANIMATION ── */
button{
  position:relative;
  overflow:hidden;
  padding:clamp(12px, 1.4vw, 15px);
  border:none;
  border-radius:8px;
  cursor:pointer;
  width:100%;
  font-size:var(--fs-btn);
  font-weight:700;
  transition:opacity 0.2s, transform 0.15s, box-shadow 0.2s;
  -webkit-tap-highlight-color:transparent;
}
button:active{opacity:0.85;transform:scale(0.96);}
button.btn-pop{animation:btnPop 0.32s ease;}
@keyframes btnPop{
  0%{transform:scale(1);}
  35%{transform:scale(0.94);}
  65%{transform:scale(1.035);}
  100%{transform:scale(1);}
}

/* ripple that spawns at the click point */
.ripple{
  position:absolute;
  border-radius:50%;
  background:rgba(255,255,255,0.55);
  transform:scale(0);
  pointer-events:none;
  animation:rippleGrow 0.6s ease-out forwards;
}
@keyframes rippleGrow{
  to{transform:scale(1);opacity:0;}
}

.btn-next{background:var(--accent);color:#fff;}
.btn-next:hover{background:var(--accent-dark);}
.btn-start{background:var(--accent);color:#fff;}
.btn-start:hover{background:var(--accent-dark);}
.btn-done{background:var(--accent);color:#fff;}
.btn-done:hover{background:var(--accent-dark);}
button:disabled{background:#e5b8d3;color:#fff;cursor:not-allowed;opacity:0.75;}

.fade-section{transition:opacity 0.5s ease,transform 0.5s ease;}
.fade-section.hidden{opacity:0;pointer-events:none;transform:translateY(8px);}
.fade-section.visible{opacity:1;pointer-events:all;transform:translateY(0);}

.display{text-align:center;padding:clamp(16px, 2.4vw, 26px) clamp(8px, 1.4vw, 14px);}

.bp-icon{font-size:var(--fs-bp-icon);display:inline-block;animation:pulse 1.5s infinite;}
@keyframes pulse{0%,100%{transform:scale(1);}50%{transform:scale(1.1);}}
.bp-icon.paused{animation:none;}

.unit{font-size:var(--fs-unit);color:var(--text3);margin-bottom:6px;}
.status{margin-top:10px;font-size:var(--fs-status);color:#555;min-height:26px;font-weight:600;}

/* BP LABELS */
.bp-labels{display:flex;justify-content:center;align-items:flex-start;gap:clamp(16px, 2.4vw, 26px);margin-top:12px;}
.bp-label-box{text-align:center;}
.bp-label-val{font-size:var(--fs-bp-val);font-weight:700;color:var(--accent);font-family:var(--mono);}
.bp-label-name{font-size:var(--fs-label);color:var(--text3);text-transform:uppercase;letter-spacing:0.5px;font-weight:600;}
.bp-slash{font-size:var(--fs-bp-val);font-weight:300;color:var(--text3);padding-top:2px;}
.pulse-row{font-size:var(--fs-li);color:var(--text3);margin-top:8px;font-family:var(--mono);font-weight:700;}

.progress{width:100%;height:9px;background:var(--accent-soft);border-radius:10px;overflow:hidden;margin-top:18px;margin-bottom:18px;}
.bar{width:0%;height:100%;background:var(--accent);transition:width 0.6s ease;}

.success-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.3);display:none;align-items:center;justify-content:center;z-index:999;}
.success-box{background:#fff;padding:clamp(26px, 3vw, 36px) clamp(24px, 3.2vw, 40px);border-radius:16px;text-align:center;animation:pop .3s ease;box-shadow:0 8px 30px rgba(0,0,0,0.15);}
.check{width:clamp(56px, 6vw, 72px);height:clamp(56px, 6vw, 72px);border-radius:50%;border:4px solid var(--green);display:flex;align-items:center;justify-content:center;margin:0 auto 14px;animation:scaleIn 0.4s ease;}
.check::after{content:"✔";color:var(--green);font-size:clamp(26px, 2.8vw, 34px);}
.success-box p{font-size:var(--fs-p);font-weight:700;color:var(--text);}
.success-box small{font-size:var(--fs-label);color:var(--text3);}

@keyframes pop{0%{transform:scale(0.8);opacity:0;}100%{transform:scale(1);opacity:1;}}
@keyframes scaleIn{0%{transform:scale(0);}100%{transform:scale(1);}}

@media (max-width:520px){
  .header{flex-wrap:wrap;gap:10px;}
}
</style>
</head>
<body>

<header class="header">
  <div class="logo">
  <img src="PASTE_YOUR_ORIGINAL_LOGO_BASE64_SRC_HERE" alt="RHU Logo" class="logo-img">
    <div>
      <div class="logo-name">RHU Kiosk</div>
      <div class="logo-sub">RURAL HEALTH UNIT POZORRUBIO</div>
    </div>
  </div>
  <div class="header-right">
    <div class="clock-block">
      <div class="clock" id="clock">--:--</div>
      <div class="clock-date" id="date">---</div>
      <div id="headerStep" class="header-step">Step 1 of 5</div>
    </div>
  </div>
</header>

<div class="main">
<div class="card">
  <div class="dot" id="dot"></div>

  <div class="step-progress-top" id="stepProgressTop">
    <div class="step-bar-top" id="stepBar"></div>
  </div>

  <!-- STEPS -->
  <div id="stepsWrapper" class="fade-section visible">

    <div class="step active">
      <span class="step-icon"></span>
      <h3>Blood Pressure Measurement</h3>
      <p>This test measures your systolic and diastolic blood pressure along with your pulse. Please follow all instructions for an accurate reading.</p>
      <button class="btn-next" onclick="nextStep(20)">Next</button>
    </div>

    <div class="step">
      <span class="step-icon"></span>
      <h3>Step 1 — Before You Begin</h3>
      <p>Prepare yourself for the best results:</p>
      <div class="instruction-box">
        <ul>
          <li>Wear the cuff properly on your arm</li>
          <li>Do not talk during the measurement</li>
        </ul>
      </div>
      <button class="btn-next" onclick="nextStep(40)">Next</button>
    </div>

    <div class="step">
      <span class="step-icon"></span>
      <h3>Step 2 — Sit Properly</h3>
      <p>Sit properly and stay relaxed during the measurement</p>
      <div class="instruction-box">
        <ul>
          <li>Do not cross your legs</li>
          <li>Sit still — do not move during the test</li>
          <li>Keep your arm at heart level</li>
        </ul>
      </div>
      <button class="btn-next" onclick="nextStep(60)">Next</button>
    </div>

    <div class="step">
      <span class="step-icon"></span>
      <h3>Step 3 — Wear the Cuff</h3>
      <p>Place the blood pressure cuff on your <strong>right upper arm</strong>.</p>
      <div class="instruction-box">
        <ul>
          <li>Cuff should be 2–3 cm above the elbow</li>
          <li>Snug but not too tight (1 finger gap)</li>
          <li>Tube should run along the inner arm</li>
          <li>Roll up sleeve to bare your arm</li>
        </ul>
      </div>
      <button class="btn-next" onclick="nextStep(80)">Next</button>
    </div>

    <div class="step">
      <span class="step-icon"></span>
      <h3>Step 4 — Ready</h3>
      <p>Stay completely still, breathe normally, and keep your arm relaxed. The cuff will inflate and deflate automatically.</p>
      <div class="instruction-box">
        <ul>
          <li>Press the button to start the measurement</li>
        </ul>
      </div>
      <button class="btn-start" onclick="startApp()">Start Measurement</button>
    </div>

  </div>

  <!-- MAIN UI -->
  <div id="mainUI" style="display:none;" class="fade-section hidden">
    <div class="display">
      <div class="bp-icon" id="bpIcon">🩺</div>

      <div class="bp-labels">
        <div class="bp-label-box">
          <div class="bp-label-val" id="systolic">--</div>
          <div class="bp-label-name">Systolic</div>
        </div>
        <div class="bp-slash">/</div>
        <div class="bp-label-box">
          <div class="bp-label-val" id="diastolic">--</div>
          <div class="bp-label-name">Diastolic</div>
        </div>
      </div>
      <div class="unit">mmHg</div>
      <div class="pulse-row" id="pulseRow">Pulse: -- bpm</div>
      <div class="status" id="statusText">Ready</div>
      <div class="progress">
        <div class="bar" id="bar"></div>
      </div>
      <button class="btn-start" id="getDataBtn" onclick="startMeasure()" style="margin-top:10px;">Get Data</button>
      <button class="btn-done" id="doneBtn" style="display:none;margin-top:8px;" onclick="saveDone()">Done</button>
    </div>
  </div>

</div>
</div>

<div class="success-overlay" id="success">
  <div class="success-box">
    <div class="check"></div>
    <p>Saved Successfully!</p>
    <small>Redirecting to home...</small>
  </div>
</div>

<script>
/* ── CLOCK (12-hour with AM/PM) ── */
function updateClock(){
  const now=new Date();
  document.getElementById("clock").textContent=now.toLocaleTimeString([],{hour:'2-digit',minute:'2-digit',hour12:true});
  document.getElementById("date").textContent=now.toLocaleDateString('en-US',{weekday:'short',month:'short',day:'numeric'});
}
setInterval(updateClock,1000);
updateClock();

/* ── BUTTON CLICK ANIMATION (ripple + pop, applies to every button) ── */
document.addEventListener("click", function(e){
  const btn = e.target.closest("button");
  if(!btn) return;

  const rect = btn.getBoundingClientRect();
  const size = Math.max(rect.width, rect.height) * 1.6;
  const ripple = document.createElement("span");
  ripple.className = "ripple";
  ripple.style.width  = size + "px";
  ripple.style.height = size + "px";
  const x = (e.clientX ?? rect.left + rect.width/2) - rect.left - size/2;
  const y = (e.clientY ?? rect.top + rect.height/2)  - rect.top  - size/2;
  ripple.style.left = x + "px";
  ripple.style.top  = y + "px";
  btn.appendChild(ripple);
  ripple.addEventListener("animationend", () => ripple.remove());

  btn.classList.remove("btn-pop");
  void btn.offsetWidth;
  btn.classList.add("btn-pop");
}, true);

/* STEPS */
let stepIndex=0;
const steps=document.querySelectorAll(".step");
function nextStep(percent){
  steps[stepIndex].classList.remove("active");
  stepIndex++;
  if(stepIndex<steps.length) steps[stepIndex].classList.add("active");
  document.getElementById("stepBar").style.width=percent+"%";
  document.getElementById("headerStep").textContent="Step "+(stepIndex+1)+" of 5";
}

/* START APP */
function startApp(){
  const wrapper=document.getElementById("stepsWrapper");
  const mainUI=document.getElementById("mainUI");
  wrapper.classList.remove("visible");wrapper.classList.add("hidden");
  setTimeout(()=>{
    wrapper.style.display="none";
    document.getElementById("stepProgressTop").style.display="none";
    document.getElementById("headerStep").textContent="Measuring Mode";
    document.getElementById("dot").style.display="block";
    mainUI.style.display="block";
    requestAnimationFrame(()=>requestAnimationFrame(()=>{
      mainUI.classList.remove("hidden");mainUI.classList.add("visible");
    }));
  },500);
}

/* WEBSOCKET — connect quietly in the background as soon as the page loads,
   so the button doesn't have to wait for a connection before it can send. */
let ws;
let wsReady=false;

function connect(){
  ws=new WebSocket(`ws://${location.hostname}:8765`);

  ws.onopen=()=>{
    console.log("[WS] connected");
    wsReady=true;
    document.getElementById("dot").style.background="#16a34a";
  };

  ws.onerror=(err)=>{
    console.error("[WS] error", err);
  };

  ws.onclose=()=>{
    console.log("[WS] closed");
    wsReady=false;
    document.getElementById("dot").style.background="#dc2626";
    if(document.getElementById("mainUI").style.display==="block"){
      document.getElementById("statusText").textContent="Reconnecting...";
    }
    setTimeout(connect,2000);
  };

  ws.onmessage=(e)=>{
    console.log("[WS message]", e.data);
    const data=JSON.parse(e.data);

    if(data.status==="error"){
      document.getElementById("statusText").textContent=data.message || "Something went wrong. Please try again.";
      document.getElementById("bpIcon").classList.add("paused");
      document.getElementById("getDataBtn").disabled=false;
      return;
    }

    if(data.systolic) document.getElementById("systolic").textContent=data.systolic;
    if(data.diastolic) document.getElementById("diastolic").textContent=data.diastolic;
    if(data.pulse) document.getElementById("pulseRow").textContent="Pulse: "+data.pulse+" bpm";

    if(data.status==="done"){
      onDone();
      return;
    }

    // Any other status update from the server (e.g. "Connecting...",
    // "Cuff inflating...") — just reflect it live in the UI.
    if(data.status) document.getElementById("statusText").textContent=data.status;
  };
}

connect(); // open the connection right away, in the background

/* GET DATA — runs when the "Get Data" button is pressed.
   It tells server.py (via the "start_bp" action) to run the omblepy
   code and read the OMRON monitor, then the incoming data gets
   displayed on screen as it arrives (see ws.onmessage above). */
function startMeasure(){

  document.getElementById("getDataBtn").disabled=true;
  document.getElementById("statusText").textContent="Connecting to sensor...";
  document.getElementById("bar").style.width="30%";
  document.getElementById("bpIcon").classList.remove("paused");

  function sendCommand(){
    console.log("[WS] sending start_bp");
    document.getElementById("statusText").textContent="Running blood pressure check...";
    ws.send(JSON.stringify({action:"start_bp"}));

    setTimeout(()=>{
      document.getElementById("bar").style.width="60%";
    },3000);
  }

  if(wsReady && ws.readyState===WebSocket.OPEN){
    sendCommand();
  } else {
    // Socket isn't open yet (rare) — send the moment it connects.
    ws.addEventListener("open", sendCommand, { once:true });
  }
}

/* ON DONE */
function onDone(){
  document.getElementById("statusText").textContent="Measurement Complete ✓";
  document.getElementById("bpIcon").classList.add("paused");
  document.getElementById("bar").style.width="100%";
  document.getElementById("getDataBtn").style.display="none";
  document.getElementById("doneBtn").style.display="block";
}

/* SAVE */
async function saveDone(){

  const systolic = document.getElementById("systolic").textContent;
  const diastolic = document.getElementById("diastolic").textContent;
  const pulse = document.getElementById("pulseRow").textContent.replace(/[^0-9]/g,"");

  sessionStorage.setItem("systolic", systolic);
  sessionStorage.setItem("diastolic", diastolic);
  sessionStorage.setItem("pulse", pulse);

  try{

    const patient_id = sessionStorage.getItem("patient_id");

    const response = await fetch("/try/api/save_vitals.php", {
      method: "POST",
      headers: { "Content-Type":"application/json" },
      body: JSON.stringify({
        patient_id: parseInt(patient_id),
        systolic_bp: parseInt(systolic),
        diastolic_bp: parseInt(diastolic),
        pulse_bpm: parseInt(pulse)
      })
    });

    const result = await response.json();
    console.log(result);

    if(result.success){

      document.getElementById("success").style.display = "flex";

      setTimeout(()=>{
         window.location.href = "http://localhost/KioskForHealthPoz/home.php";
      },1800);

    } else {
      alert("DATABASE ERROR:\n\n" + result.message);
    }

  }catch(error){
    console.error(error);
    alert("FETCH/PHP ERROR:\n\n" + error);
  }
}
</script>
</body>
</html>