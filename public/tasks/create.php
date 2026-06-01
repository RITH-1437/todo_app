<?php

require '../../includes/auth.php';

require '../../config/database.php';

$categorySql = "

SELECT *

FROM categories

ORDER BY name ASC

";

$categoryStmt = $conn->query($categorySql);

$categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

$themePreference = ($_SESSION['theme_preference'] ?? 'dark') === 'light' ? 'light' : 'dark';

$categoryIds = array_column($categories, 'id');
$formError = null;
$submitted = false;

$titleValue = '';
$descriptionValue = '';
$priorityValue = 'medium';
$dueDateValue = '';
$categoryIdValue = $categoryIds[0] ?? '';
$debugPost = null;
$debugError = null;

if (isset($_POST['create_task'])) {

    $submitted = true;
    $debugPost = $_POST;

    $title = trim($_POST['title'] ?? '');

    $description = trim($_POST['description'] ?? '');

    $priority = $_POST['priority'] ?? 'medium';

    $due_date = trim($_POST['due_date'] ?? '');

    $category_id = (int) ($_POST['category_id'] ?? 0);

    $titleValue = $title;
    $descriptionValue = $description;
    $priorityValue = $priority;
    $dueDateValue = $due_date;
    $categoryIdValue = $category_id;

    $validPriorities = ['low', 'medium', 'high'];

    if ($title === '') {
        $formError = 'Task title is required.';
    } elseif (!in_array($priority, $validPriorities, true)) {
        $formError = 'Invalid priority selected.';
    } elseif (!in_array($category_id, array_map('intval', $categoryIds), true)) {
        $formError = 'Invalid category selected.';
    } elseif ($due_date !== '') {
        $dueDateObject = DateTime::createFromFormat('Y-m-d', $due_date);
        $dueDateIsValid = $dueDateObject && $dueDateObject->format('Y-m-d') === $due_date;

        if (!$dueDateIsValid) {
            $formError = 'Invalid due date.';
        }
    }

    if ($formError) {
        // Keep the user on this page and show a toast while preserving input values.
    } else {

        $user_id = $_SESSION['user_id'];

        $sql = "

    INSERT INTO tasks (
        user_id,
        title,
        description,
        priority,
        due_date
    )

    VALUES (
        :user_id,
        :title,
        :description,
        :priority,
        :due_date
    )

    ";

        try {
            $conn->beginTransaction();

            $stmt = $conn->prepare($sql);

            $stmt->execute([

                ':user_id' => $user_id,

                ':title' => $title,

                ':description' => $description,

                ':priority' => $priority,

                ':due_date' => !empty($due_date) ? $due_date : null

            ]);

            $task_id = $conn->lastInsertId();

            $taskCategorySql = "

        INSERT INTO task_categories (
            task_id,
            category_id
        )

        VALUES (
            :task_id,
            :category_id
        )

";

            $taskCategoryStmt = $conn->prepare($taskCategorySql);

            $taskCategoryStmt->execute([

                ':task_id' => $task_id,

                ':category_id' => $category_id
            ]);

            $conn->commit();

            setToast('Task created successfully!', 'success');
            redirect('../index.php');
        } catch (Throwable $exception) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            $debugError = $exception->getMessage();
            $formError = 'Task creation failed. Please try again.';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create Task</title>

    <link rel="stylesheet" href="../../assets/css/style.css">

    <script>
        const savedTheme = localStorage.getItem('theme');
        const initialTheme = savedTheme === 'light' || savedTheme === 'dark'
            ? savedTheme
            : <?= json_encode($themePreference) ?>;
        document.documentElement.dataset.theme = initialTheme;
    </script>

    <script src="https://cdn.tailwindcss.com"></script>

</head>

<body id="create-page-body" data-theme="<?= htmlspecialchars($themePreference) ?>" class="create-page min-h-screen transition duration-300">
    <script>(function(){var t=localStorage.getItem('theme');if(t==='light'||t==='dark'){document.body.dataset.theme=t;document.documentElement.dataset.theme=t;}}());</script>

    <main class="create-page-shell px-4 py-10 sm:px-6 lg:px-8">

        <div class="mx-auto w-full max-w-3xl">

            <div class="mb-6 flex items-center justify-between">
                <a href="../index.php" class="create-back-link text-sm font-medium transition">
                    Back to dashboard
                </a>
            </div>

            <section class="form-card w-full">

                <div class="form-card-glow"></div>

                <div class="relative z-10">
                    <h1 class="create-title mb-2 text-center text-4xl font-bold tracking-tight">
                        Create Task
                    </h1>

                    <p class="create-subtitle mx-auto mb-8 max-w-xl text-center text-sm sm:text-base">
                        Plan your next move with a clean, focused workflow. Add details, set priority, and stay in control.
                    </p>

                    <form method="POST" class="space-y-5" id="create-task-form">

                        <input type="hidden" name="create_task" value="1">

                        <div class="grid gap-5">
                            <div>
                                <label class="form-label" for="task-title">Title</label>
                                <input id="task-title" type="text" name="title" placeholder="Task title" required
                                    value="<?= htmlspecialchars($titleValue) ?>"
                                    class="form-input w-full">
                            </div>

                            <div>
                                <label class="form-label" for="task-description">Description</label>
                                <textarea id="task-description" name="description" placeholder="Task description" rows="5"
                                    class="form-input w-full resize-y"><?= htmlspecialchars($descriptionValue) ?></textarea>
                            </div>

                            <div>
                                <label class="form-label" for="task-due-date">Due date</label>
                                <input id="task-due-date" type="date" name="due_date"
                                    value="<?= htmlspecialchars($dueDateValue) ?>"
                                    class="form-input form-date-input w-full">
                            </div>

                            <div class="grid gap-5 md:grid-cols-2">
                                <div>
                                    <label class="form-label" for="task-category">Category</label>
                                    <select id="task-category" name="category_id" class="form-input form-select w-full">

                                        <?php foreach ($categories as $category): ?>

                                            <option value="<?= $category['id'] ?>" <?= (string) $categoryIdValue === (string) $category['id'] ? 'selected' : '' ?>>

                                                <?= htmlspecialchars($category['name']) ?>

                                            </option>

                                        <?php endforeach; ?>

                                    </select>
                                </div>

                                <div>
                                    <label class="form-label" for="task-priority">Priority</label>
                                    <select id="task-priority" name="priority" class="form-input form-select w-full">

                                        <option value="low" <?= $priorityValue === 'low' ? 'selected' : '' ?>>
                                            Low Priority
                                        </option>

                                        <option value="medium" <?= $priorityValue === 'medium' ? 'selected' : '' ?>>
                                            Medium Priority
                                        </option>

                                        <option value="high" <?= $priorityValue === 'high' ? 'selected' : '' ?>>
                                            High Priority
                                        </option>

                                    </select>
                                </div>
                            </div>
                        </div>

                        <button type="submit" id="create-task-submit" class="form-button mt-2 w-full">
                            Add Task
                        </button>

                    </form>

                </div>

            </section>

        </div>

    </main>

    <div id="toast-container" class="fixed top-5 right-5 z-50 space-y-3"></div>

    <script src="../../assets/js/toast.js"></script>
    <script src="../../assets/js/create-task.js"></script>

    <?php if ($formError): ?>
        <script>
            showToast(
                <?= json_encode($formError) ?>,
                'error'
            );
        </script>
    <?php elseif ($submitted): ?>
        <script>
            showToast('Validation failed. Please check the form values.', 'warning');
        </script>
    <?php endif; ?>

    <?php if (TASK_CREATE_DEBUG && $debugPost !== null): ?>
        <div class="mx-auto w-full max-w-3xl px-4 pb-8 sm:px-6 lg:px-8">
            <div class="mt-6 rounded-2xl border border-amber-300/40 bg-amber-50/90 p-4 text-sm text-amber-950 shadow-lg shadow-amber-500/10 backdrop-blur">
                <p class="mb-3 font-semibold">Create Task debug</p>
                <pre class="overflow-auto whitespace-pre-wrap break-words text-xs leading-6"><?php var_dump($debugPost); ?></pre>
                <?php if ($debugError): ?>
                    <p class="mt-3 font-semibold">PDO error</p>
                    <pre class="overflow-auto whitespace-pre-wrap break-words text-xs leading-6"><?= htmlspecialchars($debugError) ?></pre>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

</body>

</html>
