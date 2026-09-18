<?php
require_once __DIR__ . '/_bootstrap.php';

$stmt = $conn->prepare('SELECT id, title, description, status, due_date, created_at, updated_at
                         FROM tasks
                         WHERE user_id = ?
                         ORDER BY created_at DESC');
$stmt->bind_param('i', $currentUserId);
$stmt->execute();
$result = $stmt->get_result();
$tasks = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode(['success' => true, 'tasks' => $tasks]);
