<?php
session_start();

if (empty($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['placeholders']) || !isset($_SESSION['total']) || !isset($_SESSION['strikes'])){
    $_SESSION['placeholders'] = [1,2,3,4,5];
    $_SESSION['total'] = 0;
    $_SESSION['strikes'] = 0;
}

$answer   = '';
$feedback = '';
$feedbackType = '';
$scoreBefore = $_SESSION['total'];

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

    $action = filter_input(INPUT_POST, 'action', FILTER_UNSAFE_RAW);
    if ($action !== null && $action !== 'reset') {
        $action = null;
    }

    $rawAnswer = filter_input(INPUT_POST, 'answer', FILTER_UNSAFE_RAW);
    if ($rawAnswer !== null && $action === null) {
        $answer = strtolower(preg_replace('/\s+/', ' ', trim((string) $rawAnswer)));

        if ($answer === '') {
            $feedback = 'Type an answer first!';
        } elseif (strlen($answer) > 40) {
            $feedback = 'That answer is too long (40 characters max).';
        } elseif (!preg_match("/^[a-z0-9 '\-]+$/", $answer)) {
            $feedback = 'Answers can only have letters, numbers, spaces, hyphens, and apostrophes.';
        } elseif (in_array($answer, $_SESSION['placeholders'], true)) {
            $feedback = 'That one is already on the board!';
        }

        if ($feedback !== '') {
            $answer = '';
            $feedbackType = 'bad';
        }
    }

    if ($action !== null){

        if ($action === 'reset') {
            unset($_SESSION['placeholders'], $_SESSION['total'], $_SESSION['strikes']);
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
    if ($_SESSION['total'] == 100 || $_SESSION['strikes'] >= 2){
        
        $_SESSION['total_points'] = ($_SESSION['total_points'] ?? 0) + $_SESSION['total'];

        $_SESSION['scores'][] = [
            'user'  => $_SESSION['user'] ?? 'guest',
            'score' => $_SESSION['total_points'],
            'time'  => time(),
        ];

        $_SESSION['last_result'] = [
            'won'          => true,
            'question'     => $question['question'],
            'answers'      => $question['answers'],
            'found'        => array_values(array_filter($_SESSION['placeholders'], 'is_string')),
            'round_score'  => $_SESSION['total'],
            'total_points' => $_SESSION['total_points'],
        ];

        $_SESSION['total'] = 0;
        $_SESSION['strikes'] = 0;
        $_SESSION['placeholders'] = [1,2,3,4,5];

        header('Location: result.php');
        exit;
    }
}

    if ($answer !== '' && $feedback === '') {
        if ($_SESSION['total'] > $scoreBefore) {
            $feedback = 'Survey says... YES! +' . ($_SESSION['total'] - $scoreBefore) . ' points';
            $feedbackType = 'good';
        } else {

        $_SESSION['strikes']++;

        $feedback = 'Survey says... nope, not on the board.';
        $feedbackType = 'bad';


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
            <a href="leaderboard.php">leaderboard</a>
            <a href="logout.php">logout</a>
        </nav>
        <?php
            foreach ($_SESSION['placeholders'] as $placeholder){
                print htmlspecialchars((string) $placeholder) . '<br>';
            }

            print 'total: ' . (int) $_SESSION['total'] . '<br>';
        ?>
        <h2>Strikes: <?= (int) $_SESSION['strikes'] ?> / 3</h2>
        <h1><?= htmlspecialchars($questions[0]["question"]) ?></h1>

        <?php if ($feedback !== ''): ?>
            <p class="feedback <?= $feedbackType ?>" role="status"><?= htmlspecialchars($feedback) ?></p>
        <?php endif; ?>

        <form method="POST" action="game.php">
            <input type="text" name="answer" required maxlength="40" autocomplete="off" autofocus>
            <button type="submit">submit</button>
        </form>
        <form action="game.php" method="POST">
            <input type="hidden" name="action" value="reset">
            <button type="submit">reset</button>
        </form>
    </body>
</html>