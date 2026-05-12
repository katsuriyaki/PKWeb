<?php
require_once __DIR__ . '/../config.php';

$error = '';
$success = '';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request';
    } else {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (empty($username) || empty($email) || empty($password)) {
            $error = 'All fields are required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email format';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $error = 'Username can only contain letters, numbers, and underscores';
        } else {
            try {
                $db = getDB();

                $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    $error = 'Username already taken';
                } else {
                    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    if ($stmt->fetch()) {
                        $error = 'Email already registered';
                    } else {
                        $hash = password_hash($password, PASSWORD_BCRYPT);

                        $stmt = $db->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                        $stmt->execute([$username, $email, $hash]);

                        // Auto-login after register
                        $stmt = $db->prepare("SELECT id, username, role FROM users WHERE username = ?");
                        $stmt->execute([$username]);
                        $newUser = $stmt->fetch();
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = $newUser['id'];
                        $_SESSION['username'] = $newUser['username'];
                        $_SESSION['authenticated'] = true;
                        $_SESSION['role'] = $newUser['role'] ?? 'user';
                        header('Location: ' . ($_SESSION['role'] === 'admin' ? 'admin.php' : 'dashboard.php'));
                        exit;
                    }
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
    <title>Register — PKWeb</title>
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

        .alert-success {
            background: #F0F7F0;
            border-color: #B4D9B4;
            color: #2A6B2A;
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

        .form-hint {
            font-size: 11.5px;
            color: var(--ink-3);
            margin-top: 6px;
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
        <div class="auth-subtitle">Create Your Account</div>
        <div class="auth-title">Register</div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <?= htmlEncode($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                <?= $success ?>
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
                    pattern="[a-zA-Z0-9_]+"
                    maxlength="50"
                    placeholder="letters, numbers, underscores"
                    value="<?= htmlEncode($_POST['username'] ?? '') ?>"
                >
                <div class="form-hint">Letters, numbers, and underscores only</div>
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Email</label>
                <input
                    type="email"
                    class="form-control"
                    id="email"
                    name="email"
                    required
                    maxlength="100"
                    placeholder="your@email.com"
                    value="<?= htmlEncode($_POST['email'] ?? '') ?>"
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
                    minlength="8"
                    placeholder="min 8 characters"
                >
            </div>

            <div class="form-group">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input
                    type="password"
                    class="form-control"
                    id="confirm_password"
                    name="confirm_password"
                    required
                    placeholder="confirm your password"
                >
            </div>

            <button type="submit" class="btn btn-primary">Create Account</button>
        </form>

        <div class="auth-footer">
            Already have an account? <a href="index.php">Sign in</a>
        </div>
    </div>

</body>
</html>