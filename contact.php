<?php
require __DIR__ . '/bootstrap.php';
$pageTitle = 'Contact';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    postOnly();
    $validator = (new Validator())->required($_POST, 'name', 'Name')->required($_POST, 'email', 'Email')->email($_POST, 'email')->required($_POST, 'message', 'Message')->length($_POST, 'message', 'Message', 10, 3000);
    $errors = $validator->errors();
    if (!$errors) {
        $to = getenv('SUPPORT_EMAIL') ?: '';
        $sent = $to !== '' && mail($to, 'Examly contact request', trim($_POST['message']), 'Reply-To: ' . trim($_POST['email']));
        if ($sent) {
            Session::flash('success', 'Thanks for reaching out. Your message has been sent.');
            redirectTo('contact.php');
        }
        $errors[] = 'Message delivery is not configured. Set SUPPORT_EMAIL and configure PHP mail in XAMPP, then try again.';
    }
}
require APP_ROOT . '/components/header.php';
?>
<section class="page-hero">
    <div class="wrap"><span class="hero-kicker">Get in touch</span>
        <h1>We’re here to help.</h1>
        <p>Questions about setting up Examly or using your account? Send us a note.</p>
    </div>
</section>
<section class="section">
    <div class="wrap contact-grid">
        <aside class="contact-card">
            <h2>Contact Examly</h2>
            <p class="muted">For account access, ask your school administrator. For general questions, use the form and we’ll get back to you.</p>
            <div class="contact-detail"><strong>Support</strong><span>Configure SUPPORT_EMAIL on your local installation to receive contact form messages.</span></div>
            <div class="contact-detail"><strong>Hours</strong><span>Monday – Friday · 9:00 AM – 5:00 PM</span></div>
        </aside>
        <div class="form-card">
            <h2>Send a message</h2><?php if ($errors): ?><ul class="error-list"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul><?php endif; ?><form method="post" class="form-stack"><?= CSRF::field() ?><div class="field"><label for="name">Name</label><input id="name" name="name" required maxlength="120" value="<?= old('name') ?>"></div>
                <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" required maxlength="190" value="<?= old('email') ?>"></div>
                <div class="field"><label for="message">Message</label><textarea id="message" name="message" required minlength="10" maxlength="3000"><?= old('message') ?></textarea></div><button class="button button-primary" type="submit">Send message</button>
            </form>
        </div>
    </div>
</section>
<?php require APP_ROOT . '/components/footer.php'; ?>