<?php
require_once __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$title = trim($input['title'] ?? '');
$description = trim($input['description'] ?? '');
$status = $input['status'] ?? 'Pending';
$dueDate = trim($input['due_date'] ?? '');

$validStatuses = ['Pending', 'In Progress', 'Completed'];

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

$dueDateParam = $dueDate !== '' ? $dueDate : null;

// user_id always comes from the session, never from the request body.
$stmt = $conn->prepare('INSERT INTO tasks (user_id, title, description, status, due_date)
                         VALUES (?, ?, ?, ?, ?)');
$stmt->bind_param('issss', $currentUserId, $title, $description, $status, $dueDateParam);

if ($stmt->execute()) {
    $newId = $stmt->insert_id;
    $stmt->close();
    echo json_encode([
        'success' => true,
        'message' => 'Task created successfully',
        'task_id' => $newId,
    ]);
} else {
    $stmt->close();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Something went wrong. Please try again.']);
}
