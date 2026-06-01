<?php
require '../../includes/auth.php';
require '../../config/database.php';

// Validate id input
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    setToast('Task not found.', 'error');
    redirect('../index.php');
}

$userId = $_SESSION['user_id'];

// Fetch task — MUST belong to current user (ownership check)
$stmt = $conn->prepare(
    "SELECT tasks.*, task_categories.category_id
     FROM tasks
     LEFT JOIN task_categories ON tasks.id = task_categories.task_id
     WHERE tasks.id = :id AND tasks.user_id = :user_id
     LIMIT 1"
);
$stmt->execute([':id' => $id, ':user_id' => $userId]);
$task = $stmt->fetch();

if (!$task) {
    setToast('Task not found.', 'error');
    redirect('../index.php');
}

$categoryStmt = $conn->query('SELECT * FROM categories ORDER BY name ASC');
$categories   = $categoryStmt->fetchAll();

if (isset($_POST['update_task'])) {
    $title       = trim($_POST['title']       ?? '');
    $description = trim($_POST['description'] ?? '');
    $status      = $_POST['status']      ?? 'pending';
    $priority    = $_POST['priority']    ?? 'medium';
    $due_date    = trim($_POST['due_date'] ?? '');
    $category_id = (int) ($_POST['category_id'] ?? 0);

    $validStatuses    = ['pending', 'completed'];
    $validPriorities  = ['low', 'medium', 'high'];
    $categoryIds      = array_map('intval', array_column($categories, 'id'));

    $error = null;
    if ($title === '')                                          { $error = 'Title is required.'; }
    elseif (!in_array($status, $validStatuses, true))          { $error = 'Invalid status.'; }
    elseif (!in_array($priority, $validPriorities, true))      { $error = 'Invalid priority.'; }
    elseif (!in_array($category_id, $categoryIds, true))       { $error = 'Invalid category.'; }

    if ($error) {
        setToast($error, 'error');
        redirect("edit.php?id={$id}");
    }

    try {
        $conn->beginTransaction();

        $conn->prepare(
            "UPDATE tasks
             SET title = :title, description = :description, status = :status,
                 priority = :priority, due_date = :due_date
             WHERE id = :id AND user_id = :user_id"
        )->execute([
            ':title'       => $title,
            ':description' => $description,
            ':status'      => $status,
            ':priority'    => $priority,
            ':due_date'    => !empty($due_date) ? $due_date : null,
            ':id'          => $id,
            ':user_id'     => $userId,
        ]);

        $conn->prepare('DELETE FROM task_categories WHERE task_id = :task_id')
             ->execute([':task_id' => $id]);

        $conn->prepare(
            'INSERT INTO task_categories (task_id, category_id) VALUES (:task_id, :category_id)'
        )->execute([':task_id' => $id, ':category_id' => $category_id]);

        $conn->commit();
    } catch (Throwable $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        error_log('Task update error: ' . $e->getMessage());
        setToast('Task update failed. Please try again.', 'error');
        redirect("edit.php?id={$id}");
    }

    setToast('Task updated successfully!', 'success');
    redirect('../index.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Task</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body id="body" class="bg-gray-100 min-h-screen py-10 px-5 transition">

    <div class="max-w-3xl mx-auto">

        <div class="flex justify-between items-center mb-8">
            <h1 id="edit-title" class="text-5xl font-bold text-gray-800">Edit Task</h1>
            <a href="../index.php" class="bg-gray-700 hover:bg-gray-800 text-white px-5 py-3 rounded-xl">Back</a>
        </div>

        <form method="POST" class="space-y-6">
            <input type="hidden" name="update_task" value="1">

            <input type="text" name="title"
                   value="<?= htmlspecialchars($task['title']) ?>" required
                   class="w-full border border-gray-300 rounded-xl px-5 py-4 focus:outline-none focus:ring-4 focus:ring-blue-300 bg-white text-gray-800 edit-input">

            <textarea name="description" rows="6"
                      class="w-full border border-gray-300 rounded-xl px-5 py-4 focus:outline-none focus:ring-4 focus:ring-blue-300 bg-white text-gray-800 edit-input"><?= htmlspecialchars($task['description']) ?></textarea>

            <div class="grid md:grid-cols-2 gap-5">
                <select name="status" class="w-full border border-gray-300 rounded-xl px-5 py-4 focus:outline-none focus:ring-4 focus:ring-blue-300 bg-white text-gray-800 edit-input">
                    <option value="pending"   <?= $task['status'] === 'pending'   ? 'selected' : '' ?>>Pending</option>
                    <option value="completed" <?= $task['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                </select>

                <select name="priority" class="w-full border border-gray-300 rounded-xl px-5 py-4 focus:outline-none focus:ring-4 focus:ring-blue-300 bg-white text-gray-800 edit-input">
                    <option value="high"   <?= $task['priority'] === 'high'   ? 'selected' : '' ?>>High Priority</option>
                    <option value="medium" <?= $task['priority'] === 'medium' ? 'selected' : '' ?>>Medium Priority</option>
                    <option value="low"    <?= $task['priority'] === 'low'    ? 'selected' : '' ?>>Low Priority</option>
                </select>
            </div>

            <div class="grid md:grid-cols-2 gap-5">
                <input type="date" name="due_date"
                       value="<?= htmlspecialchars($task['due_date'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-xl px-5 py-4 focus:outline-none focus:ring-4 focus:ring-blue-300 bg-white text-gray-800 edit-input">

                <select name="category_id" class="w-full border border-gray-300 rounded-xl px-5 py-4 focus:outline-none focus:ring-4 focus:ring-blue-300 bg-white text-gray-800 edit-input">
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['id'] ?>"
                                <?= (int) $task['category_id'] === (int) $category['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit"
                    class="w-full bg-indigo-500 hover:bg-indigo-600 text-white py-4 rounded-xl text-lg font-semibold transition">
                Update Task
            </button>
        </form>

    </div>

    <script src="../../assets/js/edit.js"></script>
</body>
</html>