<?php
/* ═══════════════════════════════════════════════════════════
   ai_coach.php — Strategic AI Coach
   Real-time Llama3 streaming · Session tracking · DB logging
   Matches dashboard.php design exactly
═══════════════════════════════════════════════════════════ */
include 'includes/auth.php';   // guards page, sets $uid $user_name
include 'includes/db.php';     // provides $conn

/* ── DB: create coach_sessions table if needed ──
   Run this once or add to your migration:

   CREATE TABLE IF NOT EXISTS coach_sessions (
     id         INT AUTO_INCREMENT PRIMARY KEY,
     user_id    INT NOT NULL,
     stage      ENUM('Prelims','Mains','Interview') DEFAULT 'Prelims',
     topic      VARCHAR(255),
     query      TEXT,
     response   TEXT,
     tokens     INT DEFAULT 0,
     duration   INT DEFAULT 0,
     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
     INDEX idx_user (user_id),
     INDEX idx_stage (stage)
   );
*/

/* ── Fetch real-time stats from DB ── */
function getStats(mysqli $conn, int $uid): array {
    $stats = ['total'=>0,'prelims'=>0,'mains'=>0,'interview'=>0,'today'=>0,'streak'=>0];

    $r = $conn->prepare('SELECT stage, COUNT(*) as cnt FROM coach_sessions WHERE user_id=? GROUP BY stage');
    $r->bind_param('i',$uid); $r->execute();
    $res = $r->get_result();
    while ($row = $res->fetch_assoc()) {
        $key = strtolower($row['stage']);
        $stats[$key] = (int)$row['cnt'];
        $stats['total'] += (int)$row['cnt'];
    }
    $r->close();

    $r2 = $conn->prepare('SELECT COUNT(*) as cnt FROM coach_sessions WHERE user_id=? AND DATE(created_at)=CURDATE()');
    $r2->bind_param('i',$uid); $r2->execute();
    $stats['today'] = (int)$r2->get_result()->fetch_assoc()['cnt'];
    $r2->close();

    $r3 = $conn->prepare('SELECT DATE(created_at) as d FROM coach_sessions WHERE user_id=? GROUP BY d ORDER BY d DESC LIMIT 30');
    $r3->bind_param('i',$uid); $r3->execute();
    $days=[]; $res3=$r3->get_result();
    while ($row=$res3->fetch_assoc()) $days[]=$row['d'];
    $r3->close();
    $streak=0; $check=date('Y-m-d');
    foreach($days as $d){ if($d===$check){$streak++;$check=date('Y-m-d',strtotime($check.'-1 day'));}else break; }
    $stats['streak']=$streak;
    return $stats;
}

/* ── Fetch recent sessions ── */
function getRecent(mysqli $conn, int $uid, int $limit=8): array {
    $rows = [];
    $r = $conn->prepare('SELECT id,stage,topic,query,created_at FROM coach_sessions WHERE user_id=? ORDER BY created_at DESC LIMIT ?');
    $r->bind_param('ii',$uid,$limit); $r->execute();
    $res = $r->get_result();
    while ($row=$res->fetch_assoc()) $rows[]=$row;
    $r->close();
    return $rows;
}

$stats  = getStats($conn, $uid);
$recent = getRecent($conn, $uid);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AI Coach — UPSC Command Center</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">

<style>
/* ── exact same variables as dashboard.php ── */
:root {
    --grad-start:#1a0533; --grad-mid:#3d0f6b; --grad-end:#6b1fa8;
    --coral:#ff6b4a;  --coral-lt:#fff0ed;
    --blue:#3b82f6;   --blue-lt:#eff5ff;
    --teal:#0fb98a;   --teal-lt:#e6faf4;
    --violet:#7c3aed; --violet-lt:#f0eaff; --violet-dk:#5b21b6;
    --amber:#f59e0b;  --amber-lt:#fffbeb;
    --green:#16a34a;  --green-lt:#f0fdf4;
    --danger:#ef4444; --danger-lt:#fef2f2;
    --ink:#1a1523;    --muted:#6b6579;
    --border:rgba(0,0,0,0.07);
    --surface:#ffffff; --page:#f4f2f8;
    --transition:all 0.28s cubic-bezier(0.2,0.8,0.2,1);
}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;-webkit-font-smoothing:antialiased;}
html{scroll-behavior:smooth;}
body{font-family:'DM Sans',sans-serif;background:var(--page);color:var(--ink);min-height:100vh;overflow-x:hidden;}
a{text-decoration:none;color:inherit;}

