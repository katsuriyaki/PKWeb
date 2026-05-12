<?php
// Delete item handler (AJAX or redirect)

if (!isset($_SESSION['user_id'])) {
    if (isset($_GET['json'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        if (isset($_GET['json'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid request']);
            exit;
        }
        header('Location: dashboard.php');
        exit;
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        header('Location: dashboard.php');
        exit;
    }

    $db = getDB();
    $stmt = $db->prepare("DELETE FROM items WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);

    if (isset($_GET['json'])) {
        echo json_encode(['success' => true]);
        exit;
    }

    header('Location: dashboard.php?deleted=1');
    exit;
}

header('Location: dashboard.php');
exit;