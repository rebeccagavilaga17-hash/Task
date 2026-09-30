<?php
session_start();
require 'db.php';

// 1. Security Check: Admin Access Only
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = "";
$error = "";

// 2. Handle Actions (CREATE, UPDATE ROLE, DELETE)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    // ACTION: Create New User
    if (isset($_POST['action']) && $_POST['action'] === 'create_user') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role     = $_POST['role'] ?? 'User';

        if (!empty($username) && !empty($password)) {
            // Check if username already exists
            $checkStmt = $pdo->prepare("SELECT id FROM Users WHERE username = :username");
            $checkStmt->execute(['username' => $username]);
            
            if ($checkStmt->fetch()) {
                $error = "Username already exists!";
            } else {
                $stmt = $pdo->prepare("INSERT INTO Users (username, password, role) VALUES (:username, :password, :role)");
                if ($stmt->execute(['username' => $username, 'password' => $password, 'role' => $role])) {
                    $message = "User created successfully.";
                } else {
                    $error = "Failed to create user.";
                }
            }
        } else {
            $error = "Please fill in all fields.";
        }
    }

    // ACTION: Update User Role
    if (isset($_POST['action']) && $_POST['action'] === 'update_role') {
        $userId  = $_POST['user_id'] ?? null;
        $newRole = $_POST['new_role'] ?? 'User';

        if ($userId) {
            $stmt = $pdo->prepare("UPDATE Users SET role = :role WHERE id = :id");
            if ($stmt->execute(['role' => $newRole, 'id' => $userId])) {
                $message = "User role updated successfully.";
            }
        }
    }

    // ACTION: Delete User
    if (isset($_POST['action']) && $_POST['action'] === 'delete_user') {
        $userId = $_POST['user_id'] ?? null;

        if ($userId) {
            // Prevent admin from deleting their own account accidentally
            $checkSelf = $pdo->prepare("SELECT username FROM Users WHERE id = :id");
            $checkSelf->execute(['id' => $userId]);
            $userToDelete = $checkSelf->fetch();

            if ($userToDelete && $userToDelete['username'] === $_SESSION['username']) {
                $error = "You cannot delete your own logged-in admin account!";
            } else {
                $stmt = $pdo->prepare("DELETE FROM Users WHERE id = :id");
                if ($stmt->execute(['id' => $userId])) {
                    $message = "User deleted successfully.";
                }
            }
        }
    }
}

// 3. Fetch All Users from Database
$stmt = $pdo->query("SELECT * FROM Users ORDER BY id DESC");
$allUsers = $stmt->fetchAll();

