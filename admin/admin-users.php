<?php
require_once __DIR__ . '/../config.php';
requireAdmin();

$error = '';
$success = '';
$search = trim($_GET['search'] ?? '');
$viewUserId = (int)($_GET['user'] ?? 0);

// Delete user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_user') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request';
    } else {
        $uid = (int)($_POST['id'] ?? 0);
        if ($uid <= 0) {
            $error = 'Invalid user ID';
        } elseif ($uid === $_SESSION['user_id']) {
            $error = 'Cannot delete your own account.';
        } else {
            try {
                $db = getDB();
                $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$uid]);
                $success = 'User deleted successfully.';
                $viewUserId = 0;
            } catch (PDOException $e) {
                $error = 'Failed to delete user.';
            }
        }
    }
}

try {
    $db = getDB();

    if ($viewUserId > 0) {
        $stmt = $db->prepare("SELECT u.*, COUNT(i.id) as item_count FROM users u LEFT JOIN items i ON i.user_id = u.id WHERE u.id = ? GROUP BY u.id");
        $stmt->execute([$viewUserId]);
        $viewUser = $stmt->fetch();

        if ($viewUser) {
            $stmt = $db->prepare("SELECT id, title, description, created_at FROM items WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->execute([$viewUserId]);
            $userItems = $stmt->fetchAll();
        }

        $stmt = $db->prepare("SELECT COUNT(*) as total FROM users");
        $stmt->execute();
        $totalUsers = $stmt->fetch()['total'];
    } else {
        $sql = "SELECT u.id, u.username, u.email, u.created_at, u.role, COUNT(i.id) as item_count FROM users u LEFT JOIN items i ON i.user_id = u.id";
        $params = [];
        if ($search !== '') {
            $sql .= " WHERE u.username LIKE ? OR u.email LIKE ?";
            $params = ["%$search%", "%$search%"];
        }
        $sql .= " GROUP BY u.id ORDER BY u.created_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll();

        $stmt = $db->prepare("SELECT COUNT(*) as total FROM users");
        $stmt->execute();
        $totalUsers = $stmt->fetch()['total'];
    }
} catch (PDOException $e) {
    $error = 'Failed to load data.';
    $users = [];
    $viewUser = null;
    $userItems = [];
    $totalUsers = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin: Users — PKWeb</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,300&display=swap" rel="stylesheet">
    <style>
        <?php include __DIR__ . '/../includes/shared-styles.php'; echo $shared_css; ?>

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

        .topbar-title { display: flex; align-items: center; gap: 10px; }

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

        .table-card-title { font-size: 13px; font-weight: 600; color: var(--ink); }
        .table-card-count { font-size: 12px; color: var(--ink-3); margin-left: 6px; }

        .search-wrap { position: relative; }
        .search-wrap svg {
            position: absolute; left: 11px; top: 50%; transform: translateY(-50%);
            width: 14px; height: 14px; stroke: var(--ink-3); pointer-events: none;
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
            transition: border-color 0.15s;
        }

        .search-input::placeholder { color: var(--ink-3); }
        .search-input:focus { border-color: var(--ink-3); }

        table { width: 100%; border-collapse: collapse; }
        thead tr { background: var(--bg); }

        th {
            text-align: left; padding: 13px 24px;
            font-size: 10.5px; font-weight: 600; letter-spacing: 1.8px;
            text-transform: uppercase; color: var(--ink-3);
            border-bottom: 1px solid var(--border); white-space: nowrap;
        }

        td { padding: 14px 24px; border-bottom: 1px solid var(--border); vertical-align: middle; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr { transition: background 0.12s; }
        tbody tr:hover { background: #FAF7F2; }

        .user-name { font-size: 14px; font-weight: 500; color: var(--ink); }
        .user-email { font-size: 12.5px; color: var(--ink-3); }
        .user-date { font-size: 12.5px; color: var(--ink-3); white-space: nowrap; }
        .user-date-day { font-weight: 500; color: var(--ink-2); }

        .actions-cell { white-space: nowrap; }
        .actions-group { display: flex; gap: 6px; align-items: center; }

        .role-admin {
            display: inline-flex; align-items: center;
            padding: 2px 8px;
            background: var(--ink); color: rgba(255,255,255,0.6);
            border-radius: 10px; font-size: 11px; font-weight: 600; letter-spacing: 0.5px;
        }

        /* ── User detail view ────────────────────── */
        .user-detail-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
            margin-bottom: 20px;
        }

        .user-detail-header {
            padding: 24px 28px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .user-detail-avatar {
            width: 52px; height: 52px;
            background: var(--accent);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-family: "DM Serif Display", serif;
            font-size: 22px; color: #fff;
            flex-shrink: 0; overflow: hidden;
        }

        .user-detail-avatar img { width: 100%; height: 100%; object-fit: cover; }

        .user-detail-info { flex: 1; }

        .user-detail-name {
            font-family: "DM Serif Display", serif;
            font-size: 20px; color: var(--ink);
            letter-spacing: -0.2px;
        }

        .user-detail-meta { font-size: 12.5px; color: var(--ink-3); margin-top: 3px; }

        .user-detail-actions { display: flex; gap: 10px; align-items: center; }

        .user-detail-fields {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
        }

        .field-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 28px;
            border-bottom: 1px solid var(--border);
            gap: 16px;
        }

        .field-row:last-child { border-bottom: none; }
        .field-label { font-size: 12px; font-weight: 500; color: var(--ink-3); min-width: 100px; }
        .field-value { font-size: 13.5px; color: var(--ink); font-weight: 500; text-align: right; }

        /* ── Modal ────────────────────────────────── */
        .modal-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.45);
            z-index: 500;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(2px);
        }

        .modal-backdrop.open { display: flex; }

        .modal-box {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 28px 32px;
            width: 100%;
            max-width: 400px;
            margin: 16px;
        }

        .modal-title {
            font-family: "DM Serif Display", serif;
            font-size: 18px;
            color: var(--ink);
            margin-bottom: 8px;
        }

        .modal-body { font-size: 13.5px; color: var(--ink-3); line-height: 1.5; margin-bottom: 24px; }
        .modal-actions { display: flex; gap: 10px; justify-content: flex-end; }

        @media (max-width: 600px) {
            .search-input { width: 140px; }
            th:nth-child(2), td:nth-child(2) { display: none; }
            td { padding: 12px 16px; }
            .user-detail-fields { grid-template-columns: 1fr; }
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

<?php if ($viewUser): ?>
<!-- Back button + User Detail View -->
<div style="margin-bottom: 20px;">
    <a href="/admin/admin-users.php" class="btn btn-ghost btn-sm">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
        </svg>
        Back to all users
    </a>
</div>

<div class="user-detail-card">
    <div class="user-detail-header">
        <div class="user-detail-avatar">
            <?php if ($viewUser['avatar']): ?>
                <img src="<?= htmlEncode($viewUser['avatar']) ?>" alt="">
            <?php else: ?>
                <?= strtoupper(substr($viewUser['username'], 0, 1)) ?>
            <?php endif; ?>
        </div>
        <div class="user-detail-info">
            <div class="user-detail-name"><?= htmlEncode($viewUser['username']) ?></div>
            <div class="user-detail-meta"><?= htmlEncode($viewUser['email']) ?> &nbsp;·&nbsp; Joined <?= date('F j, Y', strtotime($viewUser['created_at'])) ?></div>
        </div>
        <div class="user-detail-actions">
            <?php if ($viewUser['role'] === 'admin'): ?>
                <span class="role-admin">Admin</span>
            <?php endif; ?>
            <?php if ($viewUser['id'] !== $_SESSION['user_id']): ?>
            <button type="button" class="btn btn-danger-ghost btn-sm" onclick="openDeleteModal(<?= $viewUser['id'] ?>, '<?= htmlEncode($viewUser['username'], true) ?>')">Delete user</button>
            <form method="POST" style="display:none;" id="deleteForm">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="action" value="delete_user">
                <input type="hidden" name="id" id="deleteItemId" value="">
            </form>
            <?php endif; ?>
        </div>
    </div>
    <div class="user-detail-fields">
        <div class="field-row">
            <span class="field-label">User ID</span>
            <span class="field-value" style="font-family: monospace; font-size: 13px; color: var(--ink-3);">#<?= $viewUser['id'] ?></span>
        </div>
        <div class="field-row">
            <span class="field-label">Items</span>
            <span class="field-value"><?= $viewUser['item_count'] ?></span>
        </div>
        <div class="field-row">
            <span class="field-label">Role</span>
            <span class="field-value"><?= $viewUser['role'] === 'admin' ? '<span class="role-admin">Admin</span>' : 'User' ?></span>
        </div>
        <div class="field-row">
            <span class="field-label">Status</span>
            <span class="field-value" style="color: #2A6B2A;">Active</span>
        </div>
    </div>
</div>

<?php if (!empty($userItems)): ?>
<div class="table-card">
    <div class="table-card-header">
        <span class="table-card-title">User's Items</span>
        <span class="table-card-count"><?= count($userItems) ?> items</span>
    </div>
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Description</th>
                <th>Created</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($userItems as $item): ?>
            <tr>
                <td><span class="user-name"><?= htmlEncode($item['title']) ?></span></td>
                <td><span class="user-email"><?= htmlEncode($item['description'] ?? '—') ?></span></td>
                <td class="user-date"><?= date('M j, Y', strtotime($item['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
<div class="table-card">
    <div class="table-card-header">
        <span class="table-card-title">User's Items</span>
    </div>
    <div style="padding: 40px; text-align: center; color: var(--ink-3); font-size: 14px;">
        No items created yet.
    </div>
</div>
<?php endif; ?>

<?php else: ?>
<!-- User List View -->
<div class="table-card">
    <div class="table-card-header">
        <div>
            <span class="table-card-title">All Users</span>
            <span class="table-card-count"><?= $totalUsers ?> total</span>
        </div>
        <form method="GET" style="display: flex; gap: 10px; align-items: center;">
            <div class="search-wrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text" name="search" class="search-input" placeholder="Search users…" value="<?= htmlEncode($search) ?>">
            </div>
            <button type="submit" class="btn btn-ghost btn-sm">Search</button>
            <?php if ($search): ?>
                <a href="/admin/admin-users.php" class="btn btn-ghost btn-sm">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (empty($users)): ?>
    <div style="padding: 48px 24px; text-align: center; color: var(--ink-3); font-size: 14px;">
        No users found<?= $search ? " matching \"$search\"" : '' ?>.
    </div>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>User</th>
                <th>Email</th>
                <th>Role</th>
                <th>Items</th>
                <th>Joined</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><span class="user-name"><?= htmlEncode($u['username']) ?></span></td>
                <td><span class="user-email"><?= htmlEncode($u['email']) ?></span></td>
                <td>
                    <?php if ($u['role'] === 'admin'): ?>
                        <span class="role-admin">Admin</span>
                    <?php else: ?>
                        <span style="font-size: 12px; color: var(--ink-3);">User</span>
                    <?php endif; ?>
                </td>
                <td><span style="font-size: 13px; font-weight: 500; color: var(--ink-2);"><?= $u['item_count'] ?></span></td>
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
<?php endif; ?>

<!-- Delete Modal -->
<div class="modal-backdrop" id="deleteBackdrop" onclick="if(event.target===this)closeDeleteModal()">
    <div class="modal-box">
        <div class="modal-title">Delete User</div>
        <div class="modal-body">Delete user "<span id="deleteItemTitle"></span>"? This will also delete all their items.</div>
        <div class="modal-actions">
            <button type="button" class="btn btn-ghost" onclick="closeDeleteModal()">Cancel</button>
            <button type="button" class="btn btn-danger" onclick="document.getElementById('deleteForm').submit()">Delete</button>
        </div>
    </div>
</div>

<script>
    function openDeleteModal(id, username) {
        document.getElementById('deleteItemId').value = id;
        document.getElementById('deleteItemTitle').textContent = username;
        document.getElementById('deleteBackdrop').classList.add('open');
    }

    function closeDeleteModal() {
        document.getElementById('deleteBackdrop').classList.remove('open');
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeDeleteModal();
    });
</script>

<?php include __DIR__ . '/../includes/page-footer.php'; ?>