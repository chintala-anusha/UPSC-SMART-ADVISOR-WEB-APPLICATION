<?php
/* ── Auth + DB ── */
include 'includes/auth.php';   // guards page, sets $uid $user_name $user_email
include 'includes/db.php';     // provides $conn

/* ── Live dashboard stats from DB ── */
$mock_count  = 0;
$coach_count = 0;
$streak      = 0;

$r = $conn->prepare('SELECT COUNT(*) FROM progress WHERE user_id=?');
$r->bind_param('i',$uid); $r->execute();
$r->bind_result($mock_count); $r->fetch(); $r->close();

$r2 = $conn->prepare('SELECT COUNT(*) FROM coach_sessions WHERE user_id=?');
$r2->bind_param('i',$uid); $r2->execute();
$r2->bind_result($coach_count); $r2->fetch(); $r2->close();

// Day streak from coach_sessions (prepared statement)
$r3 = $conn->prepare('SELECT DATE(created_at) d FROM coach_sessions WHERE user_id=? GROUP BY d ORDER BY d DESC LIMIT 30');
$r3->bind_param('i', $uid); $r3->execute();
$r3_res = $r3->get_result();
if ($r3_res) {
    $check = date('Y-m-d');
    while ($row = $r3_res->fetch_assoc()) {
        if ($row['d'] === $check) { $streak++; $check = date('Y-m-d', strtotime($check.' -1 day')); }
        else break;
    }
}
$r3->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UPSC Command Center</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;1,9..40,400&display=swap" rel="stylesheet">

    <style>
        /* =============================================
           ROOT VARIABLES
        ============================================= */
        :root {
            --grad-start:   #1a0533;
            --grad-mid:     #3d0f6b;
            --grad-end:     #6b1fa8;
            --coral:        #ff6b4a;
            --blue:         #3b82f6;
            --teal:         #0fb98a;
            --violet:       #7c3aed;
            --danger:       #ef4444;
            --ink:          #1a1523;
            --muted:        #6b6579;
            --surface:      #ffffff;
            --page:         #f4f2f8;
            --border:       rgba(0, 0, 0, 0.07);
            --transition:   all 0.28s cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        /* =============================================
           RESET & BASE
        ============================================= */
        *, *::before, *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-font-smoothing: antialiased;
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'DM Sans', sans-serif;
            background-color: var(--page);
            color: var(--ink);
            min-height: 100vh;
            overflow-x: hidden;
        }

        a { text-decoration: none; color: inherit; }

        /* =============================================
           USER CONTROLS (top-left)
        ============================================= */
        .user-controls {
            position: absolute;
            top: 1.75rem;
            left: 1.75rem;
            z-index: 50;
            display: flex;
            gap: 0.65rem;
        }

        .ctrl-btn {
            width: 46px;
            height: 46px;
            border-radius: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.25rem;
            background: rgba(255, 255, 255, 0.13);
            border: 1px solid rgba(255, 255, 255, 0.22);
            backdrop-filter: blur(12px);
            transition: var(--transition);
        }

        .ctrl-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.45);
            transform: translateY(-3px);
        }

        .ctrl-btn.logout:hover {
            background: var(--danger);
            border-color: transparent;
        }

        /* =============================================
           HERO HEADER
        ============================================= */
        .hero {
            background: linear-gradient(135deg, var(--grad-start) 0%, var(--grad-mid) 50%, var(--grad-end) 100%);
            position: relative;
            padding: 5.5rem 2rem 7.5rem;
            text-align: center;
            color: #fff;
            overflow: hidden;
        }

        /* subtle radial glow behind text */
        .hero::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -60%);
            width: 600px;
            height: 400px;
            background: radial-gradient(ellipse, rgba(160, 80, 255, 0.35) 0%, transparent 70%);
            pointer-events: none;
        }

        .hero-inner {
            position: relative;
            z-index: 2;
            max-width: 700px;
            margin: 0 auto;
        }

        .pre-badge {
            display: inline-block;
            font-family: 'Syne', sans-serif;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.22);
            border-radius: 100px;
            padding: 0.35rem 1.1rem;
            margin-bottom: 1.25rem;
        }

        .hero h1 {
            font-family: 'Syne', sans-serif;
            font-size: clamp(2.2rem, 5.5vw, 3.6rem);
            font-weight: 800;
            line-height: 1.12;
            letter-spacing: -0.02em;
            margin-bottom: 0.85rem;
        }

        .hero p {
            font-size: 1rem;
            font-weight: 300;
            line-height: 1.65;
            opacity: 0.82;
            max-width: 480px;
            margin: 0 auto;
        }

        /* wave */
        .wave {
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            line-height: 0;
            z-index: 3;
        }

        .wave svg {
            display: block;
            width: 100%;
            height: 88px;
        }

        /* =============================================
           MAIN GRID AREA
        ============================================= */
        .main {
            max-width: 1120px;
            margin: -3.75rem auto 4rem;
            padding: 0 1.5rem;
            position: relative;
            z-index: 10;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.25rem;
        }

        /* =============================================
           CARDS
        ============================================= */
        .card {
            background: var(--surface);
            border-radius: 20px;
            border: 1px solid var(--border);
            padding: 2rem 1.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            transition: var(--transition);
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
            /* animation */
            opacity: 0;
            animation: fadeUp 0.55s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
        }

        /* top-edge colour accent */
        .card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            opacity: 0;
            transition: opacity 0.28s ease;
            border-radius: 20px 20px 0 0;
        }

        .card:hover { transform: translateY(-9px); box-shadow: 0 20px 44px rgba(109, 40, 217, 0.13); }
        .card:hover::before { opacity: 1; }

        /* accent colours per variant */
        .card.accent-coral::before { background: var(--coral); }
        .card.accent-blue::before  { background: var(--blue);  }
        .card.accent-teal::before  { background: var(--teal);  }
        .card.accent-violet::before{ background: var(--violet);}

        /* layout modifiers */
        .card-large { grid-column: span 2; grid-row: span 2; justify-content: center; padding: 2.75rem 2.25rem; }
        .card-tall  { grid-row: span 2;   justify-content: center; }
        .card-wide  { grid-column: span 2; }

        /* stagger animation delays */
        .card:nth-child(1) { animation-delay: 0.06s; }
        .card:nth-child(2) { animation-delay: 0.12s; }
        .card:nth-child(3) { animation-delay: 0.18s; }
        .card:nth-child(4) { animation-delay: 0.24s; }
        .card:nth-child(5) { animation-delay: 0.30s; }
        .card:nth-child(6) { animation-delay: 0.36s; }
        .card:nth-child(7) { animation-delay: 0.42s; }
        .card:nth-child(8) { animation-delay: 0.48s; }

        /* =============================================
           ICON
        ============================================= */
        .ico {
            width: 64px;
            height: 64px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.25rem;
            flex-shrink: 0;
        }

        .ico svg {
            width: 26px;
            height: 26px;
            stroke-width: 1.75;
        }

        .ico-coral  { background: #fff0ed; color: var(--coral); }
        .ico-blue   { background: #eff5ff; color: var(--blue);  }
        .ico-teal   { background: #e6faf4; color: var(--teal);  }
        .ico-violet { background: #f0eaff; color: var(--violet);}

        .card-large .ico {
            width: 76px;
            height: 76px;
            border-radius: 22px;
            margin-bottom: 1.5rem;
        }

        .card-large .ico svg { width: 32px; height: 32px; }

        /* =============================================
           CARD TEXT
        ============================================= */
        .card h3 {
            font-family: 'Syne', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            color: var(--ink);
            margin-bottom: 0.5rem;
            letter-spacing: -0.01em;
        }

        .card p {
            font-size: 0.82rem;
            color: var(--muted);
            line-height: 1.58;
        }

        .card-large h3 { font-size: 1.3rem; margin-bottom: 0.65rem; }
        .card-large p  { font-size: 0.9rem; max-width: 230px; }

        /* =============================================
           KEYFRAMES
        ============================================= */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0);    }
        }

        /* =============================================
           RESPONSIVE
        ============================================= */
        @media (max-width: 960px) {
            .grid { grid-template-columns: repeat(2, 1fr); }
            .card-large, .card-wide { grid-column: span 2; }
            .card-tall { grid-row: span 1; }
        }

        @media (max-width: 560px) {
            .grid { grid-template-columns: 1fr; }
            .card-large, .card-wide, .card-tall {
                grid-column: span 1;
                grid-row: span 1;
            }
            .hero { padding: 4.5rem 1.25rem 6.5rem; }
            .main { margin-top: -3rem; padding: 0 1rem; }
        }
    </style>
</head>
<body>

    <!-- ==================== HEADER ==================== -->
    <header class="hero">

        <div class="user-controls">
            <a href="profile.php" class="ctrl-btn" title="Profile">
                <!-- person icon -->
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </a>
            <a href="logout.php" class="ctrl-btn logout" title="Logout">
                <!-- logout icon -->
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
            </a>
        </div>

        <div class="hero-inner">
            <span class="pre-badge">Central Command Dashboard</span>
            <h1>Welcome, <?php echo htmlspecialchars($user_name); ?></h1>
            <p>Your strategic roadmap to the Civil Services</p>
            <!-- Live stats row -->
            <div style="display:flex;justify-content:center;gap:.85rem;margin-top:1.5rem;flex-wrap:wrap;position:relative;z-index:2;">
                <div style="background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);border-radius:100px;padding:.4rem 1.1rem;font-size:.78rem;font-weight:500;display:flex;align-items:center;gap:.4rem;">
                    <strong style="font-family:'Syne',sans-serif;font-size:.95rem;"><?php echo $mock_count; ?></strong> Tests Taken
                </div>
                <div style="background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);border-radius:100px;padding:.4rem 1.1rem;font-size:.78rem;font-weight:500;display:flex;align-items:center;gap:.4rem;">
                    <strong style="font-family:'Syne',sans-serif;font-size:.95rem;"><?php echo $coach_count; ?></strong> Coach Sessions
                </div>
                <div style="background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);border-radius:100px;padding:.4rem 1.1rem;font-size:.78rem;font-weight:500;display:flex;align-items:center;gap:.4rem;">
                    <strong style="font-family:'Syne',sans-serif;font-size:.95rem;"><?php echo $streak; ?>🔥</strong> Day Streak
                </div>
            </div>
        </div>

        <div class="wave">
            <svg viewBox="0 0 1440 88" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
                <path d="M0,44 C180,88 360,0 540,44 C720,88 900,0 1080,44 C1260,88 1380,22 1440,44 L1440,88 L0,88 Z" fill="#f4f2f8"/>
            </svg>
        </div>
    </header>

    <!-- ==================== DASHBOARD GRID ==================== -->
    <main class="main">
        <div class="grid">

            <!-- 1. AI Coach — large (2×2) -->
            <a href="ai_coach.php" class="card card-large accent-coral">
                <div class="ico ico-coral">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <circle cx="12" cy="12" r="4"/>
                        <line x1="12" y1="2"  x2="12" y2="6"/>
                        <line x1="12" y1="18" x2="12" y2="22"/>
                        <line x1="2"  y1="12" x2="6"  y2="12"/>
                        <line x1="18" y1="12" x2="22" y2="12"/>
                    </svg>
                </div>
                <h3>AI Coach</h3>
                <p>Full guidance for Prelims, Mains &amp; Interview</p>
            </a>

            <!-- 2. Chat -->
            <a href="ai_chat.php" class="card accent-blue">
                <div class="ico ico-blue">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                </div>
                <h3>Chat</h3>
                <p>Ask anything, get smart answers fast</p>
            </a>

            <!-- 3. Schedule -->
            <a href="timetable.php" class="card accent-teal" id="schedule-card">
                <div class="ico ico-teal">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8"  y1="2" x2="8"  y2="6"/>
                        <line x1="3"  y1="10" x2="21" y2="10"/>
                        <polyline points="9 16 11 18 15 14"/>
                    </svg>
                </div>
                <h3>Schedule</h3>
                <p>Daily study plan tailored to your goals</p>
            </a>

            <!-- 4. Roadmap — tall (1×2) -->
            <a href="study_guide.php" class="card card-tall accent-violet">
                <div class="ico ico-violet">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="3 11 22 2 13 21 11 13 3 11"/>
                    </svg>
                </div>
                <h3>Roadmap</h3>
                <p>Smart path through the full UPSC syllabus</p>
            </a>

            <!-- 5. Library -->
            <a href="books.php" class="card accent-coral">
                <div class="ico ico-coral">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                    </svg>
                </div>
                <h3>Library</h3>
                <p>NCERTs and core study materials</p>
            </a>

            <!-- 6. Mock Tests — wide (2×1) -->
            <a href="mocktest_ai.php" class="card card-wide accent-blue">
                <div class="ico ico-blue">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                        <polyline points="10 9 9 9 8 9"/>
                    </svg>
                </div>
                <h3>Mock Tests</h3>
                <p>AI quizzes that adapt to your weak areas</p>
            </a>

            <!-- 7. Past Papers -->
            <a href="previous_papers.php" class="card accent-violet">
                <div class="ico ico-violet">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                </div>
                <h3>Past Papers</h3>
                <p>25 years of UPSC question analysis</p>
            </a>

            <!-- 8. Analytics -->
            <a href="progress.php" class="card accent-teal">
                <div class="ico ico-teal">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                    </svg>
                </div>
                <h3>Analytics</h3>
                <p>Track performance with live metrics</p>
            </a>

        </div>
    </main>

    <script>
        // Log navigation for debugging
        document.getElementById('schedule-card').addEventListener('click', function () {
            console.log("Navigating directly to Timetable...");
        });
    </script>

</body>
</html>