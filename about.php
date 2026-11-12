<?php
require __DIR__ . '/bootstrap.php';
$pageTitle = 'About';
require APP_ROOT . '/components/header.php';
?>
<section class="page-hero">
    <div class="wrap"><span class="hero-kicker">About Examly</span>
        <h1>A clearer way to<br>assess learning.</h1>
        <p>Examly is a modern and secure online examination system built for educational institutions.</p>
    </div>
</section>
<section class="section">
    <div class="wrap content-grid">
        <div><span class="eyebrow">Our purpose</span>
            <h2>Help educators focus on what students learn</h2>
            <p class="muted">Our platform helps educators conduct exams efficiently and allows students to take tests anytime, anywhere. From class enrollment through exam creation, submission and results, each step stays in one accessible place.</p>
            <p class="muted">Examly is designed around clarity, dependable workflows and responsible handling of student information.</p><a class="button button-primary" href="<?= e(appUrl('features.php')) ?>">Explore features</a>
        </div>
        <div class="illustration-panel" aria-label="Books and learning illustration">
            <div class="book-illustration"><span></span><span></span><span></span></div>
        </div>
    </div>
</section>
<section class="section section-muted">
    <div class="wrap">
        <div class="section-heading"><span class="eyebrow">Built for your school</span>
            <h2>One connected learning workflow</h2>
            <p>Administrators organize people and classes, teachers manage assessments, and students see their exams and results in one focused space.</p>
        </div>
    </div>
</section>
<?php require APP_ROOT . '/components/footer.php'; ?>