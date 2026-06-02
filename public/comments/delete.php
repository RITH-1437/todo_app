<?php

session_start();

require __DIR__ . '/../../config/app.php';
require __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

function jsonResponse(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit();
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
}

if (!isset($_SESSION['user_id'])) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized.'], 401);
}

$commentId = filter_input(INPUT_POST, 'comment_id', FILTER_VALIDATE_INT);

if (!$commentId) {
    jsonResponse(['success' => false, 'message' => 'Invalid comment.'], 422);
}

try {
    // Verify the comment belongs to a task owned by this user.
    $stmt = $conn->prepare("
        SELECT c.id
        FROM comments c
        JOIN tasks t ON t.id = c.task_id
        WHERE c.id = :comment_id
          AND t.user_id = :user_id
        LIMIT 1
    ");
    $stmt->execute([
        ':comment_id' => $commentId,
        ':user_id'    => $_SESSION['user_id'],
    ]);

    if (!$stmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Comment not found.'], 404);
    }

    $conn->prepare("DELETE FROM comments WHERE id = :id")
         ->execute([':id' => $commentId]);

    jsonResponse(['success' => true, 'message' => 'Comment deleted.']);
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Database error.'], 500);
}
