<?php
require dirname(__DIR__).'/bootstrap.php';requireRole('admin');$pageTitle='Exams';$dashboardMode=true;
if($_SERVER['REQUEST_METHOD']==='POST'){
    postOnly();$id=(int)($_POST['exam_id']??0);$status=(string)($_POST['status']??'');$exam=(new Exam())->find($id);
    if($exam&&in_array($status,['draft','scheduled','published','closed'],true)){
        if($status==='published'&&(new Database())->count('questions',['exam_id'=>$id])===0)flashAndRedirect('error','Add at least one question before publishing this exam.','admin/exams.php');
        if($status==='published'&&(float)$exam['total_marks']<=0)flashAndRedirect('error','Published exams need questions worth more than zero marks.','admin/exams.php');
        if($status==='published'&&(float)$exam['passing_marks']>(float)$exam['total_marks'])flashAndRedirect('error','Passing marks cannot exceed total marks. Ask the teacher to update this exam.','admin/exams.php');
        (new Exam())->update($id,['status'=>$status]);audit('exam_status_'.$status,'exams',$id);Session::flash('success','Examination status updated.');
    }
    redirectTo('admin/exams.php');
}
$exams=(new Exam())->adminRecent(200);require APP_ROOT.'/components/header.php';
?>
<div class="page-heading"><div><h1>Examinations</h1><p>Review scheduled and published examinations across classes.</p></div></div><section class="panel"><div class="panel-header"><h2>All examinations</h2><span class="badge badge-blue"><?= count($exams) ?> exams</span></div><div class="panel-body flush"><div class="table-wrap"><table><thead><tr><th>Examination</th><th>Class</th><th>Teacher</th><th>Schedule</th><th>Status</th><th>Update</th></tr></thead><tbody><?php foreach($exams as $exam): ?><tr><td><strong><?= e($exam['title']) ?></strong><br><span class="muted text-small"><?= (int)$exam['duration_minutes'] ?> min · <?= e(money($exam['total_marks'])) ?> marks</span></td><td><?= e($exam['class_name']) ?></td><td><?= e($exam['teacher_name']) ?></td><td><?= dateTime($exam['start_time'],'M j, Y g:i A') ?><br><span class="muted text-small">to <?= dateTime($exam['end_time'],'M j, g:i A') ?></span></td><td><span class="badge badge-<?= $exam['status']==='published'?'green':($exam['status']==='closed'?'red':($exam['status']==='draft'?'amber':'blue')) ?>"><?= e(ucfirst($exam['status'])) ?></span></td><td><form method="post" class="table-actions"><?= CSRF::field() ?><input type="hidden" name="exam_id" value="<?= (int)$exam['id'] ?>"><select name="status" aria-label="Update exam status"><?php foreach(['draft','scheduled','published','closed'] as $status): ?><option value="<?= $status ?>"<?= selected($status,$exam['status']) ?>><?= ucfirst($status) ?></option><?php endforeach; ?></select><button class="button button-outline button-small">Save</button></form></td></tr><?php endforeach; ?><?php if(!$exams): ?><tr><td colspan="6"><div class="empty-state">No examinations have been created.</div></td></tr><?php endif; ?></tbody></table></div></div></section>
<?php require APP_ROOT.'/components/footer.php'; ?>