/* ── HERO (same as dashboard) ── */
.hero{
    background:linear-gradient(135deg,var(--grad-start) 0%,var(--grad-mid) 50%,var(--grad-end) 100%);
    position:relative;padding:5rem 2rem 7rem;text-align:center;color:#fff;overflow:hidden;
}
.hero::before{
    content:'';position:absolute;top:50%;left:50%;transform:translate(-50%,-60%);
    width:600px;height:400px;
    background:radial-gradient(ellipse,rgba(160,80,255,.35) 0%,transparent 70%);
    pointer-events:none;
}
.hero-inner{position:relative;z-index:2;max-width:700px;margin:0 auto;}
.user-controls{position:absolute;top:1.75rem;left:1.75rem;z-index:50;display:flex;gap:.65rem;}
.ctrl-btn{
    width:46px;height:46px;border-radius:13px;display:flex;align-items:center;justify-content:center;
    color:#fff;background:rgba(255,255,255,.13);border:1px solid rgba(255,255,255,.22);
    backdrop-filter:blur(12px);transition:var(--transition);
}
.ctrl-btn:hover{background:rgba(255,255,255,.25);border-color:rgba(255,255,255,.45);transform:translateY(-3px);}
.ctrl-btn.logout:hover{background:var(--danger);border-color:transparent;}
.pre-badge{
    display:inline-block;font-family:'Syne',sans-serif;font-size:.7rem;font-weight:600;
    letter-spacing:.18em;text-transform:uppercase;
    background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.22);
    border-radius:100px;padding:.35rem 1.1rem;margin-bottom:1.25rem;
}
.hero h1{
    font-family:'Syne',sans-serif;font-size:clamp(2rem,5vw,3.2rem);
    font-weight:800;line-height:1.12;letter-spacing:-.02em;margin-bottom:.8rem;
}
.hero p{font-size:1rem;font-weight:300;opacity:.82;max-width:480px;margin:0 auto;}
.wave{position:absolute;bottom:-1px;left:0;width:100%;line-height:0;z-index:3;}
.wave svg{display:block;width:100%;height:88px;}

/* ── STATS ROW (floats over hero wave) ── */
.main{max-width:1160px;margin:-3.5rem auto 4rem;padding:0 1.5rem;position:relative;z-index:10;}

.stats-row{display:grid;grid-template-columns:repeat(5,1fr);gap:1rem;margin-bottom:1.5rem;}
.stat-card{
    background:var(--surface);border-radius:18px;border:1px solid var(--border);
    padding:1.1rem 1rem;text-align:center;
    box-shadow:0 3px 14px rgba(0,0,0,.05);
    animation:fadeUp .5s cubic-bezier(.2,.8,.2,1) both;
}
.stat-card:nth-child(1){animation-delay:.05s}
.stat-card:nth-child(2){animation-delay:.10s}
.stat-card:nth-child(3){animation-delay:.15s}
.stat-card:nth-child(4){animation-delay:.20s}
.stat-card:nth-child(5){animation-delay:.25s}
.stat-ico{width:40px;height:40px;border-radius:11px;display:flex;align-items:center;justify-content:center;margin:0 auto .65rem;}
.stat-ico svg{width:20px;height:20px;}
.stat-val{font-family:'Syne',sans-serif;font-size:1.6rem;font-weight:800;line-height:1;margin-bottom:.2rem;}
.stat-lbl{font-size:.72rem;color:var(--muted);}

/* ── LAYOUT: Coach + Sidebar ── */
.coach-layout{display:grid;grid-template-columns:1fr 300px;gap:1.25rem;align-items:start;}

/* ── COACH CARD ── */
.coach-card{
    background:var(--surface);border-radius:20px;border:1px solid var(--border);
    overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.06);
    animation:fadeUp .5s cubic-bezier(.2,.8,.2,1) .1s both;
}

/* Stage tabs */
.stage-tabs{display:flex;border-bottom:1px solid var(--border);}
.stage-tab{
    flex:1;padding:.9rem .5rem;border:none;background:transparent;cursor:pointer;
    font-family:'DM Sans',sans-serif;font-size:.82rem;font-weight:500;color:var(--muted);
    display:flex;flex-direction:column;align-items:center;gap:.35rem;
    border-bottom:2.5px solid transparent;transition:all .2s;position:relative;bottom:-1px;
}
.stage-tab:hover{color:var(--ink);}
.stage-tab.active-prelims{color:var(--blue);border-bottom-color:var(--blue);}
.stage-tab.active-mains  {color:var(--teal);border-bottom-color:var(--teal);}
.stage-tab.active-interview{color:var(--amber);border-bottom-color:var(--amber);}
.tab-ico{width:32px;height:32px;border-radius:9px;display:flex;align-items:center;justify-content:center;}
.tab-ico svg{width:16px;height:16px;}
.ti-prelims{background:var(--blue-lt);color:var(--blue);}
.ti-mains  {background:var(--teal-lt);color:var(--teal);}
.ti-intv   {background:var(--amber-lt);color:var(--amber);}

