<?php
require dirname(__DIR__) . '/bootstrap.php';
requireRole('teacher');
$pageTitle = 'My Classes';
$dashboardMode = true;
$teacher = (int)AuthService::user()['id'];
$pdo = Database::connection();
$s = $pdo->prepare('SELECT c.*,s.subject_name FROM class_teachers ct JOIN classes c ON c.id=ct.class_id JOIN subjects s ON s.id=ct.subject_id WHERE ct.teacher_id=? ORDER BY c.class_name,s.subject_name');
$s->execute([$teacher]);
$classes = $s->fetchAll();
require APP_ROOT . '/components/header.php';
?>
<div class="page-heading">
    <div>
        <h1>My Classes</h1>
        <p>Students and subjects assigned to your teaching account.</p>
    </div>
</div>
<?php foreach ($classes as $class): $students = (new ClassModel())->students((int)$class['id']);
    $exams = (new Exam())->forClass((int)$class['id']);
    $exams = array_values(array_filter($exams, fn($e) => (int)$e['teacher_id'] === $teacher)); ?>
    <section class="panel">
        <div class="panel-header">
            <h2><?= e($class['class_name']) ?><?= $class['section'] ? ' · ' . e($class['section']) : '' ?> <span class="muted text-small">· <?= e($class['subject_name']) ?></span></h2><span class="badge badge-blue"><?= count($students) ?> students</span>
        </div>
        <div class="panel-body">
            <div class="content-columns equal">
                <div>
                    <h3>Students</h3><?php if ($students): ?><div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                    </tr>
                                </thead>
                                <tbody><?php foreach ($students as $student): ?><tr>
                                            <td><?= e($student['name']) ?></td>
                                            <td><?= e($student['email']) ?></td>
                                        </tr><?php endforeach; ?></tbody>
                            </table>
                        </div><?php else: ?><p class="muted text-small">No students enrolled yet.</p><?php endif; ?>
                </div>
                <div>
                    <h3>Your exams in this class</h3><?php if ($exams): ?><div class="activity-list"><?php foreach ($exams as $exam): ?><div class="exam-row"><span class="exam-row-icon">▣</span>
                                    <div class="exam-row-main"><strong><?= e($exam['title']) ?></strong><small><?= e(ucfirst($exam['status'])) ?> · <?= (int)$exam['question_count'] ?> questions</small></div>
                                </div><?php endforeach; ?></div><?php else: ?><p class="muted text-small">No exams created for this class.</p><?php endif; ?>
                </div>
            </div>
        </div>
    </section>
<?php endforeach; ?>
<?php if (!$classes): ?><section class="panel">
        <div class="empty-state"><strong>No classes are assigned to you yet</strong>Ask your administrator to assign you to a class and subject.</div>
    </section><?php endif; ?>
<?php require APP_ROOT . '/components/footer.php'; ?>