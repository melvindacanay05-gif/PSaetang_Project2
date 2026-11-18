<?php
session_start();

if (!isset($_SESSION['placeholders']) || !isset($_SESSION['total'])){
    $_SESSION['placeholders'] = [1,2,3,4,5];
    $_SESSION['total'] = 0;
}

$answer = '';

$questions = [

    [
        "question" => "Name something people bring to the beach",

        "answers" => [
            "towel" => 30,
            "sunscreen" => 25,
            "umbrella" => 20,
            "food" => 15,
            "chair" => 10
        ]
    ],

    [
        "question" => "Name something people drink in the morning",

        "answers" => [
            "coffee" => 40,
            "juice" => 25,
            "tea" => 20,
            "water" => 10,
            "milk" => 5
        ]
    ]

];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['answer'])){
        $answer = $_POST['answer'];
    }

    if (isset($_POST['action'])){
        $action = $_POST['action'];

        if ($action === 'reset') {
            session_destroy();
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
    }

    foreach ($questions as $question) {

    $index = 0;

    foreach ($question['answers'] as $ans => $points) {

        $index++;

        if ($answer === $ans && $_SESSION['placeholders'][$index - 1] === $index) {

            $_SESSION['placeholders'][$index - 1] = $ans;

            $_SESSION['total'] += $points;
        }
    }
    if ($_SESSION['total'] == 100){
        
        $_SESSION['total_points'] += $_SESSION['total'];
        $_SESSION['total'] = 0;
        $_SESSION['placeholders'] = [1,2,3,4,5];

        print "Current total: " .  $_SESSION['total_points'] . "<br>";
        exit('<a href="index.php">Good job! Go back</a>');
    }
}
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
        <?php
            foreach ($_SESSION['placeholders'] as $placeholder){
                print $placeholder . '<br>';
            }



            print 'total: ' . $_SESSION['total'] . '<br>';
            // print 'highscore: ' . $_SESSION['highscore'];
        ?>
        <h1><?=$questions[0]["question"]?></h1>

        <form method="POST" action="game.php">
            <input type="text" name="answer">
            <button type="submit">submit</button>
        </form>
        <form action="game.php" method="POST">
            <input type="hidden" name="action" value="reset">
            <button type="submit">reset</button>
        </form>
    </body>
</html>