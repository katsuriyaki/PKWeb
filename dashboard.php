<?php
require_once __DIR__ . '/../config.php';
requireLogin();

$page = 'dashboard';
$pageTitle = 'All Items';
$breadcrumb = $_SESSION['username'];

$success = '';
$error = '';

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
            $stmt = $db->prepare("DELETE FROM items WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $_SESSION['user_id']]);
            $success = 'Item deleted successfully.';
        } catch (PDOException $e) {
            $error = 'Failed to delete item.';
        }
    }
}

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM items WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $items = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Failed to load items.';
    $items = [];
}

$topbarActions = '<button class="btn btn-ghost" onclick="location.reload()">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
    </svg>
    <span>Refresh</span>
</button>
<a href="items/create.php" class="btn btn-primary">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
    </svg>
    <span>New Item</span>
</a>';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — PKWeb</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,300&display=swap" rel="stylesheet">
    <style>
        <?php include __DIR__ . '/includes/shared-styles.php'; echo $shared_css; ?>

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
            background: var(--accent);
            border-color: var(--accent);
        }

        .stat-card.accent-card .stat-label,
        .stat-card.accent-card .stat-value,
        .stat-card.accent-card .stat-sub { color: #fff; }

        .stat-card.accent-card .stat-label { opacity: 0.7; }
        .stat-card.accent-card .stat-sub { opacity: 0.75; }

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
            letter-spacing: 0.1px;
        }

        .table-card-count { font-size: 12px; color: var(--ink-3); font-weight: 400; }

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

        /* ── Data Table ───────────────────────────── */
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
            padding: 16px 24px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        tbody tr:last-child td { border-bottom: none; }
        tbody tr { transition: background 0.12s; }
        tbody tr:hover { background: #FAF7F2; }

        .item-name {
            font-weight: 600;
            font-size: 14px;
            color: var(--ink);
            text-decoration: none;
            transition: color 0.15s;
        }

        .item-name:hover { color: var(--accent); }

        .item-desc {
            color: var(--ink-3);
            font-size: 13px;
            max-width: 400px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .item-date { font-size: 12.5px; color: var(--ink-3); white-space: nowrap; }
        .item-date-day { font-weight: 500; color: var(--ink-2); }

        .actions-cell { white-space: nowrap; }
        .actions-group { display: flex; gap: 6px; align-items: center; }

        /* ── Empty state ──────────────────────────── */
        .empty-state { padding: 80px 40px; text-align: center; }

        .empty-icon {
            width: 72px; height: 72px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
        }

        .empty-icon svg { width: 28px; height: 28px; stroke: var(--ink-3); }

        .empty-title {
            font-family: "DM Serif Display", serif;
            font-size: 22px;
            color: var(--ink);
            margin-bottom: 8px;
        }

        .empty-sub { font-size: 14px; color: var(--ink-3); margin-bottom: 28px; }

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
            .search-input, .search-input:focus { width: 140px; }
            .topbar-actions .btn span { display: none; }
            th:nth-child(2), td:nth-child(2) { display: none; }
            td { padding: 14px 16px; }
            th { padding: 12px 16px; }
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/includes/page-header.php'; ?>

<?php if ($success || $error): ?>
<div class="alert <?= $success ? 'alert-success' : 'alert-danger' ?>">
    <?php if ($success): ?>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <polyline points="20 6 9 17 4 12"/>
        </svg>
    <?php else: ?>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
    <?php endif; ?>
    <?= htmlEncode($success ?: $error) ?>
</div>
<?php endif; ?>

<div class="stats-row">
    <div class="stat-card accent-card">
        <div class="stat-label">Total Items</div>
        <div class="stat-value"><?= count($items) ?></div>
        <div class="stat-sub">in your library</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">This Month</div>
        <div class="stat-value">
            <?php
                $thisMonth = array_filter($items, fn($i) => date('Y-m', strtotime($i['created_at'])) === date('Y-m'));
                echo count($thisMonth);
            ?>
        </div>
        <div class="stat-sub">items added</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Last Activity</div>
        <div class="stat-value" style="font-size: 20px; padding-top: 6px;">
            <?= !empty($items) ? date('M j', strtotime($items[0]['created_at'])) : '—' ?>
        </div>
        <div class="stat-sub"><?= !empty($items) ? date('Y', strtotime($items[0]['created_at'])) : 'no items yet' ?></div>
    </div>
</div>

<div class="table-card">
    <div class="table-card-header">
        <div>
            <span class="table-card-title">Items</span>
            <span class="table-card-count"> &middot; <?= count($items) ?> total</span>
        </div>
        <div class="search-wrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input type="text" class="search-input" id="searchInput" placeholder="Filter items…" oninput="filterItems(this.value)">
        </div>
    </div>

<?php if (empty($items)): ?>
    <div class="empty-state">
        <div class="empty-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
            </svg>
        </div>
        <div class="empty-title">Nothing here yet</div>
        <div class="empty-sub">Create your first item to get started.</div>
        <a href="items/create.php" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Create Item
        </a>
    </div>
<?php else: ?>
    <table id="itemsTable">
        <thead>
            <tr>
                <th>Name</th>
                <th>Description</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><a href="items/view.php?id=<?= $item['id'] ?>" class="item-name"><?= htmlEncode($item['title']) ?></a></td>
                <td><span class="item-desc"><?= htmlEncode($item['description'] ?? 'No description') ?></span></td>
                <td class="item-date">
                    <span class="item-date-day"><?= date('M j', strtotime($item['created_at'])) ?></span>, <?= date('Y', strtotime($item['created_at'])) ?>
                </td>
                <td class="actions-cell">
                    <div class="actions-group">
                        <a href="items/view.php?id=<?= $item['id'] ?>" class="btn btn-ghost btn-sm">View</a>
                        <a href="items/edit.php?id=<?= $item['id'] ?>" class="btn btn-ghost btn-sm">Edit</a>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                            <button type="button" class="btn btn-danger-ghost btn-sm" onclick="openDeleteModal(<?= $item['id'] ?>, '<?= htmlEncode($item['title'], true) ?>')">Delete</button>
                        </form>
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
    function filterItems(q) {
        const rows = document.querySelectorAll('#itemsTable tbody tr');
        const lower = q.toLowerCase().trim();
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(lower) ? '' : 'none';
        });
    }

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