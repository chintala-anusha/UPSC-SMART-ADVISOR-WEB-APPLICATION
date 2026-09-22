<?php
include 'includes/auth.php';   // guards page, sets $uid $user_name

/* ── AI Ask (preserved from original) ── */
$res = '';
if (isset($_POST['ask']) && !empty(trim($_POST['question']))) {
    $q = htmlspecialchars(trim($_POST['question']));
    // Hook your llama / OpenAI API here
    if (function_exists('ask_llama')) {
        include_once 'api/llama_api.php';
        $res = ask_llama("UPSC Study Guide: " . $q);
    } else {
        $res = "AI integration coming soon. Your question: \"$q\"";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Preparation Roadmap — UPSC Command Center</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">

<style>
:root {
    --grad-start:#1a0533; --grad-mid:#3d0f6b; --grad-end:#6b1fa8;
    --violet:#7c3aed; --violet-lt:#f0eaff; --violet-dk:#5b21b6;
    --coral:#ff6b4a;  --coral-lt:#fff0ed;
    --blue:#3b82f6;   --blue-lt:#eff5ff;
    --teal:#0fb98a;   --teal-lt:#e6faf4;
    --amber:#f59e0b;  --amber-lt:#fffbeb;
    --green:#16a34a;  --green-lt:#f0fdf4;
    --red:#ef4444;    --red-lt:#fef2f2;
    --pink:#ec4899;   --pink-lt:#fdf2f8;
    --ink:#1a1523; --ink2:#3d3549; --muted:#6b6579;
    --border:rgba(0,0,0,0.07); --surface:#fff; --page:#f4f2f8;
    --ease:cubic-bezier(0.2,0.8,0.2,1);
}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;-webkit-font-smoothing:antialiased;}
html{scroll-behavior:smooth;}
body{font-family:'DM Sans',sans-serif;background:var(--page);color:var(--ink);overflow-x:hidden;}
a{text-decoration:none;color:inherit;}

/* ── HERO ── */
.hero{
    background:linear-gradient(135deg,var(--grad-start) 0%,var(--grad-mid) 50%,var(--grad-end) 100%);
    position:relative;padding:5rem 2rem 7rem;text-align:center;color:#fff;overflow:hidden;
}
.hero::before{
    content:'';position:absolute;top:50%;left:50%;transform:translate(-50%,-55%);
    width:600px;height:400px;
    background:radial-gradient(ellipse,rgba(160,80,255,0.28) 0%,transparent 70%);
    pointer-events:none;
}
.hero-inner{position:relative;z-index:2;max-width:700px;margin:0 auto;}
.back-btn{
    position:absolute;top:1.75rem;left:1.75rem;z-index:50;
    width:46px;height:46px;border-radius:13px;
    display:flex;align-items:center;justify-content:center;
    background:rgba(255,255,255,0.13);border:1px solid rgba(255,255,255,0.22);
    backdrop-filter:blur(12px);color:#fff;transition:all 0.25s var(--ease);
}
.back-btn:hover{background:rgba(255,255,255,0.25);transform:translateY(-2px);}
.back-btn svg{width:20px;height:20px;}
.pre-badge{
    display:inline-block;font-family:'Syne',sans-serif;
    font-size:0.7rem;font-weight:600;letter-spacing:0.18em;text-transform:uppercase;
    background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.22);
    border-radius:100px;padding:0.35rem 1.1rem;margin-bottom:1.2rem;
}
.hero h1{
    font-family:'Syne',sans-serif;font-size:clamp(2rem,5vw,3.2rem);
    font-weight:800;line-height:1.12;letter-spacing:-0.02em;margin-bottom:0.8rem;
}
.hero p{font-size:1rem;font-weight:300;opacity:0.82;max-width:500px;margin:0 auto;}
.wave{position:absolute;bottom:-1px;left:0;width:100%;line-height:0;z-index:3;}
.wave svg{display:block;width:100%;height:80px;}

/* ── PHASE NAV TABS ── */
.phase-nav{
    position:sticky;top:0;z-index:50;
    background:var(--surface);border-bottom:1px solid var(--border);
    display:flex;justify-content:center;gap:0;overflow-x:auto;
    box-shadow:0 2px 12px rgba(0,0,0,0.06);
}
.pnav-btn{
    padding:1rem 1.5rem;border:none;background:transparent;
    font-family:'DM Sans',sans-serif;font-size:0.82rem;font-weight:500;
    color:var(--muted);cursor:pointer;transition:all 0.2s;
    display:flex;align-items:center;gap:0.5rem;white-space:nowrap;
    border-bottom:2.5px solid transparent;position:relative;top:1px;
}
.pnav-btn .pnav-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0;}
.pnav-btn:hover{color:var(--ink);}
.pnav-btn.active{color:var(--violet);border-bottom-color:var(--violet);font-weight:600;}

/* ── MAIN ── */
.main{max-width:1120px;margin:0 auto;padding:3rem 1.5rem 5rem;}

/* ── SECTION HEADER ── */
.section{display:none;}
.section.active{display:block;}
.section-header{
    display:flex;align-items:flex-start;gap:1.25rem;margin-bottom:2.5rem;
    padding-bottom:1.5rem;border-bottom:1px solid var(--border);
}
.section-ico{
    width:60px;height:60px;border-radius:18px;flex-shrink:0;
    display:flex;align-items:center;justify-content:center;
}
.section-ico svg{width:28px;height:28px;}
.section-header h2{font-family:'Syne',sans-serif;font-size:1.6rem;font-weight:800;margin-bottom:0.4rem;letter-spacing:-0.02em;}
.section-header p{font-size:0.9rem;color:var(--muted);line-height:1.6;max-width:600px;}

/* ── ROADMAP NODES ── */
.roadmap{position:relative;padding-left:2.5rem;}
.roadmap::before{
    content:'';position:absolute;left:11px;top:8px;bottom:8px;
    width:2px;background:linear-gradient(180deg,var(--violet) 0%,var(--teal) 50%,var(--coral) 100%);
    opacity:0.25;
}
.node{
    position:relative;margin-bottom:1.5rem;
    opacity:0;animation:fadeUp 0.5s var(--ease) both;
}
.node:nth-child(1){animation-delay:.05s}
.node:nth-child(2){animation-delay:.10s}
.node:nth-child(3){animation-delay:.12s}
.node:nth-child(4){animation-delay:.14s}
.node:nth-child(5){animation-delay:.16s}
.node:nth-child(6){animation-delay:.18s}
.node:nth-child(7){animation-delay:.20s}
.node:nth-child(8){animation-delay:.22s}
.node:nth-child(9){animation-delay:.24s}
.node:nth-child(n+10){animation-delay:.26s}

.node-dot{
    position:absolute;left:-2.5rem;top:1.1rem;
    width:22px;height:22px;border-radius:50%;
    border:3px solid var(--surface);
    display:flex;align-items:center;justify-content:center;
    font-size:0.55rem;font-weight:700;color:#fff;
    z-index:2;
}

/* card */
.node-card{
    background:var(--surface);border-radius:18px;border:1px solid var(--border);
    overflow:hidden;box-shadow:0 3px 16px rgba(0,0,0,0.05);
    transition:all 0.25s var(--ease);cursor:pointer;
}
.node-card:hover{transform:translateX(4px);box-shadow:0 8px 30px rgba(0,0,0,0.1);}
.node-card.expanded .node-body{max-height:2000px;opacity:1;}
.node-card.expanded .node-chevron{transform:rotate(180deg);}

