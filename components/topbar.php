<?php $currentUser = AuthService::user(); ?>
<header class="topbar">
    <div class="topbar-context"><span class="eyebrow"><?= e(ucfirst($currentUser['role'] ?? '')) ?> workspace</span><strong><?= e($pageTitle ?? 'Dashboard') ?></strong></div>
    <div class="topbar-user"><span class="avatar"><?= e(userInitials($currentUser['name'] ?? 'Examly')) ?></span><span><strong><?= e($currentUser['name'] ?? '') ?></strong><small><?= e($currentUser['email'] ?? '') ?></small></span></div>
</header>
