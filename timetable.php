<?php
include 'includes/auth.php';   // guards page, sets $uid $user_name

/* ── defaults ── */
$today       = date('Y-m-d');
$startDate   = $today;
$prelimsDate = '2026-05-24';
$mainsDate   = '2026-09-18';
$intrvDate   = '2027-02-10';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dynamic Schedule — UPSC Command Center</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">

<style>
:root{
    --grad-start:#1a0533;--grad-mid:#3d0f6b;--grad-end:#6b1fa8;
    --violet:#7c3aed;--violet-lt:#f0eaff;--violet-dk:#5b21b6;
    --coral:#ff6b4a;--coral-lt:#fff0ed;
    --blue:#3b82f6;--blue-lt:#eff5ff;
    --teal:#0fb98a;--teal-lt:#e6faf4;
    --amber:#f59e0b;--amber-lt:#fffbeb;
    --green:#16a34a;--green-lt:#f0fdf4;
    --red:#ef4444;--red-lt:#fef2f2;
    --pink:#ec4899;--pink-lt:#fdf2f8;
    --ink:#1a1523;--muted:#6b6579;
    --border:rgba(0,0,0,0.07);--surface:#fff;--page:#f4f2f8;
    --ease:cubic-bezier(0.2,0.8,0.2,1);
}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;-webkit-font-smoothing:antialiased;}
html{scroll-behavior:smooth;}
body{font-family:'DM Sans',sans-serif;background:var(--page);color:var(--ink);min-height:100vh;overflow-x:hidden;}
a{text-decoration:none;color:inherit;}

/* ── HERO ── */
.hero{
    background:linear-gradient(135deg,var(--grad-start) 0%,var(--grad-mid) 50%,var(--grad-end) 100%);
    position:relative;padding:4.5rem 2rem 6.5rem;text-align:center;color:#fff;overflow:hidden;
}
.hero::before{
    content:'';position:absolute;top:50%;left:50%;transform:translate(-50%,-55%);
    width:600px;height:380px;
    background:radial-gradient(ellipse,rgba(160,80,255,0.28) 0%,transparent 70%);
    pointer-events:none;
}
.hero-inner{position:relative;z-index:2;max-width:700px;margin:0 auto;}
.back-btn{
    position:absolute;top:1.75rem;left:1.75rem;z-index:50;
    width:46px;height:46px;border-radius:13px;
    display:flex;align-items:center;justify-content:center;
    background:rgba(255,255,255,0.13);border:1px solid rgba(255,255,255,0.22);
    backdrop-filter:blur(12px);color:#fff;transition:all .25s var(--ease);
}
.back-btn:hover{background:rgba(255,255,255,.25);transform:translateY(-2px);}
.back-btn svg{width:20px;height:20px;}
.pre-badge{
    display:inline-block;font-family:'Syne',sans-serif;
    font-size:.7rem;font-weight:600;letter-spacing:.18em;text-transform:uppercase;
    background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.22);
    border-radius:100px;padding:.35rem 1.1rem;margin-bottom:1.2rem;
}
.hero h1{
    font-family:'Syne',sans-serif;font-size:clamp(1.8rem,4.5vw,3rem);
    font-weight:800;line-height:1.12;letter-spacing:-.02em;margin-bottom:.8rem;
}
.hero p{font-size:1rem;font-weight:300;opacity:.82;max-width:480px;margin:0 auto 1.5rem;}