.node-head{
    padding:1.1rem 1.25rem;display:flex;align-items:center;gap:1rem;
}
.node-ico{
    width:44px;height:44px;border-radius:13px;flex-shrink:0;
    display:flex;align-items:center;justify-content:center;
}
.node-ico svg{width:22px;height:22px;}
.node-title-wrap{flex:1;min-width:0;}
.node-title{font-family:'Syne',sans-serif;font-size:0.95rem;font-weight:700;color:var(--ink);margin-bottom:0.15rem;}
.node-subtitle{font-size:0.78rem;color:var(--muted);}
.node-tag{
    font-size:0.65rem;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;
    border-radius:6px;padding:0.25rem 0.6rem;white-space:nowrap;flex-shrink:0;
}
.node-chevron{
    width:28px;height:28px;border-radius:8px;background:var(--page);
    display:flex;align-items:center;justify-content:center;color:var(--muted);
    transition:transform 0.25s ease;flex-shrink:0;
}
.node-chevron svg{width:14px;height:14px;}

/* body */
.node-body{
    max-height:0;overflow:hidden;opacity:0;
    transition:max-height 0.4s ease,opacity 0.3s ease;
    border-top:1px solid var(--border);
}
.node-body-inner{padding:1.25rem 1.25rem 1.5rem;}

/* ── INFO GRID inside node ── */
.info-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:0.75rem;margin-bottom:1.25rem;}
.info-cell{
    background:var(--page);border-radius:12px;padding:0.85rem 1rem;
}
.info-cell .ic-label{font-size:0.68rem;font-weight:600;text-transform:uppercase;letter-spacing:0.09em;color:var(--muted);margin-bottom:0.3rem;}
.info-cell .ic-val{font-family:'Syne',sans-serif;font-size:0.95rem;font-weight:700;color:var(--ink);line-height:1.3;}

/* ── SUBJECT TABLE ── */
.subject-table{width:100%;border-collapse:collapse;font-size:0.82rem;margin-bottom:1.25rem;}
.subject-table th{
    text-align:left;padding:0.55rem 0.85rem;font-size:0.68rem;
    font-weight:600;text-transform:uppercase;letter-spacing:0.08em;
    color:var(--muted);background:var(--page);
}
.subject-table th:first-child{border-radius:8px 0 0 8px;}
.subject-table th:last-child{border-radius:0 8px 8px 0;}
.subject-table td{padding:0.6rem 0.85rem;border-bottom:1px solid var(--border);vertical-align:top;}
.subject-table tr:last-child td{border-bottom:none;}
.subject-table td:first-child{font-weight:500;color:var(--ink);}
.subject-table td:not(:first-child){color:var(--muted);}

/* ── BOOKS LIST ── */
.books-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:0.65rem;margin-bottom:1.25rem;}
.book-chip{
    display:flex;align-items:flex-start;gap:0.65rem;
    background:var(--page);border-radius:10px;padding:0.7rem 0.85rem;
}
.book-chip .book-ico{
    width:32px;height:32px;border-radius:8px;flex-shrink:0;
    display:flex;align-items:center;justify-content:center;
    font-size:1rem;
}
.book-chip .book-title{font-size:0.8rem;font-weight:500;color:var(--ink);line-height:1.35;}
.book-chip .book-auth{font-size:0.72rem;color:var(--muted);}

/* ── TIPS ── */
.tips-list{list-style:none;display:flex;flex-direction:column;gap:0.5rem;margin-bottom:1rem;}
.tip-item{
    display:flex;align-items:flex-start;gap:0.65rem;
    font-size:0.83rem;color:var(--ink2);line-height:1.55;
}
.tip-item::before{
    content:'→';flex-shrink:0;font-weight:600;margin-top:1px;
}

/* ── TWO-COL LAYOUT ── */
.two-col{display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-bottom:1.25rem;}
@media(max-width:640px){.two-col{grid-template-columns:1fr;}}

.col-box{background:var(--page);border-radius:14px;padding:1rem 1.1rem;}
.col-box h5{
    font-family:'Syne',sans-serif;font-size:0.78rem;font-weight:700;
    text-transform:uppercase;letter-spacing:0.09em;margin-bottom:0.75rem;
}
.col-box ul{list-style:none;display:flex;flex-direction:column;gap:0.4rem;}
.col-box ul li{font-size:0.8rem;color:var(--ink2);line-height:1.5;padding-left:0.9rem;position:relative;}
.col-box ul li::before{content:'·';position:absolute;left:0;font-weight:700;}

/* ── PROGRESS TRACKER ── */
.phase-progress{
    display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;
    margin-bottom:2.5rem;
}
.prog-card{
    background:var(--surface);border-radius:16px;border:1px solid var(--border);
    padding:1.25rem;text-align:center;
    box-shadow:0 3px 14px rgba(0,0,0,0.04);
    animation:fadeUp 0.5s var(--ease) both;
}
.prog-card:nth-child(1){animation-delay:.05s}
.prog-card:nth-child(2){animation-delay:.10s}
.prog-card:nth-child(3){animation-delay:.15s}
.prog-ico{
    width:50px;height:50px;border-radius:14px;
    display:flex;align-items:center;justify-content:center;margin:0 auto 0.85rem;
}
.prog-ico svg{width:24px;height:24px;}
.prog-title{font-family:'Syne',sans-serif;font-size:0.9rem;font-weight:700;margin-bottom:0.25rem;}
.prog-sub{font-size:0.75rem;color:var(--muted);}

/* ── AI ASK BOX ── */
.ai-box{
    background:linear-gradient(135deg,var(--grad-start),var(--grad-end));
    border-radius:20px;padding:2rem;margin-top:3rem;color:#fff;
}
.ai-box h3{font-family:'Syne',sans-serif;font-size:1.1rem;font-weight:700;margin-bottom:0.4rem;}
.ai-box p{font-size:0.85rem;opacity:0.8;margin-bottom:1.25rem;}
.ai-form{display:flex;gap:0.65rem;}
.ai-form input{
    flex:1;padding:0.7rem 1rem;border-radius:12px;border:none;
    background:rgba(255,255,255,0.15);color:#fff;
    font-family:'DM Sans',sans-serif;font-size:0.88rem;outline:none;
    backdrop-filter:blur(8px);
}
.ai-form input::placeholder{color:rgba(255,255,255,0.55);}
.ai-form input:focus{background:rgba(255,255,255,0.22);}
.ai-form button{
    padding:0.7rem 1.4rem;border-radius:12px;border:none;
    background:rgba(255,255,255,0.18);color:#fff;
    font-family:'Syne',sans-serif;font-size:0.85rem;font-weight:700;
    cursor:pointer;transition:all 0.2s;white-space:nowrap;
}
.ai-form button:hover{background:rgba(255,255,255,0.28);}
.ai-result{
    margin-top:1rem;background:rgba(255,255,255,0.1);
    border-radius:12px;padding:1rem;font-size:0.85rem;line-height:1.65;
    white-space:pre-wrap;display:none;
}
.ai-result.show{display:block;}

