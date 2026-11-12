<?php
$navRole = AuthService::user()['role'] ?? 'student';
$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
$sidebars = [
    'admin' => [
        ['Dashboard','index.php','◫'], ['Students','users.php?role=student','♙'], ['Teachers','users.php?role=teacher','♟'], ['Classes','classes.php','▦'], ['Subjects','subjects.php','▤'], ['Exams','exams.php','▣'], ['Results','results.php','◉'], ['Reports','reports.php','▥'], ['Settings','settings.php','⚙'],
    ],
        'teacher' => [
        ['Dashboard','index.php','◫'], ['Create Exam','exam-form.php','＋'], ['Manage Exams','exams.php','▣'], ['Manage Questions','exams.php?manage=questions','☷'], ['My Classes','classes.php','▦'], ['View Results','results.php','◉'], ['Profile','profile.php','♙'],
    ],
    'student' => [
        ['Dashboard','index.php','◫'], ['Available Exams','exams.php','▣'], ['My Results','results.php','◉'], ['Exam History','history.php','◷'], ['Profile','profile.php','♙'],
    ],
];
?>
<aside class="sidebar">
    <a class="brand sidebar-brand" href="<?= e(appUrl(dashboardPath())) ?>" aria-label="Examly dashboard"><span class="brand-mark" aria-hidden="true">◆</span><span>Examly</span></a>
    <div class="sidebar-role"><?= e(ucfirst($navRole)) ?> workspace</div>
    <nav class="side-nav" aria-label="<?= e(ucfirst($navRole)) ?> navigation">
        <?php foreach ($sidebars[$navRole] as [$label,$href,$icon]): $target = basename(explode('?', $href)[0]); $active = $currentScript === $target; if($navRole==='admin'&&$target==='users.php')$active=$currentScript===$target&&($_GET['role']??'student')===($label==='Teachers'?'teacher':'student'); if($navRole==='teacher'&&$target==='exams.php')$active=$currentScript===$target&&(($label==='Manage Questions')===isset($_GET['manage'])); ?>
            <a class="side-link<?= $active ? ' active' : '' ?>" href="<?= e(appUrl($navRole . '/' . $href)) ?>" aria-label="<?= e($label) ?>"><span class="side-icon" aria-hidden="true"><?= $icon ?></span><span><?= e($label) ?></span></a>
        <?php endforeach; ?>
        <form class="side-logout" method="post" action="<?= e(appUrl('logout.php')) ?>"><?= CSRF::field() ?><button class="side-link" type="submit" aria-label="Logout"><span class="side-icon" aria-hidden="true">↪</span><span>Logout</span></button></form>
    </nav>
    <div class="sidebar-bottom"><span class="sidebar-dot"></span> Secure learning space</div>
</aside>
