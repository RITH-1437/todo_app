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
$taskCreateDebug = defined('TASK_CREATE_DEBUG') ? (bool) constant('TASK_CREATE_DEBUG') : false;

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
        tailwind.config = {
            darkMode: 'class'
        };
    </script>

    <script>
        const savedTheme = localStorage.getItem('theme');
        const initialTheme = savedTheme === 'light' || savedTheme === 'dark'
            ? savedTheme
            : <?= json_encode($themePreference) ?>;

        document.documentElement.dataset.theme = initialTheme;
        document.documentElement.classList.toggle('dark', initialTheme === 'dark');
    </script>

    <script src="https://cdn.tailwindcss.com"></script>

</head>

<body id="create-page-body" data-theme="<?= htmlspecialchars($themePreference) ?>" class="create-page min-h-screen overflow-x-hidden bg-gradient-to-br from-slate-100 via-white to-slate-200 text-slate-900 dark:from-slate-950 dark:via-slate-900 dark:to-black dark:text-slate-100 transition-colors duration-300">

    <script>
        (function () {
            const theme = document.documentElement.dataset.theme || <?= json_encode($themePreference) ?>;
            document.body.dataset.theme = theme;
            document.body.classList.toggle('dark', theme === 'dark');
        }());
    </script>

    <main class="create-page-shell min-h-screen bg-transparent px-4 sm:px-6 lg:px-8 transition-colors duration-300">

        <div class="mx-auto flex min-h-screen w-full max-w-5xl items-center justify-center py-8 sm:py-10 lg:py-14 xl:py-16">

            <div class="w-full max-w-3xl xl:max-w-4xl">

                <div class="mb-5 flex items-center justify-between sm:mb-6">
                    <a href="../index.php" class="create-back-link text-sm font-medium transition">
                        Back to dashboard
                    </a>
                </div>

                <section class="form-card w-full overflow-hidden border border-slate-200/80 bg-white/90 shadow-[0_24px_60px_rgba(15,23,42,0.10)] backdrop-blur-xl dark:border-white/10 dark:bg-slate-900/70 dark:shadow-[0_30px_80px_rgba(2,6,23,0.5)] transition-colors duration-300">

                    <div class="form-card-glow"></div>

                    <div class="relative z-10">
                        <h1 class="create-title mb-2 text-center text-3xl font-bold tracking-tight sm:text-4xl lg:text-[2.6rem]">
                            Create Task
                        </h1>

                        <p class="create-subtitle mx-auto mb-6 max-w-2xl px-1 text-center text-xs sm:mb-8 sm:text-sm lg:text-base">
                            Plan your next move with a clean, focused workflow. Add details, set priority, and stay in control.
                        </p>

                        <form method="POST" class="space-y-4 sm:space-y-5" id="create-task-form">

                            <input type="hidden" name="create_task" value="1">

                            <div class="grid gap-4 sm:gap-5">
                                <div>
                                    <label class="form-label" for="task-title">Title</label>
                                    <input id="task-title" type="text" name="title" placeholder="Task title" required
                                        value="<?= htmlspecialchars($titleValue) ?>"
                                        class="form-input w-full border-slate-200/80 bg-white/90 text-slate-900 dark:border-white/10 dark:bg-slate-800/80 dark:text-slate-50 transition-colors duration-300">
                                </div>

                                <div>
                                    <label class="form-label" for="task-description">Description</label>
                                    <textarea id="task-description" name="description" placeholder="Task description" rows="5"
                                        class="form-input w-full resize-y border-slate-200/80 bg-white/90 text-slate-900 dark:border-white/10 dark:bg-slate-800/80 dark:text-slate-50 transition-colors duration-300"><?= htmlspecialchars($descriptionValue) ?></textarea>
                                </div>

                                <div>
                                    <label class="form-label" for="task-due-date">Due date</label>
                                    <input id="task-due-date" type="date" name="due_date"
                                        value="<?= htmlspecialchars($dueDateValue) ?>"
                                        class="form-input form-date-input w-full border-slate-200/80 bg-white/90 text-slate-900 dark:border-white/10 dark:bg-slate-800/80 dark:text-slate-50 transition-colors duration-300">
                                </div>

                                <div class="grid gap-4 sm:gap-5 md:grid-cols-2">
                                    <div>
                                        <label class="form-label" for="task-category">Category</label>
                                        <select id="task-category" name="category_id" class="form-input form-select w-full border-slate-200/80 bg-white/90 text-slate-900 dark:border-white/10 dark:bg-slate-800/80 dark:text-slate-50 transition-colors duration-300">

                                            <?php foreach ($categories as $category): ?>

                                                <option value="<?= $category['id'] ?>" <?= (string) $categoryIdValue === (string) $category['id'] ? 'selected' : '' ?>>

                                                    <?= htmlspecialchars($category['name']) ?>

                                                </option>

                                            <?php endforeach; ?>

                                        </select>
                                    </div>

                                    <div>
                                        <label class="form-label" for="task-priority">Priority</label>
                                        <select id="task-priority" name="priority" class="form-input form-select w-full border-slate-200/80 bg-white/90 text-slate-900 dark:border-white/10 dark:bg-slate-800/80 dark:text-slate-50 transition-colors duration-300">

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

                            <button type="submit" id="create-task-submit" class="form-button mt-1 w-full bg-blue-600 text-sm text-white shadow-[0_16px_34px_rgba(37,99,235,0.24)] transition-all duration-300 hover:bg-blue-500 sm:mt-2 sm:text-base dark:bg-blue-600 dark:text-white dark:shadow-[0_18px_42px_rgba(37,99,235,0.34)] dark:hover:bg-blue-500">
                                Add Task
                            </button>

                        </form>

                    </div>

                </section>

            </div>

        </div>

    </main>

    <div id="toast-container" class="fixed top-4 left-4 right-4 z-50 space-y-3 sm:top-5 sm:left-auto sm:right-5"></div>

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

    <?php if ($taskCreateDebug && $debugPost !== null): ?>
        <div class="mx-auto w-full max-w-3xl px-4 pb-8 sm:px-6 lg:px-8">
            <div class="mt-6 rounded-2xl border border-slate-200/80 bg-white/90 p-4 text-sm text-slate-700 shadow-lg shadow-slate-200/70 backdrop-blur dark:border-slate-700/70 dark:bg-slate-900/80 dark:text-slate-200 dark:shadow-black/30 transition-colors duration-300">
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