/* Topic chips */
.topic-section{padding:1rem 1.25rem .75rem;border-bottom:1px solid var(--border);}
.topic-label{font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.09em;color:var(--muted);margin-bottom:.6rem;}
.topic-chips{display:flex;flex-wrap:wrap;gap:.4rem;}
.chip{
    padding:.3rem .8rem;border-radius:100px;border:1px solid var(--border);
    background:transparent;font-family:'DM Sans',sans-serif;font-size:.75rem;color:var(--muted);
    cursor:pointer;transition:all .18s;
}
.chip:hover{border-color:var(--violet);color:var(--violet);}
.chip.active{background:var(--violet);border-color:var(--violet);color:#fff;}

/* Input area */
.input-section{padding:1rem 1.25rem;border-bottom:1px solid var(--border);}
.input-wrap{
    display:flex;gap:.6rem;align-items:flex-end;
    background:var(--page);border-radius:14px;border:1.5px solid var(--border);
    padding:.6rem .8rem;transition:border-color .2s;
}
.input-wrap:focus-within{border-color:var(--violet);background:#fff;}
#coach-input{
    flex:1;border:none;background:transparent;resize:none;
    font-family:'DM Sans',sans-serif;font-size:.9rem;color:var(--ink);
    outline:none;min-height:44px;max-height:120px;line-height:1.5;scrollbar-width:none;
}
#coach-input::placeholder{color:var(--muted);}
#coach-input::-webkit-scrollbar{display:none;}
.send-btn{
    width:42px;height:42px;border-radius:12px;border:none;
    background:var(--violet);color:#fff;cursor:pointer;
    display:flex;align-items:center;justify-content:center;
    flex-shrink:0;transition:all .2s;
}
.send-btn:hover{background:var(--violet-dk);transform:scale(1.05);}
.send-btn:disabled{background:var(--border);color:var(--muted);cursor:not-allowed;transform:none;}
.send-btn svg{width:18px;height:18px;}

/* Response area */
.response-area{padding:1.25rem;min-height:200px;}
.resp-placeholder{
    display:flex;flex-direction:column;align-items:center;justify-content:center;
    min-height:180px;gap:.75rem;color:var(--muted);text-align:center;
}
.resp-placeholder svg{width:40px;height:40px;opacity:.3;}
.resp-placeholder p{font-size:.82rem;max-width:280px;line-height:1.55;}

/* Typing dots */
.typing-dots{display:flex;gap:4px;align-items:center;padding:.5rem 0;}
.td{width:7px;height:7px;border-radius:50%;background:var(--violet);animation:bounce .8s infinite;}
.td:nth-child(2){animation-delay:.15s;}
.td:nth-child(3){animation-delay:.30s;}

/* AI response bubble */
.ai-response{
    background:var(--violet-lt);border-radius:16px;border:1px solid rgba(124,58,237,.15);
    padding:1.1rem 1.25rem;font-size:.88rem;line-height:1.7;color:var(--ink);
    animation:fadeUp .3s cubic-bezier(.2,.8,.2,1) both;
}
.ai-response code{font-family:'DM Mono',monospace;font-size:.8rem;background:rgba(124,58,237,.1);padding:.1rem .35rem;border-radius:4px;}
.ai-response strong{font-weight:600;color:var(--violet-dk);}

/* Response meta row */
.resp-meta{
    display:flex;align-items:center;gap:.65rem;margin-top:.85rem;flex-wrap:wrap;
}
.resp-badge{
    font-size:.68rem;font-weight:600;letter-spacing:.07em;text-transform:uppercase;
    border-radius:7px;padding:.22rem .6rem;
}
.rb-prelims{background:var(--blue-lt);color:var(--blue);}
.rb-mains  {background:var(--teal-lt);color:var(--teal);}
.rb-interview{background:var(--amber-lt);color:var(--amber);}
.resp-time{font-size:.7rem;color:var(--muted);font-family:'DM Mono',monospace;margin-left:auto;}

/* Streaming cursor */
.s-cur{
    display:inline-block;width:2px;height:.9em;background:var(--violet);
    border-radius:1px;margin-left:1px;vertical-align:text-bottom;
    animation:blink 1s infinite;
}

/* Action buttons below response */
.resp-actions{display:flex;gap:.5rem;margin-top:.75rem;flex-wrap:wrap;}
.ract{
    display:flex;align-items:center;gap:.35rem;padding:.38rem .85rem;
    border-radius:100px;border:1px solid var(--border);background:transparent;
    font-family:'DM Sans',sans-serif;font-size:.75rem;color:var(--muted);cursor:pointer;
    transition:all .18s;
}
.ract:hover{border-color:var(--violet);color:var(--violet);}
.ract svg{width:13px;height:13px;}

/* ── SIDEBAR ── */
.sidebar{display:flex;flex-direction:column;gap:1rem;}

/* Progress card */
.prog-card{
    background:var(--surface);border-radius:18px;border:1px solid var(--border);
    padding:1.25rem;box-shadow:0 3px 14px rgba(0,0,0,.05);
    animation:fadeUp .5s cubic-bezier(.2,.8,.2,1) .15s both;
}
.prog-card h4{
    font-family:'Syne',sans-serif;font-size:.78rem;font-weight:700;
    text-transform:uppercase;letter-spacing:.1em;color:var(--muted);margin-bottom:1rem;
}
.prog-item{margin-bottom:.85rem;}
.prog-item:last-child{margin-bottom:0;}
.prog-meta{display:flex;justify-content:space-between;font-size:.78rem;margin-bottom:.35rem;}
.prog-meta strong{font-weight:600;}
.prog-meta span{color:var(--muted);font-family:'DM Mono',monospace;}
.prog-track{height:7px;background:var(--page);border-radius:100px;overflow:hidden;}
.prog-fill{height:100%;border-radius:100px;transition:width .7s cubic-bezier(.2,.8,.2,1);}

/* Recent sessions card */
.recent-card{
    background:var(--surface);border-radius:18px;border:1px solid var(--border);
    padding:1.25rem;box-shadow:0 3px 14px rgba(0,0,0,.05);
    animation:fadeUp .5s cubic-bezier(.2,.8,.2,1) .2s both;
}
.recent-card h4{
    font-family:'Syne',sans-serif;font-size:.78rem;font-weight:700;
    text-transform:uppercase;letter-spacing:.1em;color:var(--muted);margin-bottom:.85rem;
}
.session-item{
    display:flex;align-items:center;gap:.65rem;padding:.55rem 0;
    border-bottom:1px solid var(--border);
}
.session-item:last-child{border-bottom:none;}
.sess-dot{
    width:28px;height:28px;border-radius:8px;flex-shrink:0;
    display:flex;align-items:center;justify-content:center;font-size:.6rem;font-weight:700;
}
.sd-prelims{background:var(--blue-lt);color:var(--blue);}
.sd-mains  {background:var(--teal-lt);color:var(--teal);}
.sd-interview{background:var(--amber-lt);color:var(--amber);}
.sess-info{flex:1;min-width:0;}
.sess-q{font-size:.78rem;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.sess-time{font-size:.68rem;color:var(--muted);}
.no-sessions{font-size:.8rem;color:var(--muted);text-align:center;padding:.75rem 0;}

/* Tips card */
.tips-card{
    background:linear-gradient(135deg,var(--grad-start),var(--grad-end));
    border-radius:18px;color:#fff;padding:1.25rem;
    animation:fadeUp .5s cubic-bezier(.2,.8,.2,1) .25s both;
}
.tips-card h4{font-family:'Syne',sans-serif;font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;opacity:.75;margin-bottom:.85rem;}
.tip-item{display:flex;gap:.55rem;font-size:.78rem;opacity:.88;line-height:1.5;margin-bottom:.5rem;}
.tip-item:last-child{margin-bottom:0;}
.tip-item::before{content:'→';flex-shrink:0;opacity:.7;}

/* Toast */
.toast{
    position:fixed;bottom:1.5rem;right:1.5rem;z-index:999;
    background:var(--green);color:#fff;border-radius:13px;
    padding:.75rem 1.1rem;font-size:.8rem;display:none;align-items:center;gap:.5rem;
    box-shadow:0 10px 30px rgba(0,0,0,.2);animation:fadeUp .3s cubic-bezier(.2,.8,.2,1);
}
.toast.show{display:flex;}
.toast svg{width:16px;height:16px;}

@keyframes fadeUp{from{opacity:0;transform:translateY(18px);}to{opacity:1;transform:translateY(0);}}
@keyframes bounce{0%,80%,100%{transform:translateY(0);}40%{transform:translateY(-5px);}}
@keyframes blink{0%,100%{opacity:1;}50%{opacity:0;}}

@media(max-width:900px){
    .coach-layout{grid-template-columns:1fr;}
    .stats-row{grid-template-columns:repeat(3,1fr);}
}
@media(max-width:560px){
    .stats-row{grid-template-columns:1fr 1fr;}
}
</style>
</head>
<body>

<!-- ══════════ HERO ══════════ -->
<header class="hero">
    <div class="user-controls">
        <a href="dashboard.php" class="ctrl-btn" title="Dashboard">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
        </a>
        <a href="profile.php" class="ctrl-btn" title="Profile">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
            </svg>
        </a>
        <a href="logout.php" class="ctrl-btn logout" title="Logout">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
        </a>
    </div>
    <div class="hero-inner">
        <span class="pre-badge">Powered by Llama 3</span>
        <h1>Strategic AI Coach</h1>
        <p>Real-time guidance for Prelims, Mains &amp; Personality Test — tracked every session.</p>
    </div>
    <div class="wave">
        <svg viewBox="0 0 1440 88" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
            <path d="M0,44 C180,88 360,0 540,44 C720,88 900,0 1080,44 C1260,88 1380,22 1440,44 L1440,88 L0,88 Z" fill="#f4f2f8"/>
        </svg>
    </div>
</header>

<!-- ══════════ MAIN ══════════ -->
<main class="main">

    <!-- STATS ROW -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-ico" style="background:var(--violet-lt);color:var(--violet)">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            </div>
            <div class="stat-val" style="color:var(--violet)" id="st-total"><?php echo $stats['total']; ?></div>
            <div class="stat-lbl">Total Sessions</div>
        </div>
        <div class="stat-card">
            <div class="stat-ico" style="background:var(--blue-lt);color:var(--blue)">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/></svg>
            </div>
            <div class="stat-val" style="color:var(--blue)" id="st-prelims"><?php echo $stats['prelims']; ?></div>
            <div class="stat-lbl">Prelims</div>
        </div>
        <div class="stat-card">
            <div class="stat-ico" style="background:var(--teal-lt);color:var(--teal)">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            </div>
            <div class="stat-val" style="color:var(--teal)" id="st-mains"><?php echo $stats['mains']; ?></div>
            <div class="stat-lbl">Mains</div>
        </div>
        <div class="stat-card">
            <div class="stat-ico" style="background:var(--amber-lt);color:var(--amber)">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
            <div class="stat-val" style="color:var(--amber)" id="st-interview"><?php echo $stats['interview']; ?></div>
            <div class="stat-lbl">Interview</div>
        </div>
        <div class="stat-card">
            <div class="stat-ico" style="background:var(--coral-lt);color:var(--coral)">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            </div>
            <div class="stat-val" style="color:var(--coral)" id="st-streak"><?php echo $stats['streak']; ?>🔥</div>
            <div class="stat-lbl">Day Streak</div>
        </div>
    </div>

    <!-- COACH LAYOUT -->
    <div class="coach-layout">

        <!-- LEFT: Coach Card -->
        <div class="coach-card">

            <!-- Stage tabs -->
            <div class="stage-tabs">
                <button class="stage-tab active-prelims" id="tab-Prelims" onclick="setStage('Prelims')">
                    <div class="tab-ico ti-prelims">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/></svg>
                    </div>
                    Prelims
                </button>
                <button class="stage-tab" id="tab-Mains" onclick="setStage('Mains')">
                    <div class="tab-ico ti-mains">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    </div>
                    Mains
                </button>
                <button class="stage-tab" id="tab-Interview" onclick="setStage('Interview')">
                    <div class="tab-ico ti-intv">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </div>
                    Interview
                </button>
            </div>

            <!-- Topic chips -->
            <div class="topic-section">
                <div class="topic-label">Quick Topic</div>
                <div class="topic-chips" id="topic-chips"></div>
            </div>

            <!-- Input -->
            <div class="input-section">
                <div class="input-wrap">
                    <textarea id="coach-input" rows="2"
                        placeholder="Ask your coach anything about UPSC…"
                        onkeydown="onKey(event)" oninput="autoResize(this)"></textarea>
                    <button class="send-btn" id="send-btn" onclick="askCoach()">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                    </button>
                </div>
            </div>

            <!-- Response -->
            <div class="response-area" id="response-area">
                <div class="resp-placeholder" id="resp-placeholder">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a7 7 0 1 1 0 14 7 7 0 0 1 0-14z"/><path d="M8.56 2.75c4.37 6.03 6.02 9.42 8.03 17.72m2.54-15.38c-3.72 4.35-8.94 5.66-16.88 5.85"/></svg>
                    <p>Select a stage, pick a topic or type your question — your coach responds in real time.</p>
                </div>
            </div>
        </div>

        <!-- RIGHT: Sidebar -->
        <div class="sidebar">

            <!-- Progress -->
            <div class="prog-card">
                <h4>Stage Progress</h4>
                <?php
                $total = max($stats['total'], 1);
                $stages = [
                    ['Prelims',   $stats['prelims'],   'var(--blue)',  $total],
                    ['Mains',     $stats['mains'],     'var(--teal)',  $total],
                    ['Interview', $stats['interview'], 'var(--amber)', $total],
                ];
                foreach ($stages as [$label, $count, $color, $t]):
                    $pct = $t > 0 ? round($count / $t * 100) : 0;
                ?>
                <div class="prog-item">
                    <div class="prog-meta">
                        <strong><?php echo $label; ?></strong>
                        <span><?php echo $count; ?> sessions</span>
                    </div>
                    <div class="prog-track">
                        <div class="prog-fill" style="width:<?php echo $pct; ?>%;background:<?php echo $color; ?>"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Recent sessions -->
            <div class="recent-card">
                <h4>Recent Sessions</h4>
                <?php if (empty($recent)): ?>
                <div class="no-sessions">No sessions yet — ask your first question!</div>
                <?php else: ?>
                <div id="recent-list">
                <?php foreach ($recent as $s):
                    $stageKey = strtolower($s['stage']);
                    $ago = '';
                    $ts = strtotime($s['created_at']);
                    $diff = time() - $ts;
                    if ($diff < 3600)      $ago = round($diff/60).'m ago';
                    elseif ($diff < 86400) $ago = round($diff/3600).'h ago';
                    else                   $ago = date('d M', $ts);
                ?>
                <div class="session-item">
                    <div class="sess-dot sd-<?php echo $stageKey; ?>"><?php echo strtoupper(substr($s['stage'],0,1)); ?></div>
                    <div class="sess-info">
                        <div class="sess-q"><?php echo htmlspecialchars(mb_substr($s['query'],0,42)); ?></div>
                        <div class="sess-time"><?php echo htmlspecialchars($s['stage']); ?> · <?php echo $ago; ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Tips -->
            <div class="tips-card">
                <h4>Coach Tips</h4>
                <div class="tip-item" id="tip-text">Be specific — ask about a single topic for the best answer.</div>
                <div class="tip-item">Use full questions like "Explain Article 370 for Mains GS II".</div>
                <div class="tip-item">Ask for "answer in points" or "in 150 words" to get exam-ready responses.</div>
            </div>
        </div>
    </div>
</main>

<!-- Toast notification -->
<div class="toast" id="toast">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
    <span id="toast-msg">Session saved!</span>
</div>

<script>
/* ════════════════════════════════════════════════
   CONFIG
════════════════════════════════════════════════ */
const API   = 'api/llama_api.php';
const SAVE  = 'save_coach_session.php';
let stage   = 'Prelims';
let busy    = false;
let lastResp= '';

/* Stage → topics map */
const TOPICS = {
    Prelims:   ['History','Polity','Geography','Economy','Environment','Science & Tech','Current Affairs','CSAT Strategy'],
    Mains:     ['GS I — History','GS II — Governance','GS III — Economy','GS IV — Ethics','Essay Writing','Answer Structure','Optional Subject','Current Issues'],
    Interview: ['DAF Preparation','Hobbies & Interests','Current Affairs','Ethics Scenarios','Home State','Self Introduction','Mock Interview','Service Preference'],
};

/* Stage → prompts */
const SYSTEM = {
    Prelims: `You are a UPSC Prelims expert coach. Give precise, exam-focused guidance. 
For factual questions give structured bullet-point answers. 
For strategy questions give a clear action plan. 
Keep responses under 200 words unless a detailed explanation is asked. 
Always relate the answer to UPSC Prelims GS Paper I or CSAT.`,
    Mains: `You are a UPSC Mains expert coach specializing in GS answer writing. 
Structure all answers with Introduction, Body (multi-dimensional: social, economic, political, environmental), and Conclusion. 
For "write an answer" requests, produce a proper Mains answer with headings. 
Keep answers within the word limit asked or 250 words default. 
Always end with a constructive way-forward.`,
    Interview: `You are a UPSC Personality Test (Interview) coach with 20 years of experience. 
Give warm, practical advice on communication, body language, and answer framing. 
For mock questions, give a model answer plus coaching tips on what the board looks for. 
Help the aspirant present themselves confidently and authentically.`,
};

/* ════════════════════════════════════════════════
   INIT
════════════════════════════════════════════════ */
window.addEventListener('DOMContentLoaded', function() {
    renderTopics('Prelims');
    rotateTip();
    setInterval(rotateTip, 8000);
});

/* ════════════════════════════════════════════════
   STAGE SWITCHING
════════════════════════════════════════════════ */
function setStage(s) {
    stage = s;
    // Tab styles
    ['Prelims','Mains','Interview'].forEach(function(t) {
        const el = document.getElementById('tab-' + t);
        el.className = 'stage-tab';
        if (t === s) {
            const cls = s === 'Prelims' ? 'active-prelims' : s === 'Mains' ? 'active-mains' : 'active-interview';
            el.classList.add(cls);
        }
    });
    renderTopics(s);
    document.getElementById('coach-input').placeholder =
        s === 'Prelims'   ? 'Ask about GS subjects, strategy, books…' :
        s === 'Mains'     ? 'Ask for answer writing, GS topics, optionals…' :
                            'Ask about DAF, mock questions, personality tips…';
}

/* ════════════════════════════════════════════════
   TOPIC CHIPS
════════════════════════════════════════════════ */
function renderTopics(s) {
    const wrap = document.getElementById('topic-chips');
    wrap.innerHTML = '';
    TOPICS[s].forEach(function(t) {
        const btn = document.createElement('button');
        btn.className = 'chip';
        btn.textContent = t;
        btn.onclick = function() {
            document.querySelectorAll('.chip').forEach(function(c){ c.classList.remove('active'); });
            btn.classList.add('active');
            const inp = document.getElementById('coach-input');
            inp.value = 'Give me a comprehensive guide on ' + t + ' for UPSC ' + s;
            autoResize(inp);
            inp.focus();
        };
        wrap.appendChild(btn);
    });
}

/* ════════════════════════════════════════════════
   ASK COACH  (streaming via SSE)
════════════════════════════════════════════════ */
async function askCoach() {
    const inp  = document.getElementById('coach-input');
    const query = inp.value.trim();
    if (!query || busy) return;

    busy = true;
    document.getElementById('send-btn').disabled = true;
    inp.disabled = true;

    const area = document.getElementById('response-area');
    const placeholder = document.getElementById('resp-placeholder');
    if (placeholder) placeholder.remove();

    // Show typing indicator
    const typing = document.createElement('div');
    typing.id = 'typing-ind';
    typing.className = 'typing-dots';
    typing.innerHTML = '<div class="td"></div><div class="td"></div><div class="td"></div>';
    area.innerHTML = '';
    area.appendChild(typing);

    const startTime = Date.now();
    let fullText = '';

    try {
        /* Build full prompt with system context */
        const fullPrompt = SYSTEM[stage] + '\n\nAspirant: ' + query + '\nCoach:';

        const resp = await fetch(API + '?action=stream', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: fullPrompt, messages: [] }),
        });

        if (!resp.ok) throw new Error('HTTP ' + resp.status);

        /* Remove typing, create response bubble */
        typing.remove();
        const bubble = document.createElement('div');
        bubble.className = 'ai-response';
        bubble.innerHTML = '<span id="resp-text"></span><span class="s-cur"></span>';
        area.appendChild(bubble);
        const respText = document.getElementById('resp-text');

        /* Stream tokens */
        const reader = resp.body.getReader();
        const dec    = new TextDecoder();
        let buf = '';

        while (true) {
            const { done, value } = await reader.read();
            if (done) break;
            buf += dec.decode(value, { stream: true });
            const lines = buf.split('\n');
            buf = lines.pop();
            for (const ln of lines) {
                if (!ln.startsWith('data:')) continue;
                let d;
                try { d = JSON.parse(ln.slice(5).trim()); } catch { continue; }
                if (d.error) throw new Error(d.error);
                if (d.token) {
                    fullText += d.token;
                    respText.innerHTML = formatResp(fullText);
                }
                if (d.done) break;
            }
        }

        /* Finalise — remove cursor, add meta row */
        bubble.querySelector('.s-cur')?.remove();
        lastResp = fullText;
        const duration = Math.round((Date.now() - startTime) / 1000);

        const meta = document.createElement('div');
        meta.className = 'resp-meta';
        const stageClass = 'rb-' + stage.toLowerCase();
        meta.innerHTML =
            '<span class="resp-badge ' + stageClass + '">' + stage + '</span>' +
            '<span class="resp-badge" style="background:var(--green-lt);color:var(--green)">' + duration + 's</span>' +
            '<span class="resp-time">' + new Date().toLocaleTimeString('en-IN', {hour:'2-digit',minute:'2-digit'}) + '</span>';
        area.appendChild(meta);

        /* Action buttons */
        const acts = document.createElement('div');
        acts.className = 'resp-actions';
        acts.innerHTML =
            '<button class="ract" onclick="copyResp()"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg> Copy</button>' +
            '<button class="ract" onclick="speakResp()"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07"/></svg> Listen</button>' +
            '<button class="ract" onclick="askFollowUp()"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg> Follow-up</button>' +
            '<button class="ract" onclick="saveSession(\'' + encodeURIComponent(query) + '\', ' + duration + ')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> Save</button>';
        area.appendChild(acts);

        /* Auto-save to DB */
        saveSession(encodeURIComponent(query), duration, true);

    } catch(e) {
        typing.remove();
        area.innerHTML = '<div class="ai-response" style="background:var(--danger-lt);border-color:rgba(239,68,68,.2)">' +
            '⚠️ ' + (e.message || 'Could not reach Ollama. Make sure it is running.') +
            '</div>';
    }

    busy = false;
    document.getElementById('send-btn').disabled = false;
    inp.disabled = false;
    inp.value = '';
    autoResize(inp);
}

