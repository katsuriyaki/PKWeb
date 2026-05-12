<?php
require_once __DIR__ . '/../config.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: dashboard.php');
    exit;
}

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM items WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    $item = $stmt->fetch();
} catch (PDOException $e) {
    $item = null;
}

if (!$item) {
    header('Location: dashboard.php');
    exit;
}

$page = 'view';
$pageTitle = htmlEncode($item['title']);
$breadcrumb = $_SESSION['username'] . ' / View';
$topbarActions = '<a href="../dashboard.php" class="btn btn-ghost">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
        <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
    </svg>
    Back
</a>';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlEncode($item['title']) ?> — PKWeb</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,300&display=swap" rel="stylesheet">
    <style>
        <?php include __DIR__ . '/includes/shared-styles.php'; echo $shared_css; ?>

        /* ── Detail Card ─────────────────────────── */
        .detail-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 32px 36px;
            flex: 1;
        }

        .detail-header {
            padding-bottom: 24px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 28px;
        }

        .detail-header h1 {
            font-family: "DM Serif Display", serif;
            font-size: 22px;
            color: var(--ink);
            letter-spacing: -0.2px;
        }

        .detail-meta { display: flex; gap: 32px; margin-bottom: 28px; flex-wrap: wrap; }

        .detail-label {
            font-size: 10.5px;
            font-weight: 600;
            letter-spacing: 1.8px;
            text-transform: uppercase;
            color: var(--ink-3);
            margin-bottom: 6px;
        }

        .detail-value { font-size: 14px; color: var(--ink-2); line-height: 1.5; }

        .detail-body { margin-bottom: 32px; }
        .detail-body .detail-label { margin-bottom: 10px; }

        .detail-body-content {
            font-size: 14px;
            color: var(--ink-2);
            line-height: 1.7;
            white-space: pre-wrap;
        }

        .form-actions { display: flex; align-items: center; gap: 10px; }

        @media (max-width: 600px) {
            .detail-card { padding: 24px; }
            .detail-header h1 { font-size: 20px; }
            .detail-meta { gap: 20px; }
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/includes/page-header.php'; ?>

<div class="detail-card">
    <div class="detail-header">
        <h1><?= htmlEncode($item['title']) ?></h1>
    </div>

    <div class="detail-meta">
        <div class="detail-field">
            <div class="detail-label">Created</div>
            <div class="detail-value"><?= htmlEncode(date('F j, Y \a\t g:i a', strtotime($item['created_at']))) ?></div>
        </div>
        <?php if (!empty($item['updated_at']) && $item['updated_at'] !== $item['created_at']): ?>
        <div class="detail-field">
            <div class="detail-label">Updated</div>
            <div class="detail-value"><?= htmlEncode(date('F j, Y \a\t g:i a', strtotime($item['updated_at']))) ?></div>
        </div>
        <?php endif; ?>
    </div>

    <div class="detail-body">
        <div class="detail-label">Description</div>
        <div class="detail-body-content"><?= nl2br(htmlEncode($item['description'] ?? 'No description provided.')) ?></div>
    </div>

    <div class="form-actions">
        <a href="edit.php?id=<?= $item['id'] ?>" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>
            Edit Item
        </a>
        <a href="../dashboard.php" class="btn btn-ghost">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
            </svg>
            Back to Dashboard
        </a>
    </div>
</div>

<?php include __DIR__ . '/includes/page-footer.php'; ?>