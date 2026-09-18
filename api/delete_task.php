<?php
require_once __DIR__ . '/_bootstrap.php';

if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'DELETE'], true)) {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$taskId = isset($input['task_id']) ? (int) $input['task_id'] : 0;

if ($taskId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid task ID.']);
    exit;
}

// Ownership check first, so we can return a clear "not authorized"
// message instead of a silent no-op delete.
$check = $conn->prepare('SELECT id FROM tasks WHERE id = ? AND user_id = ?');
$check->bind_param('ii', $taskId, $currentUserId);
$check->execute();
$check->store_result();
if ($check->num_rows === 0) {
    $check->close();
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You are not authorized to delete this task.']);
    exit;
}
$check->close();

$stmt = $conn->prepare('DELETE FROM tasks WHERE id = ? AND user_id = ?');
$stmt->bind_param('ii', $taskId, $currentUserId);

if ($stmt->execute()) {
    $stmt->close();
    echo json_encode(['success' => true, 'message' => 'Task deleted successfully']);
} else {
    $stmt->close();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Something went wrong. Please try again.']);
}
