<?php
session_start();
if (!isset($_SESSION['user']) || !isset($_SESSION['total_points'])){
    // $username = $_SESSION['user'];
    $_SESSION['total_points'] = 0;
}

// $file = 'credentials.json';

// $users = json_decode(file_get_contents($file), true);

    $users = file("accounts.txt", FILE_IGNORE_NEW_LINES);

    // Convert each line into an array
    foreach ($users as $key => $user) {
        $users[$key] = explode(",", $user);
    }

    // var_dump($users);

// var_dump($users['user']);
if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    foreach ($users as $user){
        // echo $_POST['username'] === $user[0];
        if ($_POST['username'] === $user[0] && $_POST['password'] === $user[1]){
            $_SESSION['total_points'] = $user[2];
            $_SESSION['user'] = $user[0];

            print 'Login successful! <a href="login.php">yay</a><br>';
            print "Current highscore: " . $_SESSION['total_points'] . "<br>";
            print "Your best gamemode and score:" . $user[3] . $user[4];
            exit;
        }
        }
    print 'couldnt log you in <a href="login.php">retry</a>';
    exit;
}


?>

<html>
    <nav>
        <a href="index.php">home</a>
        <a href="login.php">login</a>
        <a href="register.php">register</a>
    </nav>
    <h1>login</h1>
    <form method="POST" action='login.php'>
        <input type="text" placeholder='username' name='username'><br>
        <input type="text" placeholder='password' name='password'><br>
        <button type="submit">submit</button>
    </form>
</html>