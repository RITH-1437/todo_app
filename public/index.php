<?php
// ── Bootstrap ─────────────────────────────────────────────────────────────────
require '../includes/auth.php';      
require '../config/database.php';    

// ── Current user ──────────────────────────────────────────────────────────────
$userColumnsStmt = $conn->query('SHOW COLUMNS FROM users');
$userColumns     = $userColumnsStmt->fetchAll(PDO::FETCH_COLUMN);

$currentUser = fetchCurrentUser($conn, $userColumns);
if (!$currentUser) {
    redirect('auth/login.php');
}

$_SESSION['user_name'] = $currentUser['name'];

$userAvatarPath   = getSafeAvatarPath($currentUser['avatar'] ?? null);
$userAvatarUrl    = $userAvatarPath;
$defaultAvatarUrl = DEFAULT_AVATAR;
$themePreference  = ($currentUser['theme_preference'] ?? 'dark') === 'light' ? 'light' : 'dark';

// ── Request filters ───────────────────────────────────────────────────────────
$status = isset($_GET['status']) ? trim($_GET['status'])  : '';
$search = isset($_GET['search']) ? trim($_GET['search'])  : '';
$sort   = isset($_GET['sort'])   ? trim($_GET['sort'])    : 'latest';
$isAjax = ($_GET['ajax'] ?? '') === 'tasks';

function escapeLikeTerm(string $value): string
{
    return addcslashes($value, "\\%_");
}

function renderTaskListHtml(array $tasks, array $commentsByTask): string
{
    ob_start();
    ?>
    <div id="task-list" class="<?= !empty($tasks) ? 'grid gap-5' : '' ?>">
        <?php if (!empty($tasks)): ?>
            <?php foreach ($tasks as $task):
                $isOverdue    = isTaskOverdue($task);
                $taskComments = $commentsByTask[(int) $task['id']] ?? [];
                require __DIR__ . '/../includes/components/task_card.php';
            endforeach; ?>
        <?php else: ?>
            <div id="no-tasks" class="bg-white p-10 rounded-2xl shadow text-center transition duration-300">
                <p class="no-tasks-text text-gray-500 text-lg transition duration-300">No tasks found.</p>
            </div>
        <?php endif; ?>
    </div>
    <?php
    return trim(ob_get_clean());
}

// ── Build task query ──────────────────────────────────────────────────────────
$conditions = ['t.user_id = :user_id'];
$params     = [':user_id' => $_SESSION['user_id']];

$allowedStatuses = ['pending', 'completed'];
if ($status !== '' && in_array($status, $allowedStatuses, true)) {
    $conditions[]      = 't.status = :status';
    $params[':status'] = $status;
}

if ($search !== '') {
    $conditions[]      = "LOWER(t.title) LIKE LOWER(:search) ESCAPE '\\\\'";
    $params[':search'] = '%' . escapeLikeTerm($search) . '%';
}

$where   = 'WHERE ' . implode(' AND ', $conditions);
$orderBy = match ($sort) {
    'oldest'   => 'ORDER BY t.id ASC',
    'priority' => "ORDER BY FIELD(t.priority, 'high', 'medium', 'low')",
    'due_date' => 'ORDER BY t.due_date ASC',
    default    => 'ORDER BY t.id DESC',
};

$sql = "SELECT t.*, c.name AS category_name
        FROM tasks t
        LEFT JOIN task_categories tc ON tc.task_id = t.id
        LEFT JOIN categories c       ON c.id = tc.category_id
        {$where}
        {$orderBy}";

try {
    $stmt  = $conn->prepare($sql);
    $stmt->execute($params);
    $tasks = $stmt->fetchAll();
} catch (PDOException $e) {
    $tasks = [];
    error_log('Task query error: ' . $e->getMessage());
}

// ── Batch-fetch all comments (eliminates N+1 queries) ────────────────────────
$commentsByTask = [];
if (!empty($tasks)) {
    $taskIds      = array_column($tasks, 'id');
    $placeholders = implode(',', array_fill(0, count($taskIds), '?'));
    $cStmt        = $conn->prepare(
        "SELECT * FROM comments WHERE task_id IN ({$placeholders}) ORDER BY id DESC"
    );
    $cStmt->execute($taskIds);
    foreach ($cStmt->fetchAll() as $c) {
        $commentsByTask[(int) $c['task_id']][] = $c;
    }
}

// ── Analytics counters ────────────────────────────────────────────────────────
$total_tasks     = count($tasks);
$completed_tasks = 0;
$pending_tasks   = 0;
$overdue_tasks   = 0;

foreach ($tasks as $task) {
    if ($task['status'] === 'completed') {
        $completed_tasks++;
    } else {
        $pending_tasks++;
        if (isTaskOverdue($task)) {
            $overdue_tasks++;
        }
    }
}

if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode([
        'html' => renderTaskListHtml($tasks, $commentsByTask),
        'stats' => [
            'total' => $total_tasks,
            'completed' => $completed_tasks,
            'pending' => $pending_tasks,
            'overdue' => $overdue_tasks,
        ],
    ]);
    exit;
}

// ── Flash toast ───────────────────────────────────────────────────────────────
$toast = getToast();

