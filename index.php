<?php
// session_start();
    if (isset($_SESSION['user'])){
        $user = $_SESSION['user'];
        echo $user;
    }
?>

<html>
    <head>

    <body>
        <nav>
            <a href="index.php">home</a>
            <a href="login.php">login</a>
            <a href="register.php">register</a>
            <a href="game.php">game</a>
        </nav>
        <h1>Menu</h1>
        <h2>Welcome <?=$user ?? ""?></h2>
        <form method="POST" action="game.php">
             <button type="submit">play</button>
         </form>
    </body>
</html>