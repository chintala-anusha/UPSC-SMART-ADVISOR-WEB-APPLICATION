<?php
include 'includes/auth.php';   // guards page, sets $uid
include 'includes/db.php';     // provides $conn

/* ---------------------------------------------------------------
   SCAN pyq_papers/
   p{year}.pdf  →  Prelims   e.g. p2023.pdf
   m{year}.pdf  →  Mains     e.g. m2023.pdf
--------------------------------------------------------------- */
$base_dir = __DIR__ . '/pyq_papers/';
$base_url = 'pyq_papers/';

$prelims = [];
$mains   = [];

if (is_dir($base_dir)) {
    foreach (glob($base_dir . '*.pdf') as $file) {
        $name   = pathinfo(basename($file), PATHINFO_FILENAME); // e.g. "p2023"
        $prefix = strtolower($name[0]);
        $year   = (int) substr($name, 1);
        if ($year < 2000 || $year > 2099) continue;
        $url = $base_url . basename($file);
        if ($prefix === 'p') $prelims[$year] = $url;
        if ($prefix === 'm') $mains[$year]   = $url;
    }
}

krsort($prelims);
krsort($mains);

$total_prelims = count($prelims);
$total_mains   = count($mains);
$total         = $total_prelims + $total_mains;

// Year range across both
$all_years = array_merge(array_keys($prelims), array_keys($mains));
$yr_min    = $all_years ? min($all_years) : 2001;
$yr_max    = $all_years ? max($all_years) : 2024;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Past Papers — UPSC Command Center</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500&display=swap" rel="stylesheet">

    <style>
        :root {
            --grad-start: #1a0533;
            --grad-mid:   #3d0f6b;
            --grad-end:   #6b1fa8;
            --violet:     #7c3aed;
            --violet-lt:  #f0eaff;
            --coral:      #ff6b4a;
            --coral-lt:   #fff0ed;
            --blue:       #3b82f6;
            --blue-lt:    #eff5ff;
            --ink:        #1a1523;
            --muted:      #6b6579;
            --border:     rgba(0,0,0,0.07);
            --surface:    #ffffff;
            --page:       #f4f2f8;
            --ease:       cubic-bezier(0.2,0.8,0.2,1);
        }

        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; -webkit-font-smoothing:antialiased; }
        html { scroll-behavior:smooth; }
        body { font-family:'DM Sans',sans-serif; background:var(--page); color:var(--ink); min-height:100vh; overflow-x:hidden; }
        a { text-decoration:none; color:inherit; }

        /* ── HERO ── */
        .hero {
            background:linear-gradient(135deg,var(--grad-start) 0%,var(--grad-mid) 50%,var(--grad-end) 100%);
            position:relative; padding:5rem 2rem 7rem; text-align:center; color:#fff; overflow:hidden;
        }
        .hero::before {
            content:''; position:absolute; top:50%; left:50%; transform:translate(-50%,-60%);
            width:560px; height:360px;
            background:radial-gradient(ellipse,rgba(160,80,255,0.28) 0%,transparent 70%);
            pointer-events:none;
        }
        .hero-inner { position:relative; z-index:2; max-width:660px; margin:0 auto; }

        .back-btn {
            position:absolute; top:1.75rem; left:1.75rem; z-index:50;
            width:46px; height:46px; border-radius:13px;
            display:flex; align-items:center; justify-content:center;
            background:rgba(255,255,255,0.13); border:1px solid rgba(255,255,255,0.22);
            backdrop-filter:blur(12px); color:#fff; transition:all 0.25s var(--ease);
        }
        .back-btn:hover { background:rgba(255,255,255,0.25); transform:translateY(-2px); }
        .back-btn svg { width:20px; height:20px; }

        .pre-badge {
            display:inline-block; font-family:'Syne',sans-serif;
            font-size:0.7rem; font-weight:600; letter-spacing:0.18em; text-transform:uppercase;
            background:rgba(255,255,255,0.12); border:1px solid rgba(255,255,255,0.22);
            border-radius:100px; padding:0.35rem 1.1rem; margin-bottom:1.2rem;
        }
        .hero h1 {
            font-family:'Syne',sans-serif; font-size:clamp(2rem,5vw,3.2rem);
            font-weight:800; line-height:1.12; letter-spacing:-0.02em; margin-bottom:0.8rem;
        }
        .hero p { font-size:1rem; font-weight:300; opacity:0.8; max-width:420px; margin:0 auto; }

        .stat-row { display:flex; justify-content:center; gap:1rem; margin-top:2rem; flex-wrap:wrap; }
        .stat-pill {
            background:rgba(255,255,255,0.12); border:1px solid rgba(255,255,255,0.2);
            border-radius:100px; padding:0.45rem 1.2rem;
            font-size:0.82rem; font-weight:500; display:flex; align-items:center; gap:0.45rem;
        }
        .stat-pill strong { font-family:'Syne',sans-serif; font-size:1rem; font-weight:700; }

        .wave { position:absolute; bottom:-1px; left:0; width:100%; line-height:0; z-index:3; }
        .wave svg { display:block; width:100%; height:80px; }

        /* ── MAIN ── */
        .main { max-width:1000px; margin:-3rem auto 4rem; padding:0 1.5rem; position:relative; z-index:10; }

        /* ── TOOLBAR ── */
        .toolbar {
            background:var(--surface); border-radius:16px; border:1px solid var(--border);
            padding:0.75rem 1rem; margin-bottom:1.5rem;
            box-shadow:0 4px 18px rgba(0,0,0,0.05);
            display:flex; align-items:center; gap:0.65rem; flex-wrap:wrap;
        }

        /* Tab buttons */
        .tab-btn {
            display:flex; align-items:center; gap:0.45rem;
            padding:0.45rem 1.1rem; border-radius:100px;
            border:1px solid var(--border); background:transparent;
            font-family:'DM Sans',sans-serif; font-size:0.82rem; font-weight:500;
            color:var(--muted); cursor:pointer; transition:all 0.22s var(--ease);
        }
        .tab-btn .dot {
            width:7px; height:7px; border-radius:50%; flex-shrink:0;
        }
        .tab-btn:hover { border-color:var(--violet); color:var(--violet); }
        .tab-btn.active-all    { background:var(--violet); border-color:var(--violet); color:#fff; }
        .tab-btn.active-prelims{ background:var(--coral);  border-color:var(--coral);  color:#fff; }
        .tab-btn.active-mains  { background:var(--blue);   border-color:var(--blue);   color:#fff; }

        .dot-coral  { background:var(--coral); }
        .dot-blue   { background:var(--blue);  }
        .dot-violet { background:var(--violet);}

        .spacer { flex:1; }

        /* Search */
        .search-wrap { position:relative; }
        .search-wrap svg { position:absolute; left:10px; top:50%; transform:translateY(-50%); width:15px; height:15px; color:var(--muted); pointer-events:none; }
        #search-input {
            padding:0.42rem 0.85rem 0.42rem 2rem; border-radius:100px;
            border:1px solid var(--border); background:var(--page);
            font-family:'DM Sans',sans-serif; font-size:0.82rem; color:var(--ink);
            width:190px; outline:none; transition:all 0.22s var(--ease);
        }
        #search-input:focus { border-color:var(--violet); background:#fff; }
        #result-count { font-size:0.78rem; color:var(--muted); white-space:nowrap; padding-right:0.25rem; }

        /* ── GRID ── */
        .papers-grid {
            display:grid;
            grid-template-columns:repeat(auto-fill,minmax(170px,1fr));
            gap:1rem;
        }

        /* ── CARD ── */
        .paper-card {
            background:var(--surface); border-radius:18px; border:1px solid var(--border);
            padding:1.75rem 1.25rem 1.5rem;
            display:flex; flex-direction:column; align-items:center; text-align:center;
            box-shadow:0 3px 14px rgba(0,0,0,0.04);
            transition:transform 0.25s var(--ease), box-shadow 0.25s var(--ease), border-color 0.25s var(--ease);
            opacity:0; animation:fadeUp 0.5s var(--ease) forwards;
            position:relative; overflow:hidden;
        }
        .paper-card::after {
            content:''; position:absolute; top:0; left:0; right:0; height:3px;
            opacity:0; transition:opacity 0.25s ease;
        }
        .paper-card.prelims::after { background:linear-gradient(90deg,var(--coral),#ff9f7a); }
        .paper-card.mains::after   { background:linear-gradient(90deg,var(--blue),#7bb8ff); }

        .paper-card:hover { transform:translateY(-7px); box-shadow:0 18px 40px rgba(0,0,0,0.1); }
        .paper-card.prelims:hover { border-color:rgba(255,107,74,0.25); }
        .paper-card.mains:hover   { border-color:rgba(59,130,246,0.25); }
        .paper-card:hover::after  { opacity:1; }

        /* icon circle */
        .year-ico {
            width:58px; height:58px; border-radius:16px;
            display:flex; align-items:center; justify-content:center; margin-bottom:1rem;
        }
        .year-ico svg { width:26px; height:26px; }
        .prelims .year-ico { background:var(--coral-lt); color:var(--coral); }
        .mains   .year-ico { background:var(--blue-lt);  color:var(--blue);  }

        .card-year {
            font-family:'Syne',sans-serif; font-size:1.6rem; font-weight:800;
            color:var(--ink); line-height:1; margin-bottom:0.3rem;
        }
        .card-label {
            font-size:0.7rem; font-weight:600; letter-spacing:0.1em;
            text-transform:uppercase; margin-bottom:1.25rem;
        }
        .prelims .card-label { color:var(--coral); }
        .mains   .card-label { color:var(--blue);  }

        .open-btn {
            display:inline-flex; align-items:center; gap:0.4rem;
            border-radius:100px; font-family:'DM Sans',sans-serif;
            font-size:0.78rem; font-weight:500; padding:0.45rem 1.05rem;
            transition:all 0.22s var(--ease);
        }
        .open-btn svg { width:13px; height:13px; }
        .prelims .open-btn { background:var(--coral-lt); color:var(--coral); }
        .prelims .open-btn:hover { background:var(--coral); color:#fff; }
        .mains   .open-btn { background:var(--blue-lt);  color:var(--blue);  }
        .mains   .open-btn:hover { background:var(--blue);  color:#fff; }

        /* stagger */
        .paper-card:nth-child(1)  { animation-delay:.03s }
        .paper-card:nth-child(2)  { animation-delay:.06s }
        .paper-card:nth-child(3)  { animation-delay:.09s }
        .paper-card:nth-child(4)  { animation-delay:.12s }
        .paper-card:nth-child(5)  { animation-delay:.15s }
        .paper-card:nth-child(6)  { animation-delay:.18s }
        .paper-card:nth-child(7)  { animation-delay:.21s }
        .paper-card:nth-child(8)  { animation-delay:.24s }
        .paper-card:nth-child(9)  { animation-delay:.27s }
        .paper-card:nth-child(10) { animation-delay:.30s }
        .paper-card:nth-child(n+11){ animation-delay:.33s }

        /* ── EMPTY ── */
        .empty-state {
            text-align:center; padding:4rem 2rem;
            background:var(--surface); border-radius:20px; border:1px solid var(--border);
        }
        .empty-state .big { font-size:2.8rem; margin-bottom:1rem; opacity:0.4; }
        .empty-state h3 { font-family:'Syne',sans-serif; font-size:1.1rem; margin-bottom:0.5rem; }
        .empty-state p  { font-size:0.85rem; color:var(--muted); line-height:1.6; }
        .empty-state code {
            display:inline-block; margin-top:0.75rem; background:var(--page);
            border:1px solid var(--border); border-radius:8px;
            padding:0.5rem 1.2rem; font-size:0.78rem; color:var(--violet); letter-spacing:0.02em;
        }
        #no-results { display:none; }

        @keyframes fadeUp {
            from { opacity:0; transform:translateY(20px); }
            to   { opacity:1; transform:translateY(0); }
        }

        @media(max-width:600px) {
            .papers-grid { grid-template-columns:repeat(2,1fr); }
            .toolbar { gap:0.5rem; }
            #search-input { width:100%; }
            .search-wrap { width:100%; }
            .spacer { display:none; }
        }
    </style>
</head>
<body>

<!-- ── HERO ── -->
<header class="hero">
    <a href="dashboard.php" class="back-btn" title="Back to Dashboard">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="15 18 9 12 15 6"/>
        </svg>
    </a>

    <div class="hero-inner">
        <span class="pre-badge">UPSC Question Bank</span>
        <h1>Past Papers</h1>
        <p>Prelims &amp; Mains &mdash; <?php echo $yr_min; ?> to <?php echo $yr_max; ?></p>

        <div class="stat-row">
            <div class="stat-pill"><strong><?php echo $total ?: '—'; ?></strong>&nbsp;Total PDFs</div>
            <div class="stat-pill"><strong><?php echo $total_prelims; ?></strong>&nbsp;Prelims</div>
            <div class="stat-pill"><strong><?php echo $total_mains; ?></strong>&nbsp;Mains</div>
        </div>
    </div>

    <div class="wave">
        <svg viewBox="0 0 1440 80" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
            <path d="M0,40 C180,80 360,0 540,40 C720,80 900,0 1080,40 C1260,80 1380,20 1440,40 L1440,80 L0,80 Z" fill="#f4f2f8"/>
        </svg>
    </div>
</header>

<!-- ── MAIN ── -->
<main class="main">

    <!-- Toolbar: tabs + search -->
    <div class="toolbar">
        <button class="tab-btn active-all" data-tab="all">
            <span class="dot dot-violet"></span> All
        </button>
        <button class="tab-btn" data-tab="prelims">
            <span class="dot dot-coral"></span> Prelims
        </button>
        <button class="tab-btn" data-tab="mains">
            <span class="dot dot-blue"></span> Mains
        </button>
        <div class="spacer"></div>
        <span id="result-count"><?php echo $total; ?> papers</span>
        <div class="search-wrap">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input type="text" id="search-input" placeholder="Search year e.g. 2015…">
        </div>
    </div>

    <?php
    // Merge both into one sorted array for rendering
    // We'll use data attributes for JS filtering
    $all_papers = [];
    foreach ($prelims as $year => $url) {
        $all_papers[] = ['year' => $year, 'type' => 'prelims', 'url' => $url, 'label' => 'Prelims · GS'];
    }
    foreach ($mains as $year => $url) {
        $all_papers[] = ['year' => $year, 'type' => 'mains', 'url' => $url, 'label' => 'Mains · GS'];
    }
    // Sort by year desc, then type
    usort($all_papers, function($a, $b) {
        if ($b['year'] !== $a['year']) return $b['year'] - $a['year'];
        return strcmp($a['type'], $b['type']); // mains before prelims alphabetically
    });
    ?>

    <?php if (empty($all_papers)): ?>
        <div class="empty-state">
            <div class="big">📂</div>
            <h3>No papers uploaded yet</h3>
            <p>Add your PDFs to the <strong>pyq_papers/</strong> folder using the naming below.</p>
            <code>p2024.pdf &nbsp; m2024.pdf &nbsp; p2023.pdf &nbsp; m2023.pdf</code>
        </div>

    <?php else: ?>

        <div class="papers-grid" id="papers-grid">
            <?php foreach ($all_papers as $p): ?>
            <div class="paper-card <?php echo $p['type']; ?>"
                 data-type="<?php echo $p['type']; ?>"
                 data-year="<?php echo $p['year']; ?>">

                <div class="year-ico">
                    <?php if ($p['type'] === 'prelims'): ?>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <circle cx="12" cy="12" r="4"/>
                        <line x1="12" y1="2"  x2="12" y2="6"/>
                        <line x1="12" y1="18" x2="12" y2="22"/>
                        <line x1="2"  y1="12" x2="6"  y2="12"/>
                        <line x1="18" y1="12" x2="22" y2="12"/>
                    </svg>
                    <?php else: ?>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                    </svg>
                    <?php endif; ?>
                </div>

                <div class="card-year"><?php echo $p['year']; ?></div>
                <div class="card-label"><?php echo $p['label']; ?></div>

                <a href="<?php echo htmlspecialchars($p['url']); ?>" target="_blank" class="open-btn"
                   onclick="trackView(<?php echo $p['year']; ?>, '<?php echo $p['type']; ?>')">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                        <polyline points="15 3 21 3 21 9"/>
                        <line x1="10" y1="14" x2="21" y2="3"/>
                    </svg>
                    Open PDF
                </a>
            </div>
            <?php endforeach; ?>
        </div>

        <div id="no-results" class="empty-state">
            <div class="big">🔍</div>
            <h3>No paper found</h3>
            <p>Try a different year or switch the filter tab.</p>
        </div>

    <?php endif; ?>

</main>

<script>
    const cards     = document.querySelectorAll('.paper-card');
    const countEl   = document.getElementById('result-count');
    const noResults = document.getElementById('no-results');
    const tabBtns   = document.querySelectorAll('.tab-btn');
    const searchEl  = document.getElementById('search-input');
    let activeTab   = 'all';

    function applyFilters() {
        const q = searchEl.value.trim();
        let n = 0;
        cards.forEach(function(c) {
            const typeOk = activeTab === 'all' || c.dataset.type === activeTab;
            const yearOk = q === '' || c.dataset.year.includes(q);
            const show   = typeOk && yearOk;
            c.style.display = show ? '' : 'none';
            if (show) n++;
        });
        countEl.textContent = n + (n === 1 ? ' paper' : ' papers');
        if (noResults) noResults.style.display = n === 0 ? 'block' : 'none';
    }

    // Tab click
    tabBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            activeTab = btn.dataset.tab;
            tabBtns.forEach(function(b) {
                b.className = 'tab-btn';
            });
            btn.classList.add('active-' + activeTab);
            applyFilters();
        });
    });

    // Search
    searchEl.addEventListener('input', applyFilters);
</script>

</body>
</html>