// ── Layout variables ──────────────────────────────────────────────────────────
$pageTitle   = 'Dashboard';
$headScripts = ['https://cdn.jsdelivr.net/npm/chart.js'];
$pageScripts = [
    '../assets/js/app.js',
    '../assets/js/chart.js',
    '../assets/js/comments.js',
];

require '../includes/layouts/app.php';
?>

        <!-- Search bar -->
        <form id="search-form" method="GET" class="mb-6">
            <input id="search-input"
                   type="text"
                   name="search"
                   placeholder="Search tasks..."
                   value="<?= htmlspecialchars($search) ?>"
                   class="search-input w-full rounded-2xl px-5 py-4 bg-slate-900/80 text-white placeholder-slate-400 border border-white/10 shadow-[0_18px_45px_rgba(15,23,42,0.24)] hover:shadow-[0_22px_55px_rgba(15,23,42,0.32)] focus:outline-none focus:border-blue-400/50 focus:ring-4 focus:ring-blue-500/20 focus:shadow-[0_24px_65px_rgba(59,130,246,0.18)] transition-all duration-300">
            <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">
            <input type="hidden" name="sort"   value="<?= htmlspecialchars($sort) ?>">
        </form>

        <!-- Filters + sort -->
        <div class="flex justify-between items-center mb-6">
            <div class="flex gap-3">
                <a href="?status=&amp;search=<?= urlencode($search) ?>&amp;sort=<?= urlencode($sort) ?>"
                   data-status-filter=""
                   class="px-5 py-2 rounded-lg shadow text-white <?= ($status === '') ? 'bg-blue-500' : 'bg-gray-400' ?>">All</a>
                <a href="?status=pending&amp;search=<?= urlencode($search) ?>&amp;sort=<?= urlencode($sort) ?>"
                   data-status-filter="pending"
                   class="px-5 py-2 rounded-lg shadow text-white <?= ($status === 'pending') ? 'bg-yellow-500' : 'bg-gray-400' ?>">Pending</a>
                <a href="?status=completed&amp;search=<?= urlencode($search) ?>&amp;sort=<?= urlencode($sort) ?>"
                   data-status-filter="completed"
                   class="px-5 py-2 rounded-lg shadow text-white <?= ($status === 'completed') ? 'bg-green-500' : 'bg-gray-400' ?>">Completed</a>
            </div>

            <div class="flex gap-4 items-center">
                <form method="GET" id="sort-form">
                    <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                    <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">
                    <select name="sort" id="sort-select"
                            class="bg-gray-700 text-white px-4 py-3 rounded-lg border border-gray-600">
                        <option value="latest"   <?= $sort === 'latest'   ? 'selected' : '' ?>>Latest</option>
                        <option value="oldest"   <?= $sort === 'oldest'   ? 'selected' : '' ?>>Oldest</option>
                        <option value="priority" <?= $sort === 'priority' ? 'selected' : '' ?>>Priority</option>
                        <option value="due_date" <?= $sort === 'due_date' ? 'selected' : '' ?>>Due Date</option>
                    </select>
                </form>

                <a href="tasks/create.php"
                   class="bg-blue-500 hover:bg-blue-600 text-white px-5 py-3 rounded-lg shadow">+ Add New Task</a>
            </div>
        </div>

        <!-- Analytics grid + chart -->
        <div class="w-full grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_minmax(380px,1.15fr)] items-stretch gap-8 lg:gap-10 mb-12">

            <!-- 2x2 stat cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 auto-rows-fr gap-6 lg:gap-7">
                <?php
                $cards = [
                    ['id' => 'total-card',     'label' => 'Total Tasks', 'value' => $total_tasks,     'color' => 'blue'],
                    ['id' => 'completed-card', 'label' => 'Completed',   'value' => $completed_tasks, 'color' => 'green'],
                    ['id' => 'pending-card',   'label' => 'Pending',     'value' => $pending_tasks,   'color' => 'yellow'],
                    ['id' => 'overdue-card',   'label' => 'Overdue',     'value' => $overdue_tasks,   'color' => 'red'],
                ];
                foreach ($cards as $c):
                    ['id' => $id, 'label' => $label, 'value' => $value, 'color' => $color] = $c;
                    require __DIR__ . '/../includes/components/analytics_card.php';
                endforeach;
                ?>
            </div>

            <!-- Donut chart -->
            <div class="flex items-center">
                <div id="chart-card"
                     class="chart-card w-full min-h-[360px] lg:min-h-full bg-white/[0.07] backdrop-blur-xl border border-white/10 rounded-2xl p-8 lg:p-10 shadow-[0_24px_80px_rgba(15,23,42,0.42)] flex flex-col items-center justify-center transition-all duration-300 hover:-translate-y-1.5 hover:border-blue-300/20 hover:shadow-[0_26px_90px_rgba(14,165,233,0.16)]">
                    <h2 class="chart-title w-full text-white text-2xl font-bold mb-8 tracking-tight transition-colors duration-300">Task Status</h2>
                    <div class="w-full flex flex-1 items-center justify-center">
                        <canvas id="taskChart" class="!max-w-[360px] !max-h-[360px] aspect-square"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Task list -->
        <?= renderTaskListHtml($tasks, $commentsByTask) ?>

<?php require '../includes/layouts/app_end.php'; ?>
