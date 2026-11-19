<?php
/*
 * index.php - main menu for family feud
 *
 * - welcomes the logged in player and links to every page
 */

session_start();

// get the logged in player's name, if there is one
$user = $_SESSION['user'] ?? null;
?>

<html>
    <head>
        <link rel="stylesheet" href="style.css">

    <body>
        <?php // links to every page ?>
        <nav>
            <a href="index.php">home</a>
            <a href="login.php">login</a>
            <a href="register.php">register</a>
            <a href="game.php">game</a>
            <a href="leaderboard.php">leaderboard</a>
            <a href="team.html">team</a>
            <?php if ($user !== null): ?>
                <a href="logout.php">logout</a>
            <?php endif; ?>
        </nav>
        <h1>Menu</h1>
        <h2>Welcome<?= $user !== null ? ', ' . htmlspecialchars($user) : '' ?>!</h2>
        <?php // play button starts the game ?>
        <form method="POST" action="game.php">
             <button type="submit">play</button>
         </form>
    </body>
</html>