<?php
/*
 * logout.php - logs the player out and sends them back to the login page
 */

session_start();

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}

session_destroy();

header('Location: login.php?logged_out=1');
exit;
