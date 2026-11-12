<?php
require __DIR__ . '/bootstrap.php';
if (AuthService::check()) redirectTo(dashboardPath());
$pageTitle = 'Login';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    postOnly();
    $validator = (new Validator())->required($_POST, 'email', 'Email')->email($_POST, 'email')->required($_POST, 'password', 'Password')->required($_POST, 'role', 'Role');
    if (!$validator->passes()) $error = implode(' ', $validator->errors());
    else {
        $result = AuthService::login((string)$_POST['email'], (string)$_POST['password'], (string)$_POST['role'], !empty($_POST['remember']));
        if ($result['ok']) {
            Session::flash('success', $result['message']);
            redirectTo(dashboardPath((string)$_POST['role']));
        }
        $error = $result['message'];
    }
}
require APP_ROOT . '/components/header.php';
?>
<section class="auth-wrap">
    <div class="auth-card">
        <aside class="auth-aside"><a class="brand" href="<?= e(appUrl('index.php')) ?>"><span class="brand-mark">◆</span> Examly</a>
            <p>Learn · Evaluate · Grow</p><span class="aside-caption">A secure space for every assessment.</span>
        </aside>
        <div class="auth-form"><span class="eyebrow">Welcome back</span>
            <h1>Sign in to Examly</h1>
            <p>Choose your account type to continue.</p><?php if ($error): ?><div class="flash flash-error" role="alert"><?= e($error) ?></div><?php endif; ?><form method="post" class="form-stack" autocomplete="on"><?= CSRF::field() ?><div class="role-options" role="group" aria-label="Account role"><label class="role-option"><input type="radio" name="role" value="student" required<?= checked(($_POST['role'] ?? 'student') === 'student') ?>><span>Student</span></label><label class="role-option"><input type="radio" name="role" value="teacher" required<?= checked(($_POST['role'] ?? '') === 'teacher') ?>><span>Teacher</span></label><label class="role-option"><input type="radio" name="role" value="admin" required<?= checked(($_POST['role'] ?? '') === 'admin') ?>><span>Admin</span></label></div>
                <div class="field">
                    <label for="email">Email address</label>
                    <input id="email" type="email" name="email" required maxlength="190" autocomplete="username" placeholder="you@school.edu" value="<?= old('email') ?>"></div>
                <div class="field"><label for="password">Password</label><input id="password" type="password" name="password" required autocomplete="current-password" placeholder="Enter your password"></div>
                <div class="auth-links"><label class="remember-label"><input type="checkbox" name="remember" value="1" <?= checked(!empty($_POST['remember'])) ?>> Remember me</label><a href="<?= e(appUrl('forgot-password.php')) ?>">Forgot password?</a></div><button class="button button-primary button-block" type="submit">Login</button>
            </form>
            <div class="auth-divider">OR</div>
            <p class="auth-bottom">Don’t have a student account? <a href="<?= e(appUrl('register.php')) ?>">Register here</a></p>
            <p class="text-small muted" style="margin:14px 0 0;text-align:center">Local demo: admin@examly.local · teacher@examly.local · student@examly.local<br>Password: Admin123! / Teacher123! / Student123!</p>
        </div>
    </div>
</section>
<?php require APP_ROOT . '/components/footer.php'; ?>