/* ── COLORS HELPERS ── */
.bg-violet{background:var(--violet-lt);} .c-violet{color:var(--violet);}
.bg-coral {background:var(--coral-lt);}  .c-coral {color:var(--coral);}
.bg-blue  {background:var(--blue-lt);}   .c-blue  {color:var(--blue);}
.bg-teal  {background:var(--teal-lt);}   .c-teal  {color:var(--teal);}
.bg-amber {background:var(--amber-lt);}  .c-amber {color:var(--amber);}
.bg-green {background:var(--green-lt);}  .c-green {color:var(--green);}
.bg-pink  {background:var(--pink-lt);}   .c-pink  {color:var(--pink);}

.tag-violet{background:var(--violet-lt);color:var(--violet);}
.tag-coral {background:var(--coral-lt); color:var(--coral);}
.tag-blue  {background:var(--blue-lt);  color:var(--blue);}
.tag-teal  {background:var(--teal-lt);  color:var(--teal);}
.tag-amber {background:var(--amber-lt); color:var(--amber);}
.tag-green {background:var(--green-lt); color:var(--green);}
.tag-pink  {background:var(--pink-lt);  color:var(--pink);}

@keyframes fadeUp{from{opacity:0;transform:translateY(18px);}to{opacity:1;transform:translateY(0);}}

