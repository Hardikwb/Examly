<?php
require __DIR__ . '/bootstrap.php';
requireLogin();
$pageTitle = 'Profile';
$dashboardMode = true;
$current = AuthService::user();
$errors = [];
$return = dashboardPath();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    postOnly();
    $action = (string)($_POST['action'] ?? 'profile');
    if ($action === 'profile') {
        $v = (new Validator())->required($_POST, 'name', 'Name')->length($_POST, 'name', 'Name', 2, 120)->required($_POST, 'email', 'Email')->email($_POST, 'email');
        $other = (new User())->findByEmail((string)($_POST['email'] ?? ''));
        if ($other && (int)$other['id'] !== (int)$current['id']) $v->add('email', 'This email is already in use.');
        $errors = $v->errors();
        if (!$errors) {
            (new User())->updateProfile((int)$current['id'], trim((string)$_POST['name']), trim((string)$_POST['email']));
            Session::set('auth_user', array_merge($current, ['name' => trim((string)$_POST['name']), 'email' => trim((string)$_POST['email'])]));
            audit('profile_updated', 'users', (int)$current['id']);
            Session::flash('success', 'Profile saved.');
            redirectTo($return);
        }
    } elseif ($action === 'password') {
        $v = (new Validator())->required($_POST, 'current_password', 'Current password')->required($_POST, 'new_password', 'New password')->length($_POST, 'new_password', 'New password', 10, 200)->required($_POST, 'password_confirmation', 'Confirm password');
        $user = (new User())->findByEmail($current['email']);
        if (!$user || !password_verify((string)$_POST['current_password'], $user['password_hash'])) $v->add('current_password', 'Current password is incorrect.');
        if (($_POST['new_password'] ?? '') !== ($_POST['password_confirmation'] ?? '')) $v->add('password_confirmation', 'Passwords do not match.');
        $errors = $v->errors();
        if (!$errors) {
            (new User())->updatePassword((int)$current['id'], (string)$_POST['new_password']);
            audit('password_changed', 'users', (int)$current['id']);
            Session::flash('success', 'Password updated.');
            redirectTo($return);
        }
    }
}
$current = (new User())->findById((int)$current['id']);
require APP_ROOT . '/components/header.php';
?>
<div class="page-heading">
    <div>
        <h1>Profile</h1>
        <p>Update your account information and password.</p>
    </div>
</div><?php if ($errors): ?><ul class="error-list"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul><?php endif; ?><div class="content-columns equal">
    <section class="panel">
        <div class="panel-header">
            <h2>Personal information</h2>
        </div>
        <div class="panel-body">
            <form method="post" class="form-stack"><?= CSRF::field() ?><input type="hidden" name="action" value="profile">
                <div class="field"><label for="name">Name</label><input id="name" name="name" required minlength="2" maxlength="120" value="<?= e($current['name']) ?>"></div>
                <div class="field"><label for="email">Email</label><input id="email" type="email" name="email" required maxlength="190" value="<?= e($current['email']) ?>"></div>
                <div class="field"><label>Account role</label><input value="<?= e(ucfirst($current['role'])) ?>" disabled></div><button class="button button-primary">Save profile</button>
            </form>
        </div>
    </section>
    <section class="panel">
        <div class="panel-header">
            <h2>Change password</h2>
        </div>
        <div class="panel-body">
            <form method="post" class="form-stack"><?= CSRF::field() ?><input type="hidden" name="action" value="password">
                <div class="field"><label for="current_password">Current password</label><input id="current_password" name="current_password" type="password" required autocomplete="current-password"></div>
                <div class="field"><label for="new_password">New password</label><input id="new_password" name="new_password" type="password" required minlength="10" maxlength="200" autocomplete="new-password"></div>
                <div class="field"><label for="password_confirmation">Confirm new password</label><input id="password_confirmation" name="password_confirmation" type="password" required minlength="10" maxlength="200" autocomplete="new-password"></div><button class="button button-primary">Update password</button>
            </form>
        </div>
    </section>
</div>
<?php require APP_ROOT . '/components/footer.php'; ?>