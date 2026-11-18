<?php
    session_start();
    
    $users = file("accounts.txt", FILE_IGNORE_NEW_LINES);

    // Convert each line into an array
    foreach ($users as $key => $user) {
        $users[$key] = explode(",", $user);
    }

    var_dump($users);

    if ($_SERVER['REQUEST_METHOD'] === "POST") {

        $username = $_POST['user'];
        $password = $_POST['pass'];

        // Check if account already exists
        foreach ($users as $user) {
            if ($user[0] === $username) {
                echo 'Username already taken';
                exit('<a href="register.php">go back</a>');
            }
        }

        // Add new account
        $users[] = [$username, $password, 0, "mode", 0];

        // Convert each array back into a string
        foreach ($users as $key => $user) {
            $users[$key] = implode(",", $user);
        }

        // Convert all accounts into one string
        $users = implode("\n", $users) . "\n";

        // Save everything back into the file
        file_put_contents("accounts.txt", $users);
    }
?>

<html>
    <nav>
        <a href="index.php">home</a>
        <a href="login.php">login</a>
        <a href="register.php">register</a>
    </nav>
    <h1>register</h1>
    <form method="POST">
        <input type="text" name="user" placeholder="user"><br>
        <input type="text" name="pass" placeholder="pass"><br>
        <button type="submit">Submit</button>
    </form>
</html>