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

const ACCOUNTS_FILE = __DIR__ . '/data/accounts.txt';

function addToTotalScore(string $username, int $points): int
{
    $lines = file(ACCOUNTS_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return $points;
    }

    $total = $points;
    $found = false;
    foreach ($lines as $i => $line) {
        $account = json_decode($line, true);
        if (strcasecmp($account['username'] ?? '', $username) === 0) {
            $total = ($account['total_score'] ?? 0) + $points;
            $account['total_score'] = $total;
            $lines[$i] = json_encode($account);
            $found = true;
        }
    }

    if ($found) {
        file_put_contents(ACCOUNTS_FILE, implode(PHP_EOL, $lines) . PHP_EOL, LOCK_EX);
    }
    return $total;
}

function normalizeAnswer(string $text): string
{
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9 ]+/', ' ', $text);   // hyphens, apostrophes, etc become spaces
    $text = trim(preg_replace('/\s+/', ' ', $text));
    $text = preg_replace('/^(a|an|the|some) /', '', $text); // drop a leading article
    return str_replace(' ', '', $text);
}

function matchAnswer(string $guess, array $answers): ?string
{
    $guess = normalizeAnswer($guess);
    if ($guess === '') {
        return null;
    }

    $tries = [$guess];
    if (strlen($guess) > 4 && substr($guess, -3) === 'ies') {
        $tries[] = substr($guess, 0, -3) . 'y';   // strawberries -> strawberry
    }
    if (strlen($guess) > 3 && substr($guess, -2) === 'es') {
        $tries[] = substr($guess, 0, -2);
    }
    if (strlen($guess) > 2 && substr($guess, -1) === 's') {
        $tries[] = substr($guess, 0, -1);
    }

    foreach ($answers as $ans => $points) {
        if (in_array(normalizeAnswer((string) $ans), $tries, true)) {
            return (string) $ans;
        }
    }
    return null;
}

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

    // see which answer on THIS round's board the guess matches (only the current question counts)
    $matched = ($answer !== '' && $feedback === '') ? matchAnswer($answer, $current['answers']) : null;

    if ($matched !== null) {
        $index = array_search($matched, array_keys($current['answers']), true);

        if ($_SESSION['placeholders'][$index] === $index + 1) {
            // new answer: flip it over and add the points
            $_SESSION['placeholders'][$index] = $matched;
            $_SESSION['total'] += $current['answers'][$matched];
        } else {
            // already found, no points but no strike either
            $feedback = 'That one is already on the board!';
            $feedbackType = 'bad';
        }
    }

    // score the guess (adds a strike when it is wrong)
    if ($answer !== '' && $feedback === '') {
        if ($_SESSION['total'] > $scoreBefore) {
            $feedback = 'Survey says... ' . strtoupper($matched) . '! +' . ($_SESSION['total'] - $scoreBefore) . ' points';
            $feedbackType = 'good';
        } else {
            $_SESSION['strikes']++;

            $feedback = 'Survey says... nope, not on the board.';
            $feedbackType = 'bad';
        }
    }

    $cleared   = count(array_filter($_SESSION['placeholders'], 'is_int')) === 0;
    $roundOver = $cleared || $_SESSION['strikes'] >= $maxStrikes;

    if ($roundOver) {
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
            $_SESSION['total_points'] = addToTotalScore($_SESSION['user'] ?? 'guest', $_SESSION['banked']);

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
    <link rel="stylesheet" href="style.css">
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

    <main>
        <h3>Round <?= (int) $_SESSION['round'] ?> of <?= $totalRounds ?></h3>

        <?php if ($feedback !== ''): ?>
            <p class="feedback <?= htmlspecialchars($feedbackType) ?>" role="status"><?= htmlspecialchars($feedback) ?></p>
        <?php endif; ?>

        <?php if ($_SESSION['hint_for'] !== '' && !in_array($_SESSION['hint_for'], $_SESSION['placeholders'], true)): ?>
            <p>Hint: <?= htmlspecialchars(strtoupper($_SESSION['hint_for'][0]) . preg_replace('/[^ ]/', '_', substr($_SESSION['hint_for'], 1))) ?></p>
        <?php endif; ?>

        <h2><?= htmlspecialchars($current['question']) ?></h2>

        <div class="board">
            <?php foreach ($_SESSION['placeholders'] as $placeholder): ?>
                <div class="<?= is_int($placeholder) ? 'hidden' : 'found' ?>">
                    <?= htmlspecialchars((string) $placeholder) ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="totals">
            <p>Round total <b><?= (int) $_SESSION['total'] ?></b></p>
            <p>Running total <b><?= (int) ($_SESSION['banked'] + $_SESSION['total']) ?></b></p>
        </div>

        <p class="strikes">Strikes: <?= (int) $_SESSION['strikes'] ?> / <?= $maxStrikes ?></p>

        <form method="POST" action="game.php">
            <input type="text" name="answer" required maxlength="40" autocomplete="off" autofocus>
            <button type="submit">submit</button>
        </form>

        <form method="POST" action="game.php">
            <input type="hidden" name="action" value="hint">
            <button type="submit" <?= $_SESSION['hints'] >= $maxHints ? 'disabled' : '' ?>>
                hint (<?= $maxHints - $_SESSION['hints'] ?> left)
            </button>
        </form>

        <form method="POST" action="game.php">
            <input type="hidden" name="action" value="reset">
            <button type="submit">reset</button>
        </form>
    </main>
</body>
</html>