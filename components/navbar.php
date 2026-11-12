<header class="public-header">
    <nav class="public-nav wrap" aria-label="Main navigation">
        <a class="brand" href="<?= e(appUrl('index.php')) ?>"><span class="brand-mark" aria-hidden="true">◆</span><span>Examly</span></a>
        <div class="nav-links">
            <a href="<?= e(appUrl('index.php')) ?>">Home</a>
            <a href="<?= e(appUrl('about.php')) ?>">About</a>
            <a href="<?= e(appUrl('features.php')) ?>">Features</a>
            <a href="<?= e(appUrl('contact.php')) ?>">Contact</a>
        </div>
        <div class="nav-actions">
            <?php if (AuthService::check()): ?>
                <a class="button button-ghost-light" href="<?= e(appUrl(dashboardPath())) ?>">Dashboard</a>
            <?php else: ?>
                <a class="button button-ghost-light" href="<?= e(appUrl('login.php')) ?>">Login</a>
                <a class="button button-primary button-small" href="<?= e(appUrl('register.php')) ?>">Register</a>
            <?php endif; ?>
        </div>
    </nav>
</header>
