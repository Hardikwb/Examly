<?php
require __DIR__ . '/bootstrap.php';
$pageTitle = 'Features';
require APP_ROOT . '/components/header.php';
?>
<section class="page-hero">
    <div class="wrap"><span class="hero-kicker">Examly features</span>
        <h1>Everything you need<br>to run an examination.</h1>
        <p>Purposeful tools for school administrators, teachers and students.</p>
    </div>
</section>
<section class="section">
    <div class="wrap feature-cards">
        <article class="feature-card"><span class="feature-mini-icon">♙</span>
            <h3>Role-based workspaces</h3>
            <p>Separate dashboards and permissions for administrators, teachers and students.</p>
        </article>
        <article class="feature-card"><span class="feature-mini-icon">▣</span>
            <h3>Exam scheduling</h3>
            <p>Set exam windows, duration, marks, attempt limits and class enrollment.</p>
        </article>
        <article class="feature-card"><span class="feature-mini-icon">☷</span>
            <h3>Flexible questions</h3>
            <p>Create single choice, multiple choice, true/false and descriptive questions.</p>
        </article>
        <article class="feature-card"><span class="feature-mini-icon">◷</span>
            <h3>Server-timed attempts</h3>
            <p>Responses are saved as students navigate. Expired attempts are finalized on the next request.</p>
        </article>
        <article class="feature-card"><span class="feature-mini-icon">✓</span>
            <h3>Automatic evaluation</h3>
            <p>Objective questions are evaluated on submission, with optional negative marking.</p>
        </article>
        <article class="feature-card"><span class="feature-mini-icon">▥</span>
            <h3>Class reporting</h3>
            <p>See enrollment, exam participation and published performance in simple tables.</p>
        </article>
    </div>
</section>
<section class="section section-muted">
    <div class="wrap">
        <div class="cta-band">
            <div>
                <h2>Ready to get started?</h2>
                <p>Create a student account or sign in to your workspace.</p>
            </div><a class="button button-primary" href="<?= e(appUrl('login.php')) ?>">Sign in</a>
        </div>
    </div>
</section>
<?php require APP_ROOT . '/components/footer.php'; ?>