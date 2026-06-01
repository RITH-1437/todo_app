<?php

/**
 * Shared helper functions.
 * Requires config/app.php to be loaded first (for APP_ROOT / AVATAR_PUBLIC_DIR / DEFAULT_AVATAR).
 */

// ── HTTP ──────────────────────────────────────────────────────────────────────

/**
 * Redirect to $url and terminate.
 */
function redirect(string $url): never
{
    header('Location: ' . $url);
    exit();
}

/**
 * Abort with JSON response and terminate.
 */
function jsonResponse(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit();
}

// ── Session / Auth ────────────────────────────────────────────────────────────

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

/**
 * Redirect to login if not authenticated.
 */
function requireLogin(string $to = '/auth/login.php'): void
{
    if (!isLoggedIn()) {
        redirect($to);
    }
}

/**
 * Store a flash toast message for the next page load.
 */
function setToast(string $message, string $type = 'success'): void
{
    $_SESSION['toast'] = ['message' => $message, 'type' => $type];
}

/**
 * Retrieve and clear the stored toast message. Returns null if none.
 */
function getToast(): ?array
{
    $toast = $_SESSION['toast'] ?? null;
    unset($_SESSION['toast']);
    return $toast;
}

// ── Date / Task helpers ───────────────────────────────────────────────────────

/**
 * Format a date string (e.g. '2025-06-01' → 'Jun 01, 2025').
 */
function formatDate(string $date, string $format = 'M d, Y'): string
{
    return date($format, strtotime($date));
}

/**
 * Return true if a task is overdue (past due date and not completed).
 */
function isTaskOverdue(array $task): bool
{
    return !empty($task['due_date'])
        && strtotime($task['due_date']) < strtotime(date('Y-m-d'))
        && $task['status'] !== 'completed';
}

/**
 * Return how many days a date is past today (0-based, always positive).
 */
function daysOverdue(string $dueDate): int
{
    return (int) (new DateTime())->diff(new DateTime($dueDate))->days;
}

// ── Security / Sanitization ───────────────────────────────────────────────────

/**
 * HTML-encode a string for safe output. Trims whitespace.
 */
function sanitize(string $value): string
{
    return htmlspecialchars(trim($value), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ── Avatar ────────────────────────────────────────────────────────────────────

/**
 * Validate an avatar path stored in the DB and return it (or the default).
 * Checks that the file physically exists inside the public uploads directory.
 */
function getSafeAvatarPath(?string $avatar): string
{
    if (!$avatar) {
        return DEFAULT_AVATAR;
    }

    $avatar = ltrim(str_replace('\\', '/', $avatar), '/');

    if (!str_starts_with($avatar, AVATAR_PUBLIC_DIR . '/')) {
        return DEFAULT_AVATAR;
    }

    // File lives at PUBLIC_DIR/uploads/avatars/...
    if (!file_exists(PUBLIC_DIR . '/' . $avatar)) {
        return DEFAULT_AVATAR;
    }

    return $avatar;
}

// ── Database helpers ──────────────────────────────────────────────────────────

/**
 * Fetch the currently logged-in user from the DB.
 * Returns null (and destroys session) if the user row is missing.
 *
 * @param PDO   $conn
 * @param array $columns  List of column names returned by SHOW COLUMNS FROM users
 */
function fetchCurrentUser(PDO $conn, array $columns): ?array
{
    $select = [
        'id', 'name', 'email', 'password',
        in_array('avatar', $columns, true)
            ? 'avatar'
            : 'NULL AS avatar',
        in_array('theme_preference', $columns, true)
            ? 'theme_preference'
            : "'dark' AS theme_preference",
    ];

    $stmt = $conn->prepare(
        'SELECT ' . implode(', ', $select) . ' FROM users WHERE id = :id LIMIT 1'
    );
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        session_destroy();
        return null;
    }

    return $user;
}
