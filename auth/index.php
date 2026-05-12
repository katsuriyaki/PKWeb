<?php
require_once __DIR__ . '/../config.php';

$error = '';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Username and password are required';
        } else {
            try {
                $db = getDB();
                $stmt = $db->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['authenticated'] = true;
                    $_SESSION['role'] = $user['role'] ?? 'user';
                    header('Location: ' . ($_SESSION['role'] === 'admin' ? 'admin.php' : 'dashboard.php'));
                    exit;
                } else {
                    $error = 'Invalid username or password';
                }
            } catch (PDOException $e) {
                $error = 'An error occurred';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — PKWeb</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,300&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:        #F5F1EA;
            --surface:   #FDFAF5;
            --border:    #DDD8CF;
            --ink:        #1A1714;
            --ink-2:      #4A4540;
            --ink-3:      #8A8480;
            --accent:     #C4622D;
            --radius:     2px;
        }

        html, body {
            height: 100%;
            background: var(--bg);
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            color: var(--ink);
            -webkit-font-smoothing: antialiased;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        /* ── Auth Card ────────────────────────────── */
        .auth-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            width: 100%;
            max-width: 420px;
            padding: 44px 40px;
        }

        .auth-logo {
            font-family: 'DM Serif Display', serif;
            font-size: 26px;
            color: var(--ink);
            letter-spacing: -0.5px;
            text-align: center;
            margin-bottom: 2px;
        }

        .auth-subtitle {
            text-align: center;
            font-size: 13px;
            color: var(--ink-3);
            margin-bottom: 32px;
        }

        .auth-title {
            text-align: center;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            color: var(--ink);
            margin-bottom: 28px;
        }

        /* ── Alerts ───────────────────────────────── */
        .alert {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            border-radius: var(--radius);
            margin-bottom: 20px;
            font-size: 13.5px;
            border: 1px solid;
        }

        .alert svg { width: 16px; height: 16px; flex-shrink: 0; }

        .alert-danger {
            background: #FDF1F0;
            border-color: #F0B4A8;
            color: #8B2310;
        }

        /* ── Form Elements ─────────────────────────── */
        .form-group { margin-bottom: 18px; }

        .form-label {
            display: block;
            font-size: 10.5px;
            font-weight: 600;
            letter-spacing: 1.8px;
            text-transform: uppercase;
            color: var(--ink-3);
            margin-bottom: 8px;
        }

        .form-control {
            width: 100%;
            padding: 10px 14px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            color: var(--ink);
            outline: none;
            transition: border-color 0.15s, background 0.15s;
        }

        .form-control::placeholder { color: var(--ink-3); }

        .form-control:focus {
            border-color: var(--ink-3);
            background: #fff;
        }

        /* ── Buttons ──────────────────────────────── */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 11px 20px;
            font-family: 'DM Sans', sans-serif;
            font-size: 12.5px;
            font-weight: 500;
            letter-spacing: 0.2px;
            border-radius: var(--radius);
            border: 1px solid transparent;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s;
            width: 100%;
        }

        .btn-primary {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
        }

        .btn-primary:hover { background: #b0562a; border-color: #b0562a; }

        /* ── Auth Footer ──────────────────────────── */
        .auth-footer {
            text-align: center;
            margin-top: 24px;
            font-size: 13px;
            color: var(--ink-3);
        }

        .auth-footer a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 500;
        }

        .auth-footer a:hover { text-decoration: underline; }

        /* ── Responsive ───────────────────────────── */
        @media (max-width: 480px) {
            .auth-card { padding: 32px 24px; }
            .auth-logo { font-size: 24px; }
        }
    </style>
</head>
<body>

    <div class="auth-card">
        <div class="auth-logo">PKWeb</div>
        <div class="auth-subtitle">Secure Document Management</div>
        <div class="auth-title">Welcome Back</div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <?= htmlEncode($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <input
                    type="text"
                    class="form-control"
                    id="username"
                    name="username"
                    required
                    placeholder="Enter your username"
                    value="<?= htmlEncode($_POST['username'] ?? '') ?>"
                >
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input
                    type="password"
                    class="form-control"
                    id="password"
                    name="password"
                    required
                    placeholder="Enter your password"
                >
            </div>

            <button type="submit" class="btn btn-primary">Login</button>
        </form>

        <div class="auth-footer">
            Don't have an account? <a href="register.php">Register</a>
        </div>
    </div>

</body>
</html>