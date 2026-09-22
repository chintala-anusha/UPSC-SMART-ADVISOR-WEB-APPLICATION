<?php
include 'includes/auth.php';  // guards page, sets $uid $user_name
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Neural Chat — UPSC Command Center</title>

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
    --ink:#1a1523;--muted:#6b6579;
    --border:rgba(0,0,0,0.08);--surface:#fff;--page:#f4f2f8;
    --ease:cubic-bezier(0.2,0.8,0.2,1);
}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;-webkit-font-smoothing:antialiased;}
html,body{height:100%;overflow:hidden;}
body{font-family:'DM Sans',sans-serif;background:var(--page);color:var(--ink);display:flex;flex-direction:column;}

/* ── TOP BAR ── */
.topbar{
    background:linear-gradient(135deg,var(--grad-start),var(--grad-end));
    color:#fff;height:62px;display:flex;align-items:center;
    padding:0 1.25rem;gap:.85rem;flex-shrink:0;position:relative;z-index:50;
    box-shadow:0 2px 20px rgba(0,0,0,.25);
}
.tb-back{
    width:40px;height:40px;border-radius:11px;
    display:flex;align-items:center;justify-content:center;
    background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);
    color:#fff;text-decoration:none;transition:all .2s;flex-shrink:0;
}
.tb-back:hover{background:rgba(255,255,255,.22);}
.tb-back svg{width:18px;height:18px;}
.tb-avatar{
    width:36px;height:36px;border-radius:10px;flex-shrink:0;
    background:rgba(255,255,255,.15);
    display:flex;align-items:center;justify-content:center;
}
.tb-avatar svg{width:19px;height:19px;}
.tb-info{flex:1;min-width:0;}
.tb-title{font-family:'Syne',sans-serif;font-size:.9rem;font-weight:700;}
.tb-sub{font-size:.7rem;display:flex;align-items:center;gap:.35rem;margin-top:.1rem;opacity:.8;}
.sdot{width:7px;height:7px;border-radius:50%;flex-shrink:0;}
.sdot.online {background:#6ee7b7;animation:pulse-dot 2s infinite;}
.sdot.offline{background:#fca5a5;}
.sdot.busy   {background:#fcd34d;animation:pulse-dot .6s infinite;}
.tb-actions{display:flex;gap:.45rem;}
.tb-btn{
    width:36px;height:36px;border-radius:9px;border:1px solid rgba(255,255,255,.18);
    background:rgba(255,255,255,.1);color:#fff;cursor:pointer;
    display:flex;align-items:center;justify-content:center;transition:all .2s;
}
.tb-btn:hover{background:rgba(255,255,255,.22);}
.tb-btn svg{width:15px;height:15px;}
.model-chip{
    background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);
    border-radius:100px;padding:.28rem .85rem;font-size:.7rem;font-weight:600;
    cursor:pointer;display:flex;align-items:center;gap:.35rem;white-space:nowrap;
}
.model-chip:hover{background:rgba(255,255,255,.22);}
.model-chip svg{width:11px;height:11px;}

/* ── OFFLINE PANEL ── */
.offline-panel{
    background:var(--amber-lt);border-bottom:1px solid rgba(245,158,11,.25);
    padding:.75rem 1.25rem;flex-shrink:0;display:none;
}
.offline-panel.show{display:block;}
.op-top{
    display:flex;align-items:center;gap:.65rem;cursor:pointer;
}
.op-ico{
    width:32px;height:32px;border-radius:9px;background:rgba(245,158,11,.15);
    display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#92400e;
}
.op-ico svg{width:16px;height:16px;}
.op-msg{font-size:.82rem;color:#92400e;flex:1;}
.op-msg strong{font-family:'Syne',sans-serif;font-weight:700;}
.op-chevron{color:#92400e;transition:transform .2s;}
.op-chevron svg{width:14px;height:14px;}
.offline-panel.expanded .op-chevron{transform:rotate(180deg);}
.op-detail{display:none;margin-top:.75rem;}
.offline-panel.expanded .op-detail{display:block;}
.fix-step{
    display:flex;align-items:flex-start;gap:.65rem;
    padding:.55rem .75rem;background:rgba(245,158,11,.08);
    border-radius:9px;margin-bottom:.5rem;font-size:.8rem;color:#78350f;
}
.fix-num{
    width:22px;height:22px;border-radius:6px;background:var(--amber);
    color:#fff;font-family:'Syne',sans-serif;font-weight:700;font-size:.72rem;
    display:flex;align-items:center;justify-content:center;flex-shrink:0;
}
.cmd{
    background:rgba(0,0,0,.08);border-radius:5px;padding:.15rem .5rem;
    font-family:'DM Mono',monospace;font-size:.78rem;color:#92400e;
    display:inline-block;
}
.op-actions{display:flex;gap:.6rem;margin-top:.75rem;flex-wrap:wrap;}
.op-btn{
    padding:.4rem .9rem;border-radius:8px;border:1px solid rgba(245,158,11,.4);
    background:transparent;font-family:'DM Sans',sans-serif;font-size:.78rem;
    color:#92400e;cursor:pointer;transition:all .2s;
}
.op-btn:hover{background:var(--amber);color:#fff;border-color:var(--amber);}
.op-btn.primary{background:var(--amber);color:#fff;border-color:var(--amber);}
.op-btn.primary:hover{background:#d97706;}

/* Diagnose results */
.diag-results{
    margin-top:.75rem;background:var(--surface);border-radius:10px;
    border:1px solid var(--border);overflow:hidden;display:none;
}
.diag-results.show{display:block;}
.diag-row{
    display:flex;align-items:center;gap:.65rem;padding:.55rem .85rem;
    border-bottom:1px solid var(--border);font-size:.78rem;
}
.diag-row:last-child{border-bottom:none;}
.diag-host{flex:1;font-family:'DM Mono',monospace;font-size:.72rem;}
.diag-badge{
    padding:.2rem .6rem;border-radius:6px;font-size:.68rem;font-weight:600;
}
.db-ok{background:var(--green-lt);color:var(--green);}
.db-fail{background:var(--red-lt);color:var(--red);}
.diag-models{font-size:.72rem;color:var(--muted);max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}

/* ── LAYOUT ── */
.chat-layout{display:flex;flex:1;overflow:hidden;}

/* Sidebar */
.sidebar{
    width:248px;flex-shrink:0;background:var(--surface);
    border-right:1px solid var(--border);display:flex;flex-direction:column;overflow:hidden;
    transition:width .3s var(--ease);
}
.sidebar.hidden{width:0;}
.sb-inner{width:248px;display:flex;flex-direction:column;height:100%;overflow:hidden;}
.sb-section{padding:.85rem .9rem .6rem;}
.sb-section h4{
    font-family:'Syne',sans-serif;font-size:.68rem;font-weight:700;
    text-transform:uppercase;letter-spacing:.1em;color:var(--muted);margin-bottom:.6rem;
}
.qb-list{display:flex;flex-direction:column;gap:.35rem;}
.qb{
    width:100%;text-align:left;padding:.5rem .7rem;border-radius:9px;
    border:1px solid var(--border);background:transparent;
    font-family:'DM Sans',sans-serif;font-size:.76rem;color:var(--ink);
    cursor:pointer;transition:all .18s;line-height:1.4;
}
.qb:hover{background:var(--violet-lt);border-color:rgba(124,58,237,.25);color:var(--violet);}
.sb-sep{height:1px;background:var(--border);margin:.2rem .9rem;}
.hist-area{flex:1;overflow-y:auto;padding:.4rem .5rem;}
.hist-area::-webkit-scrollbar{width:3px;}
.hist-area::-webkit-scrollbar-thumb{background:var(--border);border-radius:2px;}
.hi{
    padding:.45rem .65rem;border-radius:8px;cursor:pointer;
    font-size:.75rem;color:var(--muted);transition:all .15s;
    white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
}
.hi:hover{background:var(--page);color:var(--ink);}
.hi.cur{background:var(--violet-lt);color:var(--violet);font-weight:500;}

/* ── MAIN CHAT ── */
.chat-main{flex:1;display:flex;flex-direction:column;overflow:hidden;}

/* Messages scroll area */
.msgs{
    flex:1;overflow-y:auto;padding:1.1rem .9rem;
    display:flex;flex-direction:column;gap:.9rem;
}
.msgs::-webkit-scrollbar{width:4px;}
.msgs::-webkit-scrollbar-thumb{background:var(--border);border-radius:3px;}

/* Welcome screen */
.welcome{
    max-width:480px;margin:auto;text-align:center;padding:1.5rem;
    animation:fadeUp .4s var(--ease) both;
}
.w-ico{
    width:66px;height:66px;border-radius:20px;
    background:var(--violet-lt);
    display:flex;align-items:center;justify-content:center;margin:0 auto 1.1rem;
}
.w-ico svg{width:34px;height:34px;color:var(--violet);}
.welcome h2{font-family:'Syne',sans-serif;font-size:1.3rem;font-weight:800;margin-bottom:.45rem;}
.welcome p{font-size:.84rem;color:var(--muted);line-height:1.6;margin-bottom:1.25rem;}
.wpills{display:flex;flex-wrap:wrap;justify-content:center;gap:.4rem;}
.wp{
    padding:.35rem .8rem;border-radius:100px;border:1px solid var(--border);
    background:var(--surface);font-size:.72rem;color:var(--ink);cursor:pointer;transition:all .18s;
}
.wp:hover{background:var(--violet-lt);border-color:rgba(124,58,237,.3);color:var(--violet);}

/* Bubble rows */
.row{display:flex;gap:.55rem;align-items:flex-end;animation:fadeUp .3s var(--ease) both;}
.row.user{flex-direction:row-reverse;}
.av{
    width:30px;height:30px;border-radius:9px;flex-shrink:0;
    display:flex;align-items:center;justify-content:center;
}
.av.ai  {background:var(--violet-lt);color:var(--violet);}
.av.user{background:var(--violet);color:#fff;}
.av svg{width:15px;height:15px;}
.bub{
    max-width:74%;padding:.8rem 1rem;border-radius:17px;
    font-size:.87rem;line-height:1.65;position:relative;
}
.row.ai   .bub{background:var(--surface);border:1px solid var(--border);border-bottom-left-radius:4px;box-shadow:0 2px 10px rgba(0,0,0,.05);}
.row.user .bub{background:linear-gradient(135deg,var(--violet-dk),var(--violet));color:#fff;border-bottom-right-radius:4px;}
.bub code{font-family:'DM Mono',monospace;font-size:.78rem;background:rgba(0,0,0,.07);padding:.1rem .35rem;border-radius:4px;}
.row.user .bub code{background:rgba(255,255,255,.2);}
.bub-meta{display:flex;align-items:center;gap:.4rem;margin-top:.35rem;font-size:.65rem;color:var(--muted);}
.row.user .bub-meta{justify-content:flex-end;}
.mab{
    width:20px;height:20px;border-radius:5px;border:none;background:transparent;
    cursor:pointer;display:flex;align-items:center;justify-content:center;
    color:var(--muted);transition:all .15s;
}
.mab:hover{background:var(--page);color:var(--ink);}
.mab svg{width:11px;height:11px;}

/* Typing dots */
.typing{display:flex;gap:.55rem;align-items:flex-end;animation:fadeUp .3s var(--ease) both;}
.ty-bub{
    background:var(--surface);border:1px solid var(--border);
    border-radius:17px;border-bottom-left-radius:4px;
    padding:.65rem .9rem;display:flex;gap:4px;align-items:center;
    box-shadow:0 2px 10px rgba(0,0,0,.05);
}
.td{width:7px;height:7px;border-radius:50%;background:var(--muted);animation:bounce .8s infinite;}
.td:nth-child(2){animation-delay:.15s;}
.td:nth-child(3){animation-delay:.3s;}
.cur{
    display:inline-block;width:2px;height:.9em;
    background:var(--violet);border-radius:1px;margin-left:1px;
    vertical-align:text-bottom;animation:blink 1s infinite;
}

/* ── INPUT AREA ── */
.input-area{
    padding:.75rem .9rem;border-top:1px solid var(--border);
    background:var(--surface);flex-shrink:0;
}
.speaking-bar{
    display:none;align-items:center;gap:.5rem;
    padding:.4rem .8rem;background:var(--teal-lt);border-radius:8px;
    font-size:.75rem;color:var(--teal);font-weight:500;margin-bottom:.55rem;
}
.speaking-bar.on{display:flex;}
.speaking-bar svg{width:14px;height:14px;}
.stop-sp{
    margin-left:auto;background:rgba(15,185,138,.15);border:none;
    border-radius:6px;padding:.2rem .55rem;font-size:.7rem;color:var(--teal);
    cursor:pointer;font-family:'DM Sans',sans-serif;
}
.voice-wave{
    height:32px;display:none;align-items:center;justify-content:center;gap:3px;margin-bottom:.55rem;
}
.voice-wave.on{display:flex;}
.wb{width:3px;border-radius:2px;background:var(--violet);height:8px;animation:wave .7s infinite ease-in-out;}
.wb:nth-child(2){animation-delay:.1s;}
.wb:nth-child(3){animation-delay:.2s;height:20px;}
.wb:nth-child(4){animation-delay:.1s;}
.wb:nth-child(5){animation-delay:.0s;}
.tp-prev{
    font-size:.75rem;color:var(--violet);font-style:italic;
    padding:.35rem .7rem;background:var(--violet-lt);border-radius:7px;
    margin-bottom:.55rem;display:none;
}
.tp-prev.on{display:block;}
.irow{display:flex;gap:.5rem;align-items:flex-end;}
.iwrap{
    flex:1;background:var(--page);border-radius:14px;
    border:1.5px solid var(--border);transition:border-color .2s;
    display:flex;align-items:flex-end;padding:.5rem .7rem;gap:.4rem;
}
.iwrap:focus-within{border-color:var(--violet);background:#fff;}
#inp{
    flex:1;border:none;background:transparent;resize:none;
    font-family:'DM Sans',sans-serif;font-size:.88rem;color:var(--ink);
    outline:none;max-height:110px;min-height:22px;line-height:1.5;
    scrollbar-width:none;
}
#inp::placeholder{color:var(--muted);}
#inp::-webkit-scrollbar{display:none;}
.ib{
    width:42px;height:42px;border-radius:12px;border:none;cursor:pointer;
    display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .2s var(--ease);
}
.ib svg{width:17px;height:17px;}
.vbtn{background:var(--violet-lt);color:var(--violet);}
.vbtn:hover{background:var(--violet);color:#fff;transform:scale(1.05);}
.vbtn.on{background:var(--red);color:#fff;animation:pulse-btn 1s infinite;}
.sbtn{background:var(--violet);color:#fff;}
.sbtn:hover{background:var(--violet-dk);transform:scale(1.05);}
.sbtn:disabled{background:var(--border);color:var(--muted);cursor:not-allowed;transform:none;}
.footer-txt{text-align:center;font-size:.65rem;color:var(--muted);margin-top:.45rem;}

/* Model dropdown */
.mdrop{
    position:absolute;top:54px;right:1rem;
    background:var(--surface);border:1px solid var(--border);border-radius:13px;
    padding:.4rem;min-width:200px;
    box-shadow:0 12px 40px rgba(0,0,0,.15);z-index:200;
    display:none;animation:fadeUp .2s var(--ease) both;
}
.mdrop.open{display:block;}
.mdrop-hd{padding:.35rem .7rem .6rem;font-size:.65rem;color:var(--muted);font-weight:600;text-transform:uppercase;letter-spacing:.09em;}
.mopt{
    display:flex;align-items:center;gap:.55rem;
    padding:.5rem .7rem;border-radius:8px;cursor:pointer;
    font-size:.8rem;transition:background .15s;
}
.mopt:hover{background:var(--page);}
.mopt.on{background:var(--violet-lt);color:var(--violet);font-weight:500;}
.mabb{
    width:26px;height:26px;border-radius:7px;background:var(--page);
    display:flex;align-items:center;justify-content:center;
    font-size:.65rem;font-weight:700;color:var(--muted);flex-shrink:0;
}

@keyframes fadeUp{from{opacity:0;transform:translateY(12px);}to{opacity:1;transform:translateY(0);}}
@keyframes pulse-dot{0%,100%{opacity:1;}50%{opacity:.3;}}
@keyframes bounce{0%,80%,100%{transform:translateY(0);}40%{transform:translateY(-6px);}}
@keyframes blink{0%,100%{opacity:1;}50%{opacity:0;}}
@keyframes wave{0%,100%{height:8px;}50%{height:24px;}}
@keyframes pulse-btn{0%,100%{box-shadow:0 0 0 0 rgba(239,68,68,.4);}70%{box-shadow:0 0 0 8px rgba(239,68,68,0);}}

@media(max-width:650px){
    .sidebar{display:none;}
    .bub{max-width:88%;}
    .model-chip span{display:none;}
}
</style>
</head>
<body>

<!-- ── TOP BAR ── -->
<div class="topbar">
    <a href="dashboard.php" class="tb-back">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
    </a>
    <div class="tb-avatar">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a7 7 0 1 1 0 14 7 7 0 0 1 0-14z"/><path d="M8.56 2.75c4.37 6.03 6.02 9.42 8.03 17.72m2.54-15.38c-3.72 4.35-8.94 5.66-16.88 5.85"/></svg>
    </div>
    <div class="tb-info">
        <div class="tb-title">UPSC Neural Chat</div>
        <div class="tb-sub">
            <div class="sdot offline" id="sdot"></div>
            <span id="stxt">Connecting…</span>
        </div>
    </div>
    <div class="tb-actions">
        <div class="model-chip" id="model-chip" onclick="toggleMdrop()">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
            <span id="mname">llama3</span>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
        <button class="tb-btn" onclick="clearChat()" title="Clear conversation">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.16"/></svg>
        </button>
        <button class="tb-btn" onclick="toggleSb()" title="Topics">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
    </div>
    <!-- Model dropdown -->
    <div class="mdrop" id="mdrop">
        <div class="mdrop-hd">Available Models</div>
        <div id="mlist">
            <div class="mopt on" data-m="llama3"><div class="mabb">L3</div>llama3</div>
        </div>
    </div>
</div>

<!-- ── OFFLINE PANEL ── -->
<div class="offline-panel" id="offline-panel">
    <div class="op-top" onclick="toggleOfflineDetail()">
        <div class="op-ico">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        </div>
        <div class="op-msg">
            <strong>Ollama is offline.</strong> Click to see how to fix this.
        </div>
        <div class="op-chevron">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
    </div>

    <div class="op-detail" id="op-detail">
        <div class="fix-step"><div class="fix-num">1</div><div>Install Ollama from <strong>ollama.com/download</strong> (free, runs locally)</div></div>
        <div class="fix-step"><div class="fix-num">2</div><div>Open Terminal / Command Prompt and run: <span class="cmd">ollama serve</span></div></div>
        <div class="fix-step"><div class="fix-num">3</div><div>Pull the model (first time only): <span class="cmd">ollama pull llama3</span></div></div>
        <div class="fix-step"><div class="fix-num">4</div><div>Verify it works: <span class="cmd">curl http://localhost:11434/api/tags</span></div></div>
        <div class="fix-step" id="fix-remote" style="display:none;"><div class="fix-num">!</div><div>You're on a <strong>remote server</strong> — Ollama must run on the server itself, not your local PC. SSH in and run <span class="cmd">ollama serve</span> there.</div></div>

        <div class="op-actions">
            <button class="op-btn primary" onclick="clearCacheAndRetry()">↺ Retry Connection</button>
            <button class="op-btn" onclick="runDiagnose()">🔍 Diagnose</button>
            <a href="https://ollama.com/download" target="_blank" class="op-btn">⬇ Download Ollama</a>
            <a href="engine_setup.php" class="op-btn">⚙ Setup Guide</a>
        </div>

        <!-- Diagnose results -->
        <div class="diag-results" id="diag-results"></div>
    </div>
</div>

<!-- ── CHAT LAYOUT ── -->
<div class="chat-layout">

    <!-- Sidebar -->
    <div class="sidebar" id="sb">
        <div class="sb-inner">
            <div class="sb-section">
                <h4>Quick Topics</h4>
                <div class="qb-list">
                    <button class="qb" onclick="sendQ(this)">What is the UPSC Prelims syllabus?</button>
                    <button class="qb" onclick="sendQ(this)">Explain Article 370 for Mains answer</button>
                    <button class="qb" onclick="sendQ(this)">Best books for UPSC Polity GS II</button>
                    <button class="qb" onclick="sendQ(this)">How to write a 150-word Mains answer?</button>
                    <button class="qb" onclick="sendQ(this)">Negative marking rules in Prelims</button>
                    <button class="qb" onclick="sendQ(this)">CSAT Paper II preparation strategy</button>
                    <button class="qb" onclick="sendQ(this)">Ethics GS IV case study approach</button>
                    <button class="qb" onclick="sendQ(this)">How to choose optional subject?</button>
                </div>
            </div>
            <div class="sb-sep"></div>
            <div class="sb-section" style="padding-bottom:.3rem;"><h4>Recent</h4></div>
            <div class="hist-area" id="hist"></div>
        </div>
    </div>

    <!-- Messages -->
    <div class="chat-main">
        <div class="msgs" id="msgs">
            <div class="welcome" id="welcome">
                <div class="w-ico">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a7 7 0 1 1 0 14 7 7 0 0 1 0-14z"/><path d="M8.56 2.75c4.37 6.03 6.02 9.42 8.03 17.72m2.54-15.38c-3.72 4.35-8.94 5.66-16.88 5.85m19.5 1.9c-3.5-.93-6.63-.82-8.94 0-2.58.92-5.01 2.86-7.44 6.32"/></svg>
                </div>
                <h2>UPSC Mentor AI</h2>
                <p>Powered by Llama 3 — running 100% on your device. Ask me anything about UPSC.</p>
                <div class="wpills">
                    <span class="wp" onclick="sendQ(this)">📚 Prelims strategy</span>
                    <span class="wp" onclick="sendQ(this)">✍️ Answer writing</span>
                    <span class="wp" onclick="sendQ(this)">📖 Book list</span>
                    <span class="wp" onclick="sendQ(this)">🏛️ Polity MCQs</span>
                    <span class="wp" onclick="sendQ(this)">🌍 Geography topics</span>
                    <span class="wp" onclick="sendQ(this)">💡 Ethics tips</span>
                </div>
            </div>
        </div>

        <!-- Input -->
        <div class="input-area">
            <div class="speaking-bar" id="sp-bar">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07"/></svg>
                Speaking…
                <button class="stop-sp" onclick="stopSpeak()">Stop</button>
            </div>
            <div class="voice-wave" id="vwave">
                <div class="wb"></div><div class="wb"></div><div class="wb"></div>
                <div class="wb"></div><div class="wb"></div>
            </div>
            <div class="tp-prev" id="tprev"></div>
            <div class="irow">
                <div class="iwrap">
                    <textarea id="inp" rows="1" placeholder="Ask anything about UPSC…"
                              onkeydown="onKey(event)" oninput="resize(this)" maxlength="2000"></textarea>
                </div>
                <button class="ib vbtn" id="vbtn" onclick="toggleVoice()" title="Voice input">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="23"/><line x1="8" y1="23" x2="16" y2="23"/></svg>
                </button>
                <button class="ib sbtn" id="sbtn" onclick="send()" title="Send">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                </button>
            </div>
            <div class="footer-txt">Llama 3 via Ollama · Runs locally · Your data never leaves your device</div>
        </div>
    </div>
</div>

<script>
/* ═══════════════════════════════════════════════════════
   CONFIG
═══════════════════════════════════════════════════════ */
const API  = 'api/llama_api.php';
const UID  = <?php echo $uid; ?>;
let _sessionRef = null;
function sessionRef() {
    if (!_sessionRef) _sessionRef = 'chat-' + UID + '-' + Date.now();
    return _sessionRef;
}
let msgs   = [];
let busy   = false;
let model  = 'llama3';
let synth  = window.speechSynthesis;
let utt    = null;
let recog  = null;
let listen = false;

/* ═══════════════════════════════════════════════════════
   INIT
═══════════════════════════════════════════════════════ */
let _retryTimer = null;

window.addEventListener('DOMContentLoaded', () => {
    checkHealth();
    initVoice();
    /* Auto-retry connection every 12 seconds while offline */
    _retryTimer = setInterval(() => {
        const dot = document.getElementById('sdot');
        if (dot && dot.classList.contains('offline')) {
            checkHealth();
        }
    }, 12000);
    document.addEventListener('click', e => {
        if (!e.target.closest('#model-chip') && !e.target.closest('#mdrop'))
            document.getElementById('mdrop').classList.remove('open');
    });
});

/* ═══════════════════════════════════════════════════════
   HEALTH CHECK
═══════════════════════════════════════════════════════ */
async function checkHealth() {
    setStatus('busy', 'Connecting…');
    try {
        /* Add timestamp to bust any browser or proxy cache */
        const r = await fetch(API + '?action=health&_t=' + Date.now(), {
            cache: 'no-store',
            headers: { 'Cache-Control': 'no-cache' }
        });

        /* If PHP returns non-JSON (error page), catch it */
        const text = await r.text();
        let d;
        try { d = JSON.parse(text); }
        catch(e) {
            goOffline('PHP error in llama_api.php — check Apache error log. Raw: ' + text.substring(0,120));
            return;
        }

        if (d.ollama === 'online') {
            setStatus('online', `${(d.host||'').replace('http://','') || 'localhost:11434'} · Ready`);
            document.getElementById('offline-panel').classList.remove('show');
            loadModels();
        } else {
            /* Clear server-side host cache so next retry re-probes all addresses */
            goOffline(d.hint || 'Ollama offline — run: ollama serve');
        }
    } catch(e) {
        goOffline('Cannot reach api/llama_api.php — is Apache running? (' + e.message + ')');
    }
}

function goOffline(hint) {
    setStatus('offline', 'Ollama offline');
    const p = document.getElementById('offline-panel');
    p.classList.add('show');
    // Show extra hint if remote server
    if (hint && hint.toLowerCase().includes('ssh')) {
        document.getElementById('fix-remote').style.display = 'flex';
    }
}

function setStatus(s, t) {
    const d = document.getElementById('sdot');
    d.className = 'sdot ' + s;
    document.getElementById('stxt').textContent = t;
}

async function loadModels() {
    try {
        const r = await fetch(API + '?action=models');
        const d = await r.json();
        if (!d.models?.length) return;
        const list = document.getElementById('mlist');
        list.innerHTML = '';
        d.models.forEach(m => {
            const el = document.createElement('div');
            el.className = 'mopt' + (m === model ? ' on' : '');
            el.dataset.m = m;
            const ab = m.replace(/[^a-z0-9]/gi,'').substring(0,3).toUpperCase();
            el.innerHTML = `<div class="mabb">${ab}</div>${m}`;
            el.onclick = () => selectModel(m);
            list.appendChild(el);
        });
    } catch(e) {}
}

/* ═══════════════════════════════════════════════════════
   DIAGNOSE
═══════════════════════════════════════════════════════ */
async function runDiagnose() {
    const box = document.getElementById('diag-results');
    box.innerHTML = '<div style="padding:.75rem;font-size:.78rem;color:var(--muted)">🔍 Running diagnostics…</div>';
    box.classList.add('show');
    try {
        const r = await fetch(API + '?action=diagnose');
        const d = await r.json();
        let html = '';
        for (const [host, res] of Object.entries(d.hosts_tested || {})) {
            const ok = res.reachable;
            html += `<div class="diag-row">
                <div class="diag-host">${host}</div>
                <span class="diag-badge ${ok ? 'db-ok' : 'db-fail'}">${ok ? '✓ Reachable' : '✗ No response'}</span>
                <div class="diag-models">${ok ? (res.models.join(', ') || 'No models') : (res.curl_error || '')}</div>
            </div>`;
        }
        if (!html) html = '<div style="padding:.75rem;font-size:.78rem;color:var(--muted)">No results</div>';
        box.innerHTML = html;

        // If any online, auto retry
        if (d.success) {
            setTimeout(() => checkHealth(), 300);
        }
    } catch(e) {
        box.innerHTML = '<div style="padding:.75rem;font-size:.78rem;color:var(--red)">Diagnose failed: ' + e.message + '</div>';
    }
}

function toggleOfflineDetail() {
    document.getElementById('offline-panel').classList.toggle('expanded');
}

/* Clears server-side PHP session host cache then retries */
async function clearCacheAndRetry() {
    try {
        /* Hitting ?action=health with cache-bust forces PHP to re-probe all hosts */
        await fetch(API + '?action=health&nocache=1&_t=' + Date.now(), {
            cache: 'no-store', headers: { 'Cache-Control': 'no-cache, no-store' }
        });
    } catch(e) {}
    checkHealth();
}

/* ═══════════════════════════════════════════════════════
   MODEL
═══════════════════════════════════════════════════════ */
function toggleMdrop() { document.getElementById('mdrop').classList.toggle('open'); }
function selectModel(m) {
    model = m;
    document.getElementById('mname').textContent = m;
    document.querySelectorAll('.mopt').forEach(e => e.classList.toggle('on', e.dataset.m === m));
    document.getElementById('mdrop').classList.remove('open');
}

/* ═══════════════════════════════════════════════════════
   SEND MESSAGE
═══════════════════════════════════════════════════════ */
async function send() {
    const inp  = document.getElementById('inp');
    const text = inp.value.trim();
    if (!text || busy) return;
    inp.value = '';
    resize(inp);
    rmWelcome();
    stopSpeak();

    addMsg('user', text);
    msgs.push({ role:'user', text });

    setBusy(true);
    setStatus('busy', 'Thinking…');

    const tid = addTyping();
    try {
        await streamReply(text, tid);
    } catch(e) {
        rmEl(tid);
        addMsg('ai', `⚠️ ${e.message || 'Could not reach Ollama. Make sure it is running with: ollama serve'}`);
        goOffline(e.message);
    }
    setBusy(false);
    setStatus('online', 'Llama 3 · Ready');
}

/* ═══════════════════════════════════════════════════════
   STREAMING
═══════════════════════════════════════════════════════ */
async function streamReply(text, tid) {
    rmEl(tid);
    const id  = 'r' + Date.now();
    const row = addMsg('ai', '', id);
    const txt = row.querySelector('.msg-text');
    txt.innerHTML = '<span class="cur"></span>';
    let full = '';

    const resp = await fetch(API + '?action=stream', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ message: text, messages: msgs.slice(-12) }),
    });

    if (!resp.ok) {
        /* Try JSON first, fall back to raw text */
        const rawErr = await resp.text().catch(() => '');
        let errObj = {};
        try { errObj = JSON.parse(rawErr); } catch(e) {}
        const msg = errObj.hint || errObj.error || rawErr.substring(0,120) || 'HTTP ' + resp.status;
        throw new Error(msg);
    }

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
            if (d.error) throw new Error(d.hint || d.error);
            if (d.token) {
                full += d.token;
                txt.innerHTML = fmt(full) + '<span class="cur"></span>';
                scrollBot();
            }
            if (d.done) break;
        }
    }
    txt.innerHTML = fmt(full);
    msgs.push({ role:'assistant', text: full });
    addHist(text);
    scrollBot();
    speak(full);

    /* ── Save both turns to chat_history table ── */
    saveChatHistory(text, full);
}

function saveChatHistory(userMsg, aiMsg) {
    const ref = sessionRef();
    fetch('save_chat_history.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify([
            { role:'user',      message: userMsg, session_ref: ref },
            { role:'assistant', message: aiMsg,   session_ref: ref },
        ])
    }).catch(function(){}); // silent fail — chat still works offline
}

/* ═══════════════════════════════════════════════════════
   FORMAT
═══════════════════════════════════════════════════════ */
function fmt(t) {
    return t
        .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
        .replace(/\*(.+?)\*/g, '<em>$1</em>')
        .replace(/`([^`]+)`/g, '<code>$1</code>')
        .replace(/^(\d+)\.\s+(.+)$/gm, '<div style="display:flex;gap:.45rem;padding:.1rem 0"><span style="flex-shrink:0;font-weight:600;color:var(--violet)">$1.</span><span>$2</span></div>')
        .replace(/^[-•]\s+(.+)$/gm, '<div style="display:flex;gap:.45rem;padding:.1rem 0"><span style="flex-shrink:0;color:var(--violet)">•</span><span>$1</span></div>')
        .replace(/^#{1,3}\s+(.+)$/gm, '<div style="font-family:\'Syne\',sans-serif;font-weight:700;font-size:.87rem;margin:.5rem 0 .15rem">$1</div>')
        .replace(/\n\n/g, '<br><br>').replace(/\n/g, '<br>');
}

/* ═══════════════════════════════════════════════════════
   VOICE INPUT
═══════════════════════════════════════════════════════ */
function initVoice() {
    const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SR) return;
    recog = new SR();
    recog.continuous = false; recog.interimResults = true; recog.lang = 'en-IN';
    recog.onresult = e => {
        let fin = '', int = '';
        for (let i = e.resultIndex; i < e.results.length; i++)
            e.results[i].isFinal ? fin += e.results[i][0].transcript : int += e.results[i][0].transcript;
        const p = document.getElementById('tprev');
        p.textContent = '"' + (int || fin) + '"';
        p.classList.add('on');
        if (fin) { document.getElementById('inp').value = fin; resize(document.getElementById('inp')); }
    };
    recog.onend = () => {
        stopListen();
        const v = document.getElementById('inp').value.trim();
        if (v) send();
    };
    recog.onerror = e => { stopListen(); if (e.error !== 'aborted') addMsg('ai', '🎤 Mic error: ' + e.error); };
}

function toggleVoice() {
    if (!recog) { addMsg('ai', '🎤 Voice input not supported in this browser. Use Chrome or Edge.'); return; }
    listen ? (recog.stop(), stopListen()) : (stopSpeak(), recog.start(), startListen());
}
function startListen() {
    listen = true;
    document.getElementById('vbtn').classList.add('on');
    document.getElementById('vwave').classList.add('on');
    document.getElementById('tprev').classList.remove('on');
    setStatus('busy', 'Listening…');
}
function stopListen() {
    listen = false;
    document.getElementById('vbtn').classList.remove('on');
    document.getElementById('vwave').classList.remove('on');
    setStatus('online', 'Llama 3 · Ready');
}

/* ═══════════════════════════════════════════════════════
   VOICE OUTPUT
═══════════════════════════════════════════════════════ */
function speak(text) {
    if (!synth) return;
    stopSpeak();
    const clean = text.replace(/\*\*(.+?)\*\*/g,'$1').replace(/\*(.+?)\*/g,'$1')
        .replace(/`(.+?)`/g,'$1').replace(/#{1,3}\s/g,'').replace(/<[^>]+>/g,'').substring(0, 500);
    utt = new SpeechSynthesisUtterance(clean);
    utt.lang = 'en-IN'; utt.rate = 0.94; utt.pitch = 1.05;
    const voices = synth.getVoices();
    const v = voices.find(v => v.lang === 'en-IN') || voices.find(v => v.lang.startsWith('en-'));
    if (v) utt.voice = v;
    utt.onstart = () => document.getElementById('sp-bar').classList.add('on');
    utt.onend   = () => document.getElementById('sp-bar').classList.remove('on');
    utt.onerror = () => document.getElementById('sp-bar').classList.remove('on');
    synth.speak(utt);
}
function stopSpeak() {
    if (synth?.speaking) synth.cancel();
    document.getElementById('sp-bar').classList.remove('on');
}

/* ═══════════════════════════════════════════════════════
   UI HELPERS
═══════════════════════════════════════════════════════ */
function addMsg(role, text, id) {
    const c   = document.getElementById('msgs');
    const row = document.createElement('div');
    row.className = 'row ' + role;
    if (id) row.id = id;
    const ai_ico  = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a7 7 0 1 1 0 14 7 7 0 0 1 0-14z"/><path d="M8.56 2.75c4.37 6.03 6.02 9.42 8.03 17.72"/></svg>`;
    const usr_ico = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>`;
    const tm  = new Date().toLocaleTimeString('en-IN', {hour:'2-digit', minute:'2-digit'});
    const cp  = role === 'ai' ? `<button class="mab" onclick="cpMsg(this)" title="Copy"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg></button>` : '';
    const sp  = role === 'ai' ? `<button class="mab" onclick="reSpeak(this)" title="Speak"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07"/></svg></button>` : '';
    row.innerHTML = `
        <div class="av ${role}">${role==='ai'?ai_ico:usr_ico}</div>
        <div>
            <div class="bub"><span class="msg-text">${text ? fmt(text) : ''}</span></div>
            <div class="bub-meta">${tm}${cp}${sp}</div>
        </div>`;
    c.appendChild(row);
    scrollBot();
    return row;
}

function addTyping() {
    const c  = document.getElementById('msgs');
    const id = 't' + Date.now();
    const r  = document.createElement('div');
    r.className = 'typing'; r.id = id;
    const ai_ico = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a7 7 0 1 1 0 14 7 7 0 0 1 0-14z"/></svg>`;
    r.innerHTML = `<div class="av ai">${ai_ico}</div><div class="ty-bub"><div class="td"></div><div class="td"></div><div class="td"></div></div>`;
    c.appendChild(r); scrollBot(); return id;
}

function rmEl(id) { const e = document.getElementById(id); if(e) e.remove(); }
function rmWelcome() { const w = document.getElementById('welcome'); if(w) w.remove(); }
function setBusy(v) {
    busy = v;
    document.getElementById('sbtn').disabled = v;
    document.getElementById('inp').disabled  = v;
}
function scrollBot() { const c = document.getElementById('msgs'); c.scrollTop = c.scrollHeight; }
function resize(el) { el.style.height='auto'; el.style.height=Math.min(el.scrollHeight,110)+'px'; }
function onKey(e) { if (e.key==='Enter' && !e.shiftKey) { e.preventDefault(); send(); } }

function sendQ(el) {
    document.getElementById('inp').value = el.textContent.replace(/^[^\w]+/,'').trim();
    send();
}

function cpMsg(btn) {
    const t = btn.closest('.row').querySelector('.msg-text').textContent;
    navigator.clipboard.writeText(t).then(() => {
        const orig = btn.innerHTML;
        btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>`;
        setTimeout(() => btn.innerHTML = orig, 1500);
    });
}
function reSpeak(btn) { speak(btn.closest('.row').querySelector('.msg-text').textContent); }

function addHist(q) {
    const h = document.getElementById('hist');
    const d = document.createElement('div');
    d.className = 'hi cur';
    d.textContent = q.substring(0, 38) + (q.length > 38 ? '…' : '');
    document.querySelectorAll('.hi').forEach(e => e.classList.remove('cur'));
    h.insertBefore(d, h.firstChild);
    while (h.children.length > 20) h.removeChild(h.lastChild);
}

function clearChat() {
    msgs = [];
    const c = document.getElementById('msgs');
    c.innerHTML = '';
    const w = document.createElement('div');
    w.className = 'welcome'; w.id = 'welcome';
    w.innerHTML = `<div class="w-ico"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a7 7 0 1 1 0 14 7 7 0 0 1 0-14z"/><path d="M8.56 2.75c4.37 6.03 6.02 9.42 8.03 17.72m2.54-15.38c-3.72 4.35-8.94 5.66-16.88 5.85m19.5 1.9c-3.5-.93-6.63-.82-8.94 0-2.58.92-5.01 2.86-7.44 6.32"/></svg></div><h2>UPSC Mentor AI</h2><p>Ask me anything about UPSC — syllabus, strategy, books, or any GS topic.</p>`;
    c.appendChild(w);
    stopSpeak();
}
function toggleSb() { document.getElementById('sb').classList.toggle('hidden'); }
</script>
</body>
</html>