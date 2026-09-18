<?php
require_once __DIR__ . '/_bootstrap.php';

if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT'], true)) {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$taskId = isset($input['task_id']) ? (int) $input['task_id'] : 0;
$title = trim($input['title'] ?? '');
$description = trim($input['description'] ?? '');
$status = $input['status'] ?? 'Pending';
$dueDate = trim($input['due_date'] ?? '');

$validStatuses = ['Pending', 'In Progress', 'Completed'];

if ($taskId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid task ID.']);
    exit;
}
if ($title === '') {
    echo json_encode(['success' => false, 'message' => 'Task title is required.']);
    exit;
}
if (!in_array($status, $validStatuses, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status selected.']);
    exit;
}
if ($dueDate !== '' && !DateTime::createFromFormat('Y-m-d', $dueDate)) {
    echo json_encode(['success' => false, 'message' => 'Invalid due date.']);
    exit;
}

// First confirm this task actually belongs to the logged-in user.
$check = $conn->prepare('SELECT id FROM tasks WHERE id = ? AND user_id = ?');
$check->bind_param('ii', $taskId, $currentUserId);
$check->execute();
$check->store_result();
if ($check->num_rows === 0) {
    $check->close();
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You are not authorized to modify this task.']);
    exit;
}
$check->close();

$dueDateParam = $dueDate !== '' ? $dueDate : null;

// The WHERE clause still includes user_id as a second guard, never rely on
// the id alone even after the check above.
$stmt = $conn->prepare('UPDATE tasks
                         SET title = ?, description = ?, status = ?, due_date = ?
                         WHERE id = ? AND user_id = ?');
$stmt->bind_param('ssssii', $title, $description, $status, $dueDateParam, $taskId, $currentUserId);

if ($stmt->execute()) {
    $stmt->close();
    echo json_encode(['success' => true, 'message' => 'Task updated successfully']);
} else {
    $stmt->close();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Something went wrong. Please try again.']);
}
