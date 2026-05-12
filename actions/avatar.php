<?php
// Avatar upload/remove handler

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['avatar_action'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request']);
        exit;
    }

    if ($_POST['avatar_action'] === 'upload') {
        if (!isset($_FILES['avatar_file']) || $_FILES['avatar_file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['error' => 'No file uploaded.']);
            exit;
        }

        $file = $_FILES['avatar_file'];

        // Server-side MIME verification (not client-supplied type)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $realMime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($realMime, $allowed)) {
            echo json_encode(['error' => 'Only JPG, PNG, GIF, or WebP allowed.']);
            exit;
        }
        if ($file['size'] > 2 * 1024 * 1024) {
            echo json_encode(['error' => 'Image must be under 2MB.']);
            exit;
        }

        $dir = __DIR__ . '/../uploads/avatars';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        // Get old avatar before updating
        $db = getDB();
        $stmt = $db->prepare("SELECT avatar FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $old = $stmt->fetch()['avatar'] ?? null;

        // Path traversal protection
        $oldPath = null;
        if ($old) {
            $resolved = realpath(__DIR__ . '/../' . $old);
            $allowedDir = realpath(__DIR__ . '/../uploads/avatars');
            if ($resolved && $allowedDir && strpos($resolved, $allowedDir) === 0) {
                $oldPath = $resolved;
            }
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $user_id . '_' . time() . '.' . $ext;
        $path = '/uploads/avatars/' . $filename;
        $dest = $dir . '/' . $filename;

        if (is_uploaded_file($file['tmp_name'])) {
            $ok = move_uploaded_file($file['tmp_name'], $dest);
        } else {
            $ok = copy($file['tmp_name'], $dest);
        }

        if ($ok && file_exists($dest)) {
            // Update DB first, then delete old file (prevents race condition)
            $stmt = $db->prepare("UPDATE users SET avatar = ? WHERE id = ?");
            $stmt->execute([$path, $user_id]);
            $_SESSION['avatar'] = $path;
            if ($oldPath) {
                @unlink($oldPath);
            }
            echo json_encode(['success' => true, 'path' => $path]);
        } else {
            echo json_encode(['error' => 'Failed to save image.']);
        }
        exit;

    } elseif ($_POST['avatar_action'] === 'remove') {
        $db = getDB();
        $stmt = $db->prepare("SELECT avatar FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $old = $stmt->fetch()['avatar'] ?? null;

        // Path traversal protection
        $oldPath = null;
        if ($old) {
            $resolved = realpath(__DIR__ . '/../' . $old);
            $allowedDir = realpath(__DIR__ . '/../uploads/avatars');
            if ($resolved && $allowedDir && strpos($resolved, $allowedDir) === 0) {
                $oldPath = $resolved;
            }
        }

        $stmt = $db->prepare("UPDATE users SET avatar = NULL WHERE id = ?");
        $stmt->execute([$user_id]);
        $_SESSION['avatar'] = null;
        if ($oldPath) {
            @unlink($oldPath);
        }
        echo json_encode(['success' => true]);
        exit;
    }
}