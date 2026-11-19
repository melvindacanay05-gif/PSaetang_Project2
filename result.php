<?php
/*
 * result.php - results screen after a game ends
 *
 * - game.php saves the finished game in $_SESSION['last_result'] and sends the player here
 * - shows every round (question, answers, score, strikes), the totals, and leaderboard rank
 */

session_start();

// only logged in players can see results
if (empty($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit;
}

// nothing to show until a game is finished
if (empty($_SESSION['last_result']['rounds'])) {
    header('Location: game.php');
    exit;
}

$result = $_SESSION['last_result'];
$user   = $_SESSION['user'] ?? 'guest';
$rounds = $result['rounds'];

$won = true;
// the game counts as won only if every board was cleared
foreach ($rounds as $round) {
    if (empty($round['cleared'])) {
        $won = false;
        break;
    }
}

$best = [];
// work out the player's leaderboard rank
foreach ($_SESSION['scores'] ?? [] as $entry) {
    $key = strtolower($entry['user']);
    $best[$key] = max($best[$key] ?? 0, (int) $entry['score']);
}
arsort($best);

$rank = array_search(strtolower($user), array_keys($best), true);
$rank = ($rank === false) ? null : $rank + 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Results | Family Feud: Survey Showdown</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php // links to every page ?>
<nav>
    <a href="index.php">home</a>
    <a href="game.php">game</a>
    <a href="leaderboard.php">leaderboard</a>
    <a href="team.html">team</a>
    <a href="logout.php">logout</a>
</nav>

<main>
    <?php if ($won): ?>
        <h1>Survey says... you cleared every round! 🎉</h1>
        <p>Nice work, <?= htmlspecialchars($user) ?>. You found every answer on every board.</p>
    <?php else: ?>
        <h1>Game over!</h1>
        <p>Good try, <?= htmlspecialchars($user) ?>. Here's how each round went.</p>
    <?php endif; ?>

    <div class="stats">
        <div class="stat"><strong><?= (int) $result['game_total'] ?></strong> this game</div>
        <div class="stat"><strong><?= (int) $result['total_points'] ?></strong> total points</div>
        <?php if ($rank !== null): ?>
            <div class="stat"><strong>#<?= $rank ?></strong> on the leaderboard</div>
        <?php endif; ?>
    </div>

    <?php // one summary card per round ?>
    <?php foreach ($rounds as $round): ?>
        <section class="round">
            <h2>Round <?= (int) $round['round'] ?>: <?= htmlspecialchars($round['question']) ?></h2>
            <p class="round-meta">
                <?= (int) $round['score'] ?> points &middot;
                <?= (int) $round['strikes'] ?> strike<?= (int) $round['strikes'] === 1 ? '' : 's' ?> &middot;
                <?= !empty($round['cleared']) ? 'board cleared' : 'out of strikes' ?>
            </p>

            <ol class="board">
                <?php foreach ($round['answers'] as $answer => $points): ?>
                    <?php $found = in_array((string) $answer, $round['found'], true); ?>
                    <li class="<?= $found ? 'found' : 'missed' ?>">
                        <span><?= htmlspecialchars(ucfirst((string) $answer)) ?><?= $found ? '' : ' (missed)' ?></span>
                        <span><?= (int) $points ?></span>
                    </li>
                <?php endforeach; ?>
            </ol>
        </section>
    <?php endforeach; ?>

    <div class="actions">
        <?php // buttons for what to do next ?>
        <form method="POST" action="game.php">
            <input type="hidden" name="action" value="reset">
            <button type="submit">Play again</button>
        </form>
        <a href="leaderboard.php">See the leaderboard</a>
        <a href="index.php">Back to menu</a>
    </div>
</main>

</body>
</html>
