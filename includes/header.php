<?php
// Expects session already started (auth.php should be required before this
// on protected pages). Safe to include on public pages too.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$loggedIn = isset($_SESSION['user_id']);
$userName = $_SESSION['user_name'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' · Task Manager' : 'Task Manager'; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<header class="site-header">
    <div class="header-inner">
        <a href="<?php echo $loggedIn ? 'dashboard.php' : 'index.php'; ?>" class="brand">
    <span class="brand-logo">✓</span>
    <span>Task Manager</span>
</a>
        <?php if ($loggedIn): ?>
            <nav class="header-nav">
                <span class="welcome-text">Welcome, <?php echo htmlspecialchars($userName); ?></span>
                <a href="add_task.php" class="btn btn-primary btn-sm">+ Add Task</a>
                <a href="logout.php" class="btn btn-outline btn-sm">Logout</a>
            </nav>
        <?php endif; ?>
    </div>
</header>
<main class="site-main">
