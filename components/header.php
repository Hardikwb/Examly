<?php
$pageTitle = $pageTitle ?? APP_NAME;
$dashboardMode = $dashboardMode ?? false;
$examMode = $examMode ?? false;
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Examly helps schools create secure online examinations and share results.">
    <title><?= e($pageTitle) ?> · <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= e(appUrl('public/css/style.css')) ?>">
    <link rel="stylesheet" href="<?= e(appUrl('public/css/dashboard.css')) ?>">
    <link rel="stylesheet" href="<?= e(appUrl('public/css/exam.css')) ?>">
</head>
<body class="<?= $dashboardMode ? 'dashboard-body' : 'public-body' ?>">
<?php if ($dashboardMode): ?>
    <div class="app-shell">
        <?php require APP_ROOT . '/components/sidebar.php'; ?>
        <div class="app-main">
            <?php require APP_ROOT . '/components/topbar.php'; ?>
            <main class="page-content">
<?php elseif ($examMode): ?>
    <main class="exam-main">
<?php else: ?>
    <?php require APP_ROOT . '/components/navbar.php'; ?>
    <main class="public-main">
<?php endif; ?>
<?php require APP_ROOT . '/components/flash_message.php'; ?>
