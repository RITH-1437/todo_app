<?php

/**
 * config/env.php — Minimal .env file loader.
 *
 * Parses key=value pairs from .env into $_ENV and getenv().
 * Call this once, as early as possible (e.g. at the top of config/app.php).
 *
 * Supports:
 *   - Inline comments:  KEY=value  # comment
 *   - Quoted values:    KEY="hello world"  or  KEY='hello world'
 *   - Empty values:     KEY=
 *   - Skips blank lines and full-line comments (#)
 *   - Does NOT override variables already set in the environment
 *     (respects server / CI environment variables in production)
 */
function loadEnv(string $path): void
{
    if (!is_file($path) || !is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        // Skip blank lines and full-line comments
        if ($line === '' || $line[0] === '#') {
            continue;
        }

        // Must contain = to be a valid assignment
        if (!str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);

        $key   = trim($key);
        $value = trim($value);

        // Strip inline comment (only outside quotes)
        if (!empty($value) && $value[0] !== '"' && $value[0] !== "'") {
            // Remove trailing # comment
            $commentPos = strpos($value, ' #');
            if ($commentPos !== false) {
                $value = trim(substr($value, 0, $commentPos));
            }
        }

        // Strip surrounding quotes
        if (
            strlen($value) >= 2 &&
            (
                ($value[0] === '"'  && $value[-1] === '"')  ||
                ($value[0] === "'"  && $value[-1] === "'")
            )
        ) {
            $value = substr($value, 1, -1);
        }

        // Only set if not already defined (server env takes precedence)
        if (!array_key_exists($key, $_ENV) && getenv($key) === false) {
            $_ENV[$key]   = $value;
            putenv("{$key}={$value}");
        }
    }
}

/**
 * Read an env variable with an optional default fallback.
 *
 * Usage:
 *   env('DB_HOST')           // returns value or null
 *   env('APP_ENV', 'local')  // returns value or 'local'
 */
function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? getenv($key);

    if ($value === false) {
        return $default;
    }

    // Cast common string booleans
    return match (strtolower((string) $value)) {
        'true',  '(true)'  => true,
        'false', '(false)' => false,
        'null',  '(null)'  => null,
        'empty', '(empty)' => '',
        default            => $value,
    };
}
