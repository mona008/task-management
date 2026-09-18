<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/config/database.php';

$userId = current_user_id();
$taskId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($taskId <= 0) {
    header('Location: dashboard.php');
    exit;
}

// Load the task - MUST match both id AND user_id, or a user could edit
// someone else's task just by changing the URL.
$stmt = $conn->prepare('SELECT id, title, description, status, due_date
                         FROM tasks WHERE id = ? AND user_id = ?');
$stmt->bind_param('ii', $taskId, $userId);
$stmt->execute();
$task = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$task) {
    // Either the task doesn't exist, or it belongs to someone else.
    // Treat both the same way so we don't leak which case it is.
    header('Location: dashboard.php?unauthorized=1');
    exit;
}

$errors = [];
$title = $task['title'];
$description = $task['description'];
$status = $task['status'];
$dueDate = $task['due_date'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'Pending';
    $dueDate = trim($_POST['due_date'] ?? '');

    $validStatuses = ['Pending', 'In Progress', 'Completed'];

    if ($title === '') {
        $errors[] = 'Task title is required.';
    }
    if (!in_array($status, $validStatuses, true)) {
        $errors[] = 'Invalid status selected.';
    }
    if ($dueDate !== '' && !DateTime::createFromFormat('Y-m-d', $dueDate)) {
        $errors[] = 'Invalid due date.';
    }

    if (empty($errors)) {
        $dueDateParam = $dueDate !== '' ? $dueDate : null;
        $stmt = $conn->prepare('UPDATE tasks
                                 SET title = ?, description = ?, status = ?, due_date = ?
                                 WHERE id = ? AND user_id = ?');
        $stmt->bind_param('ssssii', $title, $description, $status, $dueDateParam, $taskId, $userId);

        if ($stmt->execute()) {
            $stmt->close();
            header('Location: dashboard.php?updated=1');
            exit;
        } else {
            $errors[] = 'Something went wrong. Please try again.';
        }
        $stmt->close();
    }
}

$pageTitle = 'Edit Task';
require_once __DIR__ . '/includes/header.php';
?>

<div class="form-wrap">
    <h1>Edit task</h1>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="edit_task.php?id=<?php echo (int)$taskId; ?>">
        <label for="title">Title</label>
        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" required>

        <label for="description">Description</label>
        <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($description); ?></textarea>

        <label for="status">Status</label>
        <select id="status" name="status">
            <?php foreach (['Pending', 'In Progress', 'Completed'] as $s): ?>
                <option value="<?php echo $s; ?>" <?php echo $status === $s ? 'selected' : ''; ?>><?php echo $s; ?></option>
            <?php endforeach; ?>
        </select>

        <label for="due_date">Due date</label>
        <input type="date" id="due_date" name="due_date" value="<?php echo htmlspecialchars($dueDate); ?>">

        <div class="form-actions">
            <a href="dashboard.php" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
