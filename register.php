<?php
require __DIR__ . '/bootstrap.php';
if (AuthService::check()) redirectTo(dashboardPath());
$pageTitle = 'Student registration';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    postOnly();
    $validator = (new Validator())->required($_POST, 'name', 'Name')->length($_POST, 'name', 'Name', 2, 120)->required($_POST, 'email', 'Email')->email($_POST, 'email')->required($_POST, 'password', 'Password')->length($_POST, 'password', 'Password', 10, 200)->required($_POST, 'password_confirmation', 'Confirm password');
    if (($_POST['password'] ?? '') !== ($_POST['password_confirmation'] ?? '')) $validator->add('password_confirmation', 'Passwords do not match.');
    if ((new User())->findByEmail((string)($_POST['email'] ?? ''))) $validator->add('email', 'An account with this email already exists.');
    $errors = $validator->errors();
    if (!$errors) {
        $id = (new User())->create(trim((string)$_POST['name']), trim((string)$_POST['email']), (string)$_POST['password'], 'student');
        audit('student_registered', 'users', $id);
        Session::flash('success', 'Your student account has been created. Sign in to continue.');
        redirectTo('login.php');
    }
}
require APP_ROOT . '/components/header.php';
?>
<section class="auth-wrap">
    <div class="auth-card">
        <aside class="auth-aside"><a class="brand" href="<?= e(appUrl('index.php')) ?>"><span class="brand-mark">◆</span> Examly</a>
            <p>Start your learning journey.</p><span class="aside-caption">Student accounts are open for registration.</span>
        </aside>
        <div class="auth-form"><span class="eyebrow">Create your account</span>
            <h1>Join Examly</h1>
            <p>Register as a student. Your school can enroll you in classes.</p><?php if ($errors): ?><ul class="error-list"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul><?php endif; ?><form method="post" class="form-stack"><?= CSRF::field() ?><div class="field"><label for="name">Full name</label><input id="name" name="name" required minlength="2" maxlength="120" autocomplete="name" value="<?= old('name') ?>"></div>
                <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" required maxlength="190" autocomplete="email" value="<?= old('email') ?>"></div>
                <div class="field"><label for="password">Password</label><input id="password" name="password" type="password" required minlength="10" maxlength="200" autocomplete="new-password"><span class="field-help">Use at least 10 characters.</span></div>
                <div class="field"><label for="password_confirmation">Confirm password</label><input id="password_confirmation" name="password_confirmation" type="password" required minlength="10" maxlength="200" autocomplete="new-password"></div><button class="button button-primary button-block" type="submit">Create student account</button>
            </form>
            <p class="auth-bottom" style="margin-top:18px">Already registered? <a href="<?= e(appUrl('login.php')) ?>">Sign in</a></p>
        </div>
    </div>
</section>
<?php require APP_ROOT . '/components/footer.php'; ?>