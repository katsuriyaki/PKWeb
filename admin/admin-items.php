<?php
require_once __DIR__ . '/../config.php';
requireAdmin();

$page = 'admin-items';
$pageTitle = 'All Items';
$breadcrumb = 'Admin / All Items';

$error = '';
$success = '';

if (isset($_GET['deleted'])) {
    $success = 'Item deleted successfully.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request';
    } else {
        $id = (int)$_POST['id'];
        try {
            $db = getDB();
            $stmt = $db->prepare("DELETE FROM items WHERE id = ?");
            $stmt->execute([$id]);
            $success = 'Item deleted successfully.';
        } catch (PDOException $e) {
            $error = 'Failed to delete item.';
        }
    }
}

$search = trim($_GET['search'] ?? '');
try {
    $db = getDB();
    $sql = "SELECT i.*, u.username FROM items i JOIN users u ON u.id = i.user_id";
    $params = [];
    if ($search !== '') {
        $sql .= " WHERE i.title LIKE ? OR u.username LIKE ?";
        $params = ["%$search%", "%$search%"];
    }
    $sql .= " ORDER BY i.created_at DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll();

    $stmt = $db->prepare("SELECT COUNT(*) as total FROM items");
    $stmt->execute();
    $totalItems = $stmt->fetch()['total'];
} catch (PDOException $e) {
    $error = 'Failed to load items.';
    $items = [];
    $totalItems = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Items — Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,300&display=swap" rel="stylesheet">
    <style>
        <?php include __DIR__ . '/includes/shared-styles.php'; echo $shared_css; ?>

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

        /* ── Stats row ────────────────────────────── */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
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

        .table-card-count { font-size: 12px; color: var(--ink-3); font-weight: 400; margin-left: 6px; }

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

        .item-name { font-size: 14px; font-weight: 500; color: var(--ink); }
        .item-desc { font-size: 12.5px; color: var(--ink-3); max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .item-date { font-size: 12.5px; color: var(--ink-3); white-space: nowrap; }
        .item-date-day { font-weight: 500; color: var(--ink-2); }

        .user-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 3px 10px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            color: var(--ink-2);
        }

        .user-chip-avatar {
            width: 18px; height: 18px;
            background: var(--accent);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 9px; font-weight: 600; color: #fff;
        }

        .actions-cell { white-space: nowrap; }
        .actions-group { display: flex; gap: 6px; align-items: center; }

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
            .stats-row { grid-template-columns: 1fr; }
            .search-input { width: 140px; }
            th:nth-child(2), td:nth-child(2) { display: none; }
            td { padding: 12px 16px; }
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/includes/page-header.php'; ?>

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

<div class="stats-row">
    <div class="stat-card accent-card">
        <div class="stat-label">Total Items</div>
        <div class="stat-value"><?= $totalItems ?></div>
        <div class="stat-sub">across all users</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Displayed</div>
        <div class="stat-value"><?= count($items) ?></div>
        <div class="stat-sub"><?= $search ? 'matching search' : 'all items' ?></div>
    </div>
</div>

<div class="table-card">
    <div class="table-card-header">
        <div>
            <span class="table-card-title">All Items</span>
        </div>
        <form method="GET" style="display: flex; gap: 10px; align-items: center;">
            <div class="search-wrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text" name="search" class="search-input" placeholder="Search items…" value="<?= htmlEncode($search) ?>">
            </div>
            <button type="submit" class="btn btn-ghost btn-sm">Search</button>
            <?php if ($search): ?>
                <a href="admin-items.php" class="btn btn-ghost btn-sm">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (empty($items)): ?>
    <div style="padding: 48px 24px; text-align: center; color: var(--ink-3); font-size: 14px;">
        No items found<?= $search ? " matching \"$search\"" : '' ?>.
    </div>
    <?php else: ?>
    <table id="itemsTable">
        <thead>
            <tr>
                <th>Title</th>
                <th>Description</th>
                <th>Owner</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><span class="item-name"><?= htmlEncode($item['title']) ?></span></td>
                <td><span class="item-desc"><?= htmlEncode($item['description'] ?? '—') ?></span></td>
                <td>
                    <span class="user-chip">
                        <span class="user-chip-avatar"><?= strtoupper(substr($item['username'], 0, 1)) ?></span>
                        <?= htmlEncode($item['username']) ?>
                    </span>
                </td>
                <td class="item-date">
                    <span class="item-date-day"><?= date('M j', strtotime($item['created_at'])) ?></span>, <?= date('Y', strtotime($item['created_at'])) ?>
                </td>
                <td class="actions-cell">
                    <div class="actions-group">
                        <a href="../view.php?id=<?= $item['id'] ?>" class="btn btn-ghost btn-sm">View</a>
                        <button type="button" class="btn btn-danger-ghost btn-sm" onclick="openDeleteModal(<?= $item['id'] ?>, '<?= htmlEncode($item['title'], true) ?>')">Delete</button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- Delete Modal -->
<div class="modal-backdrop" id="deleteBackdrop" onclick="if(event.target===this)closeDeleteModal()">
    <div class="modal-box">
        <div class="modal-title">Delete Item</div>
        <div class="modal-body">Delete "<span id="deleteItemTitle"></span>"? This cannot be undone.</div>
        <div class="modal-actions">
            <button type="button" class="btn btn-ghost" onclick="closeDeleteModal()">Cancel</button>
            <form method="POST" style="display:inline;" id="deleteForm">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="deleteItemId" value="">
                <button type="submit" class="btn btn-danger">Delete</button>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/page-footer.php'; ?>

<script>
    function openDeleteModal(id, title) {
        document.getElementById('deleteItemId').value = id;
        document.getElementById('deleteItemTitle').textContent = title;
        document.getElementById('deleteBackdrop').classList.add('open');
    }

    function closeDeleteModal() {
        document.getElementById('deleteBackdrop').classList.remove('open');
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeDeleteModal();
    });
</script>
</body>
</html>