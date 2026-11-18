<?php
/*
 * register.php - sign up page for family feud
 *
 * - grabs the form with filter_input() and checks it on the server
 * - hashes the password with password_hash() before saving it
 * - saves each account as one json line in data/accounts.txt
 * - keeps the username filled in if something goes wrong
 */

session_start();

if (!empty($_SESSION['logged_in'])) {
    header('Location: game.php');
    exit;
}

const ACCOUNTS_FILE = __DIR__ . '/data/accounts.txt';

$errors   = [];     
$success  = false;  
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username        = trim((string) filter_input(INPUT_POST, 'username', FILTER_UNSAFE_RAW));
    $password        = (string) filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW);
    $confirmPassword = (string) filter_input(INPUT_POST, 'confirm_password', FILTER_UNSAFE_RAW);

    if ($username === '' || $password === '' || $confirmPassword === '') {
        $errors[] = 'Please fill in all fields.';
    }

    if ($username !== '' && !preg_match('/^[A-Za-z0-9_]{3,20}$/', $username)) {
        $errors[] = 'Username must be 3-20 characters: letters, numbers, and underscores only.';
    }

    if ($password !== '' && strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }

    if (strlen($password) > 72) {
        $errors[] = 'Password must be 72 characters or fewer.';
    }

    if ($password !== '' && $confirmPassword !== '' && $password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $handle = @fopen(ACCOUNTS_FILE, 'c+');
        if ($handle === false) {
            $errors[] = 'Unable to access account storage. Please try again later.';
        } else {
            flock($handle, LOCK_EX);

            $taken = false;
            while (($line = fgets($handle)) !== false) {
                $account = json_decode($line, true);
                if (is_array($account)
                    && isset($account['username'])
                    && strcasecmp($account['username'], $username) === 0) {
                    $taken = true;
                    break;
                }
            }

            if ($taken) {
                $errors[] = 'That username is already taken. Please choose another.';
            } else {
                $newAccount = [
                    'username' => $username,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'created'  => date('c'),
                ];

                fseek($handle, 0, SEEK_END);
                if (fwrite($handle, json_encode($newAccount) . PHP_EOL) !== false) {
                    fflush($handle);
                    $success = true;
                } else {
                    $errors[] = 'Unable to save your account. Please try again.';
                }
            }

            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Family Feud: Survey Showdown</title>
    <style>
        .register-layout { display: flex; gap: 2rem; align-items: flex-start; flex-wrap: wrap; }
        .auth-form { display: flex; flex-direction: column; gap: 0.4rem; flex: 1 1 280px; max-width: 360px; }
        .auth-form button { margin-top: 0.8rem; }
        .requirements { flex: 0 1 260px; border: 1px solid #ccc; border-radius: 8px; padding: 1rem 1.2rem; }
        .requirements h3 { margin-top: 0; }
        .requirements ul { list-style: none; padding: 0; margin: 0 0 0.8rem; }
        .requirements li::before { content: "\2717  "; color: #b33; }
        .requirements li.met::before { content: "\2713  "; color: #2a7a3a; }
        .requirements li.met { color: #2a7a3a; }
    </style>
</head>
<body>

<main class="auth-page">
    <h1>Family Feud: Survey Showdown</h1>
    <h2>Create an Account</h2>

    <?php if ($success): ?>

        <p class="message success" role="status">
            Account created for <strong><?= htmlspecialchars($username) ?></strong>!
        </p>
        <p><a class="button" href="login.php">Go to Login</a></p>

    <?php else: ?>

        <?php if (!empty($errors)): ?>
            <div class="message error" role="alert">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="register-layout">

        <form class="auth-form" method="POST" action="register.php" novalidate>

            <label for="username">Username</label>
            <input
                type="text"
                id="username"
                name="username"
                value="<?= htmlspecialchars($username) ?>"
                required
                minlength="3"
                maxlength="20"
                pattern="[A-Za-z0-9_]{3,20}"
                autocomplete="username"
            >

            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                required
                minlength="8"
                maxlength="72"
                autocomplete="new-password"
            >

            <label for="confirm_password">Confirm Password</label>
            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                required
                minlength="8"
                maxlength="72"
                autocomplete="new-password"
            >

            <button type="submit">Register</button>
        </form>

        <aside class="requirements" aria-label="account requirements">
            <h3>What you need</h3>

            <strong>Username</strong>
            <ul>
                <li id="req-user-length">3-20 characters</li>
                <li id="req-user-chars">only letters, numbers, and underscores</li>
            </ul>

            <strong>Password</strong>
            <ul>
                <li id="req-pass-length">at least 8 characters</li>
                <li id="req-pass-max">72 characters or fewer</li>
                <li id="req-pass-match">both passwords match</li>
            </ul>
        </aside>

        </div>

        <p>Already have an account? <a href="login.php">Log in here</a></p>

    <?php endif; ?>
</main>

<script>
    const userBox    = document.getElementById('username');
    const passBox    = document.getElementById('password');
    const confirmBox = document.getElementById('confirm_password');

    function mark(id, ok) {
        document.getElementById(id).classList.toggle('met', ok);
    }

    function checkRules() {
        if (!userBox) return;
        const u = userBox.value.trim();
        const p = passBox.value;
        mark('req-user-length', u.length >= 3 && u.length <= 20);
        mark('req-user-chars', u.length > 0 && /^[A-Za-z0-9_]+$/.test(u));
        mark('req-pass-length', p.length >= 8);
        mark('req-pass-max', p.length > 0 && p.length <= 72);
        mark('req-pass-match', p.length > 0 && p === confirmBox.value);
    }

    [userBox, passBox, confirmBox].forEach(box => box && box.addEventListener('input', checkRules));
    checkRules();
</script>

</body>
</html>
