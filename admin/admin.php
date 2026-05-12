<?php
require_once __DIR__ . '/../config.php';
requireAdmin();

$success = '';
$error = '';

try {
    $db = getDB();

    $stmt = $db->prepare("SELECT COUNT(*) as total FROM users");
    $totalUsers = $stmt->execute() ? $stmt->fetch()['total'] : 0;

    $stmt = $db->prepare("SELECT COUNT(*) as total FROM items");
    $totalItems = $stmt->execute() ? $stmt->fetch()['total'] : 0;

    $stmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $newUsers = $stmt->execute() ? $stmt->fetch()['total'] : 0;

    $stmt = $db->prepare("
        SELECT u.id, u.username, u.email, u.created_at, COUNT(i.id) as item_count
        FROM users u
        LEFT JOIN items i ON i.user_id = u.id
        GROUP BY u.id
        ORDER BY u.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $recentUsers = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Failed to load data.';
    $totalUsers = $totalItems = $newUsers = 0;
    $recentUsers = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — PKWeb</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,300&display=swap" rel="stylesheet">
    <style>
        <?php include __DIR__ . '/../includes/shared-styles.php'; echo $shared_css; ?>

        /* ── Admin layout ─────────────────────────── */
        .admin-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            background: var(--ink);
            color: rgba(255,255,255,0.7);
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .admin-badge svg { width: 10px; height: 10px; }

        .topbar-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* ── Stats row ────────────────────────────── */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 20px 24px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .stat-label {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 1.8px;
            text-transform: uppercase;
            color: var(--ink-3);
        }

        .stat-value {
            font-family: "DM Serif Display", serif;
            font-size: 32px;
            color: var(--ink);
            line-height: 1.1;
        }

        .stat-sub { font-size: 12px; color: var(--ink-3); }

        .stat-card.accent-card {
            background: var(--ink);
            border-color: var(--ink);
        }

        .stat-card.accent-card .stat-label,
        .stat-card.accent-card .stat-value,
        .stat-card.accent-card .stat-sub { color: #fff; }

        .stat-card.accent-card .stat-label { opacity: 0.6; }
        .stat-card.accent-card .stat-sub { opacity: 0.65; }

        /* ── Table card ───────────────────────────── */
        .table-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
        }

        .table-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 24px;
            border-bottom: 1px solid var(--border);
        }

        .table-card-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--ink);
        }

        .table-card-count { font-size: 12px; color: var(--ink-3); margin-left: 6px; font-weight: 400; }

        .search-wrap { position: relative; }

        .search-wrap svg {
            position: absolute;
            left: 11px;
            top: 50%;
            transform: translateY(-50%);
            width: 14px; height: 14px;
            stroke: var(--ink-3);
            pointer-events: none;
        }

        .search-input {
            padding: 7px 12px 7px 32px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            font-family: "DM Sans", sans-serif;
            font-size: 12.5px;
            color: var(--ink);
            outline: none;
            width: 200px;
            transition: border-color 0.15s, width 0.2s;
        }

        .search-input::placeholder { color: var(--ink-3); }
        .search-input:focus { border-color: var(--ink-3); width: 240px; }

        table { width: 100%; border-collapse: collapse; }
        thead tr { background: var(--bg); }

        th {
            text-align: left;
            padding: 13px 24px;
            font-size: 10.5px;
            font-weight: 600;
            letter-spacing: 1.8px;
            text-transform: uppercase;
            color: var(--ink-3);
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }

        td {
            padding: 14px 24px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        tbody tr:last-child td { border-bottom: none; }
        tbody tr { transition: background 0.12s; }
        tbody tr:hover { background: #FAF7F2; }

        .user-name {
            font-size: 14px;
            font-weight: 500;
            color: var(--ink);
        }

        .user-email { font-size: 12.5px; color: var(--ink-3); }

        .user-date { font-size: 12.5px; color: var(--ink-3); white-space: nowrap; }
        .user-date-day { font-weight: 500; color: var(--ink-2); }

        .actions-cell { white-space: nowrap; }
        .actions-group { display: flex; gap: 6px; align-items: center; }

        .item-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 28px;
            height: 22px;
            padding: 0 8px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 11px;
            font-size: 12px;
            font-weight: 500;
            color: var(--ink-2);
        }

        @media (max-width: 600px) {
            .stats-row { grid-template-columns: 1fr; }
            .search-input, .search-input:focus { width: 140px; }
            th:nth-child(2), td:nth-child(2) { display: none; }
            td { padding: 12px 16px; }
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/../includes/page-header.php'; ?>

<?php if ($success): ?>
<div class="alert alert-success"><?= htmlEncode($success) ?></div>
<?php elseif ($error): ?>
<div class="alert alert-danger"><?= htmlEncode($error) ?></div>
<?php endif; ?>

<div class="stats-row">
    <div class="stat-card">
        <div class="stat-label">Total Users</div>
        <div class="stat-value"><?= $totalUsers ?></div>
        <div class="stat-sub">registered accounts</div>
    </div>
    <div class="stat-card accent-card">
        <div class="stat-label">Total Items</div>
        <div class="stat-value"><?= $totalItems ?></div>
        <div class="stat-sub">across all users</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">New This Week</div>
        <div class="stat-value"><?= $newUsers ?></div>
        <div class="stat-sub">new registrations</div>
    </div>
</div>

<div class="table-card">
    <div class="table-card-header">
        <div>
            <span class="table-card-title">Recent Users</span>
            <span class="table-card-count"><?= count($recentUsers) ?> shown</span>
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <div class="search-wrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text" class="search-input" placeholder="Filter users…" oninput="filterUsers(this.value)">
            </div>
            <a href="/admin/admin-users.php" class="btn btn-ghost btn-sm">View all →</a>
        </div>
    </div>

    <?php if (empty($recentUsers)): ?>
    <div style="padding: 48px 24px; text-align: center; color: var(--ink-3); font-size: 14px;">
        No users yet.
    </div>
    <?php else: ?>
    <table id="usersTable">
        <thead>
            <tr>
                <th>User</th>
                <th>Email</th>
                <th>Items</th>
                <th>Joined</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recentUsers as $u): ?>
            <tr>
                <td>
                    <div class="user-name"><?= htmlEncode($u['username']) ?></div>
                </td>
                <td>
                    <span class="user-email"><?= htmlEncode($u['email']) ?></span>
                </td>
                <td>
                    <span class="item-count"><?= $u['item_count'] ?></span>
                </td>
                <td class="user-date">
                    <span class="user-date-day"><?= date('M j', strtotime($u['created_at'])) ?></span>, <?= date('Y', strtotime($u['created_at'])) ?>
                </td>
                <td class="actions-cell">
                    <div class="actions-group">
                        <a href="admin-users.php?user=<?= $u['id'] ?>" class="btn btn-ghost btn-sm">View</a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/page-footer.php'; ?>

<script>
    function filterUsers(q) {
        const rows = document.querySelectorAll('#usersTable tbody tr');
        const lower = q.toLowerCase().trim();
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(lower) ? '' : 'none';
        });
    }
</script>