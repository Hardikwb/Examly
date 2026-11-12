<?php
require __DIR__ . '/bootstrap.php';
$pageTitle = 'Reset password';
$token = (string)($_GET['token'] ?? $_POST['token'] ?? '');
$errors = [];
$valid = false;
if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    $s = Database::connection()->prepare('SELECT user_id FROM password_resets WHERE token_hash=? AND expires_at>NOW()');
    $s->execute([hash('sha256', $token)]);
    $valid = (bool)$s->fetchColumn();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    postOnly();
    $validator = (new Validator())->required($_POST, 'password', 'Password')->length($_POST, 'password', 'Password', 10, 200)->required($_POST, 'password_confirmation', 'Confirm password');
    if (($_POST['password'] ?? '') !== ($_POST['password_confirmation'] ?? '')) $validator->add('password_confirmation', 'Passwords do not match.');
    if (!$valid) $validator->add('token', 'This reset link is invalid or expired.');
    $errors = $validator->errors();
    if (!$errors) {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $s = $pdo->prepare('SELECT user_id FROM password_resets WHERE token_hash=? AND expires_at>NOW() FOR UPDATE');
            $s->execute([hash('sha256', $token)]);
            $userId = (int)$s->fetchColumn();
            if (!$userId) throw new RuntimeException('Reset link is invalid or expired.');
            $pdo->prepare('UPDATE users SET password_hash=?,remember_token_hash=NULL WHERE id=?')->execute([password_hash((string)$_POST['password'], PASSWORD_DEFAULT), $userId]);
            $pdo->prepare('DELETE FROM password_resets WHERE user_id=?')->execute([$userId]);
            $pdo->commit();
            Session::flash('success', 'Your password has been changed. Sign in with your new password.');
            redirectTo('login.php');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = 'This reset link is invalid or expired.';
        }
    }
}
require APP_ROOT . '/components/header.php';
?>
<section class="auth-wrap">
    <div class="auth-card">
        <aside class="auth-aside"><a class="brand" href="<?= e(appUrl('index.php')) ?>"><span class="brand-mark">◆</span> Examly</a>
            <p>Choose a new password.</p><span class="aside-caption">Your new password protects your Examly account.</span>
        </aside>
        <div class="auth-form"><span class="eyebrow">Account recovery</span>
            <h1>Set a new password</h1><?php if (!$valid): ?><div class="flash flash-error">This reset link is invalid or expired.</div><a class="button button-primary" href="<?= e(appUrl('forgot-password.php')) ?>">Request a new link</a><?php else: ?><?php if ($errors): ?><ul class="error-list"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul><?php endif; ?><form method="post" class="form-stack"><?= CSRF::field() ?><input type="hidden" name="token" value="<?= e($token) ?>">
                <div class="field"><label for="password">New password</label><input id="password" name="password" type="password" required minlength="10" maxlength="200" autocomplete="new-password"></div>
                <div class="field"><label for="password_confirmation">Confirm password</label><input id="password_confirmation" name="password_confirmation" type="password" required minlength="10" maxlength="200" autocomplete="new-password"></div><button class="button button-primary button-block" type="submit">Change password</button>
            </form><?php endif; ?>
        </div>
    </div>
</section>
<?php require APP_ROOT . '/components/footer.php'; ?>