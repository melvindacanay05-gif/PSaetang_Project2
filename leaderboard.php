<?php
/*
 * leaderboard.php - top players for family feud
 *
 * - scores live in $_SESSION['scores'] (game.php adds one when a game ends)
 * - each player only shows up once, with their best score
 * - sorted highest first, top 10 shown
 * - session scores are temporary, they go away when the session ends
 */

session_start();

const TOP_N = 10;

$scores = $_SESSION['scores'] ?? [];

$best = [];
foreach ($scores as $entry) {
    if (!is_array($entry) || !isset($entry['user'], $entry['score'])) {
        continue; 
    }

    $name = (string) $entry['user'];
    $key  = strtolower($name);
    if (!isset($best[$key]) || (int) $entry['score'] > $best[$key]['score']) {
        $best[$key] = [
            'user'  => $name,
            'score' => (int) $entry['score'],
            'time'  => (int) ($entry['time'] ?? 0),
            'games' => ($best[$key]['games'] ?? 0),
        ];
    }
    $best[$key]['games']++; 
}

usort($best, function ($a, $b) {
    if ($a['score'] !== $b['score']) {
        return $b['score'] <=> $a['score'];
    }
    return $a['time'] <=> $b['time'];
});

$top         = array_slice($best, 0, TOP_N);
$currentUser = $_SESSION['user'] ?? null;

$myRank = null;
foreach ($best as $i => $row) {
    if ($currentUser !== null && strcasecmp($row['user'], $currentUser) === 0) {
        $myRank = ['rank' => $i + 1] + $row;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard | Family Feud: Survey Showdown</title>
    <style>
        .leaderboard { border-collapse: collapse; width: 100%; max-width: 520px; margin: 0 auto; }
        .leaderboard th, .leaderboard td { padding: 0.5rem 0.8rem; border-bottom: 1px solid #ddd; text-align: left; }
        .leaderboard td.num, .leaderboard th.num { text-align: right; }
        .leaderboard tr.me { background: #ffd24a; color: #050a7a; font-weight: bold; }
    </style>
</head>
<body>

<nav>
    <a href="index.php">home</a>
    <?php if ($currentUser === null): ?>
        <a href="login.php">login</a>
        <a href="register.php">register</a>
    <?php endif; ?>
    <a href="game.php">game</a>
    <a href="leaderboard.php">leaderboard</a>
    <a href="team.html">team</a>
    <?php if ($currentUser !== null): ?>
        <a href="logout.php">logout</a>
    <?php endif; ?>
</nav>

<main>
    <h1>Leaderboard</h1>
    <p>Top <?= TOP_N ?> players this session.</p>

    <?php if (empty($top)): ?>

        <p>No scores yet. <a href="game.php">Play a game</a> to get on the board!</p>

    <?php else: ?>

        <table class="leaderboard">
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Player</th>
                    <th class="num">Best Score</th>
                    <th class="num">Games</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($top as $i => $row): ?>
                    <?php $isMe = $currentUser !== null && strcasecmp($row['user'], $currentUser) === 0; ?>
                    <tr class="<?= $isMe ? 'me' : '' ?>">
                        <td><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($row['user']) ?><?= $isMe ? ' (you)' : '' ?></td>
                        <td class="num"><?= $row['score'] ?></td>
                        <td class="num"><?= $row['games'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($myRank !== null && $myRank['rank'] > TOP_N): ?>
            <p>You're #<?= $myRank['rank'] ?> with <?= $myRank['score'] ?> points. Keep going!</p>
        <?php endif; ?>

    <?php endif; ?>

    <p><a href="game.php">Play again</a></p>
</main>

</body>
</html>
