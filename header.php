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