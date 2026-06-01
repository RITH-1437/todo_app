<?php

session_start();

require '../../config/database.php';

header('Content-Type: application/json');

function jsonResponse(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse([
        'success' => false,
        'message' => 'Invalid request method.'
    ], 405);
}

if (!isset($_SESSION['user_id'])) {
    jsonResponse([
        'success' => false,
        'message' => 'Please log in again.'
    ], 401);
}

$taskId = filter_input(INPUT_POST, 'task_id', FILTER_VALIDATE_INT);
$message = trim($_POST['message'] ?? '');

if (!$taskId) {
    jsonResponse([
        'success' => false,
        'message' => 'Invalid task.'
    ], 422);
}

if ($message === '') {
    jsonResponse([
        'success' => false,
        'message' => 'Comment cannot be empty.'
    ], 422);
}

$taskStmt = $conn->prepare("
    SELECT id
    FROM tasks
    WHERE id = :task_id
    AND user_id = :user_id
    LIMIT 1
");

$taskStmt->execute([
    ':task_id' => $taskId,
    ':user_id' => $_SESSION['user_id']
]);

if (!$taskStmt->fetch(PDO::FETCH_ASSOC)) {
    jsonResponse([
        'success' => false,
        'message' => 'Task not found.'
    ], 404);
}

$insertStmt = $conn->prepare("
    INSERT INTO comments (
        task_id,
        message
    )
    VALUES (
        :task_id,
        :message
    )
");

$insertStmt->execute([
    ':task_id' => $taskId,
    ':message' => $message
]);

$commentId = $conn->lastInsertId();

$commentStmt = $conn->prepare("
    SELECT id, message, created_at
    FROM comments
    WHERE id = :id
    LIMIT 1
");

$commentStmt->execute([
    ':id' => $commentId
]);

$comment = $commentStmt->fetch(PDO::FETCH_ASSOC);

jsonResponse([
    'success' => true,
    'message' => 'Comment added successfully',
    'comment' => [
        'id'         => (int) $comment['id'],
        'message'    => $comment['message'],
        'created_at' => $comment['created_at'],
    ],
]);
