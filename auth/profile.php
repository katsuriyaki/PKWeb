<?php
require_once __DIR__ . '/../config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle avatar actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['avatar_action'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request';
    } elseif ($_POST['avatar_action'] === 'upload') {
        if (!isset($_FILES['avatar_file']) || $_FILES['avatar_file']['error'] !== UPLOAD_ERR_OK) {
            $error = 'No file uploaded.';
        } else {
            $file = $_FILES['avatar_file'];
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($file['type'], $allowed)) {
                $error = 'Only JPG, PNG, GIF, or WebP allowed.';
            } elseif ($file['size'] > 2 * 1024 * 1024) {
                $error = 'Image must be under 2MB.';
            } else {
                $dir = __DIR__ . '/uploads/avatars';
                if (!is_dir($dir)) mkdir($dir, 0755, true);

                $db = getDB();
                $stmt = $db->prepare("SELECT avatar FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $old = $stmt->fetch()['avatar'] ?? null;
                if ($old && file_exists(__DIR__ . $old)) unlink(__DIR__ . $old);

                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = $user_id . '_' . time() . '.' . $ext;
                $path = '/uploads/avatars/' . $filename;
                $dest = __DIR__ . $path;

                if (is_uploaded_file($file['tmp_name'])) {
                    $ok = move_uploaded_file($file['tmp_name'], $dest);
                } else {
                    $ok = copy($file['tmp_name'], $dest);
                }

                if ($ok && file_exists($dest)) {
                    $stmt = $db->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                    $stmt->execute([$path, $user_id]);
                    $_SESSION['avatar'] = $path;
                    $success = 'Avatar updated successfully.';
                } else {
                    $error = 'Failed to save image.';
                }
            }
        }
    } elseif ($_POST['avatar_action'] === 'remove') {
        $db = getDB();
        $stmt = $db->prepare("SELECT avatar FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $old = $stmt->fetch()['avatar'] ?? null;
        if ($old && file_exists(__DIR__ . $old)) unlink(__DIR__ . $old);
        $stmt = $db->prepare("UPDATE users SET avatar = NULL WHERE id = ?");
        $stmt->execute([$user_id]);
        $_SESSION['avatar'] = null;
        $success = 'Avatar removed.';
    }
}

// Stats & user data
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM items WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $stats = $stmt->fetch();

    $stmt = $db->prepare("SELECT avatar, created_at FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    $stmt = $db->prepare("SELECT title, created_at FROM items WHERE user_id = ? ORDER BY created_at DESC LIMIT 4");
    $stmt->execute([$user_id]);
    $recent = $stmt->fetchAll();
} catch (PDOException $e) {
    $stats  = ['total' => 0];
    $user   = null;
    $recent = [];
}

