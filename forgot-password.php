<?php
require __DIR__ . '/bootstrap.php';
$pageTitle = 'Forgot password';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    postOnly();
    $validator = (new Validator())->required($_POST, 'email', 'Email')->email($_POST, 'email');
    $errors = $validator->errors();
    if (!$errors) {
        $user = (new User())->findByEmail((string)$_POST['email']);
        if ($user && $user['status'] === 'active') {
            $token = bin2hex(random_bytes(32));
            $pdo = Database::connection();
            $stmt = $pdo->prepare('INSERT INTO password_resets(user_id,token_hash,expires_at) VALUES(?,?,DATE_ADD(NOW(), INTERVAL 1 HOUR)) ON DUPLICATE KEY UPDATE token_hash=VALUES(token_hash),expires_at=VALUES(expires_at),created_at=CURRENT_TIMESTAMP');
            $stmt->execute([$user['id'], hash('sha256', $token)]);
            $url = appUrl('reset-password.php?token=' . rawurlencode($token));
            $body = "Hello {$user['name']},\n\nReset your Examly password using this link within one hour:\n$url\n\nIf you did not request this, ignore this email.";
            $headers = "From: " . (getenv('MAIL_FROM') ?: 'Examly <noreply@localhost>');
            @mail($user['email'], 'Reset your Examly password', $body, $headers);
        }
        Session::flash('success', 'If an active account uses that email, password reset instructions have been sent.');
        redirectTo('forgot-password.php');
    }
}
require APP_ROOT . '/components/header.php';
?>
<section class="auth-wrap">
    <div class="auth-card">
        <aside class="auth-aside"><a class="brand" href="<?= e(appUrl('index.php')) ?>"><span class="brand-mark">◆</span> Examly</a>
            <p>Account recovery</p><span class="aside-caption">We’ll send a secure, time-limited reset link.</span>
        </aside>
        <div class="auth-form"><span class="eyebrow">Password assistance</span>
            <h1>Forgot your password?</h1>
            <p>Enter your account email and we’ll send reset instructions.</p><?php if ($errors): ?><ul class="error-list"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul><?php endif; ?><form method="post" class="form-stack"><?= CSRF::field() ?><div class="field"><label for="email">Email address</label><input id="email" type="email" name="email" required maxlength="190" autocomplete="email" value="<?= old('email') ?>"></div><button class="button button-primary button-block" type="submit">Send reset link</button></form>
            <p class="auth-bottom" style="margin-top:18px"><a href="<?= e(appUrl('login.php')) ?>">Back to login</a></p>
        </div>
    </div>
</section>
<?php require APP_ROOT . '/components/footer.php'; ?>