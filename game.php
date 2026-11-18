<?php
session_start();

if (empty($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit;
}

$questions = require __DIR__ . '/questions.php';
$totalRounds = 3;
$maxStrikes = 3;
$maxHints = 1;

if (count($questions) < $totalRounds) {
    exit('questions.php needs at least ' . $totalRounds . ' questions.');
}

if (!isset($_SESSION['placeholders'], $_SESSION['total'], $_SESSION['strikes'], $_SESSION['round'], $_SESSION['banked'], $_SESSION['question_ids'], $_SESSION['rounds'], $_SESSION['hints'], $_SESSION['hint_for'])) {
    $_SESSION['placeholders'] = [1,2,3,4,5];
    $_SESSION['total'] = 0;
    $_SESSION['strikes'] = 0;
    $_SESSION['round'] = 1;
    $_SESSION['banked'] = 0;
    $_SESSION['rounds'] = [];
    $_SESSION['hints'] = 0;
    $_SESSION['hint_for'] = '';

    // random question selection: a different random question for each round
    $ids = array_rand($questions, $totalRounds);
    shuffle($ids);
    $_SESSION['question_ids'] = $ids;
}

$answer   = '';
$feedback = '';
$feedbackType = '';
$scoreBefore = $_SESSION['total'];
$current = $questions[$_SESSION['question_ids'][$_SESSION['round'] - 1]];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = filter_input(INPUT_POST, 'action', FILTER_UNSAFE_RAW);
    if ($action !== null && !in_array($action, ['reset', 'hint'], true)) {
        $action = null;
    }
    if ($action === 'hint') {
        if ($_SESSION['hints'] >= $maxHints) {
            $feedback = 'No hints left this round.';
            $feedbackType = 'bad';
        } else {
            $i = 0;
            foreach ($current['answers'] as $ans => $points) {
                $i++;
                if ($_SESSION['placeholders'][$i - 1] === $i) {   // first answer not found yet
                    $_SESSION['hints']++;
                    $_SESSION['hint_for'] = (string) $ans;
                    $feedback = 'Hint added below.';
                    $feedbackType = 'good';
                    break;
                }
            }
        }
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

    if ($action === 'reset') {
        unset(
            $_SESSION['placeholders'], $_SESSION['total'], $_SESSION['strikes'],
            $_SESSION['round'], $_SESSION['banked'], $_SESSION['question_ids'], $_SESSION['rounds']
        );
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    $index = 0;
    foreach ($current['answers'] as $ans => $points) {
        $index++;

        if ($answer === $ans && $_SESSION['placeholders'][$index - 1] === $index) {
            $_SESSION['placeholders'][$index - 1] = $ans;
            $_SESSION['total'] += $points;
        }
    }

    // score the guess (adds a strike when it is wrong)
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

    // check for the end of the round AFTER the strike has been counted
    $cleared   = count(array_filter($_SESSION['placeholders'], 'is_int')) === 0;
    $roundOver = $cleared || $_SESSION['strikes'] >= $maxStrikes;

    if ($roundOver) {
        // remember this round for the results page
        $_SESSION['rounds'][] = [
            'round'    => $_SESSION['round'],
            'question' => $current['question'],
            'answers'  => $current['answers'],
            'found'    => array_values(array_filter($_SESSION['placeholders'], 'is_string')),
            'score'    => $_SESSION['total'],
            'strikes'  => $_SESSION['strikes'],
            'cleared'  => $cleared,
        ];

        $_SESSION['banked'] += $_SESSION['total'];

        if ($_SESSION['round'] >= $totalRounds) {
            // last round: save the game and show results
            $_SESSION['total_points'] = ($_SESSION['total_points'] ?? 0) + $_SESSION['banked'];

            $_SESSION['scores'][] = [
                'user'  => $_SESSION['user'] ?? 'guest',
                'score' => $_SESSION['total_points'],
                'time'  => time(),
            ];

            $_SESSION['last_result'] = [
                'rounds'       => $_SESSION['rounds'],
                'game_total'   => $_SESSION['banked'],
                'total_points' => $_SESSION['total_points'],
            ];

            unset(
                $_SESSION['placeholders'], $_SESSION['total'], $_SESSION['strikes'],
                $_SESSION['round'], $_SESSION['banked'], $_SESSION['question_ids'], $_SESSION['rounds'],
                $_SESSION['hints'], $_SESSION['hint_for']
            );
            header('Location: result.php');
            exit;
        }

        // otherwise: next round
        $_SESSION['flash'] = [
            'type' => $cleared ? 'good' : 'bad',
            'text' => ($cleared ? 'Board cleared!' : 'Three strikes!') . ' On to round ' . ($_SESSION['round'] + 1) . '.',
        ];
        $_SESSION['round']++;
        $_SESSION['placeholders'] = [1,2,3,4,5];
        $_SESSION['total'] = 0;
        $_SESSION['strikes'] = 0;
        $_SESSION['hints'] = 0;
        $_SESSION['hint_for'] = '';
        header('Location: game.php');
        exit;
    }
}

// message left over from a round change on the previous request
if (isset($_SESSION['flash'])) {
    $feedback = $_SESSION['flash']['text'];
    $feedbackType = $_SESSION['flash']['type'];
    unset($_SESSION['flash']);
}

?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title>Game</title>
    </head>
    <body>
        <nav>
            <a href="index.php">home</a>
            <a href="login.php">login</a>
            <a href="register.php">register</a>
            <a href="game.php">game</a>
            <a href="leaderboard.php">leaderboard</a>
            <a href="logout.php">logout</a>
        </nav>
        <h3>Round <?= (int) $_SESSION['round'] ?> of <?= $totalRounds ?></h3>
        <?php if ($feedback !== ''): ?>
            <p class="feedback <?= htmlspecialchars($feedbackType) ?>" role="status"></p>
        <?php endif; ?>
        <?php if ($_SESSION['hint_for'] !== '' && !in_array($_SESSION['hint_for'], $_SESSION['placeholders'], true)): ?>
            <p>Hint: <?= htmlspecialchars(strtoupper($_SESSION['hint_for'][0]) . preg_replace('/[^ ]/', '_', substr($_SESSION['hint_for'], 1))) ?></p>
        <?php endif; ?>
        <?php
            foreach ($_SESSION['placeholders'] as $placeholder){
                print htmlspecialchars((string) $placeholder) . '<br>';
            }

            print 'round total: ' . (int) $_SESSION['total'] . '<br>';
            print 'running total: ' . (int) ($_SESSION['banked'] + $_SESSION['total']) . '<br>';
        ?>
        <h2>Strikes: <?= (int) $_SESSION['strikes'] ?> / <?= $maxStrikes ?></h2>
        <h1><?= htmlspecialchars($current['question']) ?></h1>


        <form method="POST" action="game.php">
            <input type="text" name="answer" required maxlength="40" autocomplete="off" autofocus>
            <button type="submit">submit</button>
        </form>
        <form action="game.php" method="POST">
            <input type="hidden" name="action" value="reset">
            <button type="submit">reset</button>
        </form>
                <form action="game.php" method="POST">
            <input type="hidden" name="action" value="hint">
            <button type="submit" <?= $_SESSION['hints'] >= $maxHints ? 'disabled' : '' ?>>
                hint (<?= $maxHints - $_SESSION['hints'] ?> left)
            </button>
        </form>
    </body>
</html>
