<?php
/**
 * Reusable task card component.
 *
 * Required variables:
 *   array  $task            Task row from DB (includes category_name)
 *   bool   $isOverdue       Whether this task is overdue
 *   array  $taskComments    Pre-fetched comments for this task
 */
?>
<div class="task-card <?= $isOverdue ? 'task-card--overdue' : '' ?>">
    <div class="task-card__row">
        <div class="task-card__body">
            <h2 class="task-card__title <?= $isOverdue ? 'task-card__title--overdue' : '' ?>">
                <?= htmlspecialchars($task['title']) ?>
            </h2>

            <?php if (!empty($task['description'])): ?>
                <p class="task-card__desc <?= $isOverdue ? 'task-card__desc--overdue' : '' ?>">
                    <?= nl2br(htmlspecialchars($task['description'])) ?>
                </p>
            <?php endif; ?>

            <div class="task-card__badges">
                <?php if (!empty($task['category_name'])): ?>
                    <span class="task-badge task-badge--category">
                        #<?= htmlspecialchars($task['category_name']) ?>
                    </span>
                <?php endif; ?>

                <span class="task-badge task-badge--priority task-badge--priority-<?= htmlspecialchars($task['priority']) ?>">
                    <?= ucfirst(htmlspecialchars($task['priority'])) ?> Priority
                </span>

                <?php if (!empty($task['due_date'])): ?>
                    <span class="task-badge task-badge--due <?= $isOverdue ? 'task-badge--overdue-due' : '' ?>">
                        <img src="../assets/images/date.png" alt="" class="task-badge__icon" aria-hidden="true">
                        <?= formatDate($task['due_date']) ?>
                    </span>
                <?php endif; ?>

                <?php if ($isOverdue): ?>
                    <?php $daysLate = daysOverdue($task['due_date']); ?>
                    <span class="task-badge task-badge--late">
                        <span class="task-badge__warn" aria-hidden="true">!</span>
                        <?= $daysLate ?> day<?= $daysLate !== 1 ? 's' : '' ?> overdue
                    </span>
                <?php endif; ?>
            </div>
        </div><!-- /task-card__body -->

        <div class="task-card__actions">
            <span class="task-status-badge <?= $task['status'] === 'completed' ? 'task-status-badge--done' : 'task-status-badge--pending' ?>">
                <?= htmlspecialchars($task['status']) ?>
            </span>
            <div class="task-card__btns">
                <a href="tasks/edit.php?id=<?= (int) $task['id'] ?>" class="task-btn task-btn--edit">
                    Edit
                </a>
                <!-- POST-based delete for CSRF safety -->
                <form method="POST" action="tasks/delete.php" class="inline"
                      onsubmit="return confirm('Delete this task?')">
                    <input type="hidden" name="id" value="<?= (int) $task['id'] ?>">
                    <button type="submit" class="task-btn task-btn--delete">Delete</button>
                </form>
            </div>
        </div>
    </div><!-- /task-card__row -->

    <!-- Comment form -->
    <div class="mt-5">
        <form method="POST" action="comments/store.php" class="comment-form">
            <input type="hidden" name="task_id" value="<?= (int) $task['id'] ?>">
            <input type="text" name="message" placeholder="Add comment…" required
                   class="comment-input <?= $isOverdue ? 'overdue-input' : '' ?>">
            <button type="submit" class="comment-submit comment-button">Add</button>
        </form>
    </div>

    <!-- Comments list -->
    <h3 class="comment-heading mt-5 mb-3 <?= $isOverdue ? 'overdue-heading' : '' ?>">Comments</h3>

    <div class="comments-list space-y-3 mt-3">
        <?php foreach ($taskComments as $comment): ?>
            <?php require __DIR__ . '/comment_card.php'; ?>
        <?php endforeach; ?>
    </div>

</div><!-- /task-card -->
