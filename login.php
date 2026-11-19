<?php
/*
 * login.php - log in page for family feud
 *
 * - reads accounts from data/accounts.txt (the same file register.php saves to)
 * - checks the password with password_verify() against the saved hash
 * - keeps the username filled in if the login fails
 */

session_start();

// already logged in? skip the form
if (!empty($_SESSION['logged_in'])) {
    header('Location: index.php');
    exit;
}

const ACCOUNTS_FILE = __DIR__ . '/data/accounts.txt';

$error    = '';
$notice   = isset($_GET['logged_out']) ? "You've been logged out. See you next time!" : '';
$username = '';
// handle the login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim((string) filter_input(INPUT_POST, 'username', FILTER_UNSAFE_RAW));
    $password = (string) filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW);

    if ($username === '' || $password === '') {
        $error = 'Please enter your username and password.';
    } else {
        $found = null;
        $lines = is_readable(ACCOUNTS_FILE) ? file(ACCOUNTS_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];

        // find the account with this username
        foreach ($lines as $line) {
            $account = json_decode($line, true);
            if (is_array($account)
                && isset($account['username'], $account['password'])
                && strcasecmp($account['username'], $username) === 0) {
                $found = $account;
                break;
            }
        }

        // check the password, then start a fresh session
        if ($found === null || !password_verify($password, $found['password'])) {
            $error = 'Wrong username or password.';
        } else {
            session_regenerate_id(true);

            $_SESSION['user']         = $found['username'];
            $_SESSION['logged_in']    = true;
            $_SESSION['total_points'] = 0;

            header('Location: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Family Feud: Survey Showdown</title>
    <style>
        .auth-form { display: flex; flex-direction: column; gap: 0.4rem; max-width: 360px; }
        .auth-form button { margin-top: 0.8rem; }
    </style>
</head>
<body>

<?php // links to every page ?>
<nav>
    <a href="index.php">home</a>
    <a href="login.php">login</a>
    <a href="register.php">register</a>
    <a href="leaderboard.php">leaderboard</a>
    <a href="team.html">team</a>
</nav>

<main class="auth-page">
    <h1>Family Feud: Survey Showdown</h1>
    <h2>Log In</h2>

    <?php if ($notice !== '' && $error === ''): ?>
        <p class="message success" role="status"><?= htmlspecialchars($notice) ?></p>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <p class="message error" role="alert"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php // login form; the username stays filled in after an error ?>
    <form class="auth-form" method="POST" action="login.php">
        <label for="username">Username</label>
        <input
            type="text"
            id="username"
            name="username"
            value="<?= htmlspecialchars($username) ?>"
            required
            autocomplete="username"
        >

        <label for="password">Password</label>
        <input
            type="password"
            id="password"
            name="password"
            required
            autocomplete="current-password"
        >

        <button type="submit">Log In</button>
    </form>

    <p>Don't have an account? <a href="register.php">Sign up here</a></p>
</main>

</body>
</html>
