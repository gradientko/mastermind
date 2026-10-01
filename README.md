# mastermind

A server-rendered Mastermind game written in pure PHP.

There is **no JavaScript** in this project.

The player must discover a hidden four-color sequence within ten attempts. Every guess is submitted as a normal HTML form and processed on the server.

## Features

- pure PHP game logic
- no JavaScript
- PHP sessions
- cryptographically secure secret generation with `random_int()`
- four-color secret code
- six available colors
- duplicate colors allowed
- 10 attempts
- exact-position clues
- wrong-position clues
- correct handling of duplicate colors
- win / loss state
- session statistics
- games played
- win rate
- best attempt count
- CSRF token for POST actions
- POST/Redirect/GET after successful moves
- responsive CSS
- no framework
- no database

## Requirements

PHP 8.1+

## Run

```bash
php -S localhost:8000
```

Open:

```text
http://localhost:8000
```

## Rules

The server generates a secret sequence of four colors.

After each guess the player receives two numbers:

```text
Exact       correct color in the correct position
Misplaced   correct color in the wrong position
```

For example:

```text
Secret:  Red  Blue  Green  Yellow
Guess:   Red  Green Blue   Purple

Exact:      1
Misplaced:  2
```

The player wins by finding all four exact positions before the tenth attempt.

## Why this project is actually PHP-focused

The browser contains no JavaScript.

PHP handles:

- secret generation
- game state
- guess validation
- duplicate-color matching
- exact matches
- misplaced matches
- win/loss logic
- statistics
- CSRF validation
- new-game handling

State is stored in `$_SESSION`.

Each move is a standard `POST` request followed by a redirect back to the game page.

## Project structure

```text
mastermind/
├── index.php
├── functions.php
├── style.css
└── README.md
```

## Hosting

GitHub can host the repository source, but GitHub Pages cannot execute PHP.

Use PHP-capable hosting, a VPS, Docker/PHP-Apache, or PHP's built-in development server.

## License

MIT
