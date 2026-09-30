<?php
session_start();
require 'db.php';

// 1. Security Check: Authenticated Users Only
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$userId   = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? 'User';
$message  = "";
$error    = "";

// 2. Handle Task Operations (ADD, TOGGLE/COMPLETE STATUS, REMOVE)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    // ACTION: Add a New Task
    if (isset($_POST['action']) && $_POST['action'] === 'add_task') {
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (!empty($title)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO tasks (user_id, username, title, description, status) VALUES (:user_id, :username, :title, :description, 'Pending')");
                $stmt->execute(['user_id' => $userId, 'username' => $username, 'title' => $title, 'description' => $description]);
                $message = "Task added successfully.";
            } catch (PDOException $e) {
                // Fallback insert if column structure differs
                $stmt = $pdo->prepare("INSERT INTO tasks (user_id, title, description, status) VALUES (:user_id, :title, :description, 'Pending')");
                if ($stmt->execute(['user_id' => $userId, 'title' => $title, 'description' => $description])) {
                    $message = "Task added successfully.";
                } else {
                    $error = "Failed to add task.";
                }
            }
        } else {
            $error = "Task title is required.";
        }
    }

    // ACTION: Toggle or Set Task Status (Pending <-> Completed)
    if (isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
        $taskId        = $_POST['task_id'] ?? null;
        $targetStatus  = $_POST['target_status'] ?? '';
        $currentStatus = $_POST['current_status'] ?? 'Pending';

        if (!empty($targetStatus)) {
            $newStatus = $targetStatus;
        } else {
            $newStatus = (strcasecmp(trim($currentStatus), 'Completed') === 0) ? 'Pending' : 'Completed';
        }

        if ($taskId) {
            // Update status by matching task ID and session credentials
            $stmt = $pdo->prepare("UPDATE tasks SET status = :status WHERE id = :id");
            if ($stmt->execute(['status' => $newStatus, 'id' => $taskId])) {
                $message = "Task updated to '$newStatus'.";
            } else {
                $error = "Failed to update task.";
            }
        }
    }

    // ACTION: Remove Task
    if (isset($_POST['action']) && $_POST['action'] === 'remove_task') {
        $taskId = $_POST['task_id'] ?? null;

        if ($taskId) {
            $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = :id");
            if ($stmt->execute(['id' => $taskId])) {
                $message = "Task removed.";
            }
        }
    }
}

// 3. Fetch Tasks for Logged-In User
$tasks = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE user_id = :user_id OR username = :username ORDER BY id DESC");
    $stmt->execute(['user_id' => $userId, 'username' => $username]);
    $tasks = $stmt->fetchAll();
} catch (PDOException $e) {
    // Fallback if schema doesn't have both columns
    try {
        $stmt = $pdo->prepare("SELECT * FROM tasks WHERE user_id = :user_id ORDER BY id DESC");
        $stmt->execute(['user_id' => $userId]);
        $tasks = $stmt->fetchAll();
    } catch (PDOException $ex) {
        $tasks = [];
    }
}

