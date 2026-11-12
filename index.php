<?php
require __DIR__ . '/bootstrap.php';
$pageTitle = 'Online Examination System';
require APP_ROOT . '/components/header.php';
?>
<section class="hero">
    <div class="hero-grid wrap">
        <div class="hero-copy">
            <span class="hero-kicker">Learn · Evaluate · Grow</span>
            <h1>Online<br>Examination<br><em>System</em></h1>
            <p>Take exams, check results and manage assessments — all in one place.</p>
            <div class="button-row">
                <a class="button button-primary" href="<?= e(appUrl(AuthService::check() ? dashboardPath() : 'register.php')) ?>">Get Started</a>
                <a class="button button-ghost-light" href="<?= e(appUrl('about.php')) ?>">Learn More</a>
            </div>
        </div>
        <div class="hero-visual" aria-label="Online examination preview">
            <div class="hero-orbit"></div>
            <div class="hero-monitor">
                <div class="monitor-screen">
                    <div class="monitor-head"><span class="monitor-brand"><span class="brand-mark">◆</span> Examly</span><span class="monitor-pill">IN PROGRESS</span></div>
                    <div class="monitor-question">What is the value of 2 + 2 × 2?</div>
                    <div class="monitor-answer"><span class="monitor-radio"></span>4</div>
                    <div class="monitor-answer selected"><span class="monitor-radio"></span>6</div>
                    <div class="monitor-answer"><span class="monitor-radio"></span>8</div>
                    <div class="monitor-footer"><span>Question 5 of 20</span><span>Time remaining&nbsp; 45:32</span></div>
                </div>
            </div>
            <div class="floating-score"><strong>92%</strong><span>Performance score</span></div>
            <div class="floating-card"><span class="float-icon">✓</span><span>Assessment completed<br><span class="muted">Your result is ready</span></span></div>
        </div>
    </div>
</section>
<section class="feature-strip"><div class="feature-strip-grid wrap">
    <div class="feature-mini"><span class="feature-mini-icon">ϟ</span><div>Secure &amp; reliable<span>Exam platform</span></div></div>
    <div class="feature-mini"><span class="feature-mini-icon">✓</span><div>Auto evaluation<span>Instant results</span></div></div>
    <div class="feature-mini"><span class="feature-mini-icon">▥</span><div>Track performance<span>Reports and insights</span></div></div>
    <div class="feature-mini"><span class="feature-mini-icon">▣</span><div>Access anywhere<span>Any device</span></div></div>
</div></section>
<section class="section"><div class="wrap">
    <div class="section-heading"><span class="eyebrow">Everything in one place</span><h2>Assessment that works for everyone</h2><p>Examly brings teachers, students and administrators together with a clear, secure examination workflow.</p></div>
    <div class="feature-cards">
        <article class="feature-card"><span class="feature-mini-icon">▣</span><h3>Fair online exams</h3><p>Schedule assessments for enrolled classes with controlled availability and attempt limits.</p></article>
        <article class="feature-card"><span class="feature-mini-icon">◉</span><h3>Clear results</h3><p>Automatically evaluate objective questions and publish scores when you are ready.</p></article>
        <article class="feature-card"><span class="feature-mini-icon">▥</span><h3>Useful reporting</h3><p>Review participation, scores and class performance from simple, readable reports.</p></article>
    </div>
</div></section>
<section class="section section-muted"><div class="wrap"><div class="cta-band"><div><h2>Make every assessment count.</h2><p>Create a secure space for teaching, learning and progress.</p></div><a class="button button-primary" href="<?= e(appUrl(AuthService::check() ? dashboardPath() : 'register.php')) ?>">Start with Examly →</a></div></div></section>
<?php require APP_ROOT . '/components/footer.php'; ?>
