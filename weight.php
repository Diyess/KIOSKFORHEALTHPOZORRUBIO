<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>Weight Measurement</title>

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
  --fs-weight:    clamp(48px, 6vw, 72px);
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

.btn-next,
.btn-start{
  background:var(--accent);
  color:#fff;
}
.btn-next:hover,
.btn-start:hover{background:var(--accent-dark);}

.btn-done{background:var(--accent);color:#fff;}
.btn-done:hover{background:var(--accent-dark);}

button:disabled{background:#e5b8d3;color:#fff;cursor:not-allowed;opacity:0.75;}

.fade-section{transition:opacity 0.5s ease,transform 0.5s ease;}
.fade-section.hidden{opacity:0;pointer-events:none;transform:translateY(8px);}
.fade-section.visible{opacity:1;pointer-events:all;transform:translateY(0);}

.display{text-align:center;padding:clamp(16px, 2.4vw, 26px) clamp(8px, 1.4vw, 14px);}

.weight{
  font-size:var(--fs-weight);
  font-weight:700;
  color:var(--accent);
  font-family:var(--mono);
  margin-top:6px;
}

.unit{font-size:var(--fs-unit);color:var(--text3);margin-bottom:6px;}
.status{margin-top:10px;font-size:var(--fs-status);color:#555;min-height:26px;font-weight:600;}

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
    <img class="logo-img" src="data:image/jpeg;base64,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" alt="Bayan ng Pozorrubio, Pangasinan seal">
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
      <h3>Weight Measurement</h3>
      <p>This test measures your body weight accurately using a digital scale.</p>
      <button class="btn-next" onclick="nextStep(25)">Next</button>
    </div>

    <div class="step">
      <h3>Step 1 — Step Off the Scale</h3>

      <div class="instruction-box">
        <ul>
          <li>Do not stand on the scale yet</li>
          <li>Remove heavy items</li>
          <li>Wait for calibration</li>
        </ul>
      </div>

      <button class="btn-next" onclick="nextStep(50)">Next</button>
    </div>

    <div class="step">
      <h3>Step 2 — Wait</h3>

      <div class="instruction-box">
        <ul>
          <li>Watch the countdown</li>
          <li>Do not touch the scale</li>
          <li>Step on when instructed</li>
        </ul>
      </div>

      <button class="btn-next" onclick="nextStep(75)">Next</button>
    </div>

    <div class="step">
      <h3>Step 3 — Stand Upright</h3>

      <div class="instruction-box">
        <ul>
          <li>Stand still</li>
          <li>Feet centered</li>
          <li>Look straight ahead</li>
        </ul>
      </div>

      <button class="btn-next" onclick="nextStep(100)">Next</button>
    </div>

    <div class="step">
      <h3>Ready</h3>
      <p>Press Start to begin measurement.</p>

      <button class="btn-start" onclick="startApp()">
        Start Measurement
      </button>
    </div>

  </div>

  <!-- MAIN UI -->
  <div id="mainUI" style="display:none;" class="fade-section hidden">

    <div class="display">

      <div class="weight" id="weight">--.-</div>

      <div class="unit">kg</div>

      <!-- ONLY ONE STATUS -->
      <div class="status" id="statusText">
        Connecting...
      </div>

      <div class="progress">
        <div class="bar" id="bar"></div>
      </div>

      <button
        class="btn-start"
        id="startBtn"
        onclick="startMeasure()"
        style="margin-top:10px;"
        disabled>
        Start
      </button>

      <button
        class="btn-done"
        id="doneBtn"
        style="display:none;margin-top:8px;"
        onclick="saveDone()">
        Done
      </button>

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
  const now = new Date();
  document.getElementById("clock").textContent =
    now.toLocaleTimeString([],{hour:'2-digit',minute:'2-digit',hour12:true});
  document.getElementById("date").textContent =
    now.toLocaleDateString('en-US',{weekday:'short',month:'short',day:'numeric'});
}
setInterval(updateClock,1000);
updateClock();

/* ── BUTTON CLICK ANIMATION (ripple + pop, applies to every button) ── */
document.addEventListener("click", function(e){
  const btn = e.target.closest("button");
  if(!btn) return;

  // ripple positioned at the click/tap point
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

  // quick pop/bounce on the button itself
  btn.classList.remove("btn-pop");
  void btn.offsetWidth; // restart animation if clicked rapidly
  btn.classList.add("btn-pop");
}, true);


/* STEPS */

let stepIndex = 0;

const steps = document.querySelectorAll(".step");

function nextStep(percent){

  steps[stepIndex].classList.remove("active");

  stepIndex++;

  if(stepIndex < steps.length){
    steps[stepIndex].classList.add("active");
  }

  document.getElementById("stepBar").style.width =
    percent + "%";

  document.getElementById("headerStep").textContent =
    "Step " + (stepIndex+1) + " of 5";
}


/* START APP */

function startApp(){

  const wrapper =
    document.getElementById("stepsWrapper");

  const mainUI =
    document.getElementById("mainUI");

  wrapper.classList.remove("visible");
  wrapper.classList.add("hidden");

  setTimeout(()=>{

    wrapper.style.display = "none";

    document.getElementById("stepProgressTop")
      .style.display = "none";

    document.getElementById("headerStep")
      .textContent = "Measuring Mode";

    document.getElementById("dot")
      .style.display = "block";

    mainUI.style.display = "block";

    requestAnimationFrame(()=>
      requestAnimationFrame(()=>{

        mainUI.classList.remove("hidden");
        mainUI.classList.add("visible");

      })
    );

    connect();

  },500);
}


/* WEBSOCKET */

let ws;

function connect(){

  ws = new WebSocket(
    `ws://${location.hostname}:8765`
  );

  ws.onopen = ()=>{

    document.getElementById("dot")
      .style.background = "#16a34a";

    document.getElementById("statusText")
      .textContent =
      "Sensor Connected — Press Start";

    document.getElementById("startBtn")
      .disabled = false;
  };

  ws.onclose = ()=>{

    document.getElementById("dot")
      .style.background = "#dc2626";

    document.getElementById("statusText")
      .textContent = "Reconnecting...";

    document.getElementById("startBtn")
      .disabled = true;

    setTimeout(connect,2000);
  };

  ws.onmessage = (e)=>{

    const data = JSON.parse(e.data);

    if(data.status){

      // ONLY ONE TEXT
      document.getElementById("statusText")
        .textContent = data.status;

      const s = data.status.toLowerCase();

      if(s.includes("taring")){
        document.getElementById("bar").style.width = "30%";
      }
      else if(s.includes("step on")){
        document.getElementById("bar").style.width = "55%";
      }
      else if(s.includes("stability")){
        document.getElementById("bar").style.width = "75%";
      }
      else if(s.includes("done")){
        document.getElementById("bar").style.width = "100%";
      }
    }

    if(data.weight !== undefined){

      document.getElementById("weight")
        .textContent = data.weight;

      document.getElementById("bar")
        .style.width = "100%";

      document.getElementById("statusText")
        .textContent = "Done ✓";

      document.getElementById("startBtn")
        .style.display = "none";

      document.getElementById("doneBtn")
        .style.display = "block";
    }
  };
}


/* START MEASURE */

function startMeasure(){

  const btn =
    document.getElementById("startBtn");

  btn.disabled = true;

  btn.textContent = "Measuring...";

  document.getElementById("bar")
    .style.width = "15%";

  document.getElementById("statusText")
    .textContent = "Starting...";

  ws.send(
    JSON.stringify({
      action:"start_weight"
    })
  );
}


/* SAVE */

async function saveDone(){

  const weight =
    document.getElementById("weight")
    .textContent;

  sessionStorage.setItem("weight", weight);

  try{

    const patient_id =
      sessionStorage.getItem("patient_id");

    if(!patient_id){

      alert("Patient ID not found");
      return;
    }

    const response = await fetch(
      "/try/api/save_vitals.php",
      {
        method:"POST",

        headers:{
          "Content-Type":"application/json"
        },

        body: JSON.stringify({

          patient_id: parseInt(patient_id),

          weight_kg: parseFloat(weight)

        })
      }
    );

    const result = await response.json();

    if(result.success){

      if(result.record_id){

        sessionStorage.setItem(
          "record_id",
          result.record_id
        );
      }

      document.getElementById("success")
        .style.display = "flex";

      setTimeout(()=>{

        window.location.href =
          "http://localhost/KioskForHealthPoz/home.php";

      },1800);

    }else{

      alert(
        "DATABASE ERROR:\n\n" +
        result.message
      );
    }

  }catch(error){

    console.error(error);

    alert(
      "FETCH/PHP ERROR:\n\n" +
      error
    );
  }
}
</script>

</body>
</html>