/* Countdown row */
.countdown-row{
    display:flex;justify-content:center;gap:1rem;flex-wrap:wrap;
    position:relative;z-index:2;
}
.cd-pill{
    background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.18);
    border-radius:14px;padding:.65rem 1.1rem;min-width:130px;text-align:center;
    backdrop-filter:blur(10px);transition:all .25s var(--ease);cursor:default;
}
.cd-pill:hover{background:rgba(255,255,255,.18);}
.cd-stage{font-size:.65rem;font-weight:600;letter-spacing:.12em;text-transform:uppercase;opacity:.75;margin-bottom:.3rem;}
.cd-days{font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:800;line-height:1;}
.cd-label{font-size:.68rem;opacity:.65;margin-top:.2rem;}
.cd-pill.urgent .cd-days{color:#fca5a5;}
.cd-pill.warning .cd-days{color:#fcd34d;}
.cd-pill.safe .cd-days{color:#6ee7b7;}

.wave{position:absolute;bottom:-1px;left:0;width:100%;line-height:0;z-index:3;}
.wave svg{display:block;width:100%;height:80px;}

/* ── ALERT BANNER ── */
.alert-banner{
    background:linear-gradient(135deg,#7f1d1d,#991b1b);
    color:#fff;padding:.85rem 1.5rem;
    display:none;align-items:center;gap:.85rem;
    font-size:.85rem;position:relative;z-index:40;
}
.alert-banner.show{display:flex;}
.alert-banner svg{width:18px;height:18px;flex-shrink:0;}
.alert-banner strong{font-family:'Syne',sans-serif;font-weight:700;}
.alert-banner .alert-msg{flex:1;}
.alert-dismiss{
    background:rgba(255,255,255,.15);border:none;color:#fff;
    border-radius:8px;padding:.3rem .75rem;font-size:.75rem;cursor:pointer;
    font-family:'DM Sans',sans-serif;
}
.alert-dismiss:hover{background:rgba(255,255,255,.25);}

/* ── MAIN LAYOUT ── */
.main{max-width:1180px;margin:0 auto;padding:2rem 1.5rem 5rem;}

/* ── TABS ── */
.tabs{
    display:flex;gap:0;background:var(--surface);border-radius:16px;
    border:1px solid var(--border);overflow:hidden;
    margin-bottom:1.5rem;box-shadow:0 2px 12px rgba(0,0,0,.05);
}
.tab-btn{
    flex:1;padding:.85rem 1rem;border:none;background:transparent;
    font-family:'DM Sans',sans-serif;font-size:.82rem;font-weight:500;
    color:var(--muted);cursor:pointer;transition:all .2s;
    display:flex;align-items:center;justify-content:center;gap:.45rem;
}
.tab-btn .tdot{width:8px;height:8px;border-radius:50%;flex-shrink:0;}
.tab-btn:hover{background:var(--page);color:var(--ink);}
.tab-btn.active{background:var(--violet);color:#fff;font-weight:600;}
.tab-btn.active .tdot{background:#fff !important;}
.tab-section{display:none;}
.tab-section.active{display:block;}

/* ── SETUP CARD ── */
.setup-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-bottom:1.5rem;}
@media(max-width:700px){.setup-grid{grid-template-columns:1fr;}}

.form-card{
    background:var(--surface);border-radius:18px;border:1px solid var(--border);
    padding:1.5rem;box-shadow:0 3px 14px rgba(0,0,0,.05);
}
.form-card h3{
    font-family:'Syne',sans-serif;font-size:.9rem;font-weight:700;margin-bottom:1.1rem;
    display:flex;align-items:center;gap:.55rem;
}
.form-card h3 span{
    width:28px;height:28px;border-radius:8px;
    display:flex;align-items:center;justify-content:center;
}
.form-card h3 span svg{width:14px;height:14px;}

.field{margin-bottom:1rem;}
.field label{display:block;font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);margin-bottom:.4rem;}
.field input[type="date"],
.field input[type="text"],
.field input[type="number"],
.field select{
    width:100%;padding:.6rem .9rem;border-radius:11px;
    border:1px solid var(--border);background:var(--page);
    font-family:'DM Sans',sans-serif;font-size:.88rem;color:var(--ink);
    outline:none;transition:border-color .2s;
}
.field input:focus,.field select:focus{border-color:var(--violet);background:#fff;}

/* subject checkboxes */
.subject-grid{display:grid;grid-template-columns:1fr 1fr;gap:.5rem;}
.subj-check{
    display:flex;align-items:center;gap:.55rem;
    background:var(--page);border-radius:10px;padding:.55rem .75rem;cursor:pointer;
    border:1.5px solid transparent;transition:all .2s;
    font-size:.8rem;color:var(--ink);user-select:none;
}
.subj-check:hover{border-color:var(--violet);}
.subj-check input[type="checkbox"]{display:none;}
.subj-check.checked{background:var(--violet-lt);border-color:var(--violet);color:var(--violet);font-weight:500;}
.subj-dot{width:10px;height:10px;border-radius:3px;flex-shrink:0;}

/* time slots */
.slot-row{display:flex;gap:.6rem;align-items:center;margin-bottom:.6rem;}
.slot-row input[type="time"]{
    padding:.5rem .7rem;border-radius:10px;border:1px solid var(--border);
    background:var(--page);font-family:'DM Mono',monospace;font-size:.82rem;
    color:var(--ink);outline:none;flex:1;transition:border-color .2s;
}
.slot-row input[type="time"]:focus{border-color:var(--violet);}
.slot-row .slot-label{font-size:.75rem;color:var(--muted);white-space:nowrap;min-width:55px;}
.slot-remove{width:26px;height:26px;border-radius:7px;border:none;background:var(--red-lt);color:var(--red);cursor:pointer;display:flex;align-items:center;justify-content:center;}
.slot-remove svg{width:12px;height:12px;}
.add-slot-btn{
    display:flex;align-items:center;gap:.4rem;font-size:.78rem;color:var(--violet);
    background:var(--violet-lt);border:none;border-radius:8px;padding:.4rem .8rem;cursor:pointer;
    font-family:'DM Sans',sans-serif;transition:all .2s;
}
.add-slot-btn:hover{background:var(--violet);color:#fff;}

/* action row */
.action-row{display:flex;gap:.75rem;flex-wrap:wrap;margin-bottom:1.5rem;}
.btn{
    display:flex;align-items:center;gap:.45rem;padding:.65rem 1.3rem;
    border-radius:100px;font-family:'DM Sans',sans-serif;
    font-size:.82rem;font-weight:500;cursor:pointer;transition:all .22s var(--ease);
    border:1.5px solid var(--border);background:var(--surface);color:var(--ink);
}
.btn svg{width:15px;height:15px;}
.btn:hover{border-color:var(--violet);color:var(--violet);}
.btn.primary{background:var(--violet);border-color:var(--violet);color:#fff;}
.btn.primary:hover{background:var(--violet-dk);}
.btn.success{background:var(--green);border-color:var(--green);color:#fff;}
.btn.success:hover{background:#15803d;}
.btn.danger{background:var(--red-lt);border-color:var(--red);color:var(--red);}

/* save banner */
.save-banner{
    background:var(--green-lt);border:1px solid rgba(22,163,74,.25);
    border-radius:12px;padding:.75rem 1.1rem;margin-bottom:1.25rem;
    display:none;align-items:center;gap:.65rem;font-size:.83rem;color:var(--green);
}
.save-banner.show{display:flex;}

/* ── TIMETABLE TABLE ── */
.table-wrap{
    background:var(--surface);border-radius:18px;border:1px solid var(--border);
    overflow:hidden;box-shadow:0 3px 14px rgba(0,0,0,.05);
}
.table-toolbar{
    padding:1rem 1.25rem;border-bottom:1px solid var(--border);
    display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;
}
.table-toolbar h3{font-family:'Syne',sans-serif;font-size:.95rem;font-weight:700;flex:1;}
.filter-btn{
    padding:.35rem .85rem;border-radius:100px;border:1px solid var(--border);
    background:transparent;font-size:.75rem;cursor:pointer;color:var(--muted);
    font-family:'DM Sans',sans-serif;transition:all .2s;
}
.filter-btn:hover{border-color:var(--violet);color:var(--violet);}
.filter-btn.active{background:var(--violet);border-color:var(--violet);color:#fff;}

.tt-table{width:100%;border-collapse:collapse;}
.tt-table th{
    background:var(--page);padding:.65rem 1rem;text-align:left;
    font-size:.68rem;font-weight:600;text-transform:uppercase;letter-spacing:.09em;color:var(--muted);
}
.tt-table td{padding:.7rem 1rem;border-bottom:1px solid var(--border);vertical-align:middle;}
.tt-table tr:last-child td{border-bottom:none;}
.tt-table tr.today-row{background:rgba(124,58,237,.04);}
.tt-table tr.past-row{opacity:.55;}
.tt-table tr.hidden-row{display:none;}

.date-cell{font-family:'DM Mono',monospace;font-size:.8rem;color:var(--muted);}
.day-cell{font-family:'Syne',sans-serif;font-size:.82rem;font-weight:700;color:var(--ink);}
.day-cell.sunday{color:var(--red);}
.day-cell.saturday{color:var(--coral);}

.subj-tag{
    display:inline-block;border-radius:7px;padding:.25rem .65rem;
    font-size:.72rem;font-weight:600;margin:.1rem .1rem;
}
.hours-cell{font-family:'DM Mono',monospace;font-size:.8rem;color:var(--muted);}
.milestone-cell{}
.milestone-badge{
    display:inline-flex;align-items:center;gap:.35rem;
    border-radius:8px;padding:.3rem .75rem;font-size:.72rem;font-weight:600;
}

/* completion toggle */
.done-btn{
    width:28px;height:28px;border-radius:8px;border:1.5px solid var(--border);
    background:transparent;cursor:pointer;display:flex;align-items:center;justify-content:center;
    transition:all .2s;flex-shrink:0;
}
.done-btn svg{width:13px;height:13px;color:var(--muted);}
.done-btn.done{background:var(--green);border-color:var(--green);}
.done-btn.done svg{color:#fff;}

/* phase label in table */
.phase-label{
    font-size:.65rem;font-weight:600;letter-spacing:.09em;text-transform:uppercase;
    border-radius:6px;padding:.2rem .5rem;
}

/* ── ALERTS SECTION ── */
.alerts-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1rem;margin-bottom:1.5rem;}
.alert-card{
    background:var(--surface);border-radius:16px;border:1px solid var(--border);
    padding:1.25rem;box-shadow:0 3px 14px rgba(0,0,0,.05);
    display:flex;gap:.85rem;align-items:flex-start;
    animation:fadeUp .5s var(--ease) both;
}
.alert-card.prelims{border-left:3px solid var(--blue);}
.alert-card.mains  {border-left:3px solid var(--teal);}
.alert-card.interview{border-left:3px solid var(--amber);}
.alert-card.custom {border-left:3px solid var(--violet);}
.alert-card.dismissed{opacity:.4;}

.alert-ico{
    width:40px;height:40px;border-radius:11px;flex-shrink:0;
    display:flex;align-items:center;justify-content:center;
}
.alert-ico svg{width:20px;height:20px;}
.alert-info{flex:1;min-width:0;}
.alert-title{font-family:'Syne',sans-serif;font-size:.88rem;font-weight:700;margin-bottom:.2rem;}
.alert-date{font-family:'DM Mono',monospace;font-size:.75rem;color:var(--muted);}
.alert-countdown{
    font-family:'Syne',sans-serif;font-size:1.1rem;font-weight:800;margin-top:.3rem;
}
.alert-countdown.red{color:var(--red);}
.alert-countdown.amber{color:var(--amber);}
.alert-countdown.green{color:var(--green);}
.alert-countdown.muted{color:var(--muted);}
.alert-actions{display:flex;flex-direction:column;gap:.4rem;align-items:flex-end;}
.snooze-btn{
    font-size:.7rem;background:var(--page);border:1px solid var(--border);
    border-radius:7px;padding:.25rem .6rem;cursor:pointer;color:var(--muted);
    font-family:'DM Sans',sans-serif;transition:all .2s;
}
.snooze-btn:hover{border-color:var(--violet);color:var(--violet);}

/* ── STATS CARDS ── */
.stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.5rem;}
@media(max-width:700px){.stats-row{grid-template-columns:1fr 1fr;}}
.stat-card{
    background:var(--surface);border-radius:16px;border:1px solid var(--border);
    padding:1.1rem 1rem;text-align:center;
    box-shadow:0 3px 14px rgba(0,0,0,.04);
    animation:fadeUp .5s var(--ease) both;
}
.stat-card:nth-child(1){animation-delay:.05s}
.stat-card:nth-child(2){animation-delay:.10s}
.stat-card:nth-child(3){animation-delay:.15s}
.stat-card:nth-child(4){animation-delay:.20s}
.stat-ico{width:40px;height:40px;border-radius:11px;display:flex;align-items:center;justify-content:center;margin:0 auto .7rem;}
.stat-ico svg{width:20px;height:20px;}
.stat-val{font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:800;line-height:1;margin-bottom:.2rem;}
.stat-lbl{font-size:.72rem;color:var(--muted);}

/* ── PROGRESS BAR ── */
.phase-progress-row{
    background:var(--surface);border-radius:16px;border:1px solid var(--border);
    padding:1.25rem;margin-bottom:1.5rem;
    box-shadow:0 3px 14px rgba(0,0,0,.04);
}
.phase-progress-row h4{font-family:'Syne',sans-serif;font-size:.82rem;font-weight:700;text-transform:uppercase;letter-spacing:.09em;margin-bottom:1rem;color:var(--muted);}
.progress-item{margin-bottom:.85rem;}
.progress-item:last-child{margin-bottom:0;}
.progress-meta{display:flex;justify-content:space-between;font-size:.78rem;margin-bottom:.4rem;}
.progress-meta strong{font-weight:600;color:var(--ink);}
.progress-meta span{color:var(--muted);font-family:'DM Mono',monospace;}
.progress-track{height:8px;background:var(--page);border-radius:100px;overflow:hidden;}
.progress-fill{height:100%;border-radius:100px;transition:width .6s var(--ease);}

/* ── NOTIFICATION TOAST ── */
.toast{
    position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;
    background:var(--ink);color:#fff;border-radius:14px;
    padding:.85rem 1.25rem;font-size:.82rem;max-width:320px;
    display:none;align-items:center;gap:.65rem;
    box-shadow:0 12px 40px rgba(0,0,0,.25);
    animation:slideUp .3s var(--ease);
}
.toast.show{display:flex;}
.toast svg{width:18px;height:18px;flex-shrink:0;}
.toast.success-toast{background:var(--green);}
.toast.error-toast{background:var(--red);}
.toast.info-toast{background:var(--violet);}

/* ── SUBJECT COLOUR MAP ── */
.s-history   {background:#ede9fe;color:#5b21b6;}
.s-polity    {background:#eff5ff;color:#1d4ed8;}
.s-geography {background:#e6faf4;color:#065f46;}
.s-economy   {background:#fffbeb;color:#92400e;}
.s-science   {background:#fdf2f8;color:#9d174d;}
.s-environment{background:#ecfdf5;color:#064e3b;}
.s-csat      {background:#fef3c7;color:#92400e;}
.s-ethics    {background:#fce7f3;color:#9d174d;}
.s-currentaffairs{background:#f0f9ff;color:#0c4a6e;}
.s-revision  {background:#fef9c3;color:#713f12;}
.s-mock      {background:#fff0ed;color:#c2410c;}
.s-essay     {background:#f0eaff;color:#5b21b6;}
.s-rest      {background:#f3f4f6;color:#6b7280;}

@keyframes fadeUp{from{opacity:0;transform:translateY(18px);}to{opacity:1;transform:translateY(0);}}
@keyframes slideUp{from{opacity:0;transform:translateY(20px);}to{opacity:1;transform:translateY(0);}}
@keyframes pulse{0%,100%{opacity:1;}50%{opacity:.5;}}

@media(max-width:600px){
    .table-toolbar{flex-direction:column;align-items:flex-start;}
    .tt-table td,.tt-table th{padding:.55rem .65rem;}
}
</style>
</head>
<body>

<!-- ── HERO ── -->
<header class="hero">
    <a href="dashboard.php" class="back-btn" title="Dashboard">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
    </a>
    <div class="hero-inner">
        <span class="pre-badge">AI-Optimized Study Planner</span>
        <h1>Dynamic Schedule</h1>
        <p>Custom daily timetable with real-time exam countdowns, phase alerts, and progress tracking.</p>
        <!-- Live countdown pills -->
        <div class="countdown-row" id="countdown-row">
            <div class="cd-pill safe" id="cd-prelims">
                <div class="cd-stage">Prelims</div>
                <div class="cd-days" id="cd-prelims-days">—</div>
                <div class="cd-label">days left</div>
            </div>
            <div class="cd-pill safe" id="cd-mains">
                <div class="cd-stage">Mains</div>
                <div class="cd-days" id="cd-mains-days">—</div>
                <div class="cd-label">days left</div>
            </div>
            <div class="cd-pill safe" id="cd-interview">
                <div class="cd-stage">Interview</div>
                <div class="cd-days" id="cd-interview-days">—</div>
                <div class="cd-label">days left</div>
            </div>
        </div>
    </div>
    <div class="wave">
        <svg viewBox="0 0 1440 80" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
            <path d="M0,40 C180,80 360,0 540,40 C720,80 900,0 1080,40 C1260,80 1380,20 1440,40 L1440,80 L0,80 Z" fill="#f4f2f8"/>
        </svg>
    </div>
</header>

<!-- Alert banner (shown when exam < 30 days) -->
<div class="alert-banner" id="alert-banner">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    <div class="alert-msg"><strong id="alert-banner-text">Exam Alert</strong> — Review your schedule and ensure you're on track!</div>
    <button class="alert-dismiss" onclick="document.getElementById('alert-banner').classList.remove('show')">Dismiss</button>
</div>

<!-- ── MAIN ── -->
<main class="main">

<!-- TABS -->
<div class="tabs">
    <button class="tab-btn active" data-tab="setup">
        <span class="tdot" style="background:var(--violet)"></span> Setup
    </button>
    <button class="tab-btn" data-tab="timetable">
        <span class="tdot" style="background:var(--teal)"></span> Timetable
    </button>
    <button class="tab-btn" data-tab="alerts">
        <span class="tdot" style="background:var(--coral)"></span> Alerts
    </button>
    <button class="tab-btn" data-tab="progress">
        <span class="tdot" style="background:var(--green)"></span> Progress
    </button>
</div>

<!-- ════════ TAB: SETUP ════════ -->
<div class="tab-section active" id="tab-setup">

    <div class="save-banner" id="save-banner">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;flex-shrink:0"><polyline points="20 6 9 17 4 12"/></svg>
        Timetable saved to your account!
    </div>

    <div class="setup-grid">

        <!-- Dates -->
        <div class="form-card">
            <h3>
                <span class="bg-violet c-violet" style="background:var(--violet-lt);color:var(--violet)">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </span>
                Exam Dates
            </h3>
            <div class="field"><label>Preparation Start Date</label><input type="date" id="start-date" value="<?php echo $today; ?>"></div>
            <div class="field"><label>Prelims Date</label><input type="date" id="prelims-date" value="<?php echo $prelimsDate; ?>"></div>
            <div class="field"><label>Mains Date</label><input type="date" id="mains-date" value="<?php echo $mainsDate; ?>"></div>
            <div class="field"><label>Interview (Approx)</label><input type="date" id="interview-date" value="<?php echo $intrvDate; ?>"></div>
        </div>

        <!-- Study Settings -->
        <div class="form-card">
            <h3>
                <span style="background:var(--teal-lt);color:var(--teal);width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </span>
                Study Settings
            </h3>
            <div class="field"><label>Daily Study Hours</label>
                <select id="daily-hours">
                    <option value="4">4 hours (Light)</option>
                    <option value="6">6 hours (Moderate)</option>
                    <option value="8" selected>8 hours (Intensive)</option>
                    <option value="10">10 hours (Full-time)</option>
                    <option value="12">12 hours (Max effort)</option>
                </select>
            </div>
            <div class="field"><label>Plan Name</label><input type="text" id="plan-name" placeholder="e.g. CSE 2026 Plan" value="CSE 2026 Plan"></div>
            <div class="field"><label>Weekly Rest Day</label>
                <select id="rest-day">
                    <option value="Sunday" selected>Sunday</option>
                    <option value="Saturday">Saturday</option>
                    <option value="None">No rest day</option>
                </select>
            </div>
            <div class="field"><label>Study Schedule Mode</label>
                <select id="study-mode">
                    <option value="standard">Standard (7 subjects rotation)</option>
                    <option value="phase">Phase-based (Prelims→Mains→Interview)</option>
                    <option value="custom">Custom (pick subjects below)</option>
                </select>
            </div>
        </div>

        <!-- Subjects -->
        <div class="form-card">
            <h3>
                <span style="background:var(--coral-lt);color:var(--coral);width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                </span>
                Subjects to Include
            </h3>
            <div class="subject-grid" id="subject-grid">
                <?php
                $subjects = [
                    ['id'=>'history',       'label'=>'History',           'color'=>'#5b21b6'],
                    ['id'=>'polity',        'label'=>'Polity',            'color'=>'#1d4ed8'],
                    ['id'=>'geography',     'label'=>'Geography',         'color'=>'#065f46'],
                    ['id'=>'economy',       'label'=>'Economy',           'color'=>'#92400e'],
                    ['id'=>'science',       'label'=>'Science & Tech',    'color'=>'#9d174d'],
                    ['id'=>'environment',   'label'=>'Environment',       'color'=>'#064e3b'],
                    ['id'=>'csat',          'label'=>'CSAT',              'color'=>'#92400e'],
                    ['id'=>'ethics',        'label'=>'Ethics GS IV',      'color'=>'#9d174d'],
                    ['id'=>'currentaffairs','label'=>'Current Affairs',   'color'=>'#0c4a6e'],
                    ['id'=>'revision',      'label'=>'Revision',          'color'=>'#713f12'],
                    ['id'=>'mock',          'label'=>'Mock Test',         'color'=>'#c2410c'],
                    ['id'=>'essay',         'label'=>'Essay Writing',     'color'=>'#5b21b6'],
                ];
                foreach ($subjects as $s):
                ?>
                <label class="subj-check checked" id="lbl-<?php echo $s['id']; ?>">
                    <input type="checkbox" id="subj-<?php echo $s['id']; ?>" value="<?php echo $s['id']; ?>" checked>
                    <span class="subj-dot" style="background:<?php echo $s['color']; ?>;opacity:.35;border-radius:3px;"></span>
                    <?php echo $s['label']; ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Study Time Slots -->
        <div class="form-card">
            <h3>
                <span style="background:var(--amber-lt);color:var(--amber);width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </span>
                Daily Time Slots
            </h3>
            <div id="slots-container">
                <div class="slot-row">
                    <span class="slot-label">Morning</span>
                    <input type="time" value="06:00"> <span style="font-size:.75rem;color:var(--muted)">to</span>
                    <input type="time" value="09:00">
                    <button class="slot-remove" onclick="removeSlot(this)"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
                </div>
                <div class="slot-row">
                    <span class="slot-label">Afternoon</span>
                    <input type="time" value="11:00"> <span style="font-size:.75rem;color:var(--muted)">to</span>
                    <input type="time" value="14:00">
                    <button class="slot-remove" onclick="removeSlot(this)"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
                </div>
                <div class="slot-row">
                    <span class="slot-label">Evening</span>
                    <input type="time" value="17:00"> <span style="font-size:.75rem;color:var(--muted)">to</span>
                    <input type="time" value="20:00">
                    <button class="slot-remove" onclick="removeSlot(this)"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
                </div>
            </div>
            <button class="add-slot-btn" onclick="addSlot()" style="margin-top:.6rem;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add Time Slot
            </button>
        </div>

    </div><!-- /setup-grid -->

    <div class="action-row">
        <button class="btn primary" onclick="generateTimetable()">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg>
            Generate Timetable
        </button>
        <button class="btn success" onclick="saveTimetable()">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            Save to Account
        </button>
        <button class="btn" onclick="loadSaved()">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.16"/></svg>
            Load Saved Plan
        </button>
        <button class="btn" onclick="exportCSV()">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Export CSV
        </button>
    </div>
</div>

<!-- ════════ TAB: TIMETABLE ════════ -->
<div class="tab-section" id="tab-timetable">
    <div class="table-wrap" id="timetable-wrap">
        <div class="table-toolbar">
            <h3>📅 Your Study Schedule</h3>
            <button class="filter-btn active" data-filter="all">All</button>
            <button class="filter-btn" data-filter="upcoming">Upcoming</button>
            <button class="filter-btn" data-filter="today">Today</button>
            <button class="filter-btn" data-filter="week">This Week</button>
        </div>
        <div style="padding:3rem;text-align:center;color:var(--muted);font-size:.88rem;" id="tt-empty">
            Generate your timetable from the Setup tab to see your schedule here.
        </div>
        <div style="overflow-x:auto;">
            <table class="tt-table" id="tt-table" style="display:none;">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Day</th>
                        <th>Phase</th>
                        <th>Subjects</th>
                        <th>Hours</th>
                        <th>Milestone</th>
                        <th>Done</th>
                    </tr>
                </thead>
                <tbody id="tt-tbody"></tbody>
            </table>
        </div>
    </div>
</div>

<!-- ════════ TAB: ALERTS ════════ -->
<div class="tab-section" id="tab-alerts">
    <div id="alerts-container" class="alerts-grid"></div>
    <div id="alerts-empty" style="text-align:center;padding:3rem;color:var(--muted);display:none;">
        Generate your timetable first to see exam alerts here.
    </div>
</div>

<!-- ════════ TAB: PROGRESS ════════ -->
<div class="tab-section" id="tab-progress">
    <div class="stats-row" id="stats-row">
        <div class="stat-card">
            <div class="stat-ico" style="background:var(--teal-lt);color:var(--teal)">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <div class="stat-val" id="stat-completed" style="color:var(--teal)">0</div>
            <div class="stat-lbl">Days Completed</div>
        </div>
        <div class="stat-card">
            <div class="stat-ico" style="background:var(--violet-lt);color:var(--violet)">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div class="stat-val" id="stat-total-days" style="color:var(--violet)">0</div>
            <div class="stat-lbl">Total Study Days</div>
        </div>
        <div class="stat-card">
            <div class="stat-ico" style="background:var(--amber-lt);color:var(--amber)">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            </div>
            <div class="stat-val" id="stat-streak" style="color:var(--amber)">0</div>
            <div class="stat-lbl">Day Streak 🔥</div>
        </div>
        <div class="stat-card">
            <div class="stat-ico" style="background:var(--green-lt);color:var(--green)">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <div class="stat-val" id="stat-pct" style="color:var(--green)">0%</div>
            <div class="stat-lbl">Completion Rate</div>
        </div>
    </div>

    <div class="phase-progress-row">
        <h4>Phase Progress</h4>
        <div class="progress-item">
            <div class="progress-meta"><strong>Prelims Prep</strong><span id="prog-prelims-val">0 / 0 days</span></div>
            <div class="progress-track"><div class="progress-fill" id="prog-prelims-fill" style="width:0%;background:var(--blue)"></div></div>
        </div>
        <div class="progress-item">
            <div class="progress-meta"><strong>Mains Prep</strong><span id="prog-mains-val">0 / 0 days</span></div>
            <div class="progress-track"><div class="progress-fill" id="prog-mains-fill" style="width:0%;background:var(--teal)"></div></div>
        </div>
        <div class="progress-item">
            <div class="progress-meta"><strong>Interview Prep</strong><span id="prog-intv-val">0 / 0 days</span></div>
            <div class="progress-track"><div class="progress-fill" id="prog-intv-fill" style="width:0%;background:var(--amber)"></div></div>
        </div>
    </div>
</div>

</main>

<!-- Toast -->
<div class="toast" id="toast">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
    <span id="toast-msg">Saved!</span>
</div>

<script>
/* ════════════════════════════════════════════════════════
   STATE
════════════════════════════════════════════════════════ */
let PLAN = {
    name:'CSE 2026 Plan', startDate:'', prelimsDate:'', mainsDate:'', interviewDate:'',
    dailyHours:8, restDay:'Sunday', mode:'standard',
    subjects:[], schedule:[], completedDays:{}, planId:null
};

/* ════════════════════════════════════════════════════════
   SUBJECT CONFIG
════════════════════════════════════════════════════════ */
const SUBJECT_CSS = {
    history:'s-history',polity:'s-polity',geography:'s-geography',economy:'s-economy',
    science:'s-science',environment:'s-environment',csat:'s-csat',ethics:'s-ethics',
    currentaffairs:'s-currentaffairs',revision:'s-revision',mock:'s-mock',essay:'s-essay',rest:'s-rest'
};
const SUBJECT_LABELS = {
    history:'History',polity:'Polity',geography:'Geography',economy:'Economy',
    science:'Science & Tech',environment:'Environment',csat:'CSAT',ethics:'Ethics GS IV',
    currentaffairs:'Current Affairs',revision:'Revision',mock:'Mock Test',essay:'Essay',rest:'Rest Day'
};

/* ════════════════════════════════════════════════════════
   INIT
════════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', function() {
    // Checkbox toggle style
    document.querySelectorAll('.subj-check input[type="checkbox"]').forEach(function(cb) {
        cb.addEventListener('change', function() {
            const lbl = document.getElementById('lbl-' + this.value);
            lbl.classList.toggle('checked', this.checked);
        });
    });

    // Tab switching
    document.querySelectorAll('.tab-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.tab-btn').forEach(function(b){ b.classList.remove('active'); });
            document.querySelectorAll('.tab-section').forEach(function(s){ s.classList.remove('active'); });
            btn.classList.add('active');
            document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
        });
    });

    // Table filter buttons
    document.querySelectorAll('.filter-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.filter-btn').forEach(function(b){ b.classList.remove('active'); });
            btn.classList.add('active');
            filterTable(btn.dataset.filter);
        });
    });

    updateCountdowns();
    setInterval(updateCountdowns, 60000);
    checkBrowserNotifications();
});

/* ════════════════════════════════════════════════════════
   COUNTDOWN
════════════════════════════════════════════════════════ */
function updateCountdowns() {
    const pDate = document.getElementById('prelims-date').value;
    const mDate = document.getElementById('mains-date').value;
    const iDate = document.getElementById('interview-date').value;

    setCountdown('cd-prelims-days', 'cd-prelims', pDate);
    setCountdown('cd-mains-days',   'cd-mains',   mDate);
    setCountdown('cd-interview-days','cd-interview',iDate);

    // Alert banner
    const pDays = daysUntil(pDate);
    if (pDays !== null && pDays <= 30 && pDays >= 0) {
        document.getElementById('alert-banner-text').textContent =
            pDays === 0 ? '🚨 PRELIMS IS TODAY!' :
            '⚠️ Prelims in ' + pDays + ' days';
        document.getElementById('alert-banner').classList.add('show');
    }
}

function daysUntil(dateStr) {
    if (!dateStr) return null;
    const today = new Date(); today.setHours(0,0,0,0);
    const target = new Date(dateStr);
    return Math.round((target - today) / 86400000);
}

function setCountdown(elId, pillId, dateStr) {
    const days = daysUntil(dateStr);
    const el   = document.getElementById(elId);
    const pill = document.getElementById(pillId);
    if (!el) return;
    if (days === null) { el.textContent = '—'; return; }
    if (days < 0) { el.textContent = 'Done'; pill.className = 'cd-pill safe'; return; }
    el.textContent = days;
    pill.className = 'cd-pill ' + (days <= 30 ? 'urgent' : days <= 90 ? 'warning' : 'safe');
}

/* ════════════════════════════════════════════════════════
   GENERATE TIMETABLE
════════════════════════════════════════════════════════ */
function generateTimetable() {
    const start    = document.getElementById('start-date').value;
    const prelims  = document.getElementById('prelims-date').value;
    const mains    = document.getElementById('mains-date').value;
    const intv     = document.getElementById('interview-date').value;
    const hours    = parseInt(document.getElementById('daily-hours').value);
    const rest     = document.getElementById('rest-day').value;
    const mode     = document.getElementById('study-mode').value;
    const planName = document.getElementById('plan-name').value;

    if (!start || !prelims) { showToast('Please set start and prelims dates first', 'error'); return; }

    // Gather selected subjects
    const selSubjects = [];
    document.querySelectorAll('.subj-check input[type="checkbox"]:checked').forEach(function(cb) {
        selSubjects.push(cb.value);
    });
    if (selSubjects.length === 0) { showToast('Select at least one subject', 'error'); return; }

    PLAN = { name:planName, startDate:start, prelimsDate:prelims, mainsDate:mains,
             interviewDate:intv, dailyHours:hours, restDay:rest, mode:mode,
             subjects:selSubjects, schedule:[], completedDays:{}, planId:PLAN.planId };

    const end   = intv || mains || prelims;
    const dates = getDateRange(start, end);
    const subjectRotation = buildSubjectRotation(selSubjects, mode, prelims, mains);

    let idx = 0;
    PLAN.schedule = dates.map(function(d) {
        const dayName = getDayName(d);
        const isRest  = rest !== 'None' && dayName === rest;
        const phase   = getPhase(d, prelims, mains, intv);
        const milestone = getMilestone(d, prelims, mains, intv);
        const subjs   = isRest ? ['rest'] : getSubjectsForDay(subjectRotation, idx, phase, mode);
        if (!isRest) idx++;

        return {date:d, day:dayName, phase:phase, subjects:subjs,
                hours: isRest ? 0 : hours, milestone:milestone, completed:false};
    });

    renderTable();
    renderAlerts();
    updateProgress();
    updateCountdowns();

    // Switch to timetable tab
    document.querySelectorAll('.tab-btn').forEach(function(b){ b.classList.remove('active'); });
    document.querySelectorAll('.tab-section').forEach(function(s){ s.classList.remove('active'); });
    document.querySelector('[data-tab="timetable"]').classList.add('active');
    document.getElementById('tab-timetable').classList.add('active');

    showToast('Timetable generated! ' + PLAN.schedule.length + ' days planned.', 'success');
}

function buildSubjectRotation(subjects, mode, prelims, mains) {
    // Standard / custom: just return subjects as-is
    return subjects;
}

function getSubjectsForDay(subjects, idx, phase, mode) {
    // phase-based: assign different subject pools
    if (mode === 'phase') {
        const preliSubj = ['history','polity','geography','economy','science','environment','csat','currentaffairs','revision','mock'];
        const mainsSubj = ['history','polity','geography','economy','science','environment','ethics','essay','currentaffairs','revision','mock'];
        const intvSubj  = ['ethics','currentaffairs','revision','mock','polity'];
        const pool = phase === 'Prelims'   ? preliSubj.filter(s => subjects.includes(s)) :
                     phase === 'Mains'     ? mainsSubj.filter(s => subjects.includes(s)) :
                     phase === 'Interview' ? intvSubj.filter(s => subjects.includes(s))  : subjects;
        const p = pool.length > 0 ? pool : subjects;
        // 2 subjects per day
        return [ p[idx % p.length], p[(idx+1) % p.length] ];
    }
    return [ subjects[idx % subjects.length], subjects[(idx+1) % subjects.length] ];
}

function getPhase(dateStr, prelims, mains, intv) {
    if (!prelims) return 'Prelims';
    if (dateStr <= prelims) return 'Prelims';
    if (mains && dateStr <= mains) return 'Mains';
    if (intv && dateStr <= intv) return 'Interview';
    return 'Post-Exam';
}

function getMilestone(dateStr, prelims, mains, intv) {
    if (dateStr === prelims)  return {type:'prelims',  label:'📍 Prelims Exam'};
    if (dateStr === mains)    return {type:'mains',    label:'📍 Mains Exam'};
    if (dateStr === intv)     return {type:'interview',label:'📍 Interview'};
    const d = daysUntil(dateStr) !== null ? null : null;
    // Warn 30, 15, 7 days before prelims
    if (prelims) {
        const diff = Math.round((new Date(prelims) - new Date(dateStr)) / 86400000);
        if (diff === 30) return {type:'warn', label:'⚡ 30 days to Prelims'};
        if (diff === 15) return {type:'warn', label:'⚡ 15 days to Prelims'};
        if (diff === 7)  return {type:'warn', label:'🔥 1 week to Prelims'};
    }
    if (mains) {
        const diff = Math.round((new Date(mains) - new Date(dateStr)) / 86400000);
        if (diff === 30) return {type:'warn', label:'⚡ 30 days to Mains'};
        if (diff === 7)  return {type:'warn', label:'🔥 1 week to Mains'};
    }
    return null;
}

function getDayName(dateStr) {
    const days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    return days[new Date(dateStr).getDay()];
}

function getDateRange(start, end) {
    const dates = [];
    let cur = new Date(start);
    const last = new Date(end);
    while (cur <= last) {
        dates.push(cur.toISOString().split('T')[0]);
        cur.setDate(cur.getDate() + 1);
    }
    return dates;
}

/* ════════════════════════════════════════════════════════
   RENDER TABLE
════════════════════════════════════════════════════════ */
function renderTable() {
    const tbody  = document.getElementById('tt-tbody');
    const table  = document.getElementById('tt-table');
    const empty  = document.getElementById('tt-empty');
    const today  = new Date().toISOString().split('T')[0];
    tbody.innerHTML = '';

    if (PLAN.schedule.length === 0) { table.style.display='none'; empty.style.display='block'; return; }

    table.style.display = 'table';
    empty.style.display = 'none';

    PLAN.schedule.forEach(function(row, i) {
        const isPast    = row.date < today;
        const isToday   = row.date === today;
        const isDone    = row.completed || PLAN.completedDays[row.date];

        const tr = document.createElement('tr');
        tr.dataset.date  = row.date;
        tr.dataset.phase = row.phase.toLowerCase();
        tr.className = (isToday ? 'today-row' : '') + (isPast && !isToday ? ' past-row' : '');

        // Phase colour
        const phaseColour = row.phase === 'Prelims'   ? 'var(--blue);background:var(--blue-lt)' :
                            row.phase === 'Mains'     ? 'var(--teal);background:var(--teal-lt)' :
                            row.phase === 'Interview' ? 'var(--amber);background:var(--amber-lt)' :
                                                        'var(--muted);background:var(--page)';

        // Subject tags
        const tagsHtml = row.subjects.map(function(s) {
            return '<span class="subj-tag ' + (SUBJECT_CSS[s]||'') + '">' + (SUBJECT_LABELS[s]||s) + '</span>';
        }).join('');

        // Milestone
        const milHtml = row.milestone
            ? '<span class="milestone-badge" style="background:' +
              (row.milestone.type==='prelims'   ? 'var(--blue-lt);color:var(--blue)' :
               row.milestone.type==='mains'     ? 'var(--teal-lt);color:var(--teal)' :
               row.milestone.type==='interview' ? 'var(--amber-lt);color:var(--amber)' :
               'var(--red-lt);color:var(--red)') + '">' + row.milestone.label + '</span>'
            : '<span style="color:var(--border)">—</span>';

        const doneClass = isDone ? 'done' : '';
        const doneIcon  = isDone
            ? '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>'
            : '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/></svg>';

        const dayClass = row.day === 'Sunday' ? 'day-cell sunday' :
                         row.day === 'Saturday' ? 'day-cell saturday' : 'day-cell';

        tr.innerHTML =
            '<td class="date-cell">' + formatDate(row.date) + '</td>' +
            '<td class="' + dayClass + '">' + row.day.substring(0,3) + '</td>' +
            '<td><span class="phase-label" style="color:' + phaseColour + '">' + row.phase + '</span></td>' +
            '<td>' + tagsHtml + '</td>' +
            '<td class="hours-cell">' + (row.hours > 0 ? row.hours + 'h' : '—') + '</td>' +
            '<td>' + milHtml + '</td>' +
            '<td><button class="done-btn ' + doneClass + '" onclick="toggleDone(\'' + row.date + '\', this)">' + doneIcon + '</button></td>';

        tbody.appendChild(tr);
    });
}

function formatDate(d) {
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    const dt = new Date(d);
    return dt.getDate() + ' ' + months[dt.getMonth()];
}

/* ════════════════════════════════════════════════════════
   FILTER TABLE
════════════════════════════════════════════════════════ */
function filterTable(f) {
    const today = new Date().toISOString().split('T')[0];
    const weekEnd = new Date(); weekEnd.setDate(weekEnd.getDate() + 7);
    const weekEndStr = weekEnd.toISOString().split('T')[0];

    document.querySelectorAll('#tt-tbody tr').forEach(function(row) {
        const d = row.dataset.date;
        const show =
            f === 'all'      ? true :
            f === 'today'    ? d === today :
            f === 'upcoming' ? d >= today :
            f === 'week'     ? (d >= today && d <= weekEndStr) : true;
        row.classList.toggle('hidden-row', !show);
    });
}

/* ════════════════════════════════════════════════════════
   MARK DONE
════════════════════════════════════════════════════════ */
function toggleDone(date, btn) {
    const isDone = btn.classList.toggle('done');
    PLAN.completedDays[date] = isDone;

    // Find schedule entry and update
    const entry = PLAN.schedule.find(function(r){ return r.date === date; });
    if (entry) entry.completed = isDone;

    btn.innerHTML = isDone
        ? '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>'
        : '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/></svg>';

    updateProgress();

    // Save to server
    if (PLAN.planId && entry) {
        fetch('save_timetable.php', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body:JSON.stringify({action:'mark_complete', date:date, subject:entry.subjects.join(','),
                completed:isDone?1:0, hours:isDone?entry.hours:0, timetable_id:PLAN.planId})
        }).catch(function(){});
    }
}

/* ════════════════════════════════════════════════════════
   RENDER ALERTS
════════════════════════════════════════════════════════ */
function renderAlerts() {
    const container = document.getElementById('alerts-container');
    const empty     = document.getElementById('alerts-empty');
    container.innerHTML = '';

    const alerts = buildAlerts();
    if (alerts.length === 0) { empty.style.display='block'; return; }
    empty.style.display = 'none';

    alerts.forEach(function(a, i) {
        const days   = daysUntil(a.date);
        const passed = days !== null && days < 0;
        const cdClass = days === null ? 'muted' : days <= 7 ? 'red' : days <= 30 ? 'amber' : 'green';
        const cdText  = passed ? 'Completed' : days === 0 ? 'TODAY!' : days + ' days left';

        const icoHtml = {
            prelims:  '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/></svg>',
            mains:    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>',
            interview:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
            custom:   '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
        };
        const icoBg = {prelims:'var(--blue-lt);color:var(--blue)', mains:'var(--teal-lt);color:var(--teal)', interview:'var(--amber-lt);color:var(--amber)', custom:'var(--violet-lt);color:var(--violet)'};

        const div = document.createElement('div');
        div.className = 'alert-card ' + a.type + (passed ? ' dismissed' : '');
        div.style.animationDelay = (i * 0.06) + 's';
        div.innerHTML =
            '<div class="alert-ico" style="background:' + (icoBg[a.type]||icoBg.custom) + '">' + (icoHtml[a.type]||icoHtml.custom) + '</div>' +
            '<div class="alert-info">' +
                '<div class="alert-title">' + a.label + '</div>' +
                '<div class="alert-date">' + (a.date ? formatDate(a.date) + ' ' + a.date.split('-')[0] : '') + '</div>' +
                '<div class="alert-countdown ' + cdClass + '">' + cdText + '</div>' +
            '</div>' +
            '<div class="alert-actions">' +
                (!passed ? '<button class="snooze-btn" onclick="dismissAlert(this)">Dismiss</button>' : '') +
            '</div>';
        container.appendChild(div);
    });
}

function buildAlerts() {
    const alerts = [];
    const p = PLAN.prelimsDate, m = PLAN.mainsDate, i = PLAN.interviewDate;
    if (p) {
        alerts.push({type:'prelims',   label:'UPSC Prelims Exam',    date:p});
        alerts.push({type:'custom',    label:'30-day Prelims prep milestone', date:offsetDate(p,-30)});
        alerts.push({type:'custom',    label:'Final revision week begins', date:offsetDate(p,-7)});
    }
    if (m) {
        alerts.push({type:'mains',     label:'UPSC Mains Exam',      date:m});
        alerts.push({type:'custom',    label:'30-day Mains prep milestone', date:offsetDate(m,-30)});
    }
    if (i) {
        alerts.push({type:'interview', label:'Personality Test (Interview)', date:i});
    }
    // Sort by date
    return alerts.sort(function(a,b){ return a.date > b.date ? 1 : -1; });
}

function offsetDate(dateStr, days) {
    const d = new Date(dateStr);
    d.setDate(d.getDate() + days);
    return d.toISOString().split('T')[0];
}

function dismissAlert(btn) {
    btn.closest('.alert-card').classList.add('dismissed');
    btn.style.display = 'none';
}

/* ════════════════════════════════════════════════════════
   UPDATE PROGRESS
════════════════════════════════════════════════════════ */
function updateProgress() {
    const schedule  = PLAN.schedule;
    const total     = schedule.filter(function(r){ return r.hours > 0; }).length;
    const completed = schedule.filter(function(r){ return r.completed || PLAN.completedDays[r.date]; }).length;
    const pct       = total > 0 ? Math.round(completed / total * 100) : 0;

    document.getElementById('stat-completed').textContent    = completed;
    document.getElementById('stat-total-days').textContent   = total;
    document.getElementById('stat-pct').textContent          = pct + '%';

    // Streak (consecutive completed days up to today)
    const today = new Date().toISOString().split('T')[0];
    let streak = 0;
    const past = schedule.filter(function(r){ return r.date <= today && r.hours > 0; }).reverse();
    for (let i = 0; i < past.length; i++) {
        if (past[i].completed || PLAN.completedDays[past[i].date]) streak++;
        else break;
    }
    document.getElementById('stat-streak').textContent = streak;

    // Phase breakdown
    const pDays  = schedule.filter(function(r){ return r.phase === 'Prelims'   && r.hours > 0; });
    const mDays  = schedule.filter(function(r){ return r.phase === 'Mains'     && r.hours > 0; });
    const iDays  = schedule.filter(function(r){ return r.phase === 'Interview' && r.hours > 0; });
    const pDone  = pDays.filter(function(r){ return r.completed || PLAN.completedDays[r.date]; }).length;
    const mDone  = mDays.filter(function(r){ return r.completed || PLAN.completedDays[r.date]; }).length;
    const iDone  = iDays.filter(function(r){ return r.completed || PLAN.completedDays[r.date]; }).length;

    setPhaseProgress('prog-prelims', pDone, pDays.length, 'var(--blue)');
    setPhaseProgress('prog-mains',   mDone, mDays.length, 'var(--teal)');
    setPhaseProgress('prog-intv',    iDone, iDays.length, 'var(--amber)');
}

function setPhaseProgress(id, done, total, color) {
    const pct = total > 0 ? Math.round(done/total*100) : 0;
    document.getElementById(id + '-val').textContent  = done + ' / ' + total + ' days';
    document.getElementById(id + '-fill').style.width = pct + '%';
    document.getElementById(id + '-fill').style.background = color;
}

/* ════════════════════════════════════════════════════════
   SAVE / LOAD
════════════════════════════════════════════════════════ */
function saveTimetable() {
    if (PLAN.schedule.length === 0) { showToast('Generate a timetable first', 'error'); return; }

    const payload = {
        action:'save_plan', plan_name:PLAN.name,
        start_date:PLAN.startDate, prelims_date:PLAN.prelimsDate,
        mains_date:PLAN.mainsDate, interview_date:PLAN.interviewDate,
        daily_hours:PLAN.dailyHours, subjects:PLAN.subjects,
        schedule:PLAN.schedule,
        alerts: buildAlerts().map(function(a){ return {type:a.type,label:a.label,date:a.date,days_before:0}; })
    };

    fetch('save_timetable.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body:JSON.stringify(payload)
    })
    .then(function(r){ return r.json(); })
    .then(function(d) {
        if (d.success) {
            PLAN.planId = d.plan_id;
            document.getElementById('save-banner').classList.add('show');
            setTimeout(function(){ document.getElementById('save-banner').classList.remove('show'); }, 3000);
            showToast('Timetable saved to your account!', 'success');
        } else {
            showToast('Save failed: ' + (d.message||'Unknown error'), 'error');
        }
    })
    .catch(function(e) {
        // Graceful offline fallback — save to localStorage
        localStorage.setItem('upsc_timetable_' + <?php echo $uid; ?>, JSON.stringify(PLAN));
        showToast('Saved locally (offline mode)', 'info');
    });
}

function loadSaved() {
    fetch('save_timetable.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body:JSON.stringify({action:'load_plan'})
    })
    .then(function(r){ return r.json(); })
    .then(function(d) {
        if (d.success && d.plan) {
            const p = d.plan;
            PLAN.planId       = p.id;
            PLAN.name         = p.plan_name;
            PLAN.startDate    = p.start_date;
            PLAN.prelimsDate  = p.prelims_date || '';
            PLAN.mainsDate    = p.mains_date   || '';
            PLAN.interviewDate= p.interview_date || '';
            PLAN.dailyHours   = p.daily_hours;
            PLAN.subjects     = p.subjects || [];
            PLAN.schedule     = p.schedule || [];

            // Restore UI
            document.getElementById('start-date').value    = PLAN.startDate;
            document.getElementById('prelims-date').value  = PLAN.prelimsDate;
            document.getElementById('mains-date').value    = PLAN.mainsDate;
            document.getElementById('interview-date').value= PLAN.interviewDate;
            document.getElementById('daily-hours').value   = PLAN.dailyHours;
            document.getElementById('plan-name').value     = PLAN.name;

            renderTable();
            renderAlerts();
            updateProgress();
            updateCountdowns();
            showToast('Plan loaded: ' + PLAN.name, 'success');
        } else {
            // Try localStorage
            const local = localStorage.getItem('upsc_timetable_' + <?php echo $uid; ?>);
            if (local) {
                PLAN = JSON.parse(local);
                renderTable(); renderAlerts(); updateProgress(); updateCountdowns();
                showToast('Loaded from local storage', 'info');
            } else {
                showToast('No saved plan found', 'error');
            }
        }
    })
    .catch(function() {
        const local = localStorage.getItem('upsc_timetable_' + <?php echo $uid; ?>);
        if (local) {
            PLAN = JSON.parse(local);
            renderTable(); renderAlerts(); updateProgress(); updateCountdowns();
            showToast('Loaded from local storage', 'info');
        } else {
            showToast('Could not load plan', 'error');
        }
    });
}

/* ════════════════════════════════════════════════════════
   EXPORT CSV
════════════════════════════════════════════════════════ */
function exportCSV() {
    if (PLAN.schedule.length === 0) { showToast('Generate timetable first', 'error'); return; }
    let csv = 'Date,Day,Phase,Subjects,Hours,Milestone,Completed\n';
    PLAN.schedule.forEach(function(row) {
        const subjs = row.subjects.map(function(s){ return SUBJECT_LABELS[s]||s; }).join(' + ');
        const mil   = row.milestone ? row.milestone.label : '';
        const done  = (row.completed || PLAN.completedDays[row.date]) ? 'Yes' : 'No';
        csv += [row.date, row.day, row.phase, '"'+subjs+'"', row.hours, '"'+mil+'"', done].join(',') + '\n';
    });
    const blob = new Blob([csv], {type:'text/csv'});
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href = url; a.download = 'UPSC_Timetable_' + PLAN.name.replace(/\s+/g,'_') + '.csv';
    a.click(); URL.revokeObjectURL(url);
    showToast('CSV downloaded!', 'success');
}

/* ════════════════════════════════════════════════════════
   TIME SLOTS
════════════════════════════════════════════════════════ */
const SLOT_NAMES = ['Morning','Afternoon','Evening','Night','Pre-dawn','Late Night'];
let slotCount = 3;
function addSlot() {
    const label = SLOT_NAMES[slotCount % SLOT_NAMES.length];
    slotCount++;
    const div = document.createElement('div');
    div.className = 'slot-row';
    div.innerHTML = '<span class="slot-label">'+label+'</span>' +
        '<input type="time" value="21:00"> <span style="font-size:.75rem;color:var(--muted)">to</span>' +
        '<input type="time" value="23:00">' +
        '<button class="slot-remove" onclick="removeSlot(this)"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>';
    document.getElementById('slots-container').appendChild(div);
}
function removeSlot(btn) {
    const slots = document.querySelectorAll('.slot-row');
    if (slots.length <= 1) { showToast('Keep at least one time slot', 'error'); return; }
    btn.closest('.slot-row').remove();
}

/* ════════════════════════════════════════════════════════
   BROWSER NOTIFICATIONS
════════════════════════════════════════════════════════ */
function checkBrowserNotifications() {
    if (!('Notification' in window)) return;
    if (Notification.permission === 'default') {
        setTimeout(function() {
            Notification.requestPermission();
        }, 3000);
    }
}

function sendNotification(title, body) {
    if (Notification.permission === 'granted') {
        new Notification(title, {body: body, icon: 'assets/icon.png'});
    }
}

// Check daily alerts on load
function checkDailyAlerts() {
    const alerts = buildAlerts();
    const today  = new Date().toISOString().split('T')[0];
    alerts.forEach(function(a) {
        const d = daysUntil(a.date);
        if (d === 0)  sendNotification('📅 ' + a.label + ' — TODAY!', 'Best of luck!');
        if (d === 7)  sendNotification('⚡ 1 week to ' + a.label, 'Stay on track with your schedule.');
        if (d === 30) sendNotification('📣 30 days to ' + a.label, 'Keep your momentum going!');
    });
}

/* ════════════════════════════════════════════════════════
   TOAST
════════════════════════════════════════════════════════ */
let toastTimer = null;
function showToast(msg, type) {
    const t  = document.getElementById('toast');
    const tm = document.getElementById('toast-msg');
    tm.textContent = msg;
    t.className = 'toast show ' + (type==='success'?'success-toast':type==='error'?'error-toast':'info-toast');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function(){ t.classList.remove('show'); }, 3000);
}
</script>
</body>
</html>