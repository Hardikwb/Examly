<?php if (($dashboardMode ?? false)): ?>
            </main>
        </div>
    </div>
<?php elseif(($examMode ?? false)): ?>
    </main>
<?php else: ?>
    </main>
<?php endif; ?>
<?php if (!($examMode ?? false)): ?><footer class="site-footer <?= ($dashboardMode ?? false) ? 'app-footer' : '' ?>">
    <div class="footer-inner"><span>© <?= date('Y') ?> Examly. All rights reserved.</span><span><a href="<?= e(appUrl('about.php')) ?>">About</a><a href="<?= e(appUrl('features.php')) ?>">Features</a><a href="<?= e(appUrl('contact.php')) ?>">Contact</a></span></div>
</footer><?php endif; ?>
</body>
</html>