$avatar = $user['avatar'] ?? null;
$initials = strtoupper(substr($_SESSION['username'], 0, 2));
$daysActive = $user ? (int)((time() - strtotime($user['created_at'])) / 86400) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile — PKWeb</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,300&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:        #F5F1EA;
            --surface:   #FDFAF5;
            --border:    #DDD8CF;
            --ink:       #1A1714;
            --ink-2:     #4A4540;
            --ink-3:     #8A8480;
            --accent:    #C4622D;
            --accent-lt: #F2E8E1;
            --sidebar-w: 260px;
            --top-h:     72px;
            --radius:    2px;
        }

        html, body {
            min-height: 100%;
            background: var(--bg);
            font-family: "DM Sans", sans-serif;
            font-size: 14px;
            color: var(--ink);
            -webkit-font-smoothing: antialiased;
        }

        .shell { display: flex; min-height: 100vh; }

        /* ── Sidebar ──────────────────────────────── */
        .sidebar {
            width: var(--sidebar-w);
            min-height: 100vh;
            background: var(--ink);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            z-index: 200;
            transition: transform 0.3s cubic-bezier(.4,0,.2,1);
        }

        .sidebar-logo {
            padding: 32px 28px 28px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

        .sidebar-logo-mark {
            font-family: "DM Serif Display", serif;
            font-size: 22px;
            color: #fff;
            letter-spacing: -0.5px;
        }

        .sidebar-logo-sub {
            font-size: 11px;
            color: rgba(255,255,255,0.35);
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-top: 4px;
        }

        .sidebar-section { padding: 20px 0 8px; }

        .sidebar-section-label {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            color: rgba(255,255,255,0.25);
            padding: 0 28px;
            margin-bottom: 8px;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 28px;
            color: rgba(255,255,255,0.5);
            text-decoration: none;
            font-size: 13.5px;
            transition: color 0.15s, background 0.15s;
            position: relative;
        }

        .sidebar-nav a svg {
            width: 16px; height: 16px;
            flex-shrink: 0;
            opacity: 0.6;
            transition: opacity 0.15s;
        }

        .sidebar-nav a:hover { color: rgba(255,255,255,0.85); background: rgba(255,255,255,0.04); }
        .sidebar-nav a:hover svg { opacity: 1; }

        .sidebar-nav a.active { color: #fff; background: rgba(255,255,255,0.07); }

        .sidebar-nav a.active::before {
            content: "";
            position: absolute;
            left: 0; top: 6px; bottom: 6px;
            width: 3px;
            background: var(--accent);
            border-radius: 0 2px 2px 0;
        }

        .sidebar-nav a.active svg { opacity: 1; }

        .sidebar-divider { height: 1px; background: rgba(255,255,255,0.06); margin: 8px 0; }

        .sidebar-footer {
            margin-top: auto;
            padding: 20px 28px;
            border-top: 1px solid rgba(255,255,255,0.08);
        }

        .sf-row { display: flex; align-items: center; gap: 12px; }

        .sf-avatar {
            width: 34px; height: 34px;
            background: var(--accent);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 13px;
            color: #fff;
            flex-shrink: 0;
            overflow: hidden;
        }

        .sf-avatar img { width: 100%; height: 100%; object-fit: cover; }

        .sf-name { font-size: 13px; font-weight: 500; color: #fff; line-height: 1.2; }

        .sf-action {
            font-size: 11.5px;
            color: rgba(255,255,255,0.35);
            text-decoration: none;
            transition: color 0.15s;
            display: block;
            margin-top: 2px;
        }

        .sf-action:hover { color: rgba(255,255,255,0.65); }

        .sf-link {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            transition: opacity 0.15s;
        }

        .sf-link:hover { opacity: 0.8; }

        /* ── Main ─────────────────────────────────── */
        .main {
            margin-left: var(--sidebar-w);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* ── Topbar ───────────────────────────────── */
        .topbar {
            height: var(--top-h);
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 0 40px;
            display: flex;
            align-items: center;
            gap: 16px;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 16px;
            flex: 1;
        }

        .menu-toggle {
            display: none;
            width: 38px; height: 38px;
            background: var(--ink);
            border: none;
            border-radius: var(--radius);
            cursor: pointer;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .menu-toggle svg { width: 18px; height: 18px; stroke: #fff; }

        .topbar-title {
            font-family: "DM Serif Display", serif;
            font-size: 22px;
            color: var(--ink);
            letter-spacing: -0.3px;
        }

        .topbar-breadcrumb {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: var(--ink-3);
        }

        .topbar-breadcrumb a { color: var(--ink-3); text-decoration: none; }
        .topbar-breadcrumb a:hover { color: var(--ink); }
        .topbar-breadcrumb span { opacity: 0.5; }

        /* ── Content ──────────────────────────────── */
        .content {
            padding: 36px 40px;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* ── Alerts ───────────────────────────────── */
        .alert {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 18px;
            border-radius: var(--radius);
            font-size: 13.5px;
            border: 1px solid;
        }

        .alert svg { width: 16px; height: 16px; flex-shrink: 0; }

        .alert-success { background: #F0F7F0; border-color: #B4D9B4; color: #2A6B2A; }
        .alert-danger  { background: #FDF1F0; border-color: #F0B4A8; color: #8B2310; }

        /* ── Buttons ──────────────────────────────── */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 9px 18px;
            font-family: "DM Sans", sans-serif;
            font-size: 12.5px;
            font-weight: 500;
            letter-spacing: 0.2px;
            border-radius: var(--radius);
            border: 1px solid transparent;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s;
            white-space: nowrap;
        }

        .btn svg { width: 14px; height: 14px; flex-shrink: 0; }

        .btn-primary { background: var(--accent); border-color: var(--accent); color: #fff; }
        .btn-primary:hover { background: #b0562a; border-color: #b0562a; }

        .btn-danger { background: #c0392b; border-color: #c0392b; color: #fff; }
        .btn-danger:hover { background: #a93226; border-color: #a93226; }

        /* ── Hero ─────────────────────────────────── */
        .hero {
            background: var(--ink);
            border-radius: var(--radius);
            padding: 40px;
            display: flex;
            align-items: center;
            gap: 32px;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.07'/%3E%3C/svg%3E");
            opacity: 0.25;
            pointer-events: none;
        }

        .hero-avatar {
            width: 88px; height: 88px;
            background: var(--accent);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: "DM Serif Display", serif;
            font-size: 30px;
            color: #fff;
            flex-shrink: 0;
            overflow: hidden;
            position: relative;
            cursor: pointer;
            transition: opacity 0.15s;
        }

        .hero-avatar:hover { opacity: 0.85; }

        .hero-avatar img { width: 100%; height: 100%; object-fit: cover; }

        .avatar-overlay {
            position: absolute;
            inset: 0;
            border-radius: 50%;
            background: rgba(0,0,0,0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.15s;
        }

        .hero-avatar:hover .avatar-overlay { opacity: 1; }
        .avatar-overlay svg { width: 22px; height: 22px; stroke: #fff; }

        .hero-info { flex: 1; position: relative; z-index: 1; }

        .hero-name {
            font-family: "DM Serif Display", serif;
            font-size: 30px;
            color: #fff;
            letter-spacing: -0.5px;
            line-height: 1.1;
        }

        .hero-meta {
            font-size: 13px;
            color: rgba(255,255,255,0.45);
            margin-top: 6px;
        }

        .hero-meta strong { color: rgba(255,255,255,0.7); font-weight: 500; }

        .hero-stats { display: flex; gap: 32px; margin-top: 20px; }

        .hero-stat { display: flex; flex-direction: column; gap: 2px; }

        .hero-stat-val {
            font-family: "DM Serif Display", serif;
            font-size: 24px;
            color: #fff;
            line-height: 1;
        }

        .hero-stat-lbl {
            font-size: 11px;
            color: rgba(255,255,255,0.35);
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .hero-stat-sep { width: 1px; background: rgba(255,255,255,0.1); align-self: stretch; }

        .hero-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
            position: relative;
            z-index: 1;
        }

        .hero-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 18px;
            border-radius: var(--radius);
            font-family: "DM Sans", sans-serif;
            font-size: 12.5px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            transition: all 0.15s;
            white-space: nowrap;
        }

        .hero-btn svg { width: 14px; height: 14px; flex-shrink: 0; }

        .hero-btn-upload {
            background: rgba(255,255,255,0.1);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.15);
        }

        .hero-btn-upload:hover { background: rgba(255,255,255,0.16); }

        .hero-btn-remove {
            background: transparent;
            color: rgba(255,255,255,0.4);
            border: 1px solid rgba(255,255,255,0.1);
            font-size: 12px;
        }

        .hero-btn-remove:hover {
            color: #ff9f9f;
            border-color: rgba(255,100,100,0.3);
            background: rgba(255,100,100,0.06);
        }

        /* ── Two-column grid ──────────────────────── */
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
        }

        .card-header {
            padding: 18px 24px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-title {
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 1.6px;
            text-transform: uppercase;
            color: var(--ink-3);
        }

        .card-body { padding: 0; }

        .field-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 24px;
            border-bottom: 1px solid var(--border);
            gap: 16px;
        }

        .field-row:last-child { border-bottom: none; }

        .field-label {
            font-size: 12px;
            font-weight: 500;
            color: var(--ink-3);
            letter-spacing: 0.2px;
            flex-shrink: 0;
            min-width: 110px;
        }

        .field-value {
            font-size: 14px;
            color: var(--ink);
            font-weight: 500;
            text-align: right;
        }

        .field-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            background: var(--accent-lt);
            color: var(--accent);
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .field-badge::before {
            content: '';
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--accent);
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 24px;
            border-bottom: 1px solid var(--border);
            transition: background 0.12s;
        }

        .activity-item:last-child { border-bottom: none; }
        .activity-item:hover { background: #FAF7F2; }

        .activity-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: var(--accent);
            flex-shrink: 0;
            opacity: 0.6;
        }

        .activity-name {
            flex: 1;
            font-size: 13.5px;
            color: var(--ink);
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .activity-date { font-size: 12px; color: var(--ink-3); flex-shrink: 0; }

        .activity-empty {
            padding: 32px 24px;
            text-align: center;
            color: var(--ink-3);
            font-size: 13px;
        }

        /* ── Danger zone ──────────────────────────── */
        .danger-zone {
            border: 1px solid #F0C4B8;
            border-radius: var(--radius);
            background: var(--surface);
            overflow: hidden;
        }

        .danger-header {
            padding: 16px 24px;
            border-bottom: 1px solid #F0C4B8;
            background: #FDF5F3;
        }

        .danger-title {
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 1.6px;
            text-transform: uppercase;
            color: #8B2310;
        }

        .danger-body { padding: 20px 24px; }

        .danger-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .danger-desc {
            font-size: 13px;
            color: var(--ink-3);
            line-height: 1.5;
        }

        .danger-desc strong {
            display: block;
            font-size: 14px;
            color: var(--ink-2);
            font-weight: 500;
            margin-bottom: 2px;
        }

        /* ── Overlay ──────────────────────────────── */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.45);
            z-index: 199;
            backdrop-filter: blur(2px);
        }

        /* ── Responsive ───────────────────────────── */
        @media (max-width: 991px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .sidebar-overlay.open { display: block; }
            .main { margin-left: 0; }
            .menu-toggle { display: flex; }
            .topbar { padding: 0 20px; }
            .content { padding: 24px 20px; }
            .grid { grid-template-columns: 1fr; }
            .hero { padding: 28px; gap: 24px; flex-wrap: wrap; }
            .hero-actions { flex-direction: row; flex-wrap: wrap; }
        }

        @media (max-width: 600px) {
            .hero { flex-direction: column; align-items: flex-start; }
            .hero-avatar { width: 72px; height: 72px; font-size: 26px; }
            .hero-name { font-size: 24px; }
            .hero-stats { gap: 20px; }
            .danger-row { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>

<div class="sidebar-overlay" id="overlay" onclick="closeSidebar()"></div>

<div class="shell">

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <div class="sidebar-logo-mark">PKWeb</div>
            <div class="sidebar-logo-sub">Workspace</div>
        </div>

        <div class="sidebar-section">
            <div class="sidebar-section-label">Library</div>
            <nav class="sidebar-nav">
                <a href="../dashboard.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/>
                    </svg>
                    All Items
                </a>
                <a href="#">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                    Recent
                </a>
                <a href="#">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                    </svg>
                    Favorites
                </a>
                <a href="../items/create.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    New Item
                </a>
            </nav>
        </div>

        <div class="sidebar-divider"></div>

        <?php if (isAdmin()): ?>
        <div class="sidebar-section">
            <div class="sidebar-section-label">Platform</div>
            <nav class="sidebar-nav">
                <a href="../admin.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                    </svg>
                    Overview
                </a>
                <a href="../admin-items.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                    </svg>
                    All Items
                </a>
                <a href="../admin-users.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                    Users
                </a>
            </nav>
        </div>

        <div class="sidebar-divider"></div>
        <?php endif; ?>

        <div class="sidebar-section">
            <div class="sidebar-section-label">Account</div>
            <nav class="sidebar-nav">
                <a href="profile.php" class="active">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                    </svg>
                    Profile
                </a>
                <a href="#">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/>
                    </svg>
                    Settings
                </a>
            </nav>
        </div>

        <div class="sidebar-footer">
            <div class="sf-row">
                <a href="profile.php" class="sf-link">
                    <div class="sf-avatar">
                        <?php if ($avatar): ?>
                            <img src="<?= htmlEncode($avatar) ?>" alt="">
                        <?php else: ?>
                            <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div class="sf-name"><?= htmlEncode($_SESSION['username']) ?></div>
                        <a href="logout.php" class="sf-action">Sign out</a>
                    </div>
                </a>
            </div>
        </div>
    </aside>

    <main class="main">

        <header class="topbar">
            <div class="topbar-left">
                <button class="menu-toggle" onclick="toggleSidebar()" aria-label="Toggle menu">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <line x1="3" y1="6"  x2="21" y2="6"/>
                        <line x1="3" y1="12" x2="21" y2="12"/>
                        <line x1="3" y1="18" x2="21" y2="18"/>
                    </svg>
                </button>
                <div>
                    <div class="topbar-title">Profile</div>
                    <div class="topbar-breadcrumb">
                        <a href="../dashboard.php">PKWeb</a>
                        <span>/</span>
                        Account
                    </div>
                </div>
            </div>
        </header>

        <div class="content">

            <?php if ($success): ?>
            <div class="alert alert-success">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                <?= htmlEncode($success) ?>
            </div>
            <?php elseif ($error): ?>
            <div class="alert alert-danger">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <?= htmlEncode($error) ?>
            </div>
            <?php endif; ?>

            <!-- Hero -->
            <div class="hero">
                <label class="hero-avatar" title="Change photo">
                    <?php if ($avatar): ?>
                        <img src="<?= htmlEncode($avatar) ?>" alt="">
                    <?php else: ?>
                        <?= $initials ?>
                    <?php endif; ?>
                    <div class="avatar-overlay">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>
                        </svg>
                    </div>
                    <form method="POST" enctype="multipart/form-data" id="avatarForm" style="display:none;">
                        <input type="hidden" name="avatar_action" id="avatarAction" value="">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="file" name="avatar_file" id="avatarFile" accept="image/*" onchange="submitAvatarForm('upload')">
                    </form>
                </label>

                <div class="hero-info">
                    <div class="hero-name"><?= htmlEncode($_SESSION['username']) ?></div>
                    <div class="hero-meta">
                        Member since <strong><?= $user ? date('F Y', strtotime($user['created_at'])) : '—' ?></strong>
                        &nbsp;·&nbsp; ID <strong>#<?= htmlEncode($_SESSION['user_id']) ?></strong>
                    </div>
                    <div class="hero-stats">
                        <div class="hero-stat">
                            <div class="hero-stat-val"><?= $stats['total'] ?></div>
                            <div class="hero-stat-lbl">Items</div>
                        </div>
                        <div class="hero-stat-sep"></div>
                        <div class="hero-stat">
                            <div class="hero-stat-val"><?= $daysActive ?></div>
                            <div class="hero-stat-lbl">Days active</div>
                        </div>
                    </div>
                </div>

                <div class="hero-actions">
                    <label class="hero-btn hero-btn-upload" style="cursor:pointer;" onclick="document.getElementById('avatarFile').click(); return false;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>
                        </svg>
                        Upload Photo
                    </label>
                    <?php if ($avatar): ?>
                    <button class="hero-btn hero-btn-remove" onclick="submitAvatarForm('remove')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                            <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/>
                        </svg>
                        Remove photo
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Two-column grid -->
            <div class="grid">

                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Account Details</span>
                    </div>
                    <div class="card-body">
                        <div class="field-row">
                            <span class="field-label">Username</span>
                            <span class="field-value"><?= htmlEncode($_SESSION['username']) ?></span>
                        </div>
                        <div class="field-row">
                            <span class="field-label">Account ID</span>
                            <span class="field-value" style="font-family: monospace; font-size: 13px; color: var(--ink-3);">#<?= htmlEncode($_SESSION['user_id']) ?></span>
                        </div>
                        <div class="field-row">
                            <span class="field-label">Status</span>
                            <span class="field-value"><span class="field-badge">Active</span></span>
                        </div>
                        <div class="field-row">
                            <span class="field-label">Joined</span>
                            <span class="field-value"><?= $user ? date('d M Y', strtotime($user['created_at'])) : '—' ?></span>
                        </div>
                        <div class="field-row">
                            <span class="field-label">Avatar</span>
                            <span class="field-value" style="color: var(--ink-3); font-size: 13px;">
                                <?= $avatar ? 'Custom photo' : 'Initials (' . $initials . ')' ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Recent Items</span>
                        <a href="../dashboard.php" style="font-size: 12px; color: var(--accent); text-decoration: none; font-weight: 500;">View all →</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent)): ?>
                        <div class="activity-empty">No items yet. <a href="../items/create.php" style="color: var(--accent); text-decoration: none;">Create one →</a></div>
                        <?php else: ?>
                            <?php foreach ($recent as $r): ?>
                            <div class="activity-item">
                                <div class="activity-dot"></div>
                                <div class="activity-name"><?= htmlEncode($r['title']) ?></div>
                                <div class="activity-date"><?= date('M j', strtotime($r['created_at'])) ?></div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- Danger zone -->
            <div class="danger-zone">
                <div class="danger-header">
                    <div class="danger-title">Danger Zone</div>
                </div>
                <div class="danger-body">
                    <div class="danger-row">
                        <div class="danger-desc">
                            <strong>Sign out of your account</strong>
                            Ends your current session on this device.
                        </div>
                        <a href="logout.php" class="btn btn-danger">Sign Out</a>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('open');
        document.getElementById('overlay').classList.toggle('open');
    }
    function closeSidebar() {
        document.getElementById('sidebar').classList.remove('open');
        document.getElementById('overlay').classList.remove('open');
    }
    function submitAvatarForm(action) {
        document.getElementById('avatarAction').value = action;
        document.getElementById('avatarForm').submit();
    }
</script>
</body>
</html>