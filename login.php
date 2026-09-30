<?php
session_start();
require 'db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        
        $stmt = $pdo->prepare("SELECT * FROM Users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        
        if ($user && $password === $user['password']) {
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id']   = $user['id']; // <-- Added user_id to session
            $_SESSION['username']  = $user['username'];
            $_SESSION['role']      = $user['role'];

            if ($user['role'] === 'admin') {
                header("Location: admin.php");
                exit();
            } else {
                header("Location: user.php");
                exit();
            }
        } else {
            $error = "Invalid username or password!";
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
    <title>System Access | Login</title>
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
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', system-ui, sans-serif; }
        body { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: var(--bg-gradient); padding: 1.25rem; }
        .login-card { background: var(--card-bg); width: 100%; max-width: 420px; padding: 2.5rem; border-radius: 16px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3); }
        .login-header { text-align: center; margin-bottom: 2rem; }
        .login-header h2 { color: var(--text-main); font-size: 1.75rem; font-weight: 700; margin-bottom: 0.5rem; }
        .login-header p { color: var(--text-muted); font-size: 0.875rem; }
        .alert-error { background-color: var(--error-bg); color: var(--error-text); border: 1px solid var(--error-border); padding: 0.75rem 1rem; border-radius: 8px; font-size: 0.875rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem; }
        .form-group { margin-bottom: 1.25rem; }
        .form-group label { display: block; font-size: 0.875rem; font-weight: 500; color: var(--text-main); margin-bottom: 0.5rem; }
        .form-control { width: 100%; padding: 0.75rem 1rem; font-size: 0.95rem; color: var(--text-main); background-color: #f8fafc; border: 1.5px solid var(--border); border-radius: 8px; outline: none; }
        .form-control:focus { background-color: #ffffff; border-color: var(--primary); box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.15); }
        .btn-submit { width: 100%; padding: 0.85rem; margin-top: 0.5rem; background-color: var(--primary); color: #ffffff; border: none; border-radius: 8px; font-size: 0.95rem; font-weight: 600; cursor: pointer; }
        .btn-submit:hover { background-color: var(--primary-hover); }
        .login-footer { margin-top: 1.5rem; text-align: center; font-size: 0.875rem; color: var(--text-muted); }
        .login-footer a { color: var(--primary); text-decoration: none; font-weight: 600; }
        .login-footer a:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="login-header">
            <h2>Welcome Back</h2>
            <p>Please enter your details to sign in</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert-error" role="alert">
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" placeholder="Enter your username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-submit">Sign In</button>
        </form>

        <div class="login-footer">
            Don't have an account? <a href="register.php">Register here</a>
        </div>
    </div>

</body>
</html>