@media(max-width:768px){
    .phase-progress{grid-template-columns:1fr 1fr;}
    .info-grid{grid-template-columns:1fr 1fr;}
}
@media(max-width:480px){
    .phase-progress{grid-template-columns:1fr;}
    .info-grid{grid-template-columns:1fr 1fr;}
    .books-grid{grid-template-columns:1fr;}
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
        <span class="pre-badge">Complete UPSC Path</span>
        <h1>Preparation Roadmap</h1>
        <p>From eligibility to IAS — every step, every book, every subject mapped out as a clear node-by-node path.</p>
    </div>
    <div class="wave">
        <svg viewBox="0 0 1440 80" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
            <path d="M0,40 C180,80 360,0 540,40 C720,80 900,0 1080,40 C1260,80 1380,20 1440,40 L1440,80 L0,80 Z" fill="#f4f2f8"/>
        </svg>
    </div>
</header>

<!-- ── PHASE NAV ── -->
<nav class="phase-nav">
    <button class="pnav-btn active" data-section="overview">
        <span class="pnav-dot" style="background:#7c3aed"></span> Overview
    </button>
    <button class="pnav-btn" data-section="eligibility">
        <span class="pnav-dot" style="background:#ff6b4a"></span> Eligibility
    </button>
    <button class="pnav-btn" data-section="prelims">
        <span class="pnav-dot" style="background:#3b82f6"></span> Prelims
    </button>
    <button class="pnav-btn" data-section="mains">
        <span class="pnav-dot" style="background:#0fb98a"></span> Mains
    </button>
    <button class="pnav-btn" data-section="interview">
        <span class="pnav-dot" style="background:#f59e0b"></span> Interview
    </button>
    <button class="pnav-btn" data-section="askguide">
        <span class="pnav-dot" style="background:#ec4899"></span> AI Guide
    </button>
</nav>

<!-- ══════════════════════════════════════════════
     MAIN
══════════════════════════════════════════════ -->
<main class="main">

<!-- ════════════ OVERVIEW ════════════ -->
<section class="section active" id="section-overview">

    <div class="section-header">
        <div class="section-ico bg-violet c-violet">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polygon points="3 11 22 2 13 21 11 13 3 11"/></svg>
        </div>
        <div>
            <h2>The Complete UPSC Journey</h2>
            <p>Three stages, one goal — the Indian Administrative Service. This roadmap covers every node from application to final recommendation.</p>
        </div>
    </div>

    <!-- Phase cards -->
    <div class="phase-progress">
        <div class="prog-card" style="border-top:3px solid var(--blue);">
            <div class="prog-ico bg-blue c-blue">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </div>
            <div class="prog-title">Stage 1 — Prelims</div>
            <div class="prog-sub">2 papers · 400 marks · Qualifying</div>
        </div>
        <div class="prog-card" style="border-top:3px solid var(--teal);">
            <div class="prog-ico bg-teal c-teal">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            </div>
            <div class="prog-title">Stage 2 — Mains</div>
            <div class="prog-sub">9 papers · 1750 marks · Descriptive</div>
        </div>
        <div class="prog-card" style="border-top:3px solid var(--amber);">
            <div class="prog-ico bg-amber c-amber">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
            <div class="prog-title">Stage 3 — Interview</div>
            <div class="prog-sub">Personality Test · 275 marks</div>
        </div>
    </div>

    <!-- Timeline roadmap -->
    <div class="roadmap">

        <?php
        $overviewNodes = [
            ['color'=>'--violet','bg'=>'bg-violet','ico'=>'coral','tag'=>'tag-coral','tag_txt'=>'Start Here',
             'title'=>'Check Eligibility','sub'=>'Age, nationality, education, attempts',
             'body'=>'<ul class="tips-list">
                <li class="tip-item">Age: 21–32 years (General). OBC: 35 yrs. SC/ST: 37 yrs. PwBD: 42 yrs.</li>
                <li class="tip-item">Nationality: Indian citizen (or Nepal/Bhutan/Tibet/Pakistan/East Africa migrant with intent to settle)</li>
                <li class="tip-item">Education: Bachelor\'s degree in any discipline from a recognised university</li>
                <li class="tip-item">Attempts: General — 6. OBC — 9. SC/ST — Unlimited (till age limit). EWS — 9</li>
             </ul>'],
            ['color'=>'--blue','bg'=>'bg-blue','ico'=>'blue','tag'=>'tag-blue','tag_txt'=>'~Feb/Mar',
             'title'=>'Apply for Civil Services Exam','sub'=>'UPSC notification released annually',
             'body'=>'<ul class="tips-list">
                <li class="tip-item">UPSC releases notification typically in February each year</li>
                <li class="tip-item">Fill online form at upsconline.nic.in within 2–3 week window</li>
                <li class="tip-item">Application fee: ₹100 (SC/ST/Female/PwBD — exempt)</li>
                <li class="tip-item">Upload photo, signature, and required documents</li>
             </ul>'],
            ['color'=>'--blue','bg'=>'bg-blue','ico'=>'blue','tag'=>'tag-blue','tag_txt'=>'May/Jun',
             'title'=>'Prelims Examination','sub'=>'2 papers, same day · Objective MCQ',
             'body'=>'<p style="font-size:.85rem;color:var(--muted);margin-bottom:1rem;">Click the <strong>Prelims</strong> tab for full details, subjects, and books.</p>
             <div class="info-grid">
                <div class="info-cell"><div class="ic-label">GS Paper I</div><div class="ic-val">100 Qs · 200 marks · 2 hrs</div></div>
                <div class="info-cell"><div class="ic-label">CSAT Paper II</div><div class="ic-val">80 Qs · 200 marks · 2 hrs</div></div>
                <div class="info-cell"><div class="ic-label">Negative Marking</div><div class="ic-val">−1/3 per wrong answer</div></div>
                <div class="info-cell"><div class="ic-label">CSAT</div><div class="ic-val">Qualifying only (33%)</div></div>
             </div>'],
            ['color'=>'--violet','bg'=>'bg-violet','ico'=>'violet','tag'=>'tag-violet','tag_txt'=>'Result',
             'title'=>'Prelims Result & Cut-off','sub'=>'Only GS Paper I score counts',
             'body'=>'<ul class="tips-list">
                <li class="tip-item">Result announced ~2 months after exam</li>
                <li class="tip-item">Cut-off varies: General ~90–110/200, OBC ~85+, SC/ST ~75+</li>
                <li class="tip-item">Approx 10,000–12,000 candidates qualify for Mains out of ~500,000</li>
             </ul>'],
            ['color'=>'--teal','bg'=>'bg-teal','ico'=>'teal','tag'=>'tag-teal','tag_txt'=>'Sep/Oct',
             'title'=>'Mains Examination','sub'=>'9 papers over 5 days · Descriptive',
             'body'=>'<p style="font-size:.85rem;color:var(--muted);margin-bottom:1rem;">Click the <strong>Mains</strong> tab for full paper breakdown, subjects, and books.</p>
             <div class="info-grid">
                <div class="info-cell"><div class="ic-label">Total Papers</div><div class="ic-val">9 Papers</div></div>
                <div class="info-cell"><div class="ic-label">Marks Counted</div><div class="ic-val">1750 (7 papers)</div></div>
                <div class="info-cell"><div class="ic-label">Duration</div><div class="ic-val">3 hrs each paper</div></div>
                <div class="info-cell"><div class="ic-label">No Negative</div><div class="ic-val">Descriptive answers</div></div>
             </div>'],
            ['color'=>'--violet','bg'=>'bg-violet','ico'=>'violet','tag'=>'tag-violet','tag_txt'=>'Result',
             'title'=>'Mains Result & DAF','sub'=>'Detailed Application Form submission',
             'body'=>'<ul class="tips-list">
                <li class="tip-item">~2,500 candidates called for interview from Mains</li>
                <li class="tip-item">Fill Detailed Application Form (DAF) — this forms basis of interview questions</li>
                <li class="tip-item">List all hobbies, optional subject, graduation, home state, family background accurately</li>
             </ul>'],
            ['color'=>'--amber','bg'=>'bg-amber','ico'=>'amber','tag'=>'tag-amber','tag_txt'=>'Feb–May',
             'title'=>'Personality Test (Interview)','sub'=>'Board interview at UPSC Bhavan, New Delhi',
             'body'=>'<ul class="tips-list">
                <li class="tip-item">275 marks · No syllabus officially — tests mental calibre and personality</li>
                <li class="tip-item">Panel of 5 members, ~30–45 minutes</li>
                <li class="tip-item">Focus: DAF, current affairs, optional subject, state awareness, ethics</li>
                <li class="tip-item">Be honest, balanced, aware of national and global issues</li>
             </ul>'],
            ['color'=>'--green','bg'=>'bg-green','ico'=>'green','tag'=>'tag-green','tag_txt'=>'Final',
             'title'=>'Final Merit List & Allocation','sub'=>'Service allocation based on rank and preference',
             'body'=>'<ul class="tips-list">
                <li class="tip-item">Final rank = Mains (1750) + Interview (275) = 2025 marks total</li>
                <li class="tip-item">~1000 candidates recommended annually</li>
                <li class="tip-item">Services: IAS, IPS, IFS, IRS, IPoS, etc. in order of preference filled by rank</li>
                <li class="tip-item">Training at LBSNAA, Mussoorie (IAS) for 2 years</li>
             </ul>'],
        ];

        foreach ($overviewNodes as $i => $n):
        ?>
        <div class="node">
            <div class="node-dot" style="background:var(<?php echo $n['color']; ?>);"><?php echo $i+1; ?></div>
            <div class="node-card" onclick="toggleNode(this)">
                <div class="node-head">
                    <div class="node-ico <?php echo $n['bg'].' c-'.$n['ico']; ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <div class="node-title-wrap">
                        <div class="node-title"><?php echo $n['title']; ?></div>
                        <div class="node-subtitle"><?php echo $n['sub']; ?></div>
                    </div>
                    <span class="node-tag <?php echo $n['tag']; ?>"><?php echo $n['tag_txt']; ?></span>
                    <div class="node-chevron"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg></div>
                </div>
                <div class="node-body"><div class="node-body-inner"><?php echo $n['body']; ?></div></div>
            </div>
        </div>
        <?php endforeach; ?>

    </div>
</section>

<!-- ════════════ ELIGIBILITY ════════════ -->
<section class="section" id="section-eligibility">
    <div class="section-header">
        <div class="section-ico bg-coral c-coral">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </div>
        <div>
            <h2>Eligibility Criteria</h2>
            <p>Before you begin, verify you meet all UPSC eligibility requirements. These are strict and non-negotiable.</p>
        </div>
    </div>
    <div class="roadmap">

        <?php
        function renderNode($num, $color, $bg, $ico, $tag_class, $tag_txt, $title, $sub, $body) {
            echo '<div class="node">';
            echo '<div class="node-dot" style="background:var('.$color.');">'.$num.'</div>';
            echo '<div class="node-card" onclick="toggleNode(this)">';
            echo '<div class="node-head">';
            echo '<div class="node-ico '.$bg.' c-'.$ico.'">';
            echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>';
            echo '</div>';
            echo '<div class="node-title-wrap"><div class="node-title">'.$title.'</div><div class="node-subtitle">'.$sub.'</div></div>';
            echo '<span class="node-tag '.$tag_class.'">'.$tag_txt.'</span>';
            echo '<div class="node-chevron"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg></div>';
            echo '</div>';
            echo '<div class="node-body"><div class="node-body-inner">'.$body.'</div></div>';
            echo '</div></div>';
        }

        renderNode(1,'--coral','bg-coral','coral','tag-coral','Mandatory',
            'Nationality',
            'Indian citizen or specified categories',
            '<ul class="tips-list">
                <li class="tip-item">Must be a citizen of India</li>
                <li class="tip-item">Subjects of Nepal or Bhutan are eligible</li>
                <li class="tip-item">Tibetan refugees who came before 1st Jan 1962 with intent to permanently settle in India</li>
                <li class="tip-item">Persons of Indian origin from Pakistan, Burma, Sri Lanka, Kenya, Uganda, Tanzania, Zambia, Malawi, Zaire, Ethiopia, Vietnam who have migrated with intent to permanently settle in India</li>
            </ul>'
        );

        renderNode(2,'--coral','bg-coral','coral','tag-coral','Age Limits',
            'Age Criteria',
            '21–32 years (General) with relaxations',
            '<table class="subject-table">
                <tr><th>Category</th><th>Min Age</th><th>Max Age</th></tr>
                <tr><td>General / EWS</td><td>21 years</td><td>32 years</td></tr>
                <tr><td>OBC (Non-Creamy Layer)</td><td>21 years</td><td>35 years (+3)</td></tr>
                <tr><td>SC / ST</td><td>21 years</td><td>37 years (+5)</td></tr>
                <tr><td>PwBD (General/EWS)</td><td>21 years</td><td>42 years (+10)</td></tr>
                <tr><td>PwBD (OBC)</td><td>21 years</td><td>45 years (+13)</td></tr>
                <tr><td>PwBD (SC/ST)</td><td>21 years</td><td>47 years (+15)</td></tr>
                <tr><td>Ex-Servicemen</td><td>21 years</td><td>37 years</td></tr>
                <tr><td>J&K Domicile (1980–89)</td><td>21 years</td><td>37 years</td></tr>
            </table>'
        );

        renderNode(3,'--blue','bg-blue','blue','tag-blue','Academic',
            'Educational Qualification',
            'Bachelor\'s degree from a recognised university',
            '<ul class="tips-list">
                <li class="tip-item">A degree of a Central/State/Deemed University or any equivalent qualification</li>
                <li class="tip-item">Any stream is accepted — Arts, Science, Commerce, Engineering, Medicine, Law</li>
                <li class="tip-item">Final year students can also apply (must produce degree before Mains DAF)</li>
                <li class="tip-item">AMIE (Institution of Engineers India) and similar professional qualifications are accepted</li>
            </ul>'
        );

        renderNode(4,'--violet','bg-violet','violet','tag-violet','Critical',
            'Number of Attempts',
            'Strictly monitored — counts from first Prelims appearance',
            '<table class="subject-table">
                <tr><th>Category</th><th>Max Attempts</th></tr>
                <tr><td>General</td><td>6 attempts</td></tr>
                <tr><td>EWS</td><td>9 attempts</td></tr>
                <tr><td>OBC (NCL)</td><td>9 attempts</td></tr>
                <tr><td>SC / ST</td><td>Unlimited (within age limit)</td></tr>
                <tr><td>PwBD (General/EWS)</td><td>9 attempts</td></tr>
                <tr><td>PwBD (OBC)</td><td>9 attempts</td></tr>
                <tr><td>PwBD (SC/ST)</td><td>Unlimited</td></tr>
            </table>
            <ul class="tips-list" style="margin-top:.75rem">
                <li class="tip-item">An attempt is counted only if you appear in at least one paper of Prelims</li>
                <li class="tip-item">Absent in Prelims = attempt NOT counted</li>
            </ul>'
        );

        renderNode(5,'--teal','bg-teal','teal','tag-teal','Physical',
            'Physical Standards',
            'Applies to specific services like IPS, Railway Services',
            '<ul class="tips-list">
                <li class="tip-item">For IAS: No minimum height/weight requirement</li>
                <li class="tip-item">For IPS: Male — 165 cm height, 84 cm chest; Female — 150 cm height</li>
                <li class="tip-item">Vision standards vary by service — check UPSC notification for specific service standards</li>
                <li class="tip-item">Medical examination conducted after final selection</li>
            </ul>'
        );
        ?>

    </div>
</section>

<!-- ════════════ PRELIMS ════════════ -->
<section class="section" id="section-prelims">
    <div class="section-header">
        <div class="section-ico bg-blue c-blue">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/><line x1="12" y1="2" x2="12" y2="6"/><line x1="12" y1="18" x2="12" y2="22"/><line x1="2" y1="12" x2="6" y2="12"/><line x1="18" y1="12" x2="22" y2="12"/></svg>
        </div>
        <div>
            <h2>Prelims — Stage 1</h2>
            <p>The screening test. Objective MCQ format. Only GS Paper I marks matter for cut-off. CSAT is qualifying only.</p>
        </div>
    </div>
    <div class="roadmap">

        <?php
        renderNode(1,'--blue','bg-blue','blue','tag-blue','Overview',
            'Prelims at a Glance',
            '2 Papers · Same day · June · Objective MCQ',
            '<div class="info-grid">
                <div class="info-cell"><div class="ic-label">Exam Mode</div><div class="ic-val">Offline (OMR sheet)</div></div>
                <div class="info-cell"><div class="ic-label">Total Papers</div><div class="ic-val">2 (GS + CSAT)</div></div>
                <div class="info-cell"><div class="ic-label">Total Marks</div><div class="ic-val">400 (only GS I counts)</div></div>
                <div class="info-cell"><div class="ic-label">Negative Marking</div><div class="ic-val">−⅓ mark per wrong answer</div></div>
                <div class="info-cell"><div class="ic-label">Language</div><div class="ic-val">English & Hindi</div></div>
                <div class="info-cell"><div class="ic-label">Qualifying Marks</div><div class="ic-val">CSAT: 33% (66/200)</div></div>
            </div>'
        );

        renderNode(2,'--blue','bg-blue','blue','tag-blue','GS Paper I',
            'General Studies Paper I',
            '100 Questions · 200 Marks · 2 Hours',
            '<table class="subject-table">
                <tr><th>Subject</th><th>Approx Questions</th><th>Key Topics</th></tr>
                <tr><td>History & Culture</td><td>15–20</td><td>Ancient, Medieval, Modern India; Art & Culture</td></tr>
                <tr><td>Geography</td><td>12–18</td><td>Physical, Indian, World Geography; Climate</td></tr>
                <tr><td>Polity & Governance</td><td>12–15</td><td>Constitution, Parliament, Judiciary, Schemes</td></tr>
                <tr><td>Economy</td><td>10–15</td><td>Macro/Micro, Budget, Banking, Poverty</td></tr>
                <tr><td>Environment & Ecology</td><td>10–15</td><td>Biodiversity, Climate Change, Conventions</td></tr>
                <tr><td>Science & Technology</td><td>8–12</td><td>Space, Defence, Biotech, IT, Health</td></tr>
                <tr><td>Current Affairs</td><td>15–20</td><td>National & International events (1 year)</td></tr>
            </table>
            <ul class="tips-list" style="margin-top:.75rem">
                <li class="tip-item">No negative for un-attempted questions</li>
                <li class="tip-item">Each correct answer: +2 marks. Each wrong: −0.66 marks</li>
                <li class="tip-item">Attempt only when 60%+ confident — random guessing is costly</li>
            </ul>'
        );

        renderNode(3,'--violet','bg-violet','violet','tag-violet','CSAT Paper II',
            'Civil Services Aptitude Test',
            '80 Questions · 200 Marks · 2 Hours — Qualifying only',
            '<table class="subject-table">
                <tr><th>Subject</th><th>Approx Questions</th></tr>
                <tr><td>Reading Comprehension</td><td>25–30</td></tr>
                <tr><td>Interpersonal & Communication Skills</td><td>5–8</td></tr>
                <tr><td>Logical Reasoning & Analytical Ability</td><td>15–20</td></tr>
                <tr><td>Decision Making & Problem Solving</td><td>8–12</td></tr>
                <tr><td>General Mental Ability</td><td>5–8</td></tr>
                <tr><td>Basic Numeracy (Class X)</td><td>8–12</td></tr>
                <tr><td>Data Interpretation</td><td>5–8</td></tr>
            </table>
            <ul class="tips-list" style="margin-top:.75rem">
                <li class="tip-item">Minimum qualifying marks: 66/200 (33%)</li>
                <li class="tip-item">Even if you score 199/200 in CSAT, it does NOT add to your cut-off</li>
                <li class="tip-item">Negative marking applies here too: −⅓ per wrong answer</li>
            </ul>'
        );

        renderNode(4,'--coral','bg-coral','coral','tag-coral','Books',
            'Best Books for Prelims GS Paper I',
            'Standard references — quality over quantity',
            '<div class="books-grid">
                <div class="book-chip"><div class="book-ico">📘</div><div><div class="book-title">NCERT History (6–12)</div><div class="book-auth">Old NCERT + New NCERT both recommended</div></div></div>
                <div class="book-chip"><div class="book-ico">📗</div><div><div class="book-title">Indian Polity — M. Laxmikanth</div><div class="book-auth">Most important single book for Polity</div></div></div>
                <div class="book-chip"><div class="book-ico">📙</div><div><div class="book-title">Indian Economy — Ramesh Singh</div><div class="book-auth">Or Nitin Sangwan notes for economy</div></div></div>
                <div class="book-chip"><div class="book-ico">🌍</div><div><div class="book-title">Geography of India — Majid Husain</div><div class="book-auth">NCERT Geography 11–12 as base</div></div></div>
                <div class="book-chip"><div class="book-ico">🌿</div><div><div class="book-title">Environment — Shankar IAS</div><div class="book-auth">Ecology & Biodiversity, CPCB reports</div></div></div>
                <div class="book-chip"><div class="book-ico">🔬</div><div><div class="book-title">Science NCERT (6–10)</div><div class="book-auth">Physics, Chemistry, Biology basics</div></div></div>
                <div class="book-chip"><div class="book-ico">📰</div><div><div class="book-title">The Hindu / Indian Express</div><div class="book-auth">Daily reading for Current Affairs</div></div></div>
                <div class="book-chip"><div class="book-ico">📒</div><div><div class="book-title">India Year Book</div><div class="book-auth">Published by Ministry of I&B — factual data</div></div></div>
                <div class="book-chip"><div class="book-ico">🏛️</div><div><div class="book-title">Art & Culture — Nitin Singhania</div><div class="book-auth">Comprehensive for art, music, dance, architecture</div></div></div>
                <div class="book-chip"><div class="book-ico">📊</div><div><div class="book-title">Economic Survey</div><div class="book-auth">Released before Union Budget — must read</div></div></div>
            </div>'
        );

        renderNode(5,'--teal','bg-teal','teal','tag-teal','Strategy',
            'Prelims Preparation Strategy',
            'How to study, when to attempt, revision plan',
            '<div class="two-col">
                <div class="col-box"><h5 style="color:var(--teal)">Do This</h5><ul>
                    <li>Complete NCERTs first (3–4 months)</li>
                    <li>Solve 15+ years of PYQs — patterns repeat</li>
                    <li>Revise current affairs weekly, not at the end</li>
                    <li>Attempt 20+ mock tests before exam</li>
                    <li>Attempt only ≥60% confident questions first</li>
                    <li>Read newspaper for 1–1.5 hrs daily</li>
                </ul></div>
                <div class="col-box"><h5 style="color:var(--red)">Avoid This</h5><ul>
                    <li>Do not collect 30 books — stick to 8–10</li>
                    <li>Do not attempt random guesses (−0.66 hurts)</li>
                    <li>Do not skip Environment & Ecology section</li>
                    <li>Do not ignore CSAT — many fail to qualify</li>
                    <li>Do not start new topics 2 weeks before exam</li>
                    <li>Do not compare your score to others daily</li>
                </ul></div>
            </div>'
        );
        ?>

    </div>
</section>

<!-- ════════════ MAINS ════════════ -->
<section class="section" id="section-mains">
    <div class="section-header">
        <div class="section-ico bg-teal c-teal">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        </div>
        <div>
            <h2>Mains — Stage 2</h2>
            <p>The real battle. Descriptive answers over 5 days. 9 papers. 1750 marks counted towards final rank.</p>
        </div>
    </div>
    <div class="roadmap">

        <?php
        renderNode(1,'--teal','bg-teal','teal','tag-teal','Overview',
            'Mains at a Glance',
            '9 Papers · 5 Days · September/October',
            '<div class="info-grid">
                <div class="info-cell"><div class="ic-label">Total Papers</div><div class="ic-val">9 Papers</div></div>
                <div class="info-cell"><div class="ic-label">Marks Counted</div><div class="ic-val">1750 (GS × 4 + Essay + Optional × 2)</div></div>
                <div class="info-cell"><div class="ic-label">Qualifying Papers</div><div class="ic-val">2 (Language + English) — 300 marks each</div></div>
                <div class="info-cell"><div class="ic-label">Duration</div><div class="ic-val">3 hours per paper</div></div>
                <div class="info-cell"><div class="ic-label">Negative Marking</div><div class="ic-val">None</div></div>
                <div class="info-cell"><div class="ic-label">Answer Medium</div><div class="ic-val">Hindi or English (own choice)</div></div>
            </div>'
        );

        renderNode(2,'--violet','bg-violet','violet','tag-violet','Qualifying',
            'Paper A — Indian Language',
            '300 Marks · Qualifying (need 25%) · Any 8th Schedule language',
            '<ul class="tips-list">
                <li class="tip-item">Choose from 22 languages in the 8th Schedule (Telugu, Tamil, Hindi, etc.)</li>
                <li class="tip-item">Tests: Comprehension, precis writing, translation, essay in that language</li>
                <li class="tip-item">Minimum qualifying marks: 75/300 (25%)</li>
                <li class="tip-item">Exempted for candidates from North-East states and those from areas using Roman script</li>
            </ul>'
        );

        renderNode(3,'--violet','bg-violet','violet','tag-violet','Qualifying',
            'Paper B — English',
            '300 Marks · Qualifying (need 25%)',
            '<ul class="tips-list">
                <li class="tip-item">Tests basic English proficiency: comprehension, precis, translation, essay</li>
                <li class="tip-item">Minimum qualifying marks: 75/300</li>
                <li class="tip-item">Score does NOT count toward final merit — just needs to be cleared</li>
            </ul>'
        );

        renderNode(4,'--teal','bg-teal','teal','tag-teal','250 Marks',
            'Paper I — Essay',
            '2 essays · 250 Marks · 3 Hours',
            '<ul class="tips-list">
                <li class="tip-item">2 sections, write 1 essay from each: ~1200 words each</li>
                <li class="tip-item">Section A: Abstract philosophical topics (e.g. "Courage to accept and dedication to improve")</li>
                <li class="tip-item">Section B: Social/national/global issues (e.g. "Digital economy and financial inclusion")</li>
                <li class="tip-item">Key: Structure + Examples + Multiple perspectives + Balance + Conclusion</li>
                <li class="tip-item">Best books: Arihant Essay Guide, practice writing 2 essays/week</li>
            </ul>'
        );

        renderNode(5,'--teal','bg-teal','teal','tag-teal','250 × 4',
            'Papers II–V — General Studies I to IV',
            '4 papers × 250 marks = 1000 marks · 3 hrs each',
            '<table class="subject-table">
                <tr><th>Paper</th><th>Subject</th><th>Key Themes</th></tr>
                <tr><td>GS I</td><td>History, Heritage & Geography</td><td>Indian History, Freedom Struggle, Art & Culture, World Geography, Urbanisation, Women issues</td></tr>
                <tr><td>GS II</td><td>Governance, Constitution & IR</td><td>Polity, Parliament, Judiciary, Social Justice, Welfare schemes, International Relations, Bilateral relations</td></tr>
                <tr><td>GS III</td><td>Economy, Technology & Security</td><td>Indian Economy, Agriculture, S&T, Environment, Disaster Mgmt, Internal Security, Space, Biotech</td></tr>
                <tr><td>GS IV</td><td>Ethics, Integrity & Aptitude</td><td>Ethical theories, Public service values, Attitude, Emotional intelligence, Case studies</td></tr>
            </table>'
        );

        renderNode(6,'--coral','bg-coral','coral','tag-coral','250 × 2',
            'Papers VI & VII — Optional Subject',
            'Choose 1 optional · 2 papers × 250 = 500 marks',
            '<div class="two-col">
                <div class="col-box"><h5 style="color:var(--coral)">Science Optionals</h5><ul>
                    <li>Mathematics</li><li>Physics</li><li>Chemistry</li>
                    <li>Botany / Zoology</li><li>Geology / Geography</li>
                    <li>Agriculture / Animal Husbandry</li><li>Medical Science</li>
                    <li>Civil / Electrical / Mechanical / Chemical Engg.</li>
                </ul></div>
                <div class="col-box"><h5 style="color:var(--blue)">Humanities Optionals</h5><ul>
                    <li>History (Most popular)</li><li>Public Administration</li>
                    <li>Political Science & IR</li><li>Sociology</li>
                    <li>Anthropology</li><li>Psychology</li><li>Philosophy</li>
                    <li>Economics / Commerce / Law</li><li>Literature (any 8th Schedule language)</li>
                </ul></div>
            </div>
            <ul class="tips-list" style="margin-top:.75rem">
                <li class="tip-item">Choose based on: background, interest, availability of material, scoring trend</li>
                <li class="tip-item">Top scorers: PSIR, Anthropology, Sociology, History, Geography, Mathematics</li>
            </ul>'
        );

        renderNode(7,'--coral','bg-coral','coral','tag-coral','Books',
            'Best Books for Mains',
            'Paper-wise reference list',
            '<div class="books-grid">
                <div class="book-chip"><div class="book-ico">🏛️</div><div><div class="book-title">GS I: Spectrum Modern History</div><div class="book-auth">Rajiv Ahir — Freedom struggle</div></div></div>
                <div class="book-chip"><div class="book-ico">🌏</div><div><div class="book-title">GS I: NCERT Geography 11–12</div><div class="book-auth">Physical + Human Geography</div></div></div>
                <div class="book-chip"><div class="book-ico">⚖️</div><div><div class="book-title">GS II: Indian Polity — Laxmikanth</div><div class="book-auth">Constitution & Governance</div></div></div>
                <div class="book-chip"><div class="book-ico">🌐</div><div><div class="book-title">GS II: India\'s Foreign Policy — Rajiv Sikri</div><div class="book-auth">International Relations</div></div></div>
                <div class="book-chip"><div class="book-ico">💰</div><div><div class="book-title">GS III: Indian Economy — Ramesh Singh</div><div class="book-auth">Economic policies & data</div></div></div>
                <div class="book-chip"><div class="book-ico">🔒</div><div><div class="book-title">GS III: Internal Security — Ashok Kumar</div><div class="book-auth">Security challenges & management</div></div></div>
                <div class="book-chip"><div class="book-ico">💡</div><div><div class="book-title">GS IV: Ethics — G Subba Rao</div><div class="book-auth">Lexicon of Ethics + Subba Rao</div></div></div>
                <div class="book-chip"><div class="book-ico">📝</div><div><div class="book-title">Essay: Mrunal Patel / Vision IAS</div><div class="book-auth">Essay frameworks and examples</div></div></div>
                <div class="book-chip"><div class="book-ico">📊</div><div><div class="book-title">Economic Survey + Budget</div><div class="book-auth">Released annually — mandatory</div></div></div>
                <div class="book-chip"><div class="book-ico">🗞️</div><div><div class="book-title">The Hindu Editorial</div><div class="book-auth">Daily editorial for GS II + III answers</div></div></div>
            </div>'
        );

        renderNode(8,'--amber','bg-amber','amber','tag-amber','Answer Writing',
            'Answer Writing Strategy',
            'The single most important Mains skill',
            '<div class="two-col">
                <div class="col-box"><h5 style="color:var(--amber)">10-mark answers (150 words)</h5><ul>
                    <li>Introduction: 2–3 lines (define/context)</li>
                    <li>Body: 3–4 points with sub-headings</li>
                    <li>Conclusion: 1 constructive line</li>
                    <li>Time: ~7 minutes maximum</li>
                </ul></div>
                <div class="col-box"><h5 style="color:var(--violet)">15-mark answers (250 words)</h5><ul>
                    <li>Introduction: 3–4 lines</li>
                    <li>Body: 5–6 points, use flowcharts/diagrams</li>
                    <li>Multiple dimensions: social/economic/political</li>
                    <li>Conclusion: way forward or quote</li>
                </ul></div>
            </div>
            <ul class="tips-list">
                <li class="tip-item">Practice writing minimum 1 answer daily from Day 1</li>
                <li class="tip-item">Get your answers evaluated — join a test series (Insights, ForumIAS, Vision)</li>
                <li class="tip-item">Use keywords, diagrams, maps wherever appropriate — examiners reward it</li>
            </ul>'
        );
        ?>

    </div>
</section>

<!-- ════════════ INTERVIEW ════════════ -->
<section class="section" id="section-interview">
    <div class="section-header">
        <div class="section-ico bg-amber c-amber">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </div>
        <div>
            <h2>Personality Test — Stage 3</h2>
            <p>The final frontier. 275 marks. A board of 5 members assesses your personality, judgement and suitability for civil services.</p>
        </div>
    </div>
    <div class="roadmap">

        <?php
        renderNode(1,'--amber','bg-amber','amber','tag-amber','Overview',
            'Interview at a Glance',
            '275 Marks · ~30–45 min · UPSC Bhavan, Delhi',
            '<div class="info-grid">
                <div class="info-cell"><div class="ic-label">Total Marks</div><div class="ic-val">275 Marks</div></div>
                <div class="info-cell"><div class="ic-label">Duration</div><div class="ic-val">30–45 minutes</div></div>
                <div class="info-cell"><div class="ic-label">Panel</div><div class="ic-val">5 members + Chairman</div></div>
                <div class="info-cell"><div class="ic-label">Typical Score</div><div class="ic-val">130–175 for top ranks</div></div>
                <div class="info-cell"><div class="ic-label">Counts Toward</div><div class="ic-val">Final merit (Mains + Interview)</div></div>
                <div class="info-cell"><div class="ic-label">Negative Marking</div><div class="ic-val">None</div></div>
            </div>'
        );

        renderNode(2,'--amber','bg-amber','amber','tag-amber','DAF',
            'Detailed Application Form (DAF)',
            'The entire interview is based on this document',
            '<ul class="tips-list">
                <li class="tip-item">Fill DAF extremely carefully — every hobby, activity, and qualification is fair game</li>
                <li class="tip-item">List genuine hobbies (cooking, chess, trekking, music, etc.) — be prepared to be grilled</li>
                <li class="tip-item">Mention your home state, graduation subject, optional subject accurately</li>
                <li class="tip-item">Prepare 20+ questions from each entry you make in DAF</li>
                <li class="tip-item">Do not list anything you cannot confidently discuss for 5+ minutes</li>
            </ul>'
        );

        renderNode(3,'--blue','bg-blue','blue','tag-blue','Preparation',
            'What to Prepare',
            'Six areas that cover 90% of interview questions',
            '<table class="subject-table">
                <tr><th>Area</th><th>What to Cover</th></tr>
                <tr><td>About Yourself</td><td>Why UPSC, why this service, strengths/weaknesses, family background, hometown</td></tr>
                <tr><td>Academic Background</td><td>Graduation subject — know it inside out; final year projects, college activities</td></tr>
                <tr><td>Optional Subject</td><td>Applied/practical aspects, current relevance, policy dimensions</td></tr>
                <tr><td>Home State</td><td>Economy, geography, politics, culture, historical figures, major issues, Chief Minister</td></tr>
                <tr><td>Current Affairs</td><td>Last 6 months — domestic + international; government policies and schemes</td></tr>
                <tr><td>Ethics & Governance</td><td>Case studies, opinions on recent policy debates, integrity scenarios</td></tr>
            </table>'
        );

        renderNode(4,'--teal','bg-teal','teal','tag-teal','Mock Interviews',
            'Mock Interview Practice',
            'Non-negotiable — do minimum 5 mock boards',
            '<ul class="tips-list">
                <li class="tip-item">Join reputed institutes: Chanakya IAS, Vajirao & Reddy, Sriram IAS, ALS, Chahal Academy</li>
                <li class="tip-item">Record yourself answering and watch for posture, eye contact, filler words</li>
                <li class="tip-item">Form peer groups for mock sessions — ask each other tough questions</li>
                <li class="tip-item">Read interview transcripts of successful candidates (published on websites like IASbaba)</li>
            </ul>'
        );

        renderNode(5,'--violet','bg-violet','violet','tag-violet','Mindset',
            'The Interview Mindset',
            'What the board is actually looking for',
            '<div class="two-col">
                <div class="col-box"><h5 style="color:var(--teal)">They Want to See</h5><ul>
                    <li>Intellectual honesty — say "I don\'t know" when you don\'t</li>
                    <li>Balanced views on controversial topics</li>
                    <li>Clarity of thought and expression</li>
                    <li>Genuine interest in public service</li>
                    <li>Awareness of your own state and region</li>
                    <li>Stability under pressure</li>
                </ul></div>
                <div class="col-box"><h5 style="color:var(--red)">Red Flags for Board</h5><ul>
                    <li>Bluffing when you don\'t know</li>
                    <li>Contradicting yourself</li>
                    <li>Sounding bookish / mugged up</li>
                    <li>Extreme views on political issues</li>
                    <li>Overconfidence or excessive humility</li>
                    <li>Not knowing your own DAF details</li>
                </ul></div>
            </div>'
        );

        renderNode(6,'--green','bg-green','green','tag-green','Final Merit',
            'Final Selection & Service Allocation',
            'Mains + Interview = Your rank',
            '<div class="info-grid">
                <div class="info-cell"><div class="ic-label">Mains Marks</div><div class="ic-val">1750 marks</div></div>
                <div class="info-cell"><div class="ic-label">Interview</div><div class="ic-val">275 marks</div></div>
                <div class="info-cell"><div class="ic-label">Total</div><div class="ic-val">2025 marks</div></div>
                <div class="info-cell"><div class="ic-label">~Top Rank Score</div><div class="ic-val">~1100+/2025</div></div>
            </div>
            <ul class="tips-list" style="margin-top:.75rem">
                <li class="tip-item">Fill service preference form carefully — IAS/IPS/IFS at top, then rest in order</li>
                <li class="tip-item">State cadre preference also impacts your posting — research carefully</li>
                <li class="tip-item">Training at LBSNAA, Mussoorie for IAS; Sardar Vallabhbhai Patel NPA for IPS</li>
                <li class="tip-item">Probationary period: 2 years before confirmed appointment</li>
            </ul>'
        );
        ?>

    </div>
</section>

<!-- ════════════ AI GUIDE ════════════ -->
<section class="section" id="section-askguide">
    <div class="section-header">
        <div class="section-ico bg-pink c-pink">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        </div>
        <div>
            <h2>Ask the AI Guide</h2>
            <p>Get personalised guidance on any UPSC topic — books, strategy, current affairs, optional selection, and more.</p>
        </div>
    </div>

    <div class="ai-box">
        <h3>Your Personal UPSC Guide</h3>
        <p>Ask anything — "Which optional should I pick?", "How to prepare for Ethics?", "Suggest a 6-month study plan"</p>
        <form method="post" class="ai-form" id="ai-form">
            <input type="text" name="question" id="ai-input"
                   placeholder="Ask your UPSC question…"
                   value="<?php echo isset($_POST['question']) ? htmlspecialchars($_POST['question']) : ''; ?>"
                   autocomplete="off" required>
            <button type="submit" name="ask">Ask AI</button>
        </form>
        <?php if ($res): ?>
        <div class="ai-result show"><?php echo htmlspecialchars($res); ?></div>
        <?php else: ?>
        <div class="ai-result" id="ai-result"></div>
        <?php endif; ?>
    </div>

    <!-- Quick questions -->
    <div style="margin-top:1.5rem;">
        <p style="font-size:.8rem;color:var(--muted);margin-bottom:.75rem;font-weight:500;">Quick questions:</p>
        <div style="display:flex;flex-wrap:wrap;gap:.6rem;">
            <?php
            $quickq = [
                'Best optional subject for engineers?',
                'How to read newspaper for UPSC?',
                'Suggest a 12-month study plan',
                'How many hours should I study daily?',
                'When to start answer writing practice?',
                'Which coaching is best for Mains?',
            ];
            foreach ($quickq as $qq):
            ?>
            <button onclick="setQuestion('<?php echo addslashes($qq); ?>')"
                style="padding:.4rem .9rem;border-radius:100px;border:1px solid var(--border);
                       background:var(--surface);font-size:.78rem;cursor:pointer;
                       transition:all .2s;color:var(--ink);font-family:'DM Sans',sans-serif;"
                onmouseover="this.style.borderColor='var(--violet)';this.style.color='var(--violet)'"
                onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--ink)'">
                <?php echo htmlspecialchars($qq); ?>
            </button>
            <?php endforeach; ?>
        </div>
    </div>
</section>

</main><!-- end .main -->

<script>
/* ── TAB NAVIGATION ── */
document.querySelectorAll('.pnav-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const target = btn.dataset.section;
        document.querySelectorAll('.pnav-btn').forEach(function(b){ b.classList.remove('active'); });
        document.querySelectorAll('.section').forEach(function(s){ s.classList.remove('active'); });
        btn.classList.add('active');
        document.getElementById('section-' + target).classList.add('active');
        window.scrollTo({top:0, behavior:'smooth'});
    });
});

/* ── NODE EXPAND/COLLAPSE ── */
function toggleNode(card) {
    const isExpanded = card.classList.contains('expanded');
    // Close all
    document.querySelectorAll('.node-card.expanded').forEach(function(c){ c.classList.remove('expanded'); });
    // Open clicked unless it was already open
    if (!isExpanded) card.classList.add('expanded');
}

/* ── AI QUICK Q ── */
function setQuestion(q) {
    document.getElementById('ai-input').value = q;
    document.getElementById('ai-input').focus();
    // Switch to AI Guide tab
    document.querySelectorAll('.pnav-btn').forEach(function(b){ b.classList.remove('active'); });
    document.querySelectorAll('.section').forEach(function(s){ s.classList.remove('active'); });
    document.querySelector('[data-section="askguide"]').classList.add('active');
    document.getElementById('section-askguide').classList.add('active');
}

/* ── Auto open first node in active section ── */
document.querySelectorAll('.section.active .node-card').forEach(function(c, i){
    if (i === 0) c.classList.add('expanded');
});

/* ── Open first node when switching tabs ── */
document.querySelectorAll('.pnav-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        setTimeout(function() {
            const activeSection = document.querySelector('.section.active');
            if (activeSection) {
                const firstCard = activeSection.querySelector('.node-card');
                if (firstCard) firstCard.classList.add('expanded');
            }
        }, 50);
    });
});
</script>
</body>
</html>