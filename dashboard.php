<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/config/database.php';

$userId = current_user_id();

// Fetch only this user's tasks - never trust any ID from the request here.
$stmt = $conn->prepare('SELECT id, title, description, status, due_date, created_at
                         FROM tasks
                         WHERE user_id = ?
                         ORDER BY created_at DESC');
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
$tasks = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$deleted = isset($_GET['deleted']);
$created = isset($_GET['created']);
$updated = isset($_GET['updated']);

function status_class(string $status): string {
    return match ($status) {
        'Pending' => 'status-pending',
        'In Progress' => 'status-progress',
        'Completed' => 'status-completed',
        default => '',
    };
}

$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<div class="dashboard-wrap">
    <div class="dashboard-head">
        <div>
            <h1>Your tasks</h1>
            <p class="muted"><?php echo count($tasks); ?> total</p>
        </div>
        <a href="add_task.php" class="btn btn-primary">+ Add Task</a>
    </div>

    <?php if ($created): ?>
        <div class="alert alert-success">Task created successfully.</div>
    <?php endif; ?>
    <?php if ($updated): ?>
        <div class="alert alert-success">Task updated successfully.</div>
    <?php endif; ?>
    <?php if ($deleted): ?>
        <div class="alert alert-success">Task deleted successfully.</div>
    <?php endif; ?>

    <div id="task-list" class="task-grid">
        <?php if (empty($tasks)): ?>
            <div class="empty-state">
                <p>You don't have any tasks yet.</p>
                <a href="add_task.php" class="btn btn-primary">Create your first task</a>
            </div>
        <?php else: ?>
            <?php foreach ($tasks as $task): ?>
                <div class="task-card" data-task-id="<?php echo (int)$task['id']; ?>">
                    <div class="task-card-top">
                        <h3><?php echo htmlspecialchars($task['title']); ?></h3>
                        <span class="badge <?php echo status_class($task['status']); ?>">
                            <?php echo htmlspecialchars($task['status']); ?>
                        </span>
                    </div>
                    <?php if (!empty($task['description'])): ?>
                        <p class="task-desc"><?php echo nl2br(htmlspecialchars($task['description'])); ?></p>
                    <?php endif; ?>
                    <div class="task-meta">
                        <?php if (!empty($task['due_date'])): ?>
                            <span>Due: <?php echo htmlspecialchars(date('d M Y', strtotime($task['due_date']))); ?></span>
                        <?php endif; ?>
                        <span>Created: <?php echo htmlspecialchars(date('d M Y', strtotime($task['created_at']))); ?></span>
                    </div>
                    <div class="task-actions">
                        <a href="edit_task.php?id=<?php echo (int)$task['id']; ?>" class="btn btn-outline btn-sm">Edit</a>
                        <button
                            class="btn btn-danger btn-sm js-delete-task"
                            data-task-id="<?php echo (int)$task['id']; ?>"
                        >Delete</button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script src="js/app.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
