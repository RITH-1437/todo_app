<?php

require '../../includes/auth.php';

require '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    setToast('Invalid request.', 'error');
    redirect('../index.php');
}

$id = filter_var($_POST['id'], FILTER_VALIDATE_INT);

$user_id = $_SESSION['user_id'];

$sql = "

DELETE FROM tasks

WHERE id = :id

AND user_id = :user_id

";

$stmt = $conn->prepare($sql);

$stmt->execute([
    ':id' => $id,
    ':user_id' => $user_id
]);

setToast('Task deleted successfully!', 'success');
redirect('../index.php');
