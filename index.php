<?php
session_start();

$user = $_SESSION['user'] ?? null;
?>

<html>
    <head>

    <body>
        <nav>
            <a href="index.php">home</a>
            <a href="login.php">login</a>
            <a href="register.php">register</a>
            <a href="game.php">game</a>
            <a href="leaderboard.php">leaderboard</a>
            <?php if ($user !== null): ?>
                <a href="logout.php">logout</a>
            <?php endif; ?>
        </nav>
        <h1>Menu</h1>
        <h2>Welcome<?= $user !== null ? ', ' . htmlspecialchars($user) : '' ?>!</h2>
        <form method="POST" action="game.php">
             <button type="submit">play</button>
         </form>
    </body>
</html>