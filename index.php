<?php
require_once __DIR__ . '/config.php';
if (isLoggedIn()) {
    header('Location: /dashboard.php');
} else {
    header('Location: /auth/index.php');
}
exit;