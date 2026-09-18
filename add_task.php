<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/config/database.php';

$userId = current_user_id();
$errors = [];
$title = $description = '';
$status = 'Pending';
$dueDate = '';

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
        $stmt = $conn->prepare('INSERT INTO tasks (user_id, title, description, status, due_date)
                                 VALUES (?, ?, ?, ?, ?)');
        $dueDateParam = $dueDate !== '' ? $dueDate : null;
        $stmt->bind_param('issss', $userId, $title, $description, $status, $dueDateParam);

        if ($stmt->execute()) {
            $stmt->close();
            header('Location: dashboard.php?created=1');
            exit;
        } else {
            $errors[] = 'Something went wrong. Please try again.';
        }
        $stmt->close();
    }
}

$pageTitle = 'Add Task';
require_once __DIR__ . '/includes/header.php';
?>

<div class="form-wrap">
    <h1>Add a task</h1>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="add_task.php">
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
            <button type="submit" class="btn btn-primary">Add Task</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