$currentAdmin = htmlspecialchars($_SESSION['username'] ?? 'Admin', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | User Management</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
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
            --error-bg: rgba(239, 68, 68, 0.15);
            --error-text: #f87171;
            --error-border: rgba(239, 68, 68, 0.3);
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

        /* Top Navigation Header */
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

        .btn-logout {
            background-color: transparent;
            color: var(--text-muted);
            border: 1px solid var(--card-border);
            padding: 0.5rem 1.2rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .btn-logout:hover {
            background-color: var(--danger);
            color: #ffffff;
            border-color: var(--danger);
        }

        /* Container Layout */
        .container {
            max-width: 1100px;
            width: 100%;
            margin: 2rem auto;
            padding: 0 1.5rem;
            flex: 1;
        }

        .welcome-title {
            font-size: 1.85rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        .welcome-title span {
            color: #818cf8;
        }

        /* Feedback Alerts */
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

        .alert-success {
            background-color: var(--success-bg);
            color: var(--success-text);
            border: 1px solid var(--success-border);
        }

        .alert-error {
            background-color: var(--error-bg);
            color: var(--error-text);
            border: 1px solid var(--error-border);
        }

        /* Panels / Cards */
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

        /* Inline Form for "Create New User" */
        .create-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 140px auto;
            gap: 1rem;
            align-items: end;
        }

        @media (max-width: 768px) {
            .create-form-grid {
                grid-template-columns: 1fr;
            }
        }

        .form-field {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        .form-field label {
            font-size: 0.85rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .form-control {
            background-color: #0f172a;
            border: 1px solid var(--card-border);
            color: var(--text-main);
            padding: 0.65rem 0.85rem;
            border-radius: 8px;
            font-size: 0.9rem;
            outline: none;
            transition: border-color 0.2s ease;
        }

        .form-control:focus {
            border-color: var(--primary);
        }

        .btn-primary {
            background-color: var(--primary);
            color: #ffffff;
            border: none;
            padding: 0.68rem 1.25rem;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s ease;
            white-space: nowrap;
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
        }

        /* Modern Data Table */
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
            padding: 0.85rem 1rem;
            border-bottom: 1px solid var(--card-border);
            vertical-align: middle;
        }

        .custom-table tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.02);
        }

        .role-select {
            background-color: #0f172a;
            border: 1px solid var(--card-border);
            color: var(--text-main);
            padding: 0.4rem 0.6rem;
            border-radius: 6px;
            font-size: 0.85rem;
            cursor: pointer;
        }

        .btn-danger {
            background-color: transparent;
            color: var(--danger);
            border: 1px solid var(--danger);
            padding: 0.35rem 0.75rem;
            border-radius: 6px;
            font-size: 0.825rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-danger:hover {
            background-color: var(--danger);
            color: #ffffff;
        }

        .user-id-badge {
            font-weight: 600;
            color: var(--text-muted);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-4px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

    <!-- Top Navigation Bar -->
    <nav class="navbar">
        <div class="navbar-brand">
            <span>🛡️</span> Admin Dashboard
        </div>
        <a href="logout.php" class="btn-logout">Log Out</a>
    </nav>

    <!-- Main Container -->
    <main class="container">
        
        <h1 class="welcome-title">Welcome to the Admin Area, <span><?php echo $currentAdmin; ?></span>!</h1>

        <!-- Status Alerts -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-success">
                <span>✓</span> <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <span>⚠️</span> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Panel 1: Create New User Form -->
        <div class="panel">
            <h2 class="panel-header">➕ Create New User</h2>
            <form method="POST" action="admin.php">
                <input type="hidden" name="action" value="create_user">
                
                <div class="create-form-grid">
                    <div class="form-field">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" class="form-control" placeholder="e.g. admin_jane2" required>
                    </div>

                    <div class="form-field">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>

                    <div class="form-field">
                        <label for="role">Role</label>
                        <select id="role" name="role" class="form-control">
                            <option value="Admin">Admin</option>
                            <option value="User" selected>User</option>
                        </select>
                    </div>

                    <button type="submit" class="btn-primary">Add User</button>
                </div>
            </form>
        </div>

        <!-- Panel 2: All Users Table -->
        <div class="panel">
            <h2 class="panel-header">👥 All Users</h2>
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th style="width: 80px;">ID</th>
                            <th>Username</th>
                            <th style="width: 160px;">Role</th>
                            <th style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($allUsers) > 0): ?>
                            <?php foreach ($allUsers as $user): ?>
                                <tr>
                                    <td class="user-id-badge"><?php echo htmlspecialchars($user['id']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                                    <td>
                                        <!-- Inline form to change role on dropdown change -->
                                        <form method="POST" action="admin.php" style="margin: 0;">
                                            <input type="hidden" name="action" value="update_role">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <select name="new_role" class="role-select" onchange="this.form.submit()">
                                                <option value="User" <?php echo (strtolower($user['role']) === 'user') ? 'selected' : ''; ?>>User</option>
                                                <option value="Admin" <?php echo (strtolower($user['role']) === 'admin') ? 'selected' : ''; ?>>Admin</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        <!-- Form to delete user -->
                                        <form method="POST" action="admin.php" style="margin: 0;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" class="btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: var(--text-muted);">No users found in database.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

</body>
</html>