/* ════════════════════════════════════════════════
   FORMAT RESPONSE (markdown-like)
════════════════════════════════════════════════ */
function formatResp(t) {
    return t
        .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
        .replace(/\*(.+?)\*/g, '<em>$1</em>')
        .replace(/`([^`]+)`/g, '<code>$1</code>')
        .replace(/^(\d+)\.\s+(.+)$/gm, '<div style="display:flex;gap:.4rem;padding:.1rem 0"><span style="flex-shrink:0;font-weight:600;color:var(--violet-dk)">$1.</span><span>$2</span></div>')
        .replace(/^[-•]\s+(.+)$/gm, '<div style="display:flex;gap:.4rem;padding:.1rem 0"><span style="flex-shrink:0;color:var(--violet-dk)">•</span><span>$1</span></div>')
        .replace(/^#{1,3}\s+(.+)$/gm, '<div style="font-family:\'Syne\',sans-serif;font-weight:700;font-size:.88rem;margin:.5rem 0 .15rem;color:var(--ink)">$1</div>')
        .replace(/\n\n/g,'<br><br>').replace(/\n/g,'<br>');
}

/* ════════════════════════════════════════════════
   SAVE SESSION  → save_coach_session.php
════════════════════════════════════════════════ */
function saveSession(encodedQuery, duration, silent) {
    const query = decodeURIComponent(encodedQuery);
    fetch(SAVE, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            stage:    stage,
            topic:    document.querySelector('.chip.active')?.textContent || '',
            query:    query,
            response: lastResp,
            duration: duration,
        })
    })
    .then(function(r){ return r.json(); })
    .then(function(d) {
        if (d.success) {
            // Update stats live
            const newTotal    = (parseInt(document.getElementById('st-total').textContent)||0) + 1;
            const stageEl     = document.getElementById('st-' + stage.toLowerCase());
            document.getElementById('st-total').textContent = newTotal;
            if (stageEl) stageEl.textContent = (parseInt(stageEl.textContent)||0) + 1;

            // Add to recent list
            addToRecent(query, stage);

            if (!silent) showToast('Session saved!');
        }
    })
    .catch(function(){ if (!silent) showToast('Saved locally'); });
}

/* ════════════════════════════════════════════════
   ADD TO RECENT LIST (live, no reload)
════════════════════════════════════════════════ */
function addToRecent(query, stg) {
    const list = document.getElementById('recent-list');
    if (!list) return;
    const noSess = list.querySelector('.no-sessions');
    if (noSess) noSess.remove();

    const div = document.createElement('div');
    div.className = 'session-item';
    const stageKey = stg.toLowerCase();
    div.innerHTML =
        '<div class="sess-dot sd-' + stageKey + '">' + stg[0].toUpperCase() + '</div>' +
        '<div class="sess-info">' +
            '<div class="sess-q">' + query.substring(0,42) + (query.length>42?'…':'') + '</div>' +
            '<div class="sess-time">' + stg + ' · just now</div>' +
        '</div>';
    list.insertBefore(div, list.firstChild);
    // Keep max 8
    while (list.children.length > 8) list.removeChild(list.lastChild);
}

/* ════════════════════════════════════════════════
   COPY / SPEAK / FOLLOW-UP
════════════════════════════════════════════════ */
function copyResp() {
    const t = document.getElementById('resp-text')?.textContent || lastResp;
    navigator.clipboard.writeText(t).then(function(){ showToast('Copied to clipboard!'); });
}

function speakResp() {
    if (!window.speechSynthesis) return;
    window.speechSynthesis.cancel();
    const clean = lastResp.replace(/\*\*(.+?)\*\*/g,'$1').replace(/\*(.+?)\*/g,'$1')
        .replace(/`(.+?)`/g,'$1').replace(/#{1,3}\s/g,'').replace(/<[^>]+>/g,'').substring(0,600);
    const utt = new SpeechSynthesisUtterance(clean);
    utt.lang = 'en-IN'; utt.rate = 0.94;
    const v = speechSynthesis.getVoices().find(function(v){ return v.lang==='en-IN'; });
    if (v) utt.voice = v;
    speechSynthesis.speak(utt);
    showToast('Speaking response…');
}

function askFollowUp() {
    const inp = document.getElementById('coach-input');
    inp.value = 'Can you elaborate more on this? ';
    inp.focus();
    autoResize(inp);
}

/* ════════════════════════════════════════════════
   UI HELPERS
════════════════════════════════════════════════ */
function onKey(e) { if (e.key==='Enter' && !e.shiftKey) { e.preventDefault(); askCoach(); } }
function autoResize(el) { el.style.height='auto'; el.style.height=Math.min(el.scrollHeight,120)+'px'; }

let toastTimer;
function showToast(msg) {
    const t = document.getElementById('toast');
    document.getElementById('toast-msg').textContent = msg;
    t.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function(){ t.classList.remove('show'); }, 2500);
}

/* Rotating tips */
const TIPS = [
    'Be specific — ask about a single topic for the best answer.',
    'Say "in 150 words" to get exam-ready Mains-length answers.',
    'Ask "give me 10 MCQs on Environment" for Prelims practice.',
    'Use Interview tab to practice with your home state questions.',
    'Ask "what are the common mistakes in GS IV answers?" for tips.',
    'Type "write a model answer on Climate Change for GS III".',
];
let tipIdx = 0;
function rotateTip() {
    document.getElementById('tip-text').textContent = TIPS[tipIdx % TIPS.length];
    tipIdx++;
}
</script>
</body>
</html>