<?php
session_start();
require 'db.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!empty($username) && !empty($password) && !empty($confirm_password)) {
        if ($password !== $confirm_password) {
            $error = "Passwords do not match!";
        } else {
           
            $stmt = $pdo->prepare("SELECT id FROM Users WHERE username = :username");
            $stmt->execute(['username' => $username]);
            
            if ($stmt->fetch()) {
                $error = "Username is already taken!";
            } else {
              
                $role = 'user'; 

                $insertStmt = $pdo->prepare("INSERT INTO Users (username, password, role) VALUES (:username, :password, :role)");
                if ($insertStmt->execute(['username' => $username, 'password' => $password, 'role' => $role])) {
                    $success = "Account created successfully! You can now <a href='login.php'>Login</a>.";
                } else {
                    $error = "Registration failed. Please try again.";
                }
            }
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | Registration</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --error-bg: #fef2f2;
            --error-text: #991b1b;
            --error-border: #fecaca;
            --success-bg: #f0fdf4;
            --success-text: #166534;
            --success-border: #bbf7d0;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', system-ui, sans-serif; }
        body { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: var(--bg-gradient); padding: 1.25rem; }
        .register-card { background: var(--card-bg); width: 100%; max-width: 420px; padding: 2.5rem; border-radius: 16px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3); }
        .register-header { text-align: center; margin-bottom: 2rem; }
        .register-header h2 { color: var(--text-main); font-size: 1.75rem; font-weight: 700; margin-bottom: 0.5rem; }
        .register-header p { color: var(--text-muted); font-size: 0.875rem; }
        .alert-error { background-color: var(--error-bg); color: var(--error-text); border: 1px solid var(--error-border); padding: 0.75rem 1rem; border-radius: 8px; font-size: 0.875rem; margin-bottom: 1.5rem; }
        .alert-success { background-color: var(--success-bg); color: var(--success-text); border: 1px solid var(--success-border); padding: 0.75rem 1rem; border-radius: 8px; font-size: 0.875rem; margin-bottom: 1.5rem; }
        .alert-success a { color: var(--success-text); font-weight: 700; text-decoration: underline; }
        .form-group { margin-bottom: 1.25rem; }
        .form-group label { display: block; font-size: 0.875rem; font-weight: 500; color: var(--text-main); margin-bottom: 0.5rem; }
        .form-control { width: 100%; padding: 0.75rem 1rem; font-size: 0.95rem; color: var(--text-main); background-color: #f8fafc; border: 1.5px solid var(--border); border-radius: 8px; outline: none; }
        .form-control:focus { background-color: #ffffff; border-color: var(--primary); box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.15); }
        .btn-submit { width: 100%; padding: 0.85rem; margin-top: 0.5rem; background-color: var(--primary); color: #ffffff; border: none; border-radius: 8px; font-size: 0.95rem; font-weight: 600; cursor: pointer; }
        .btn-submit:hover { background-color: var(--primary-hover); }
        .register-footer { margin-top: 1.5rem; text-align: center; font-size: 0.875rem; color: var(--text-muted); }
        .register-footer a { color: var(--primary); text-decoration: none; font-weight: 600; }
        .register-footer a:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <div class="register-card">
        <div class="register-header">
            <h2>Create Account</h2>
            <p>Fill out the details below to register</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert-error" role="alert">
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert-success" role="alert">
                <span><?php echo $success; ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" placeholder="Choose a username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-submit">Register</button>
        </form>

        <div class="register-footer">
            Already have an account? <a href="login.php">Sign In</a>
        </div>
    </div>

</body>
</html>