<?php
/**
 * Admin Login Page
 * ────────────────
 * Authenticates admin users against the MySQL admin_users table.
 */

session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'db.php';

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $stmt = $pdo->prepare("SELECT id, username, password FROM admin_users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Regenerate session ID to prevent fixation
            session_regenerate_id(true);
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Valorol</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f0f4f8;
            position: relative;
            overflow: hidden;
        }

        /* Background pattern */
        body::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background:
                radial-gradient(circle at 20% 50%, rgba(0, 73, 144, 0.06) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 199, 44, 0.06) 0%, transparent 50%),
                radial-gradient(circle at 60% 80%, rgba(0, 73, 144, 0.04) 0%, transparent 50%);
            z-index: 0;
        }

        .login-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 420px;
            padding: 0 20px;
        }

        .login-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow:
                0 4px 6px rgba(0, 0, 0, 0.04),
                0 10px 40px rgba(0, 73, 144, 0.08);
            overflow: hidden;
        }

        .login-header {
            background: linear-gradient(135deg, #004990 0%, #003366 100%);
            padding: 32px 32px 28px;
            text-align: center;
        }

        .login-header h1 {
            color: #ffffff;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .login-header p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.875rem;
            font-weight: 400;
        }

        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 199, 44, 0.15);
            border: 1px solid rgba(255, 199, 44, 0.3);
            border-radius: 8px;
            padding: 8px 16px;
            margin-bottom: 16px;
        }

        .brand-badge i {
            color: #FFC72C;
            font-size: 1.25rem;
        }

        .brand-badge span {
            color: #FFC72C;
            font-weight: 600;
            font-size: 0.95rem;
            letter-spacing: 0.5px;
        }

        .login-body {
            padding: 32px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 1rem;
            transition: color 0.2s;
        }

        .input-wrapper input {
            width: 100%;
            padding: 12px 14px 12px 42px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.9rem;
            color: #1f2937;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }

        .input-wrapper input:focus {
            border-color: #004990;
            box-shadow: 0 0 0 3px rgba(0, 73, 144, 0.1);
        }

        .input-wrapper input:focus + i,
        .input-wrapper input:focus ~ i {
            color: #004990;
        }

        .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #9ca3af;
            cursor: pointer;
            padding: 0;
            font-size: 1rem;
        }

        .toggle-password:hover {
            color: #004990;
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #004990 0%, #003366 100%);
            border: none;
            border-radius: 10px;
            color: #ffffff;
            font-family: 'Poppins', sans-serif;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.15s, box-shadow 0.15s;
        }

        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 73, 144, 0.3);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-error i {
            color: #dc2626;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .alert-error span {
            color: #991b1b;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .login-footer {
            text-align: center;
            padding: 0 32px 24px;
        }

        .login-footer a {
            color: #6b7280;
            font-size: 0.8rem;
            text-decoration: none;
            transition: color 0.2s;
        }

        .login-footer a:hover {
            color: #004990;
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <div class="brand-badge">
                    <i class="bi bi-shield-lock-fill"></i>
                    <span>VALOROL</span>
                </div>
                <h1>Admin Panel</h1>
                <p>Sign in to manage COA entries</p>
            </div>

            <div class="login-body">
                <?php if (!empty($error)): ?>
                    <div class="alert-error">
                        <i class="bi bi-exclamation-circle-fill"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="index.php" autocomplete="off">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <div class="input-wrapper">
                            <input type="text" id="username" name="username" placeholder="Enter your username"
                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                            <i class="bi bi-person-fill"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <input type="password" id="password" name="password" placeholder="Enter your password" required>
                            <i class="bi bi-lock-fill"></i>
                            <button type="button" class="toggle-password" onclick="togglePassword()">
                                <i class="bi bi-eye-fill" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-login">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                    </button>
                </form>
            </div>

            <div class="login-footer">
                <a href="../index.html"><i class="bi bi-arrow-left me-1"></i>Back to Website</a>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('bi-eye-fill', 'bi-eye-slash-fill');
            } else {
                input.type = 'password';
                icon.classList.replace('bi-eye-slash-fill', 'bi-eye-fill');
            }
        }
    </script>
</body>
</html>
