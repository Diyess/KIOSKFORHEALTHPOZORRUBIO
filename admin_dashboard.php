<?php
session_start();
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}
include "db.php";

$result = $conn->query("
    SELECT hr.id, p.first_name, p.last_name, p.age, p.barangay, p.gender, p.face_image,
           hr.weight_kg, hr.height_cm, hr.bmi,
           hr.temperature_c, hr.spo2_percent, hr.systolic_bp, hr.diastolic_bp, hr.pulse_bpm,
           hr.recorded_at
    FROM health_records hr
    INNER JOIN patients p ON hr.patient_id = p.id
    ORDER BY hr.id DESC
");
$totalRecords = $result->num_rows;

$notifications = [];
$nq = mysqli_query($conn,"
    SELECT hr.id, p.first_name, p.last_name,
           hr.systolic_bp, hr.diastolic_bp, hr.temperature_c, hr.spo2_percent, hr.pulse_bpm, hr.recorded_at
    FROM health_records hr INNER JOIN patients p ON hr.patient_id = p.id
    WHERE hr.systolic_bp>=140 OR hr.temperature_c>=37.5 OR hr.spo2_percent<95 OR hr.pulse_bpm>=100
    ORDER BY hr.recorded_at DESC
");
while($row = mysqli_fetch_assoc($nq)){
    $msg = "";
    if($row['systolic_bp']>=140)       $msg=$row['first_name']." ".$row['last_name']." has HIGH BLOOD PRESSURE (".$row['systolic_bp']."/".$row['diastolic_bp']." mmHg)";
    elseif($row['temperature_c']>=37.5) $msg=$row['first_name']." ".$row['last_name']." has FEVER (".$row['temperature_c']." °C)";
    elseif($row['spo2_percent']<95)     $msg=$row['first_name']." ".$row['last_name']." has LOW SpO2 (".$row['spo2_percent']."%)";
    elseif($row['pulse_bpm']>=100)      $msg=$row['first_name']." ".$row['last_name']." has HIGH HEART RATE (".$row['pulse_bpm']." bpm)";
    $notifications[] = ["name"=>$row['first_name']." ".$row['last_name'], "alert"=>$msg, "time"=>date("M d, Y h:i A", strtotime($row['recorded_at']))];
}

$weeklyData = [];
foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $day){
    $r = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as total FROM health_records WHERE DAYNAME(recorded_at) LIKE '$day%'"));
    $weeklyData[] = intval($r['total']);
}

$highBP  = intval(mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as t FROM health_records WHERE systolic_bp>=140"))['t']);
$fever   = intval(mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as t FROM health_records WHERE temperature_c>=37.5"))['t']);
$lowSpo2 = intval(mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as t FROM health_records WHERE spo2_percent<95"))['t']);
$highHR  = intval(mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as t FROM health_records WHERE pulse_bpm>=100"))['t']);

$avg = mysqli_fetch_assoc(mysqli_query($conn,"SELECT AVG(systolic_bp) as bp, AVG(spo2_percent) as spo2, AVG(temperature_c) as temp, AVG(pulse_bpm) as hr, AVG(weight_kg) as weight, AVG(bmi) as bmi FROM health_records"));

$genderM = intval(mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as t FROM patients WHERE gender='Male'"))['t']);
$genderF = intval(mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as t FROM patients WHERE gender='Female'"))['t']);

$ageChild  = intval(mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as t FROM patients WHERE age<18"))['t']);
$ageAdult  = intval(mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as t FROM patients WHERE age>=18 AND age<60"))['t']);
$ageSenior = intval(mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as t FROM patients WHERE age>=60"))['t']);

$totalPatients = intval(mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as t FROM patients"))['t']);
$printedReports = $totalRecords;

$monthlyData = [];
$monthlyLabels = [];
for($i = 5; $i >= 0; $i--) {
    $r = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as total FROM health_records WHERE YEAR(recorded_at)=YEAR(DATE_SUB(NOW(), INTERVAL $i MONTH)) AND MONTH(recorded_at)=MONTH(DATE_SUB(NOW(), INTERVAL $i MONTH))"));
    $monthlyData[] = intval($r['total']);
    $monthlyLabels[] = date('M', strtotime("-$i months"));
}

// Today / This week / This month counts for patients page
$todayCount = intval(mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as t FROM health_records WHERE DATE(recorded_at)=CURDATE()"))['t']);
$weekCount  = intval(mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as t FROM health_records WHERE YEARWEEK(recorded_at,1)=YEARWEEK(CURDATE(),1)"))['t']);
$monthCount = intval(mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as t FROM health_records WHERE YEAR(recorded_at)=YEAR(CURDATE()) AND MONTH(recorded_at)=MONTH(CURDATE())"))['t']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HealthKiosk — Admin Panel</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
:root {
  --green-950: #4a0d2c;
  --green-900: #7a1245;
  --green-800: #9d174d;
  --green-700: #be185d;
  --green-600: #ec4899;
  --green-500: #f472b6;
  --green-400: #f9a8d4;
  --green-100: #fce7f3;
  --green-50:  #fdf2f8;
  --slate-900: #0f172a;
  --slate-800: #1e293b;
  --slate-700: #334155;
  --slate-600: #475569;
  --slate-500: #64748b;
  --slate-400: #94a3b8;
  --slate-200: #e2e8f0;
  --slate-100: #f1f5f9;
  --slate-50:  #f8fafc;
  --red-600:   #dc2626;
  --red-100:   #fee2e2;
  --amber-500: #f59e0b;
  --amber-100: #fef3c7;
  --blue-600:  #2563eb;
  --blue-100:  #dbeafe;
  --sidebar-w: 256px;
  --topbar-h:  64px;
  --radius-sm: 6px;
  --radius:    10px;
  --radius-lg: 16px;
  --radius-xl: 22px;
  --shadow-sm: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
  --shadow:    0 4px 12px rgba(0,0,0,.06);
  --shadow-lg: 0 12px 32px rgba(0,0,0,.1);
}
*, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
html, body { height:100%; font-family:'Poppins',sans-serif; font-size:15px; color:var(--slate-800); background:#fdf5f9; overflow:hidden; }
.shell { display:flex; height:100vh; }
.sidebar { width:var(--sidebar-w); flex-shrink:0; background:linear-gradient(180deg,#831843 0%,#500724 100%); display:flex; flex-direction:column; height:100vh; position:fixed; left:0; top:0; z-index:100; border-right:1px solid rgba(255,255,255,.06); }
.sidebar-brand { padding:24px 20px 20px; border-bottom:1px solid rgba(255,255,255,.06); }
.brand-mark { display:flex; align-items:center; gap:10px; margin-bottom:2px; }
.brand-square { width:44px; height:44px; background:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; padding:3px; box-shadow:0 2px 8px rgba(0,0,0,.15); flex-shrink:0; }
.brand-square img { width:100%; height:100%; object-fit:contain; border-radius:50%; }
.brand-name { font-size:19px; font-weight:700; color:#fff; letter-spacing:-.3px; }
.brand-sub { font-size:12.5px; color:rgba(255,255,255,.55); margin-left:52px; margin-top:1px; }
.nav-section-label { font-size:10px; font-weight:600; letter-spacing:1.2px; text-transform:uppercase; color:rgba(255,255,255,.3); padding:20px 20px 8px; }
.nav-link { display:flex; align-items:center; gap:10px; padding:12px 12px; margin:2px 10px; border-radius:var(--radius); cursor:pointer; color:rgba(255,255,255,.65); font-size:15px; font-weight:500; transition:background .15s,color .15s; user-select:none; position:relative; }
.nav-link:hover { background:rgba(255,255,255,.06); color:rgba(255,255,255,.9); }
.nav-link.active { background:var(--green-600); color:#fff; font-weight:600; }
.nav-link svg { width:16px; height:16px; flex-shrink:0; stroke:currentColor; fill:none; stroke-width:1.75; stroke-linecap:round; stroke-linejoin:round; }
.nav-badge { margin-left:auto; background:var(--red-600); color:#fff; font-size:10px; font-weight:700; padding:2px 6px; border-radius:999px; min-width:18px; text-align:center; }
.sidebar-footer { margin-top:auto; border-top:1px solid rgba(255,255,255,.06); padding:16px; }
.admin-card { background:rgba(255,255,255,.06); border-radius:var(--radius); padding:12px 14px; display:flex; align-items:center; gap:10px; }
.admin-avatar { width:36px; height:36px; border-radius:var(--radius-sm); background:var(--green-700); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.admin-avatar svg { width:18px; height:18px; stroke:#fff; fill:none; stroke-width:1.75; stroke-linecap:round; stroke-linejoin:round; }
.admin-name { font-size:12px; font-weight:600; color:#fff; }
.admin-role { font-size:11px; color:rgba(255,255,255,.45); margin-top:1px; }
.logout-link { display:block; text-align:center; margin-top:10px; padding:9px; border:1px solid rgba(255,255,255,.1); border-radius:var(--radius); color:rgba(255,255,255,.55); font-size:12px; font-weight:500; cursor:pointer; text-decoration:none; transition:.15s; }
.logout-link:hover { background:var(--red-600); border-color:var(--red-600); color:#fff; }
.main { margin-left:var(--sidebar-w); flex:1; display:flex; flex-direction:column; height:100vh; overflow:hidden; }
.topbar { height:var(--topbar-h); background:#fff; border-bottom:1px solid var(--slate-200); display:flex; align-items:center; padding:0 28px; gap:16px; flex-shrink:0; }
.topbar-title { font-size:21px; font-weight:700; color:var(--slate-900); letter-spacing:-.4px; }
.topbar-sub { font-size:13.5px; color:var(--slate-400); margin-left:4px; font-weight:400; }
.topbar-right { margin-left:auto; display:flex; align-items:center; gap:8px; }
.topbar-btn { width:36px; height:36px; border:1px solid var(--slate-200); background:var(--slate-50); border-radius:var(--radius); display:flex; align-items:center; justify-content:center; cursor:pointer; transition:.15s; position:relative; }
.topbar-btn:hover { border-color:var(--green-600); background:var(--green-50); }
.topbar-btn svg { width:16px; height:16px; stroke:var(--slate-600); fill:none; stroke-width:1.75; stroke-linecap:round; stroke-linejoin:round; }
.notif-dot { position:absolute; top:-3px; right:-3px; width:16px; height:16px; background:var(--red-600); border-radius:50%; border:2px solid #fff; font-size:9px; color:#fff; font-weight:700; display:flex; align-items:center; justify-content:center; }
.clock-pill { background:var(--green-900); color:#fff; padding:7px 14px; border-radius:var(--radius); font-family:'Poppins',monospace; font-size:13px; font-weight:500; letter-spacing:.5px; }
.content { flex:1; overflow-y:auto; padding:28px; scrollbar-width:thin; scrollbar-color:var(--slate-300) transparent; }
.page { display:none; }
.page.active { display:block; }
.card { background:#fff; border:1px solid var(--slate-200); border-radius:var(--radius-lg); box-shadow:var(--shadow-sm); }
.card-head { display:flex; align-items:flex-start; justify-content:space-between; padding:18px 22px; border-bottom:1px solid var(--slate-100); }
.card-title { font-size:16px; font-weight:700; color:var(--slate-800); }
.card-sub { font-size:13px; color:var(--slate-400); margin-top:2px; }
.card-body { padding:20px 22px; }
.btn-ghost { border:1px solid var(--slate-200); background:var(--slate-50); border-radius:var(--radius-sm); padding:8px 15px; font-family:'Poppins',sans-serif; font-size:13.5px; font-weight:600; color:var(--slate-600); cursor:pointer; transition:.15s; white-space:nowrap; }
.btn-ghost:hover { background:var(--green-600); color:#fff; border-color:var(--green-600); }
.btn-green { border:1px solid var(--green-600); background:var(--green-50); border-radius:var(--radius-sm); padding:8px 15px; font-family:'Poppins',sans-serif; font-size:13.5px; font-weight:700; color:var(--green-700); cursor:pointer; transition:.15s; }
.btn-green:hover { background:var(--green-600); color:#fff; }
.stat-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:24px; }
.stat-card { background:#fff; border:1px solid var(--slate-200); border-radius:var(--radius-lg); padding:22px; position:relative; overflow:hidden; transition:transform .2s,box-shadow .2s; }
.stat-card:hover { transform:translateY(-2px); box-shadow:var(--shadow); }
.stat-card::before { content:''; position:absolute; bottom:0; left:0; right:0; height:3px; }
.stat-card.s-green::before  { background:var(--green-500); }
.stat-card.s-red::before    { background:var(--red-600); }
.stat-card.s-amber::before  { background:var(--amber-500); }
.stat-card.s-blue::before   { background:var(--blue-600); }
.stat-card.s-teal::before   { background:#0d9488; }
.stat-card.s-violet::before { background:#7c3aed; }
.stat-card.s-rose::before   { background:#e11d48; }
.stat-card.s-sky::before    { background:#0284c7; }
.stat-label { font-size:12.5px; font-weight:600; text-transform:uppercase; letter-spacing:1px; color:var(--slate-400); margin-bottom:10px; }
.stat-val { font-size:42px; font-weight:800; color:var(--slate-900); letter-spacing:-1.5px; line-height:1; font-family:'Poppins',sans-serif; }
.stat-val-unit { font-size:15px; font-weight:500; color:var(--slate-400); margin-left:4px; letter-spacing:0; }
.stat-change { margin-top:10px; font-size:13px; color:var(--slate-500); }
.tbl-wrap { overflow-x:auto; }
table { width:100%; border-collapse:collapse; }
thead th { text-align:left; padding:12px 14px; font-size:12px; font-weight:700; color:var(--slate-400); text-transform:uppercase; letter-spacing:.8px; border-bottom:1px solid var(--slate-100); background:var(--slate-50); white-space:nowrap; }
tbody td { padding:14px 14px; border-bottom:1px solid var(--slate-100); font-size:14.5px; vertical-align:middle; }
tbody tr { transition:background .12s; cursor:pointer; }
tbody tr:hover { background:var(--green-50); }
tbody tr:last-child td { border-bottom:none; }
.mono { font-family:'Poppins',sans-serif; font-size:14px; font-weight:500; }
.badge { display:inline-flex; align-items:center; padding:3px 9px; border-radius:999px; font-size:11px; font-weight:700; white-space:nowrap; }
.badge-ok    { background:var(--green-100); color:#166534; }
.badge-warn  { background:var(--amber-100); color:#92400e; }
.badge-alert { background:var(--red-100);   color:var(--red-600); }
.badge-blue  { background:var(--blue-100);  color:var(--blue-600); }
.badge-gray  { background:var(--slate-100); color:var(--slate-600); }
.pt-cell { display:flex; align-items:center; gap:10px; }
.pt-av { width:34px; height:34px; border-radius:50%; flex-shrink:0; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:700; color:#fff; overflow:hidden; }
.pt-av img { width:100%; height:100%; object-fit:cover; }
.pt-name { font-weight:600; font-size:14.5px; }
.pt-sub { font-size:12.5px; color:var(--slate-400); }
.alert-row { display:flex; gap:12px; padding:14px 0; border-bottom:1px solid var(--slate-100); }
.alert-row:last-child { border-bottom:none; }
.alert-pip { width:8px; height:8px; border-radius:50%; flex-shrink:0; margin-top:5px; }
.pip-red   { background:var(--red-600); }
.pip-amber { background:var(--amber-500); }
.pip-green { background:var(--green-500); }
.alert-title { font-size:14.5px; font-weight:600; }
.alert-meta { font-size:13px; color:var(--slate-400); margin-top:2px; }
.toolbar { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:14px; align-items:center; }
.search-box { display:flex; align-items:center; gap:8px; background:#fff; border:1px solid var(--slate-200); border-radius:var(--radius); padding:9px 14px; flex:1; min-width:240px; }
.search-box svg { width:14px; height:14px; stroke:var(--slate-400); fill:none; stroke-width:2; stroke-linecap:round; }
.search-box input { border:none; background:none; outline:none; font-family:'Poppins',sans-serif; font-size:14.5px; width:100%; }
.filter-select { border:1px solid var(--slate-200); background:#fff; border-radius:var(--radius); padding:9px 12px; font-family:'Poppins',sans-serif; font-size:14px; color:var(--slate-700); cursor:pointer; outline:none; transition:.15s; }
.filter-select:focus { border-color:var(--green-500); }
/* Date range picker */
.date-input { border:1px solid var(--slate-200); background:#fff; border-radius:var(--radius); padding:8px 11px; font-family:'Poppins',sans-serif; font-size:12.5px; color:var(--slate-700); cursor:pointer; outline:none; transition:.15s; }
.date-input:focus { border-color:var(--green-500); }
/* Period tabs */
.period-tabs { display:flex; background:var(--slate-100); border-radius:var(--radius); padding:3px; gap:3px; }
.period-tab { padding:8px 16px; border-radius:var(--radius-sm); font-size:13.5px; font-weight:600; cursor:pointer; transition:.15s; color:var(--slate-500); border:none; background:none; font-family:'Poppins',sans-serif; }
.period-tab.active { background:#fff; color:var(--green-700); box-shadow:var(--shadow-sm); }
/* Export dropdown */
.export-wrap { position:relative; display:inline-block; }
.export-menu { display:none; position:absolute; top:calc(100% + 4px); right:0; background:#fff; border:1px solid var(--slate-200); border-radius:var(--radius); box-shadow:var(--shadow); z-index:50; min-width:180px; overflow:hidden; }
.export-menu.open { display:block; }
.export-item { display:block; padding:9px 16px; font-size:13px; font-weight:500; color:var(--slate-700); cursor:pointer; transition:background .12s; white-space:nowrap; }
.export-item:hover { background:var(--green-50); color:var(--green-700); }
.export-divider { height:1px; background:var(--slate-100); margin:4px 0; }
.drawer-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.25); z-index:200; backdrop-filter:blur(2px); }
.drawer-overlay.open { display:block; }
.drawer { position:fixed; top:0; right:0; bottom:0; width:440px; background:#fff; z-index:201; transform:translateX(100%); transition:transform .3s cubic-bezier(.4,0,.2,1); overflow-y:auto; box-shadow:-8px 0 48px rgba(0,0,0,.12); }
.drawer.open { transform:translateX(0); }
.drawer-head { position:sticky; top:0; background:#fff; padding:20px 22px; border-bottom:1px solid var(--slate-100); display:flex; align-items:center; gap:14px; }
.drawer-av { width:48px; height:48px; border-radius:12px; background:var(--green-700); display:flex; align-items:center; justify-content:center; font-size:18px; font-weight:700; color:#fff; flex-shrink:0; overflow:hidden; }
.drawer-av img { width:100%; height:100%; object-fit:cover; }
.drawer-name { font-size:19px; font-weight:700; letter-spacing:-.3px; }
.drawer-id { font-size:12px; color:var(--slate-400); margin-top:2px; }
.drawer-close { margin-left:auto; width:32px; height:32px; border:1px solid var(--slate-200); background:var(--slate-50); border-radius:var(--radius-sm); cursor:pointer; display:flex; align-items:center; justify-content:center; transition:.15s; font-size:14px; color:var(--slate-500); }
.drawer-close:hover { background:var(--red-100); color:var(--red-600); border-color:var(--red-100); }
.drawer-body { padding:22px; }
.drawer-section { margin-bottom:24px; }
.drawer-section-title { font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:var(--slate-400); margin-bottom:12px; }
.info-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
.info-tile { background:var(--slate-50); border:1px solid var(--slate-100); border-radius:var(--radius); padding:12px 14px; }
.info-tile-label { font-size:10px; color:var(--slate-400); text-transform:uppercase; letter-spacing:.8px; margin-bottom:4px; }
.info-tile-val { font-size:14px; font-weight:700; }
.vital-row { display:flex; align-items:center; gap:12px; padding:11px 0; border-bottom:1px solid var(--slate-100); }
.vital-row:last-child { border-bottom:none; }
.vital-name { font-size:14.5px; color:var(--slate-600); flex:1; }
.vital-val { font-family:'Poppins',sans-serif; font-size:15.5px; font-weight:600; }
.dash-grid { display:grid; grid-template-columns:1fr 340px; gap:20px; }
.col { display:flex; flex-direction:column; gap:20px; }
.chart-h200 { height:200px; }
.chart-h180 { height:180px; }
.vital-avg-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:24px; }
.vital-avg-card { background:#fff; border:1px solid var(--slate-200); border-radius:var(--radius-lg); padding:20px 22px; }
.vac-label { font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:1px; color:var(--slate-400); margin-bottom:8px; }
.vac-val { font-family:'Poppins',sans-serif; font-size:34px; font-weight:800; letter-spacing:-1px; }
.vac-unit { font-size:13px; color:var(--slate-400); margin-left:4px; }
.report-stat-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:20px; }
.analytics-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:24px; }
.settings-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
.setting-row { display:flex; align-items:center; justify-content:space-between; padding:14px 0; border-bottom:1px solid var(--slate-100); }
.setting-row:last-child { border-bottom:none; }
.setting-label { font-size:15px; font-weight:600; }
.setting-sub { font-size:13px; color:var(--slate-400); margin-top:2px; }
.toggle { width:40px; height:22px; background:var(--green-500); border-radius:999px; cursor:pointer; position:relative; flex-shrink:0; }
.toggle::after { content:''; position:absolute; left:3px; top:3px; width:16px; height:16px; background:#fff; border-radius:50%; transition:.2s; }
.toggle.off { background:var(--slate-300); }
.toggle.off::after { left:calc(100% - 19px); }
.form-input { border:1px solid var(--slate-200); border-radius:var(--radius); padding:9px 12px; font-family:'Poppins',sans-serif; font-size:13px; width:100%; outline:none; transition:.15s; }
.form-input:focus { border-color:var(--green-500); box-shadow:0 0 0 3px rgba(34,197,94,.12); }
.form-group { margin-bottom:16px; }
.form-label { font-size:12px; font-weight:600; color:var(--slate-600); margin-bottom:6px; display:block; }
.form-select { border:1px solid var(--slate-200); border-radius:var(--radius); padding:9px 12px; font-family:'Poppins',sans-serif; font-size:13px; width:100%; outline:none; transition:.15s; background:#fff; }
.form-select:focus { border-color:var(--green-500); box-shadow:0 0 0 3px rgba(34,197,94,.12); }
.modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.4); z-index:300; align-items:center; justify-content:center; backdrop-filter:blur(4px); }
.modal-overlay.open { display:flex; }
.modal { background:#fff; border-radius:var(--radius-xl); padding:32px; width:480px; box-shadow:var(--shadow-lg); animation:fadeUp .25s ease; }
@keyframes fadeUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
.modal-title { font-size:21px; font-weight:700; margin-bottom:6px; letter-spacing:-.3px; }
.modal-sub { font-size:13px; color:var(--slate-500); margin-bottom:24px; }
.modal-actions { display:flex; gap:10px; margin-top:24px; justify-content:flex-end; }
.btn-primary { background:var(--green-600); color:#fff; border:none; border-radius:var(--radius); padding:11px 22px; font-family:'Poppins',sans-serif; font-size:14.5px; font-weight:600; cursor:pointer; transition:.15s; }
.btn-primary:hover { background:var(--green-700); }
.btn-outline { background:#fff; color:var(--slate-700); border:1px solid var(--slate-200); border-radius:var(--radius); padding:11px 22px; font-family:'Poppins',sans-serif; font-size:14.5px; font-weight:600; cursor:pointer; transition:.15s; }
.btn-outline:hover { background:var(--slate-50); }
.empty-state { text-align:center; padding:48px 24px; color:var(--slate-400); font-size:13px; }
.photo-zoomable { cursor:zoom-in !important; transition:transform .15s,box-shadow .15s; }
.photo-zoomable:hover { transform:scale(1.1); box-shadow:0 4px 16px rgba(0,0,0,.2); }
.lightbox-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.85); z-index:500; align-items:center; justify-content:center; backdrop-filter:blur(8px); cursor:zoom-out; flex-direction:column; gap:16px; }
.lightbox-overlay.open { display:flex; }
.lightbox-img { max-width:min(440px,88vw); max-height:75vh; border-radius:var(--radius-xl); box-shadow:0 24px 80px rgba(0,0,0,.5); animation:lbIn .22s cubic-bezier(.4,0,.2,1); object-fit:cover; }
.lightbox-initials { width:200px; height:200px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:68px; font-weight:700; color:#fff; box-shadow:0 24px 80px rgba(0,0,0,.5); animation:lbIn .22s cubic-bezier(.4,0,.2,1); }
@keyframes lbIn { from{opacity:0;transform:scale(.8)} to{opacity:1;transform:scale(1)} }
.lightbox-close { position:absolute; top:20px; right:24px; color:rgba(255,255,255,.7); font-size:28px; cursor:pointer; line-height:1; transition:color .15s; z-index:501; }
.lightbox-close:hover { color:#fff; }
.lightbox-name { color:rgba(255,255,255,.85); font-size:15px; font-weight:600; text-shadow:0 2px 8px rgba(0,0,0,.4); letter-spacing:.2px; }
.alert-card-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.35); z-index:400; align-items:center; justify-content:center; backdrop-filter:blur(3px); }
.alert-card-overlay.open { display:flex; }
.alert-card { background:#fff; border-radius:var(--radius-xl); width:520px; max-width:94vw; box-shadow:var(--shadow-lg); animation:fadeUp .22s ease; overflow:hidden; }
.alert-card-banner { padding:22px 24px 18px; display:flex; align-items:center; gap:16px; }
.alert-card-banner.banner-red   { background:linear-gradient(135deg,#fee2e2,#fecaca); border-bottom:2px solid #fca5a5; }
.alert-card-banner.banner-amber { background:linear-gradient(135deg,#fef3c7,#fde68a); border-bottom:2px solid #fcd34d; }
.alert-card-banner.banner-blue  { background:linear-gradient(135deg,#dbeafe,#bfdbfe); border-bottom:2px solid #93c5fd; }
.alert-card-av { width:52px; height:52px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:20px; font-weight:700; color:#fff; flex-shrink:0; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,.15); }
.alert-card-av img { width:100%; height:100%; object-fit:cover; }
.alert-card-hd { flex:1; }
.alert-card-patient { font-size:17px; font-weight:700; letter-spacing:-.3px; color:var(--slate-900); }
.alert-card-type { font-size:12px; font-weight:700; margin-top:3px; text-transform:uppercase; letter-spacing:.8px; }
.alert-card-type.type-red   { color:var(--red-600); }
.alert-card-type.type-amber { color:#b45309; }
.alert-card-type.type-blue  { color:var(--blue-600); }
.alert-card-close { width:30px; height:30px; border-radius:var(--radius-sm); background:rgba(0,0,0,.08); display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:14px; color:var(--slate-600); transition:.15s; flex-shrink:0; }
.alert-card-close:hover { background:rgba(0,0,0,.15); }
.alert-card-body { padding:20px 24px 24px; }
.alert-vitals-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-bottom:16px; }
.alert-vital-tile { background:var(--slate-50); border:1px solid var(--slate-100); border-radius:var(--radius); padding:11px 14px; }
.alert-vital-tile.flagged       { background:var(--red-100); border-color:#fca5a5; }
.alert-vital-tile.flagged-amber { background:var(--amber-100); border-color:#fcd34d; }
.avt-label { font-size:9.5px; font-weight:700; text-transform:uppercase; letter-spacing:.8px; color:var(--slate-400); margin-bottom:4px; }
.avt-val { font-family:'Poppins',monospace; font-size:15px; font-weight:700; }
.avt-val.danger { color:var(--red-600); }
.avt-val.warn   { color:#b45309; }
.alert-info-row { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:14px; }
.alert-info-pill { background:var(--slate-100); border-radius:999px; padding:4px 11px; font-size:12px; font-weight:600; color:var(--slate-600); }
.alert-card-actions { display:flex; gap:8px; }
@media(max-width:1200px) { .stat-grid{grid-template-columns:repeat(2,1fr)} .dash-grid{grid-template-columns:1fr} .analytics-grid{grid-template-columns:1fr} }

/* Fit comfortably on 14" laptop screens (~1366-1440px wide, 768-900px tall) */
@media (max-width:1440px) {
  :root { --sidebar-w:220px; --topbar-h:58px; }
  html, body { font-size:13.5px; }
  .content { padding:20px; }
  .stat-card { padding:16px; }
  .stat-val { font-size:32px; }
  .card-title { font-size:14.5px; }
  .topbar-title { font-size:18px; }
  .brand-name { font-size:16px; }
  .brand-square { width:38px; height:38px; }
  .nav-link { font-size:13.5px; padding:9px 10px; }
  .dash-grid { grid-template-columns:1fr 300px; gap:16px; }
  .drawer { width:380px; }
}
@media (max-height:800px) {
  .content { padding:18px; }
  .stat-card { padding:14px; }
  .card-body { padding:14px 18px; }
  .card-head { padding:14px 18px; }
  .chart-h200 { height:160px; }
  .chart-h180 { height:150px; }
}
@media print { .sidebar,.topbar,.toolbar,.btn-ghost,.btn-green{display:none!important} .main{margin-left:0} .content{padding:0} }
</style>
</head>
<body>
<div class="shell">

<aside class="sidebar">
  <div class="sidebar-brand">
    <div class="brand-mark">
      <div class="brand-square">
        <img src="data:image/jpeg;base64,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" alt="RHU Pozorrubio Seal">
      </div>
      <span class="brand-name">RURAL HEALTH UNIT</span>
    </div>
    <div class="brand-sub">Healthkiosk Pozorrubio</div>
  </div>
  <div class="nav-section-label">Main</div>
  <div class="nav-link active" onclick="nav('dashboard',this)">
    <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
    Dashboard
  </div>
  <div class="nav-link" onclick="nav('patients',this)">
    <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
    Patients
  </div>
  <div class="nav-link" onclick="nav('vitals',this)">
    <svg viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
    Vital Monitoring
  </div>
  <div class="nav-section-label">Reports</div>
  <div class="nav-link" onclick="nav('reports',this)">
    <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
    Reports Printed
  </div>
  <div class="nav-link" onclick="nav('analytics',this)">
    <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
    Analytics
  </div>
  <div class="nav-section-label">System</div>
  <div class="nav-link" onclick="nav('notifications',this)" id="navNotif">
    <svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
    Notifications
    <span class="nav-badge" id="navNotifBadge">0</span>
  </div>
  <div class="nav-link" onclick="nav('settings',this)">
    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
    Settings
  </div>
  <div class="sidebar-footer">
    <div class="admin-card">
      <div class="admin-avatar">
        <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      </div>
      <div>
        <div class="admin-name">RHU Pozorrubio</div>
        <div class="admin-role"></div>
      </div>
    </div>
    <a href="admin_logout.php" class="logout-link">Sign Out</a>
  </div>
</aside>

<div class="main">
  <div class="topbar">
    <div>
      <span class="topbar-title" id="pageTitle">Dashboard</span>
      <span class="topbar-sub" id="pageSub">Overview &amp; Monitoring</span>
    </div>
    <div class="topbar-right">
    
      <button class="topbar-btn" title="Refresh" onclick="location.reload()">
        <svg viewBox="0 0 24 24"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
      </button>
      <div class="clock-pill" id="clock">00:00:00</div>
    </div>
  </div>

  <div class="content">

  <!-- DASHBOARD -->
  <div class="page active" id="page-dashboard">
    <div class="stat-grid">
      <div class="stat-card s-green">
        <div class="stat-label">Total Patients</div>
        <div class="stat-val"><?php echo $totalPatients; ?></div>
        <div class="stat-change">Registered in system</div>
      </div>
      <div class="stat-card s-blue">
        <div class="stat-label">Completed Scans</div>
        <div class="stat-val"><?php echo $totalRecords; ?></div>
        <div class="stat-change">Health records saved</div>
      </div>
      <div class="stat-card s-red">
        <div class="stat-label">Flagged Vitals</div>
        <div class="stat-val"><?php echo $highBP+$fever+$lowSpo2+$highHR; ?></div>
        <div class="stat-change">Require attention</div>
      </div>
      <div class="stat-card s-amber">
        <div class="stat-label">Reports Printed</div>
        <div class="stat-val"><?php echo $printedReports; ?></div>
        <div class="stat-change">Total generated</div>
      </div>
    </div>
    <div class="dash-grid">
      <div class="col">
        <div class="card">
          <div class="card-head">
            <div><div class="card-title">Recent Patient Records</div><div class="card-sub">Last 8 scan sessions</div></div>
            <button class="btn-ghost" onclick="nav('patients', document.querySelectorAll('.nav-link')[1])">View All</button>
          </div>
          <div class="tbl-wrap">
            <table>
              <thead><tr><th>Patient</th><th>Barangay</th><th>Time</th><th>BP</th><th>SpO2</th><th>Temp</th><th>Status</th></tr></thead>
              <tbody id="recentTable"></tbody>
            </table>
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
          <div class="card">
            <div class="card-head"><div><div class="card-title">Daily Volume</div><div class="card-sub">Patients this week</div></div></div>
            <div class="card-body"><div class="chart-h200"><canvas id="volChart"></canvas></div></div>
          </div>
          <div class="card">
            <div class="card-head"><div><div class="card-title">Vital Flags</div><div class="card-sub">Abnormal readings</div></div></div>
            <div class="card-body"><div class="chart-h200"><canvas id="flagChart"></canvas></div></div>
          </div>
        </div>
      </div>
      <div class="col">
        <div class="card">
          <div class="card-head">
            <div><div class="card-title">Active Alerts</div><div class="card-sub">Requires attention</div></div>
            <button class="btn-ghost" onclick="nav('notifications', document.querySelectorAll('.nav-link')[6])">All</button>
          </div>
          <div class="card-body" id="dashAlerts"></div>
        </div>
        <div class="card">
          <div class="card-head"><div><div class="card-title">Demographics</div><div class="card-sub">Gender split</div></div></div>
          <div class="card-body"><div class="chart-h180"><canvas id="genderChart"></canvas></div></div>
        </div>
        <div class="card">
          <div class="card-head"><div><div class="card-title">Age Groups</div><div class="card-sub">Patient distribution</div></div></div>
          <div class="card-body">
            <div style="display:flex;flex-direction:column;gap:10px">
              <div>
                <div style="display:flex;justify-content:space-between;margin-bottom:4px"><span style="font-size:12px;font-weight:600">Children (&lt;18)</span><span style="font-size:12px;font-family:'Poppins',monospace"><?php echo $ageChild; ?></span></div>
                <div style="height:6px;background:var(--slate-100);border-radius:999px"><div style="height:100%;width:<?php echo $totalPatients>0?round($ageChild/$totalPatients*100):0; ?>%;background:var(--blue-600);border-radius:999px"></div></div>
              </div>
              <div>
                <div style="display:flex;justify-content:space-between;margin-bottom:4px"><span style="font-size:12px;font-weight:600">Adults (18–59)</span><span style="font-size:12px;font-family:'Poppins',monospace"><?php echo $ageAdult; ?></span></div>
                <div style="height:6px;background:var(--slate-100);border-radius:999px"><div style="height:100%;width:<?php echo $totalPatients>0?round($ageAdult/$totalPatients*100):0; ?>%;background:var(--green-500);border-radius:999px"></div></div>
              </div>
              <div>
                <div style="display:flex;justify-content:space-between;margin-bottom:4px"><span style="font-size:12px;font-weight:600">Seniors (60+)</span><span style="font-size:12px;font-family:'Poppins',monospace"><?php echo $ageSenior; ?></span></div>
                <div style="height:6px;background:var(--slate-100);border-radius:999px"><div style="height:100%;width:<?php echo $totalPatients>0?round($ageSenior/$totalPatients*100):0; ?>%;background:var(--amber-500);border-radius:999px"></div></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- PATIENTS -->
  <div class="page" id="page-patients">
    <!-- Period quick-view tabs + date range -->
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;flex-wrap:wrap">
      <div class="period-tabs">
        <button class="period-tab active" id="tabAll"   onclick="setPeriod('all')">All Records</button>
        <button class="period-tab"        id="tabToday" onclick="setPeriod('today')">Today <span style="background:var(--green-100);color:var(--green-700);border-radius:999px;padding:1px 7px;font-size:10px;margin-left:4px"><?php echo $todayCount; ?></span></button>
        <button class="period-tab"        id="tabWeek"  onclick="setPeriod('week')">This Week <span style="background:var(--blue-100);color:var(--blue-600);border-radius:999px;padding:1px 7px;font-size:10px;margin-left:4px"><?php echo $weekCount; ?></span></button>
        <button class="period-tab"        id="tabMonth" onclick="setPeriod('month')">This Month <span style="background:var(--amber-100);color:#92400e;border-radius:999px;padding:1px 7px;font-size:10px;margin-left:4px"><?php echo $monthCount; ?></span></button>
      </div>
      <div style="display:flex;align-items:center;gap:6px;margin-left:auto">
        <span style="font-size:12px;color:var(--slate-400);font-weight:600">Custom:</span>
        <input type="date" class="date-input" id="dateFrom" onchange="filterByDateRange()">
        <span style="font-size:12px;color:var(--slate-400)">to</span>
        <input type="date" class="date-input" id="dateTo" onchange="filterByDateRange()">
        <button class="btn-ghost" onclick="clearDateRange()" style="padding:7px 10px;font-size:11px">✕ Clear</button>
      </div>
    </div>

    <div class="toolbar">
      <div class="search-box">
        <svg viewBox="0 0 24 24" style="stroke-width:2;stroke-linecap:round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" placeholder="Search patient name, barangay…" oninput="filterPatients(this.value)" id="patientSearch">
      </div>
      <!-- Export dropdown -->
      <div class="export-wrap" id="exportWrap">
        <button class="btn-green" onclick="toggleExportMenu()">Export ▾</button>
        <div class="export-menu" id="exportMenu">
          <div class="export-item" onclick="exportCSV('all');closeExportMenu()">Export All Records</div>
          <div class="export-item" onclick="exportCSV('today');closeExportMenu()">Export Today</div>
          <div class="export-item" onclick="exportCSV('week');closeExportMenu()">Export This Week</div>
          <div class="export-item" onclick="exportCSV('month');closeExportMenu()">Export This Month</div>
          <div class="export-divider"></div>
          <div class="export-item" onclick="exportCSV('range');closeExportMenu()">Export by Date Range</div>
          <div class="export-item" onclick="exportCSV('filtered');closeExportMenu()">Export Current View</div>
        </div>
      </div>
      <select class="filter-select" onchange="resetFilters('sf');filterByStatus(this.value)" id="sf">
        <option value="">All Status</option><option value="normal">Normal</option><option value="warning">Flagged</option>
      </select>
      <select class="filter-select" onchange="resetFilters('bpf');filterByBP(this.value)" id="bpf">
        <option value="">All BP</option><option value="normal">Normal BP</option><option value="high">High BP</option>
      </select>
      <select class="filter-select" onchange="resetFilters('tf');filterByTemp(this.value)" id="tf">
        <option value="">All Temp</option><option value="normal">Normal</option><option value="fever">Fever</option><option value="high">High Fever</option>
      </select>
      <select class="filter-select" onchange="resetFilters('sf2');filterBySpO2(this.value)" id="sf2">
        <option value="">All SpO2</option><option value="normal">Normal (≥95%)</option><option value="low">Low (90–94%)</option><option value="critical">Critical (&lt;90%)</option>
      </select>
      <select class="filter-select" onchange="resetFilters('af');filterByAddress(this.value)" id="af">
        <option value="">All Barangay</option>
        <?php
        $brgys = ["Alipangpang","Amagbagan","Balacag","Banding","Batakil","Bantugan","Bobonan","Buneg","Cablong","Casanfernandoan","Castano","Dilan","Don Benito","Haway","Imbalbalatong","Inoman","Laoac","Maambal","Malasin","Malokiat","Manaol","Nama","Nantangalan","Palacpalac","Palguyod","Poblacion District I","Poblacion District II","Poblacion District III","Poblacion District IV","Rosario","Sugcong","Talogtog","Tulnac","Villegas"];
        foreach($brgys as $b) echo "<option value=\"$b\">$b</option>\n";
        ?>
      </select>
    </div>
    <div class="card">
      <div class="card-head">
        <div><div class="card-title">Patient Records</div><div class="card-sub" id="ptCount">Loading…</div></div>
      </div>
      <div class="tbl-wrap">
        <table>
          <thead><tr><th>Patient</th><th>Barangay</th><th>Age / Gender</th><th>Weight</th><th>Height / BMI</th><th>BP</th><th>SpO2</th><th>Temp</th><th>HR</th><th>Time</th><th>Status</th></tr></thead>
          <tbody id="patientTable"></tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- VITAL MONITORING -->
  <div class="page" id="page-vitals">
    <div class="vital-avg-grid">
      <div class="vital-avg-card"><div class="vac-label">Avg Systolic BP</div><div class="vac-val"><?php echo round($avg['bp']??0,1); ?><span class="vac-unit">mmHg</span></div></div>
      <div class="vital-avg-card"><div class="vac-label">Avg SpO2</div><div class="vac-val"><?php echo round($avg['spo2']??0,1); ?><span class="vac-unit">%</span></div></div>
      <div class="vital-avg-card"><div class="vac-label">Avg Temperature</div><div class="vac-val"><?php echo round($avg['temp']??0,1); ?><span class="vac-unit">°C</span></div></div>
      <div class="vital-avg-card"><div class="vac-label">Avg Heart Rate</div><div class="vac-val"><?php echo round($avg['hr']??0,1); ?><span class="vac-unit">bpm</span></div></div>
      <div class="vital-avg-card"><div class="vac-label">Avg Weight</div><div class="vac-val"><?php echo round($avg['weight']??0,1); ?><span class="vac-unit">kg</span></div></div>
      <div class="vital-avg-card"><div class="vac-label">Avg BMI</div><div class="vac-val"><?php echo round($avg['bmi']??0,1); ?></div></div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
      <div class="card">
        <div class="card-head"><div><div class="card-title">Flagged Conditions Summary</div><div class="card-sub">Abnormal reading counts</div></div></div>
        <div class="card-body">
          <div style="display:flex;flex-direction:column;gap:14px">
            <?php
            $flags = [
              ['High Blood Pressure (≥140 mmHg)', $highBP,  '#dc2626', $totalRecords],
              ['Fever (≥37.5 °C)',                 $fever,   '#f59e0b', $totalRecords],
              ['Low SpO2 (<95%)',                  $lowSpo2, '#2563eb', $totalRecords],
              ['High Heart Rate (≥100 bpm)',        $highHR,  '#7c3aed', $totalRecords],
            ];
            foreach($flags as $f) {
              $pct = $f[3]>0 ? round($f[1]/$f[3]*100) : 0;
              echo "<div>
                <div style='display:flex;justify-content:space-between;margin-bottom:5px;'>
                  <span style='font-size:12.5px;font-weight:600'>{$f[0]}</span>
                  <span style='font-family:Poppins,sans-serif;font-size:12px'>{$f[1]} <span style='color:var(--slate-400)'>({$pct}%)</span></span>
                </div>
                <div style='height:7px;background:var(--slate-100);border-radius:999px;'>
                  <div style='height:100%;width:{$pct}%;background:{$f[2]};border-radius:999px;transition:.6s'></div>
                </div>
              </div>";
            }
            ?>
          </div>
        </div>
      </div>
      <div class="card">
        <div class="card-head"><div><div class="card-title">BP Distribution</div><div class="card-sub">Normal vs. High</div></div></div>
        <div class="card-body"><div class="chart-h200"><canvas id="bpDistChart"></canvas></div></div>
      </div>
    </div>
    <div class="card">
      <div class="card-head"><div><div class="card-title">All Patient Vitals</div><div class="card-sub">Full vital signs table</div></div></div>
      <div class="tbl-wrap">
        <table>
          <thead><tr><th>Patient</th><th>BP</th><th>SpO2</th><th>Temp</th><th>HR</th><th>Weight</th><th>BMI</th><th>Time</th><th>Status</th></tr></thead>
          <tbody id="vitalsTable"></tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- REPORTS PRINTED -->
  <div class="page" id="page-reports">
    <div class="report-stat-grid">
      <div class="stat-card s-green"><div class="stat-label">Total Reports</div><div class="stat-val"><?php echo $printedReports; ?></div><div class="stat-change">All time</div></div>
      <div class="stat-card s-blue"><div class="stat-label">This Week</div><div class="stat-val"><?php echo array_sum($weeklyData); ?></div><div class="stat-change">Mon – Sun</div></div>
      <div class="stat-card s-amber"><div class="stat-label">Flagged Reports</div><div class="stat-val"><?php echo $highBP+$fever+$lowSpo2+$highHR; ?></div><div class="stat-change">With abnormal vitals</div></div>
    </div>
    <div class="toolbar">
      <div class="search-box">
        <svg viewBox="0 0 24 24" style="stroke-width:2;stroke-linecap:round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" placeholder="Search reports…" oninput="filterReports(this.value)">
      </div>
      <select class="filter-select" onchange="filterReportStatus(this.value)">
        <option value="">All Status</option><option value="normal">Normal</option><option value="warning">Flagged</option>
      </select>
      <button class="btn-green" onclick="exportCSV('all')">Export CSV</button>
      <button class="btn-ghost" onclick="window.print()">Print Page</button>
    </div>
    <div class="card">
      <div class="card-head"><div><div class="card-title">Printed Health Reports</div><div class="card-sub" id="reportCount">All records</div></div></div>
      <div class="tbl-wrap">
        <table>
          <thead><tr><th>Patient</th><th>Barangay</th><th>Date &amp; Time</th><th>BP</th><th>SpO2</th><th>Temp</th><th>HR</th><th>Status</th></tr></thead>
          <tbody id="reportsTable"></tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ANALYTICS -->
  <div class="page" id="page-analytics">
    <div class="stat-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:24px">
      <div class="stat-card s-green"><div class="stat-label">Total Patients</div><div class="stat-val"><?php echo $totalPatients; ?></div></div>
      <div class="stat-card s-blue"><div class="stat-label">Male</div><div class="stat-val"><?php echo $genderM; ?></div></div>
      <div class="stat-card s-rose"><div class="stat-label">Female</div><div class="stat-val"><?php echo $genderF; ?></div></div>
      <div class="stat-card s-amber"><div class="stat-label">Seniors (60+)</div><div class="stat-val"><?php echo $ageSenior; ?></div></div>
    </div>
    <div class="analytics-grid">
      <div class="card">
        <div class="card-head"><div><div class="card-title">Weekly Patient Volume</div><div class="card-sub">Patients per day</div></div></div>
        <div class="card-body"><div class="chart-h200"><canvas id="analyticsVol"></canvas></div></div>
      </div>
      <div class="card">
        <div class="card-head"><div><div class="card-title">Gender Distribution</div><div class="card-sub">Male vs Female</div></div></div>
        <div class="card-body"><div class="chart-h200"><canvas id="analyticsGender"></canvas></div></div>
      </div>
      <div class="card">
        <div class="card-head"><div><div class="card-title">Vital Flags Breakdown</div><div class="card-sub">Count per condition</div></div></div>
        <div class="card-body"><div class="chart-h200"><canvas id="analyticsFlags"></canvas></div></div>
      </div>
      <div class="card">
        <div class="card-head"><div><div class="card-title">Age Group Distribution</div><div class="card-sub">Child / Adult / Senior</div></div></div>
        <div class="card-body"><div class="chart-h200"><canvas id="analyticsAge"></canvas></div></div>
      </div>
    </div>
    <div class="card">
      <div class="card-head"><div><div class="card-title">Top Barangays by Patient Count</div><div class="card-sub">Patient volume per area</div></div></div>
      <div class="card-body"><div id="barangayBars" style="display:flex;flex-direction:column;gap:10px"></div></div>
    </div>
  </div>

  <!-- NOTIFICATIONS -->
  <div class="page" id="page-notifications">
    <div class="card">
      <div class="card-head">
        <div><div class="card-title">Vital Alert Notifications</div><div class="card-sub" id="notifSubCount">Loading…</div></div>
        <button class="btn-ghost" onclick="clearNotifUI()">Clear All</button>
      </div>
      <div class="card-body" id="notifList"></div>
    </div>
  </div>

  <!-- SETTINGS -->
  <div class="page" id="page-settings">
    <div class="settings-grid">

      <!-- Kiosk Information -->
      <div class="card">
        <div class="card-head"><div><div class="card-title">🏥 Kiosk Information</div><div class="card-sub">Facility & location details</div></div></div>
        <div class="card-body">
          <div class="form-group"><label class="form-label">Facility Name</label><input class="form-input" type="text" value="RHU Pozorrubio"></div>
          <div class="form-group"><label class="form-label">Municipality</label><input class="form-input" type="text" value="Pozorrubio"></div>
          <div class="form-group"><label class="form-label">Province</label><input class="form-input" type="text" value="Pangasinan"></div>
          <div class="form-group"><label class="form-label">Kiosk Location</label><input class="form-input" type="text" value="Main Lobby"></div>
          <div class="form-group">
            <label class="form-label">Contact Number</label>
            <div style="display:flex;align-items:center;gap:0">
              <span style="background:var(--slate-100);border:1px solid var(--slate-200);border-right:none;border-radius:var(--radius) 0 0 var(--radius);padding:9px 12px;font-size:13px;color:var(--slate-500);font-weight:600;white-space:nowrap">+63</span>
              <input class="form-input" type="tel" id="contactNumber" style="border-radius:0 var(--radius) var(--radius) 0;border-left:none"
                placeholder="9XXXXXXXXX" maxlength="11" pattern="0?9[0-9]{9}"
                oninput="formatPHPhone(this)" value="">
            </div>
            <div style="font-size:11px;color:var(--slate-400);margin-top:4px">Format: 09XXXXXXXXX (11 digits)</div>
          </div>
          <button class="btn-primary" onclick="showToast('Kiosk information saved!')">Save Changes</button>
        </div>
      </div>

      <!-- Admin Account -->
      <div class="card">
        <div class="card-head"><div><div class="card-title">🔐 Admin Account</div><div class="card-sub">Credentials & security</div></div></div>
        <div class="card-body">
          <div class="form-group"><label class="form-label">Admin Username</label><input class="form-input" type="text" value="<?php echo htmlspecialchars($_SESSION['admin_username']); ?>"></div>
          <div class="form-group"><label class="form-label">Email Address</label><input class="form-input" type="email" placeholder="admin@rhu.gov.ph"></div>
          <div class="form-group"><label class="form-label">Current Password</label><input class="form-input" type="password" placeholder="••••••••"></div>
          <div class="form-group"><label class="form-label">New Password</label><input class="form-input" type="password" placeholder="••••••••"></div>
          <div class="form-group"><label class="form-label">Confirm New Password</label><input class="form-input" type="password" placeholder="••••••••"></div>
          <button class="btn-primary" onclick="showToast('Password updated successfully!')">Update Credentials</button>
        </div>
      </div>

    

      

      <!-- System Info — removed "Run Diagnostics" button -->
      <div class="card">
        <div class="card-head"><div><div class="card-title">ℹ️ System Information</div><div class="card-sub">Version & diagnostics</div></div></div>
        <div class="card-body">
          <div style="display:flex;flex-direction:column;gap:0">
            <div class="setting-row">
              <div><div class="setting-label">App Version</div><div class="setting-sub">HealthKiosk RHU</div></div>
              <span class="badge badge-ok">v2.4.1</span>
            </div>
            <div class="setting-row">
              <div><div class="setting-label">Database Status</div><div class="setting-sub">MySQL connection</div></div>
              <span class="badge badge-ok">Connected</span>
            </div>
            <div class="setting-row">
              <div><div class="setting-label">Total Records</div><div class="setting-sub">Health records in DB</div></div>
              <span class="badge badge-blue"><?php echo $totalRecords; ?></span>
            </div>
            <div class="setting-row">
              <div><div class="setting-label">Total Patients</div><div class="setting-sub">Registered patients</div></div>
              <span class="badge badge-blue"><?php echo $totalPatients; ?></span>
            </div>
            <div class="setting-row">
              <div><div class="setting-label">Last Record</div><div class="setting-sub">Most recent scan</div></div>
              <span style="font-size:11.5px;color:var(--slate-500)"><?php
                $lr = mysqli_fetch_assoc(mysqli_query($conn,"SELECT recorded_at FROM health_records ORDER BY recorded_at DESC LIMIT 1"));
                echo $lr ? date('M d, Y', strtotime($lr['recorded_at'])) : 'N/A';
              ?></span>
            </div>
            <div class="setting-row">
              <div><div class="setting-label">Scans Today</div><div class="setting-sub">Records created today</div></div>
              <span class="badge badge-blue"><?php echo $todayCount; ?></span>
            </div>
            <div class="setting-row">
              <div><div class="setting-label">Scans This Week</div><div class="setting-sub">Current calendar week</div></div>
              <span class="badge badge-blue"><?php echo $weekCount; ?></span>
            </div>
            <div class="setting-row">
              <div><div class="setting-label">Scans This Month</div><div class="setting-sub">Current calendar month</div></div>
              <span class="badge badge-blue"><?php echo $monthCount; ?></span>
            </div>
          </div>
          <div style="margin-top:16px;display:flex;gap:8px;flex-wrap:wrap">
            <button class="btn-ghost" onclick="showToast('Cache cleared!')">Clear Cache</button>
            <button class="btn-ghost" onclick="location.reload()">Restart Session</button>
          </div>
        </div>
      </div>

    </div>
  </div>

  </div><!-- /content -->
</div><!-- /main -->
</div><!-- /shell -->

<!-- PATIENT DRAWER -->
<div class="drawer-overlay" id="drawerOverlay" onclick="closeDrawer()"></div>
<div class="drawer" id="drawer">
  <div class="drawer-head">
    <div class="drawer-av" id="drawerAv" style="cursor:zoom-in" onclick="zoomDrawerPhoto()"></div>
    <div>
      <div class="drawer-name" id="drawerName">—</div>
      <div class="drawer-id" id="drawerId">—</div>
    </div>
    <button class="drawer-close" onclick="closeDrawer()">✕</button>
  </div>
  <div class="drawer-body">
    <div class="drawer-section">
      <div class="drawer-section-title">Patient Information</div>
      <div class="info-grid" id="drawerInfo"></div>
    </div>
    <div class="drawer-section">
      <div class="drawer-section-title">Vital Signs</div>
      <div id="drawerVitals"></div>
    </div>
    
  </div>
</div>


<!-- PHOTO LIGHTBOX -->
<div class="lightbox-overlay" id="lightboxOverlay" onclick="closeLightbox()">
  <span class="lightbox-close" onclick="closeLightbox()">✕</span>
  <img class="lightbox-img" id="lightboxImg" src="" style="display:none">
  <div class="lightbox-initials" id="lightboxInitials" style="display:none"></div>
  <div class="lightbox-name" id="lightboxName"></div>
</div>

<!-- ALERT PATIENT CARD -->
<div class="alert-card-overlay" id="alertCardOverlay" onclick="if(event.target===this)closeAlertCard()">
  <div class="alert-card" id="alertCard">
    <div class="alert-card-banner" id="acBanner">
      <div class="alert-card-av" id="acAv"></div>
      <div class="alert-card-hd">
        <div class="alert-card-patient" id="acName">—</div>
        <div class="alert-card-type" id="acType">—</div>
      </div>
      <div class="alert-card-close" onclick="closeAlertCard()">✕</div>
    </div>
    <div class="alert-card-body">
      <div class="alert-info-row" id="acInfoRow"></div>
      <div class="alert-vitals-grid" id="acVitals"></div>
      <div class="alert-card-actions">
        <button class="btn-primary" id="acViewBtn">View Full Record</button>
        <button class="btn-ghost" onclick="closeAlertCard()">Dismiss</button>
      </div>
    </div>
  </div>
</div>

<!-- TOAST -->
<div id="toast" style="position:fixed;bottom:28px;right:28px;background:var(--slate-900);color:#fff;padding:12px 20px;border-radius:var(--radius);font-size:13px;font-weight:500;opacity:0;transform:translateY(8px);transition:all .25s;pointer-events:none;z-index:600;box-shadow:var(--shadow-lg)"></div>

<script>
const PATIENTS = [
<?php
$result->data_seek(0);
while($row = $result->fetch_assoc()){
  $status = ($row['systolic_bp']>=140||$row['temperature_c']>=37.5||$row['spo2_percent']<95||$row['pulse_bpm']>=100) ? 'warning' : 'normal';
  $photo = !empty($row['face_image']) ? base64_encode($row['face_image']) : '';
  // Store date as ISO for JS filtering
  $isoDate = date("Y-m-d", strtotime($row['recorded_at']));
  $timeFormatted = date("M d, Y h:i A", strtotime($row['recorded_at']));
  echo "{"
    ."id:'".addslashes($row['id'])."',"
    ."name:'".addslashes($row['first_name']." ".$row['last_name'])."',"
    ."barangay:'".addslashes($row['barangay']??'')."',"
    ."age:".intval($row['age']).","
    ."gender:'".addslashes($row['gender']??'')."',"
    ."weight:".floatval($row['weight_kg']).","
    ."height:".floatval($row['height_cm']).","
    ."bmi:".floatval($row['bmi']).","
    ."bp:'".intval($row['systolic_bp'])."/".intval($row['diastolic_bp'])."',"
    ."spo2:".floatval($row['spo2_percent']).","
    ."temp:".floatval($row['temperature_c']).","
    ."hr:".intval($row['pulse_bpm']).","
    ."time:'".addslashes($timeFormatted)."',"
    ."isoDate:'$isoDate',"
    ."status:'$status',"
    ."photo:'$photo'"
    ."},\n";
}
?>
];

const ALL = PATIENTS;
let filtered = [...ALL];
let currentPatient = null;
let activePeriod = 'all';

const NOTIFICATIONS = <?php echo json_encode($notifications); ?>;
const WEEKLY        = <?php echo json_encode($weeklyData); ?>;
const HIGH_BP       = <?php echo intval($highBP); ?>;
const FEVER         = <?php echo intval($fever); ?>;
const LOW_SPO2      = <?php echo intval($lowSpo2); ?>;
const HIGH_HR       = <?php echo intval($highHR); ?>;
const GENDER_M      = <?php echo intval($genderM); ?>;
const GENDER_F      = <?php echo intval($genderF); ?>;
const AGE_CHILD     = <?php echo intval($ageChild); ?>;
const AGE_ADULT     = <?php echo intval($ageAdult); ?>;
const AGE_SENIOR    = <?php echo intval($ageSenior); ?>;

const PALETTE = ['#15803d','#0ea5e9','#7c3aed','#f59e0b','#e11d48','#0d9488'];

// ── CLOCK (12-hour) ──────────────────────────────────────────
setInterval(() => {
  document.getElementById('clock').textContent =
    new Date().toLocaleTimeString('en-PH',{hour:'2-digit',minute:'2-digit',second:'2-digit',hour12:true});
}, 1000);

// ── TOAST ────────────────────────────────────────────────────
function showToast(msg) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.style.opacity = '1'; t.style.transform = 'translateY(0)';
  setTimeout(() => { t.style.opacity='0'; t.style.transform='translateY(8px)'; }, 2800);
}

// ── PHONE FORMAT ─────────────────────────────────────────────
function formatPHPhone(input) {
  let v = input.value.replace(/\D/g,'');
  if(v.startsWith('63')) v = '0' + v.slice(2);
  if(!v.startsWith('09') && v.length > 0) {
    if(v.startsWith('9')) v = '0' + v;
  }
  if(v.length > 11) v = v.slice(0,11);
  input.value = v;
}

// ── NAV ──────────────────────────────────────────────────────
const PAGE_META = {
  dashboard:     ['Dashboard',         'Overview & Monitoring'],
  patients:      ['Patients',          'Health Records'],
  vitals:        ['Vital Monitoring',  'Average Readings & Distribution'],
  reports:       ['Reports Printed',   'All generated health reports'],
  analytics:     ['Analytics',         'Patient statistics & trends'],
  notifications: ['Notifications',     'Vital Alerts'],
  settings:      ['Settings',          'System configuration'],
};
function nav(page, el) {
  document.querySelectorAll('.nav-link').forEach(n=>n.classList.remove('active'));
  if(el) el.classList.add('active');
  document.querySelectorAll('.page').forEach(p=>p.classList.remove('active'));
  document.getElementById('page-'+page).classList.add('active');
  const m = PAGE_META[page]||[page,''];
  document.getElementById('pageTitle').textContent = m[0];
  document.getElementById('pageSub').textContent   = m[1]||'';
  if(page==='patients')      { setPeriod('all'); }
  if(page==='vitals')        renderVitalsTable();
  if(page==='reports')       renderReports(ALL);
  if(page==='analytics')     renderAnalytics();
  if(page==='notifications') renderNotifications();
}

// ── PERIOD TABS ──────────────────────────────────────────────
function getToday() { return new Date().toISOString().slice(0,10); }
function getWeekStart() {
  const d = new Date();
  const day = d.getDay(); // 0=Sun
  const diff = d.getDate() - day + (day===0?-6:1);
  return new Date(d.setDate(diff)).toISOString().slice(0,10);
}
function getMonthStart() {
  const d = new Date();
  return new Date(d.getFullYear(), d.getMonth(), 1).toISOString().slice(0,10);
}

function setPeriod(period) {
  activePeriod = period;
  ['All','Today','Week','Month'].forEach(t => {
    const el = document.getElementById('tab'+t);
    if(el) el.classList.remove('active');
  });
  const tabMap = {all:'tabAll',today:'tabToday',week:'tabWeek',month:'tabMonth'};
  if(tabMap[period]) document.getElementById(tabMap[period]).classList.add('active');

  // Clear date inputs
  document.getElementById('dateFrom').value = '';
  document.getElementById('dateTo').value   = '';

  let data;
  const today = getToday();
  const weekStart = getWeekStart();
  const monthStart = getMonthStart();

  if(period==='today')  data = ALL.filter(p=>p.isoDate===today);
  else if(period==='week')  data = ALL.filter(p=>p.isoDate>=weekStart && p.isoDate<=today);
  else if(period==='month') data = ALL.filter(p=>p.isoDate>=monthStart && p.isoDate<=today);
  else data = [...ALL];

  renderPatients(data);
  document.getElementById('patientSearch').value='';
}

function filterByDateRange() {
  const from = document.getElementById('dateFrom').value;
  const to   = document.getElementById('dateTo').value;
  if(!from && !to) { setPeriod('all'); return; }
  // Clear period tabs
  ['tabAll','tabToday','tabWeek','tabMonth'].forEach(id=>{
    const el=document.getElementById(id);
    if(el) el.classList.remove('active');
  });
  activePeriod='custom';
  let data = ALL.filter(p=>{
    if(from && p.isoDate < from) return false;
    if(to   && p.isoDate > to)   return false;
    return true;
  });
  renderPatients(data);
}

function clearDateRange() {
  document.getElementById('dateFrom').value='';
  document.getElementById('dateTo').value='';
  setPeriod('all');
}

// ── EXPORT CSV (period-aware) ─────────────────────────────────
function getDataForExport(mode) {
  const today = getToday();
  const weekStart = getWeekStart();
  const monthStart = getMonthStart();
  const from = document.getElementById('dateFrom')?.value||'';
  const to   = document.getElementById('dateTo')?.value||'';

  if(mode==='today')    return ALL.filter(p=>p.isoDate===today);
  if(mode==='week')     return ALL.filter(p=>p.isoDate>=weekStart && p.isoDate<=today);
  if(mode==='month')    return ALL.filter(p=>p.isoDate>=monthStart && p.isoDate<=today);
  if(mode==='range')    return ALL.filter(p=>(!from||p.isoDate>=from)&&(!to||p.isoDate<=to));
  if(mode==='filtered') return filtered;
  return ALL;
}
function exportCSV(mode='all') {
  const data = getDataForExport(mode);
  if(!data.length){ showToast('No records in this period to export.'); return; }
  const rows=[['ID','Name','Barangay','Age','Gender','Weight','Height','BMI','BP','SpO2','Temp','HR','Time','Status']];
  data.forEach(p=>rows.push([p.id,p.name,p.barangay,p.age,p.gender,p.weight,p.height,p.bmi,p.bp,p.spo2,p.temp,p.hr,p.time,p.status]));
  const csv=rows.map(r=>r.map(v=>`"${v}"`).join(',')).join('\n');
  const suffix = mode==='all'?'all':mode==='filtered'?'current_view':mode;
  const a=document.createElement('a');
  a.href=URL.createObjectURL(new Blob([csv],{type:'text/csv'}));
  a.download=`health_records_${suffix}.csv`;
  a.click();
  showToast(`CSV exported (${data.length} records)!`);
}

// ── EXPORT DROPDOWN ──────────────────────────────────────────
function toggleExportMenu() {
  document.getElementById('exportMenu').classList.toggle('open');
}
function closeExportMenu() {
  document.getElementById('exportMenu').classList.remove('open');
}
document.addEventListener('click', e=>{
  const wrap = document.getElementById('exportWrap');
  if(wrap && !wrap.contains(e.target)) closeExportMenu();
});

// ── AVATAR ───────────────────────────────────────────────────
function avatarEl(p, size=34) {
  const clickHandler = `onclick="event.stopPropagation();openLightbox(ALL.find(x=>x.id=='${p.id}'))" class="photo-zoomable"`;
  if(p.photo) return `<div class="pt-av" style="width:${size}px;height:${size}px;cursor:zoom-in" ${clickHandler}><img src="data:image/jpeg;base64,${p.photo}" style="width:100%;height:100%;object-fit:cover"></div>`;
  const initials = p.name.split(' ').map(w=>w[0]).slice(0,2).join('').toUpperCase();
  const col = PALETTE[(p.id.charCodeAt(0)||0) % PALETTE.length];
  return `<div class="pt-av" style="width:${size}px;height:${size}px;background:${col}" ${clickHandler}>${initials}</div>`;
}

// ── LIGHTBOX ─────────────────────────────────────────────────
function openLightbox(p) {
  if(!p) return;
  const overlay=document.getElementById('lightboxOverlay'),
        img=document.getElementById('lightboxImg'),
        ini=document.getElementById('lightboxInitials');
  document.getElementById('lightboxName').textContent=p.name;
  if(p.photo){ img.src='data:image/jpeg;base64,'+p.photo; img.style.display='block'; ini.style.display='none'; }
  else {
    const initials=p.name.split(' ').map(w=>w[0]).slice(0,2).join('').toUpperCase();
    ini.textContent=initials; ini.style.background=PALETTE[(p.id.charCodeAt(0)||0)%PALETTE.length];
    ini.style.display='flex'; img.style.display='none';
  }
  overlay.classList.add('open');
}
function closeLightbox(){ document.getElementById('lightboxOverlay').classList.remove('open'); }
function zoomDrawerPhoto(){ if(currentPatient) openLightbox(currentPatient); }
document.addEventListener('keydown', e=>{
  if(e.key==='Escape'){ closeLightbox(); closeAlertCard(); closePrintModal(); }
});

// ── ALERT CARD ───────────────────────────────────────────────
function openAlertCard(patientName) {
  const p = ALL.find(x=>x.name.toLowerCase()===patientName.toLowerCase());
  if(!p) return;
  let type='', bannerClass='', typeClass='';
  if(parseInt(p.bp)>=140)  { type='⚠ High Blood Pressure'; bannerClass='banner-red';   typeClass='type-red'; }
  else if(p.temp>=37.5)    { type='🌡 Fever Detected';      bannerClass='banner-amber'; typeClass='type-amber'; }
  else if(p.spo2<95)       { type='💧 Low SpO2 Level';      bannerClass='banner-blue';  typeClass='type-blue'; }
  else if(p.hr>=100)       { type='❤ High Heart Rate';      bannerClass='banner-red';   typeClass='type-red'; }
  const banner=document.getElementById('acBanner');
  banner.className='alert-card-banner '+bannerClass;
  const av=document.getElementById('acAv');
  if(p.photo){ av.innerHTML=`<img src="data:image/jpeg;base64,${p.photo}">`; av.style.background=''; }
  else{ const initials=p.name.split(' ').map(w=>w[0]).slice(0,2).join('').toUpperCase(); av.innerHTML=initials; av.style.background=PALETTE[(p.id.charCodeAt(0)||0)%PALETTE.length]; }
  document.getElementById('acName').textContent=p.name;
  const acType=document.getElementById('acType');
  acType.textContent=type; acType.className='alert-card-type '+typeClass;
  document.getElementById('acInfoRow').innerHTML=`
    <span class="alert-info-pill">📍 ${p.barangay}</span>
    <span class="alert-info-pill">🗓 Age ${p.age}</span>
    <span class="alert-info-pill">${p.gender}</span>
    <span class="alert-info-pill">🕐 ${p.time}</span>`;
  const bpHigh=parseInt(p.bp)>=140, spo2Low=p.spo2<95, tempHigh=p.temp>=37.5, hrHigh=p.hr>=100, bmiFlag=p.bmi>27.5||p.bmi<18.5;
  document.getElementById('acVitals').innerHTML=`
    <div class="alert-vital-tile ${bpHigh?'flagged':''}"><div class="avt-label">Blood Pressure</div><div class="avt-val ${bpHigh?'danger':''}">${p.bp}</div><div style="font-size:10px;color:var(--slate-400);margin-top:2px">mmHg</div></div>
    <div class="alert-vital-tile ${spo2Low?'flagged':''}"><div class="avt-label">SpO2</div><div class="avt-val ${spo2Low?'danger':''}">${p.spo2}%</div><div style="font-size:10px;color:var(--slate-400);margin-top:2px">oxygen sat.</div></div>
    <div class="alert-vital-tile ${tempHigh?'flagged-amber':''}"><div class="avt-label">Temperature</div><div class="avt-val ${tempHigh?'warn':''}">${p.temp}°C</div><div style="font-size:10px;color:var(--slate-400);margin-top:2px">${tempHigh?'Fever':'Normal'}</div></div>
    <div class="alert-vital-tile ${hrHigh?'flagged':''}"><div class="avt-label">Heart Rate</div><div class="avt-val ${hrHigh?'danger':''}">${p.hr}</div><div style="font-size:10px;color:var(--slate-400);margin-top:2px">bpm</div></div>
    <div class="alert-vital-tile"><div class="avt-label">Weight</div><div class="avt-val">${p.weight}</div><div style="font-size:10px;color:var(--slate-400);margin-top:2px">kg</div></div>
    <div class="alert-vital-tile ${bmiFlag?'flagged-amber':''}"><div class="avt-label">BMI</div><div class="avt-val ${bmiFlag?'warn':''}">${p.bmi}</div><div style="font-size:10px;color:var(--slate-400);margin-top:2px">${p.bmi>27.5?'Overweight':p.bmi<18.5?'Underweight':'Normal'}</div></div>`;
  document.getElementById('acViewBtn').onclick=()=>{ closeAlertCard(); openDrawer(p.id); };
  document.getElementById('alertCardOverlay').classList.add('open');
}
function closeAlertCard(){ document.getElementById('alertCardOverlay').classList.remove('open'); }

// ── RECENT TABLE ─────────────────────────────────────────────
function renderRecent() {
  if(!ALL.length){ document.getElementById('recentTable').innerHTML='<tr><td colspan="7" class="empty-state">No patient records yet.</td></tr>'; return; }
  document.getElementById('recentTable').innerHTML = ALL.slice(0,8).map(p=>`
    <tr onclick="openDrawer('${p.id}')">
      <td><div class="pt-cell">${avatarEl(p)}<span class="pt-name">${p.name}</span></div></td>
      <td>${p.barangay}</td>
      <td class="mono">${p.time.split(' ').slice(-2).join(' ')}</td>
      <td class="mono" style="color:${parseInt(p.bp)>=140?'var(--red-600)':'inherit'}">${p.bp}</td>
      <td class="mono" style="color:${p.spo2<95?'var(--red-600)':'var(--green-700)'}">${p.spo2}%</td>
      <td class="mono" style="color:${p.temp>=37.5?'var(--amber-500)':'inherit'}">${p.temp}°C</td>
      <td><span class="badge ${p.status==='warning'?'badge-warn':'badge-ok'}">${p.status==='warning'?'Flagged':'Normal'}</span></td>
    </tr>`).join('');
}

// ── PATIENTS TABLE ───────────────────────────────────────────
function renderPatients(data) {
  filtered = [...data];
  document.getElementById('ptCount').textContent = data.length+' record'+(data.length!==1?'s':'');
  document.getElementById('patientTable').innerHTML = data.length ? data.map(p=>`
    <tr onclick="openDrawer('${p.id}')">
      <td><div class="pt-cell">${avatarEl(p)}<div><div class="pt-name">${p.name}</div><div class="pt-sub">${p.age}y ${p.gender.charAt(0)}</div></div></div></td>
      <td>${p.barangay}</td>
      <td>${p.age} / ${p.gender}</td>
      <td class="mono">${p.weight} kg</td>
      <td class="mono">${p.height} cm / ${p.bmi}</td>
      <td class="mono" style="font-weight:700;color:${parseInt(p.bp)>=140?'var(--red-600)':'inherit'}">${p.bp}</td>
      <td class="mono" style="color:${p.spo2<95?'var(--red-600)':'var(--green-700)'}">${p.spo2}%</td>
      <td class="mono" style="color:${p.temp>=37.5?'var(--amber-500)':'inherit'}">${p.temp}°C</td>
      <td class="mono" style="color:${p.hr>=100?'var(--red-600)':'inherit'}">${p.hr} bpm</td>
      <td style="font-size:12px;color:var(--slate-400)">${p.time}</td>
      <td><span class="badge ${p.status==='warning'?'badge-warn':'badge-ok'}">${p.status==='warning'?'Flagged':'Normal'}</span></td>
    </tr>`).join('')
  : '<tr><td colspan="11" class="empty-state">No records found for this period.</td></tr>';
}

// ── VITALS TABLE ─────────────────────────────────────────────
function renderVitalsTable() {
  if(!ALL.length){ document.getElementById('vitalsTable').innerHTML='<tr><td colspan="9" class="empty-state">No vital records yet.</td></tr>'; return; }
  document.getElementById('vitalsTable').innerHTML = ALL.map(p=>`
    <tr onclick="openDrawer('${p.id}')">
      <td><div class="pt-cell">${avatarEl(p)}<span class="pt-name">${p.name}</span></div></td>
      <td class="mono" style="color:${parseInt(p.bp)>=140?'var(--red-600)':'inherit'}">${p.bp}</td>
      <td class="mono" style="color:${p.spo2<95?'var(--red-600)':'var(--green-700)'}">${p.spo2}%</td>
      <td class="mono" style="color:${p.temp>=37.5?'var(--amber-500)':'inherit'}">${p.temp}°C</td>
      <td class="mono" style="color:${p.hr>=100?'var(--red-600)':'inherit'}">${p.hr} bpm</td>
      <td class="mono">${p.weight} kg</td>
      <td class="mono">${p.bmi}</td>
      <td style="font-size:12px;color:var(--slate-400)">${p.time}</td>
      <td><span class="badge ${p.status==='warning'?'badge-warn':'badge-ok'}">${p.status==='warning'?'Flagged':'Normal'}</span></td>
    </tr>`).join('');
}

// ── REPORTS TABLE ────────────────────────────────────────────
function renderReports(data) {
  document.getElementById('reportCount').textContent = data.length+' reports';
  document.getElementById('reportsTable').innerHTML = data.length ? data.map(p=>`
    <tr>
      <td><div class="pt-cell">${avatarEl(p)}<div><div class="pt-name">${p.name}</div><div class="pt-sub">${p.barangay}</div></div></div></td>
      <td>${p.barangay}</td>
      <td style="font-size:12px;color:var(--slate-500)">${p.time}</td>
      <td class="mono" style="color:${parseInt(p.bp)>=140?'var(--red-600)':'inherit'}">${p.bp}</td>
      <td class="mono" style="color:${p.spo2<95?'var(--red-600)':'var(--green-700)'}">${p.spo2}%</td>
      <td class="mono" style="color:${p.temp>=37.5?'var(--amber-500)':'inherit'}">${p.temp}°C</td>
      <td class="mono">${p.hr} bpm</td>
      <td><span class="badge ${p.status==='warning'?'badge-warn':'badge-ok'}">${p.status==='warning'?'Flagged':'Normal'}</span></td>
      
    </tr>`).join('') : '<tr><td colspan="9" class="empty-state">No reports yet.</td></tr>';
}
function filterReports(q){ q=q.toLowerCase(); renderReports(ALL.filter(p=>p.name.toLowerCase().includes(q)||p.barangay.toLowerCase().includes(q))); }
function filterReportStatus(s){ renderReports(s?ALL.filter(p=>p.status===s):ALL); }

// ── ANALYTICS ────────────────────────────────────────────────
function renderAnalytics() {
  const brgyCounts={};
  ALL.forEach(p=>{ if(p.barangay) brgyCounts[p.barangay]=(brgyCounts[p.barangay]||0)+1; });
  const sorted=Object.entries(brgyCounts).sort((a,b)=>b[1]-a[1]).slice(0,8);
  const max=sorted[0]?.[1]||1;
  document.getElementById('barangayBars').innerHTML = sorted.length ? sorted.map(([name,count])=>`
    <div>
      <div style="display:flex;justify-content:space-between;margin-bottom:4px">
        <span style="font-size:12.5px;font-weight:600">${name}</span>
        <span style="font-family:'Poppins',monospace;font-size:12px">${count}</span>
      </div>
      <div style="height:7px;background:var(--slate-100);border-radius:999px">
        <div style="height:100%;width:${Math.round(count/max*100)}%;background:var(--green-500);border-radius:999px;transition:.6s"></div>
      </div>
    </div>`).join('') : '<div class="empty-state">No barangay data yet.</div>';
  setTimeout(()=>{
    mkChart('analyticsVol','bar',['Mon','Tue','Wed','Thu','Fri','Sat','Sun'],
      [{data:WEEKLY,backgroundColor:'rgba(219,39,119,.2)',borderColor:'#db2777',borderWidth:2,borderRadius:5}]);
    mkChart('analyticsGender','doughnut',['Male','Female'],
      [{data:[GENDER_M,GENDER_F],backgroundColor:['#dbeafe','#fce7f3'],borderColor:['#2563eb','#db2777'],borderWidth:2}]);
    mkChart('analyticsFlags','bar',['High BP','Fever','Low SpO2','High HR'],
      [{data:[HIGH_BP,FEVER,LOW_SPO2,HIGH_HR],backgroundColor:['#fee2e2','#fef3c7','#dbeafe','#ede9fe'],borderColor:['#dc2626','#f59e0b','#2563eb','#7c3aed'],borderWidth:2,borderRadius:5}]);
    mkChart('analyticsAge','pie',['Children','Adults','Seniors'],
      [{data:[AGE_CHILD,AGE_ADULT,AGE_SENIOR],backgroundColor:['#dbeafe','#dcfce7','#fef3c7'],borderColor:['#2563eb','#16a34a','#f59e0b'],borderWidth:2}]);
  },50);
}

// ── NOTIFICATIONS ────────────────────────────────────────────
function renderNotifications() {
  const sub=document.getElementById('notifSubCount'), list=document.getElementById('notifList');
  if(!NOTIFICATIONS.length){ sub.textContent='0 alerts'; list.innerHTML='<div class="empty-state">No abnormal vital records detected.</div>'; return; }
  sub.textContent=NOTIFICATIONS.length+' alert'+(NOTIFICATIONS.length!==1?'s':'');
  list.innerHTML=NOTIFICATIONS.map(n=>{
    const pip=n.alert.includes('FEVER')||n.alert.includes('SpO2')?'pip-amber':'pip-red';
    const safeName=n.name.replace(/'/g,"\\'");
    return `<div class="alert-row" style="cursor:pointer;border-radius:var(--radius);padding:14px 10px;margin:0 -10px;transition:background .12s"
      onmouseover="this.style.background='var(--green-50)'" onmouseout="this.style.background=''"
      onclick="openAlertCard('${safeName}')">
      <div class="alert-pip ${pip}"></div>
      <div style="flex:1">
        <div class="alert-title">${n.alert}</div>
        <div class="alert-meta">${n.time} &bull; <span style="color:var(--green-600);font-size:11px">Click to view patient details →</span></div>
      </div>
    </div>`;
  }).join('');
}
function clearNotifUI(){ document.getElementById('notifList').innerHTML='<div class="empty-state">All notifications cleared.</div>'; document.getElementById('notifSubCount').textContent='0 alerts'; }

// ── DASH ALERTS ──────────────────────────────────────────────
function renderDashAlerts() {
  const alerts=ALL.filter(p=>p.status==='warning').slice(0,6);
  if(!alerts.length){ document.getElementById('dashAlerts').innerHTML='<div class="empty-state" style="padding:20px">No active alerts.</div>'; return; }
  document.getElementById('dashAlerts').innerHTML=alerts.map(p=>{
    let type='', meta='', pip='pip-red';
    if(parseInt(p.bp)>=140)  { type='High BP';   meta=p.bp+' mmHg'; }
    else if(p.temp>=37.5)    { type='Fever';      meta=p.temp+'°C'; pip='pip-amber'; }
    else if(p.spo2<95)       { type='Low SpO2';   meta=p.spo2+'%';  pip='pip-amber'; }
    else if(p.hr>=100)       { type='High HR';    meta=p.hr+' bpm'; }
    const safeName=p.name.replace(/'/g,"\\'");
    return `<div class="alert-row" style="cursor:pointer;border-radius:var(--radius);padding:14px 6px;margin:0 -6px;transition:background .12s"
      onmouseover="this.style.background='var(--green-50)'" onmouseout="this.style.background=''"
      onclick="openAlertCard('${safeName}')">
      <div class="alert-pip ${pip}"></div>
      <div>
        <div class="alert-title">${type} — ${p.name}</div>
        <div class="alert-meta">${meta} &bull; ${p.time.split(' ').slice(-2).join(' ')}</div>
      </div>
    </div>`;
  }).join('');
}

// ── FILTERS ──────────────────────────────────────────────────
const FILTER_IDS=['sf','bpf','tf','sf2','af'];
function resetFilters(except){ FILTER_IDS.forEach(id=>{ if(id!==except){ const el=document.getElementById(id); if(el) el.selectedIndex=0; } }); }
function filterPatients(q){ q=q.toLowerCase(); renderPatients(ALL.filter(p=>p.name.toLowerCase().includes(q)||p.barangay.toLowerCase().includes(q)||p.id.toString().includes(q))); }
function filterByStatus(s){ resetFilters('sf'); renderPatients(s?ALL.filter(p=>p.status===s):ALL); }
function filterByBP(t){ resetFilters('bpf'); renderPatients(t?ALL.filter(p=>t==='high'?parseInt(p.bp)>=140:parseInt(p.bp)<140):ALL); }
function filterByTemp(t){ resetFilters('tf'); renderPatients(t?ALL.filter(p=>t==='high'?p.temp>=38.5:t==='fever'?p.temp>=37.5&&p.temp<38.5:p.temp>=36&&p.temp<37.5):ALL); }
function filterBySpO2(t){ resetFilters('sf2'); renderPatients(t?ALL.filter(p=>t==='critical'?p.spo2<90:t==='low'?p.spo2>=90&&p.spo2<95:p.spo2>=95):ALL); }
function filterByAddress(v){ resetFilters('af'); renderPatients((!v||v==='')?ALL:ALL.filter(p=>p.barangay.trim().toLowerCase()===v.trim().toLowerCase())); }

// ── DRAWER ───────────────────────────────────────────────────
function openDrawer(id) {
  const p=ALL.find(x=>x.id==id||x.id===id);
  if(!p) return;
  currentPatient=p;
  const av=document.getElementById('drawerAv');
  if(p.photo){ av.innerHTML=`<img src="data:image/jpeg;base64,${p.photo}" style="width:100%;height:100%;object-fit:cover;">`; }
  else{ const initials=p.name.split(' ').map(w=>w[0]).slice(0,2).join('').toUpperCase(); av.textContent=initials; av.style.background=PALETTE[(p.id.charCodeAt(0)||0)%PALETTE.length]; }
  document.getElementById('drawerName').textContent=p.name;
  document.getElementById('drawerId').textContent='Record #'+p.id+' · '+p.time;
  document.getElementById('drawerInfo').innerHTML=`
    <div class="info-tile"><div class="info-tile-label">Age</div><div class="info-tile-val">${p.age} years</div></div>
    <div class="info-tile"><div class="info-tile-label">Gender</div><div class="info-tile-val">${p.gender}</div></div>
    <div class="info-tile"><div class="info-tile-label">Barangay</div><div class="info-tile-val">${p.barangay}</div></div>
    <div class="info-tile"><div class="info-tile-label">Status</div><div class="info-tile-val"><span class="badge ${p.status==='warning'?'badge-warn':'badge-ok'}">${p.status==='warning'?'Flagged':'Normal'}</span></div></div>`;
  const vitals=[
    {n:'Blood Pressure',v:p.bp+' mmHg',  flag:parseInt(p.bp)>=140},
    {n:'SpO2',          v:p.spo2+'%',    flag:p.spo2<95},
    {n:'Temperature',   v:p.temp+'°C',   flag:p.temp>=37.5},
    {n:'Heart Rate',    v:p.hr+' bpm',   flag:p.hr>=100},
    {n:'Weight',        v:p.weight+' kg',flag:false},
    {n:'Height',        v:p.height+' cm',flag:false},
    {n:'BMI',           v:p.bmi,         flag:p.bmi>27.5||p.bmi<18.5},
  ];
  document.getElementById('drawerVitals').innerHTML=vitals.map(v=>`
    <div class="vital-row">
      <div class="vital-name">${v.n}</div>
      <div class="vital-val" style="color:${v.flag?'var(--red-600)':'var(--slate-800)'}">${v.v}</div>
      ${v.flag?'<span class="badge badge-alert" style="font-size:10px">Alert</span>':''}
    </div>`).join('');
  document.getElementById('drawerOverlay').classList.add('open');
  document.getElementById('drawer').classList.add('open');
}
function closeDrawer(){ document.getElementById('drawerOverlay').classList.remove('open'); document.getElementById('drawer').classList.remove('open'); currentPatient=null; }
function openDrawerAndPrint(id){ openDrawer(id); setTimeout(openPrintModal,300); }
function openPrintModal(){ document.getElementById('printModal').classList.add('open'); }
function closePrintModal(){ document.getElementById('printModal').classList.remove('open'); }

// ── CHART HELPER ─────────────────────────────────────────────
const chartInstances={};
function mkChart(id,type,labels,datasets,opts={}) {
  if(chartInstances[id]) chartInstances[id].destroy();
  const ctx=document.getElementById(id);
  if(!ctx) return;
  chartInstances[id]=new Chart(ctx.getContext('2d'),{
    type, data:{labels,datasets},
    options:{responsive:true,maintainAspectRatio:false,
      plugins:{legend:{display:type==='doughnut'||type==='pie',position:'right',
        labels:{font:{family:'Poppins',size:11},color:'#64748b',boxWidth:10,padding:10}}},
      scales:(type==='bar')?{
        x:{grid:{display:false},ticks:{color:'#94a3b8',font:{family:'Poppins',size:10}}},
        y:{grid:{color:'#f1f5f9'},ticks:{color:'#94a3b8',font:{family:'Poppins',size:10}}}}:{},
      ...opts}
  });
}

// ── INIT ─────────────────────────────────────────────────────
function init(){
  const cnt=NOTIFICATIONS.length;

  // Guard: topbar has no notification bell/badge element in this template.
  // Only touch it if it actually exists, so a missing element never halts init().
  const topBadge = document.getElementById('topNotifBadge');
  if(topBadge){
    topBadge.textContent = cnt;
    topBadge.style.display = cnt>0?'flex':'none';
  }

  document.getElementById('navNotifBadge').textContent=cnt;
  document.getElementById('navNotifBadge').style.display=cnt>0?'inline-block':'none';
  renderRecent();
  renderDashAlerts();
  mkChart('volChart','bar',['Mon','Tue','Wed','Thu','Fri','Sat','Sun'],
    [{data:WEEKLY,backgroundColor:'rgba(219,39,119,.15)',borderColor:'#db2777',borderWidth:2,borderRadius:5}]);
  mkChart('flagChart','doughnut',['High BP','Fever','Low SpO2','High HR'],
    [{data:[HIGH_BP,FEVER,LOW_SPO2,HIGH_HR],backgroundColor:['#fee2e2','#fef3c7','#dbeafe','#ede9fe'],borderColor:['#dc2626','#f59e0b','#2563eb','#7c3aed'],borderWidth:2}]);
  mkChart('genderChart','doughnut',['Male','Female'],
    [{data:[GENDER_M,GENDER_F],backgroundColor:['#dbeafe','#fce7f3'],borderColor:['#2563eb','#db2777'],borderWidth:2}]);
  setTimeout(()=>{
    const normal=ALL.filter(p=>parseInt(p.bp)<140).length;
    const high=ALL.filter(p=>parseInt(p.bp)>=140).length;
    mkChart('bpDistChart','doughnut',['Normal BP','High BP'],
      [{data:[normal,high],backgroundColor:['#dcfce7','#fee2e2'],borderColor:['#db2777','#dc2626'],borderWidth:2}]);
  },100);
}
init();
</script>
</body>
</html>