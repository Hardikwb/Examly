<?php
require __DIR__ . '/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}
CSRF::verify();
AuthService::logout();
Session::start();
Session::flash('success', 'You have been signed out.');
redirectTo('login.php');
?>