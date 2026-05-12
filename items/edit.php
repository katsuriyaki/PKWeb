<?php
require_once __DIR__ . '/../config.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: /dashboard.php'); exit; }

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM items WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    $item = $stmt->fetch();
} catch (PDOException $e) {
    $item = null;
}

if (!$item) { header('Location: /dashboard.php'); exit; }

$page = 'edit';
$pageTitle = 'Edit Item';
$breadcrumb = $_SESSION['username'] . ' / Edit Item';
$topbarActions = '<a href="/items/view.php?id=' . $id . '" class="btn btn-ghost">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
        <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
    </svg>
    Back
</a>';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request';
    } else {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (empty($title)) {
            $error = 'Title is required';
        } elseif (strlen($title) > 100) {
            $error = 'Title must be less than 100 characters';
        } else {
            try {
                $db = getDB();
                $stmt = $db->prepare("UPDATE items SET title = ?, description = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
                $stmt->execute([$title, $description, $id, $_SESSION['user_id']]);
                header('Location: /dashboard.php');
                exit;
            } catch (PDOException $e) {
                $error = 'Failed to update item';
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
    <title>Edit Item — PKWeb</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,300&display=swap" rel="stylesheet">
    <style>
        <?php include __DIR__ . '/../includes/shared-styles.php'; echo $shared_css; ?>
    </style>
</head>
<body>
<?php include __DIR__ . '/../includes/page-header.php'; ?>

<?php if ($error): ?>
<div class="alert alert-danger">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
    </svg>
    <?= htmlEncode($error) ?>
</div>
<?php endif; ?>

<div class="form-card">
    <h1 class="form-card-title">Edit Item</h1>
    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <div class="form-group">
            <label for="title" class="form-label">Title <span class="required">*</span></label>
            <input type="text" class="form-control" id="title" name="title" required maxlength="100" placeholder="Item title" value="<?= htmlEncode($item['title']) ?>">
            <div class="form-hint">Max 100 characters</div>
        </div>
        <div class="form-group">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control" id="description" name="description" rows="5" placeholder="Optional description"><?= htmlEncode($item['description']) ?></textarea>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                Save Changes
            </button>
            <a href="/dashboard.php" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/page-footer.php'; ?>