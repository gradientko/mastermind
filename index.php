<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/functions.php';

if (!isset($_SESSION['mastermind'])) {
    $_SESSION['mastermind'] = createGame();
}

if (!isset($_SESSION['mastermind_stats'])) {
    $_SESSION['mastermind_stats'] = [
        'played' => 0,
        'won' => 0,
        'best_attempts' => null,
    ];
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        $error = 'Invalid request token.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'new') {
            $_SESSION['mastermind'] = createGame();
            header('Location: index.php');
            exit;
        }

        if ($action === 'guess') {
            $game = $_SESSION['mastermind'];

            if ($game['status'] !== 'playing') {
                $error = 'This game is already finished.';
            } else {
                $guess = [];

                for ($i = 0; $i < CODE_LENGTH; $i++) {
                    $value = $_POST['slot_' . $i] ?? '';

                    if (!is_string($value) || !array_key_exists($value, COLORS)) {
                        $error = 'Choose a color for every slot.';
                        break;
                    }

                    $guess[] = $value;
                }

                if ($error === null) {
                    $result = evaluateGuess($game['secret'], $guess);

                    $game['attempts'][] = [
                        'guess' => $guess,
                        'exact' => $result['exact'],
                        'misplaced' => $result['misplaced'],
                    ];

                    if ($result['exact'] === CODE_LENGTH) {
                        $game['status'] = 'won';

                        $_SESSION['mastermind_stats']['played']++;
                        $_SESSION['mastermind_stats']['won']++;

                        $attemptCount = count($game['attempts']);
                        $best = $_SESSION['mastermind_stats']['best_attempts'];

                        if ($best === null || $attemptCount < $best) {
                            $_SESSION['mastermind_stats']['best_attempts'] = $attemptCount;
                        }
                    } elseif (count($game['attempts']) >= MAX_ATTEMPTS) {
                        $game['status'] = 'lost';
                        $_SESSION['mastermind_stats']['played']++;
                    }

                    $_SESSION['mastermind'] = $game;

                    header('Location: index.php');
                    exit;
                }
            }
        }
    }
}

$game = $_SESSION['mastermind'];
$stats = $_SESSION['mastermind_stats'];

$attemptsUsed = count($game['attempts']);
$attemptsLeft = MAX_ATTEMPTS - $attemptsUsed;
$winRate = $stats['played'] > 0
    ? (int) round(($stats['won'] / $stats['played']) * 100)
    : 0;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mastermind — Pure PHP</title>
    <meta name="description" content="A server-rendered Mastermind game written in pure PHP without JavaScript.">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main class="shell">
    <header class="hero">
        <div>
            <span class="eyebrow">PURE PHP GAME</span>
            <h1>Crack the code.</h1>
            <p>
                Guess the hidden <?= CODE_LENGTH ?>-color sequence in <?= MAX_ATTEMPTS ?> attempts.
                Every move is processed entirely on the server.
            </p>
        </div>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="action" value="new">
            <button class="text-button" type="submit">New game</button>
        </form>
    </header>

    <section class="stats">
        <div>
            <span>Attempts left</span>
            <strong><?= $attemptsLeft ?></strong>
        </div>
        <div>
            <span>Games</span>
            <strong><?= (int) $stats['played'] ?></strong>
        </div>
        <div>
            <span>Win rate</span>
            <strong><?= $winRate ?>%</strong>
        </div>
        <div>
            <span>Best</span>
            <strong><?= $stats['best_attempts'] === null ? '—' : (int) $stats['best_attempts'] ?></strong>
        </div>
    </section>

    <section class="layout">
        <section class="board">
            <div class="board-head">
                <div>
                    <span class="eyebrow">ATTEMPTS</span>
                    <h2><?= statusTitle($game['status']) ?></h2>
                </div>
                <span class="counter"><?= $attemptsUsed ?>/<?= MAX_ATTEMPTS ?></span>
            </div>

            <?php if ($error !== null): ?>
                <div class="notice error"><?= e($error) ?></div>
            <?php endif; ?>

            <?php if ($game['status'] === 'won'): ?>
                <div class="notice success">
                    Code cracked in <?= $attemptsUsed ?> attempt<?= $attemptsUsed === 1 ? '' : 's' ?>.
                </div>
            <?php elseif ($game['status'] === 'lost'): ?>
                <div class="notice error">
                    No attempts left. The hidden code was:
                    <span class="inline-code">
                        <?php foreach ($game['secret'] as $color): ?>
                            <i class="peg <?= e($color) ?>" title="<?= e(COLORS[$color]) ?>"></i>
                        <?php endforeach; ?>
                    </span>
                </div>
            <?php endif; ?>

            <div class="attempt-list">
                <?php if ($attemptsUsed === 0): ?>
                    <div class="empty">
                        No guesses yet. Choose four colors below.
                    </div>
                <?php else: ?>
                    <?php foreach (array_reverse($game['attempts'], true) as $index => $attempt): ?>
                        <article class="attempt">
                            <div class="attempt-number">#<?= $index + 1 ?></div>

                            <div class="code-row">
                                <?php foreach ($attempt['guess'] as $color): ?>
                                    <i class="peg large <?= e($color) ?>" title="<?= e(COLORS[$color]) ?>"></i>
                                <?php endforeach; ?>
                            </div>

                            <div class="result">
                                <span>
                                    <strong><?= (int) $attempt['exact'] ?></strong>
                                    exact
                                </span>
                                <span>
                                    <strong><?= (int) $attempt['misplaced'] ?></strong>
                                    misplaced
                                </span>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if ($game['status'] === 'playing'): ?>
                <form class="guess-form" method="post">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="guess">

                    <div class="guess-grid">
                        <?php for ($slot = 0; $slot < CODE_LENGTH; $slot++): ?>
                            <label>
                                <span>Slot <?= $slot + 1 ?></span>
                                <select name="slot_<?= $slot ?>" required>
                                    <option value="">Choose</option>
                                    <?php foreach (COLORS as $key => $label): ?>
                                        <option value="<?= e($key) ?>">
                                            <?= e($label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        <?php endfor; ?>
                    </div>

                    <button class="primary" type="submit">Submit guess</button>
                </form>
            <?php endif; ?>
        </section>

        <aside class="side">
            <section class="panel">
                <span class="eyebrow">HOW IT WORKS</span>
                <h3>Read the clues.</h3>
                <p>
                    <strong>Exact</strong> means the right color in the right position.
                    <strong>Misplaced</strong> means the right color in the wrong position.
                </p>
            </section>

            <section class="palette">
                <?php foreach (COLORS as $key => $label): ?>
                    <div>
                        <i class="peg <?= e($key) ?>"></i>
                        <span><?= e($label) ?></span>
                    </div>
                <?php endforeach; ?>
            </section>

            <section class="panel">
                <span class="eyebrow">SERVER SIDE</span>
                <h3>No JavaScript.</h3>
                <p>
                    Secret generation, guesses, scoring, statistics and game state
                    are all handled by PHP sessions and POST requests.
                </p>
            </section>
        </aside>
    </section>

    <footer>
        <span>PHP sessions · server-rendered forms · no JavaScript</span>
        <span>mastermind</span>
    </footer>
</main>
</body>
</html>
