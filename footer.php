<!-- ==================== FOOTER ==================== -->
    <footer style="
        margin-top: 4rem;
        padding: 2.5rem 2rem;
        text-align: center;
        background: linear-gradient(135deg, var(--grad-start) 0%, var(--grad-mid) 50%, var(--grad-end) 100%);
        color: rgba(255,255,255,0.75);
        font-size: 0.82rem;
        font-family: 'DM Sans', sans-serif;
    ">
        <div style="max-width:900px;margin:0 auto;">

            <!-- Nav links -->
            <nav style="display:flex;flex-wrap:wrap;justify-content:center;gap:0.5rem 1.5rem;margin-bottom:1.25rem;">
                <a href="dashboard.php"       style="color:rgba(255,255,255,.85);transition:color .2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,.85)'">Dashboard</a>
                <a href="timetable.php"       style="color:rgba(255,255,255,.85);transition:color .2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,.85)'">Timetable</a>
                <a href="study_guide.php"     style="color:rgba(255,255,255,.85);transition:color .2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,.85)'">AI Guide</a>
                <a href="books.php"           style="color:rgba(255,255,255,.85);transition:color .2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,.85)'">Library</a>
                <a href="mocktest_ai.php"     style="color:rgba(255,255,255,.85);transition:color .2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,.85)'">Mock Tests</a>
                <a href="previous_papers.php" style="color:rgba(255,255,255,.85);transition:color .2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,.85)'">Past Papers</a>
                <a href="progress.php"        style="color:rgba(255,255,255,.85);transition:color .2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,.85)'">Analytics</a>
                <a href="logout.php"          style="color:rgba(255,255,255,.85);transition:color .2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,.85)'">Logout</a>
            </nav>

            <!-- Divider -->
            <div style="border-top:1px solid rgba(255,255,255,0.15);margin:0 auto 1.25rem;max-width:400px;"></div>

            <!-- Brand + copyright -->
            <p style="font-family:'Syne',sans-serif;font-size:0.95rem;font-weight:700;color:#fff;margin-bottom:0.35rem;">
                UPSC AI Guide
            </p>
            <p style="margin:0;">
                &copy; <?php echo date('Y'); ?> UPSC AI Guide &mdash; All rights reserved.
            </p>
        </div>
    </footer>