$currentUser = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Portal | Workspace</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #3b82f6;
            --primary-hover: #2563eb;
            --bg-body: #0f172a;
            --card-bg: #1e293b;
            --card-border: #334155;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --danger: #ef4444;
            --danger-hover: #dc2626;
            --success-bg: rgba(34, 197, 94, 0.15);
            --success-text: #4ade80;
            --success-border: rgba(34, 197, 94, 0.3);
            --info-bg: rgba(59, 130, 246, 0.15);
            --info-text: #60a5fa;
            --info-border: rgba(59, 130, 246, 0.3);
            --warning-bg: rgba(245, 158, 11, 0.15);
            --warning-text: #fbbf24;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        body {
            min-height: 100vh;
            background-color: var(--bg-body);
            color: var(--text-main);
            display: flex;
            flex-direction: column;
        }

        .navbar {
            background-color: var(--card-bg);
            border-bottom: 1px solid var(--card-border);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar-brand {
            font-size: 1.25rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 0.9rem;
        }

        .nav-links a {
            color: var(--text-muted);
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .nav-links a:hover {
            color: var(--text-main);
        }

        .nav-divider {
            color: var(--card-border);
        }

        .btn-logout {
            background-color: transparent;
            color: var(--text-muted) !important;
            border: 1px solid var(--card-border);
            padding: 0.45rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s ease !important;
        }

        .btn-logout:hover {
            background-color: var(--danger) !important;
            color: #ffffff !important;
            border-color: var(--danger) !important;
        }

        .container {
            max-width: 1000px;
            width: 100%;
            margin: 2rem auto;
            padding: 0 1.5rem;
            flex: 1;
        }

        .welcome-title {
            font-size: 1.85rem;
            font-weight: 700;
            margin-bottom: 1.25rem;
        }

        .welcome-title span {
            color: #60a5fa;
        }

        .alert {
            padding: 0.85rem 1.25rem;
            border-radius: 8px;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            animation: fadeIn 0.3s ease;
        }

        .alert-info {
            background-color: var(--info-bg);
            color: var(--info-text);
            border: 1px solid var(--info-border);
        }

        .alert-error {
            background-color: rgba(239, 68, 68, 0.15);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .panel {
            background-color: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            padding: 1.75rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
        }

        .panel-header {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            font-size: 0.85rem;
            color: var(--text-muted);
            font-weight: 500;
            margin-bottom: 0.4rem;
        }

        .form-control {
            width: 100%;
            background-color: #0f172a;
            border: 1px solid var(--card-border);
            color: var(--text-main);
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
            outline: none;
            transition: border-color 0.2s ease;
        }

        .form-control:focus {
            border-color: var(--primary);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 80px;
        }

        .btn-primary {
            background-color: var(--primary);
            color: #ffffff;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
        }

        .table-responsive {
            overflow-x: auto;
        }

        .custom-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.9rem;
        }

        .custom-table th {
            background-color: rgba(15, 23, 42, 0.6);
            color: var(--text-muted);
            font-weight: 600;
            padding: 0.85rem 1rem;
            border-bottom: 2px solid var(--card-border);
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }

        .custom-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--card-border);
            vertical-align: middle;
        }

        .custom-table tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.02);
        }

        /* Status Badges */
        .btn-status-toggle {
            background: transparent;
            border: 1px solid transparent;
            padding: 0.4rem 0.85rem;
            border-radius: 20px;
            font-size: 0.825rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            transition: all 0.2s ease;
        }

        .btn-status-toggle.completed {
            background-color: var(--success-bg);
            color: var(--success-text);
            border-color: var(--success-border);
        }

        .btn-status-toggle.pending {
            background-color: var(--warning-bg);
            color: var(--warning-text);
            border-color: rgba(245, 158, 11, 0.3);
        }

        .btn-status-toggle:hover {
            opacity: 0.85;
            transform: translateY(-1px);
        }

        .task-title-text {
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--text-main);
            margin-bottom: 0.2rem;
        }

        .task-title-text.is-completed {
            text-decoration: line-through;
            color: var(--text-muted);
        }

        .task-desc-text {
            font-size: 0.85rem;
            color: var(--text-muted);
            line-height: 1.4;
        }

        /* Action Buttons */
        .action-group {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.5rem;
        }

        .btn-complete-action {
            background-color: var(--success-bg);
            color: var(--success-text);
            border: 1px solid var(--success-border);
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            padding: 0.35rem 0.75rem;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .btn-complete-action:hover {
            background-color: rgba(34, 197, 94, 0.3);
        }

        .btn-reopen-action {
            background-color: var(--warning-bg);
            color: var(--warning-text);
            border: 1px solid rgba(245, 158, 11, 0.3);
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            padding: 0.35rem 0.75rem;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .btn-reopen-action:hover {
            background-color: rgba(245, 158, 11, 0.3);
        }

        .btn-remove {
            background-color: transparent;
            color: var(--danger);
            border: none;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            padding: 0.35rem 0.6rem;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .btn-remove:hover {
            background-color: rgba(239, 68, 68, 0.15);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-4px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

    <!-- Navigation Header -->
    <nav class="navbar">
        <div class="navbar-brand">
            <span>👤</span> User Portal
        </div>
        <div class="nav-links">
            <a href="#my-tasks">My Tasks</a>
            <span class="nav-divider">|</span>
            <a href="logout.php" class="btn-logout">Log Out</a>
        </div>
    </nav>

    <!-- Main Workspace -->
    <main class="container">

        <!-- Welcome Title -->
        <h1 class="welcome-title">Welcome, <span><?php echo $currentUser; ?></span>!</h1>

        <!-- Feedback Alert Messages -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-info">
                <span>ℹ️</span> <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <span>⚠️</span> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Panel 1: Add a New Task -->
        <div class="panel">
            <h2 class="panel-header">📝 Add a New Task</h2>
            <form method="POST" action="user.php">
                <input type="hidden" name="action" value="add_task">

                <div class="form-group">
                    <label for="title">Task Title:</label>
                    <input type="text" id="title" name="title" class="form-control" placeholder="Enter task title..." required>
                </div>

                <div class="form-group">
                    <label for="description">Description (Optional):</label>
                    <textarea id="description" name="description" class="form-control" placeholder="Enter additional task details..."></textarea>
                </div>

                <button type="submit" class="btn-primary">Add Task</button>
            </form>
        </div>

        <!-- Panel 2: Active Workspace -->
        <div class="panel" id="my-tasks">
            <h2 class="panel-header">💼 My Active Workspace</h2>
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th style="width: 140px;">Status</th>
                            <th>Task Details</th>
                            <th style="width: 220px; text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($tasks) > 0): ?>
                            <?php foreach ($tasks as $task): ?>
                                <?php $isCompleted = (strcasecmp(trim($task['status']), 'Completed') === 0); ?>
                                <tr>
                                    <!-- Status Column (Click badge to toggle) -->
                                    <td>
                                        <form method="POST" action="user.php" style="margin:0;">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                            <input type="hidden" name="current_status" value="<?php echo htmlspecialchars($task['status']); ?>">
                                            
                                            <?php if ($isCompleted): ?>
                                                <button type="submit" class="btn-status-toggle completed" title="Click to mark as Pending">
                                                    <span>✅</span> Completed
                                                </button>
                                            <?php else: ?>
                                                <button type="submit" class="btn-status-toggle pending" title="Click to mark as Completed">
                                                    <span>⏳</span> Pending
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    </td>

                                    <!-- Task Title & Description -->
                                    <td>
                                        <div class="task-title-text <?php echo $isCompleted ? 'is-completed' : ''; ?>">
                                            <?php echo htmlspecialchars($task['title']); ?>
                                        </div>
                                        <?php if (!empty($task['description'])): ?>
                                            <div class="task-desc-text"><?php echo htmlspecialchars($task['description']); ?></div>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Actions Column -->
                                    <td style="text-align: right;">
                                        <div class="action-group">
                                            <!-- Dedicated Mark Done / Reopen Button -->
                                            <form method="POST" action="user.php" style="margin:0;">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                                
                                                <?php if ($isCompleted): ?>
                                                    <input type="hidden" name="target_status" value="Pending">
                                                    <button type="submit" class="btn-reopen-action">↺ Reopen</button>
                                                <?php else: ?>
                                                    <input type="hidden" name="target_status" value="Completed">
                                                    <button type="submit" class="btn-complete-action">✓ Complete</button>
                                                <?php endif; ?>
                                            </form>

                                            <!-- Remove Task Button -->
                                            <form method="POST" action="user.php" style="margin:0;" onsubmit="return confirm('Are you sure you want to remove this task?');">
                                                <input type="hidden" name="action" value="remove_task">
                                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                                <button type="submit" class="btn-remove">[X] Remove</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                                    No tasks found in your workspace. Add a task above to get started!
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

</body>
</html>