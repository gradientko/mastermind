<?php
declare(strict_types=1);

const CODE_LENGTH = 4;
const MAX_ATTEMPTS = 10;

const COLORS = [
    'red' => 'Red',
    'orange' => 'Orange',
    'yellow' => 'Yellow',
    'green' => 'Green',
    'blue' => 'Blue',
    'purple' => 'Purple',
];

function createGame(): array
{
    $keys = array_keys(COLORS);
    $secret = [];

    for ($i = 0; $i < CODE_LENGTH; $i++) {
        $secret[] = $keys[random_int(0, count($keys) - 1)];
    }

    return [
        'secret' => $secret,
        'attempts' => [],
        'status' => 'playing',
    ];
}

function evaluateGuess(array $secret, array $guess): array
{
    $exact = 0;
    $remainingSecret = [];
    $remainingGuess = [];

    for ($i = 0; $i < CODE_LENGTH; $i++) {
        if ($secret[$i] === $guess[$i]) {
            $exact++;
        } else {
            $remainingSecret[] = $secret[$i];
            $remainingGuess[] = $guess[$i];
        }
    }

    $misplaced = 0;

    foreach ($remainingGuess as $color) {
        $index = array_search($color, $remainingSecret, true);

        if ($index !== false) {
            $misplaced++;
            unset($remainingSecret[$index]);
        }
    }

    return [
        'exact' => $exact,
        'misplaced' => $misplaced,
    ];
}

function statusTitle(string $status): string
{
    return match ($status) {
        'won' => 'Code cracked.',
        'lost' => 'Code survived.',
        default => 'Find the sequence.',
    };
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
