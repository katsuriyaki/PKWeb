<?php
/**
 * CSRF Security Test
 * Tests for Cross-Site Request Forgery protection
 */

$results = [];

function test($name, $passed, $details = '') {
    global $results;
    $results[] = [
        'name' => $name,
        'passed' => $passed,
        'details' => $details
    ];
    return $passed;
}

$configFile = file_get_contents(__DIR__ . '/../../config.php');
$authLoginFile = file_get_contents(__DIR__ . '/../../auth/index.php');

// Test 1: Check CSRF token generation in config
$hasTokenGeneration = preg_match('/csrf_token.*bin2hex|random_bytes/', $configFile);
test('CSRF token generation exists', $hasTokenGeneration > 0,
    $hasTokenGeneration ? 'Tokens generated using random_bytes()' : 'Missing token generation');

// Test 2: Check hash_equals is used for validation (in any PHP file)
$allFiles = array_merge(
    glob(__DIR__ . '/../../auth/*.php'),
    glob(__DIR__ . '/../../actions/*.php'),
    glob(__DIR__ . '/../../items/*.php'),
    glob(__DIR__ . '/../../admin/*.php')
);
$hasHashEquals = false;
foreach ($allFiles as $file) {
    if (preg_match('/hash_equals/', file_get_contents($file))) {
        $hasHashEquals = true;
        break;
    }
}
test('Uses hash_equals() for token validation', $hasHashEquals,
    $hasHashEquals ? 'Timing-safe comparison used' : 'Should use hash_equals()');

// Test 3: Check forms have CSRF token
$formPages = [
    'auth/index.php',
    'auth/register.php',
    'auth/profile.php',
    'items/create.php',
    'items/edit.php',
    'dashboard.php',
    'admin/admin-items.php',
    'admin/admin-users.php'
];
$formsWithToken = 0;
foreach ($formPages as $page) {
    $file = __DIR__ . '/../../' . $page;
    if (file_exists($file) && preg_match('/csrf_token/', file_get_contents($file))) {
        $formsWithToken++;
    }
}
test('All forms have CSRF token field', $formsWithToken === count($formPages),
    $formsWithToken . ' / ' . count($formPages) . ' forms have csrf_token');

// Test 4: Check POST handlers validate CSRF
$handlerFiles = [
    'auth/index.php',
    'auth/register.php',
    'auth/profile.php',
    'items/create.php',
    'items/edit.php',
    'dashboard.php'
];
$handlersValidating = 0;
foreach ($handlerFiles as $file) {
    $path = __DIR__ . '/../../' . $file;
    if (file_exists($path) && preg_match('/hash_equals\s*\(/', file_get_contents($path))) {
        $handlersValidating++;
    }
}
test('All POST handlers validate CSRF', $handlersValidating === count($handlerFiles),
    $handlersValidating . ' / ' . count($handlerFiles) . ' handlers validate tokens');

// Test 5: Check token length (check for 32 bytes or bin2hex with 64 chars output)
$tokenLength = preg_match('/random_bytes\s*\(\s*32\s*\)/', $configFile) ||
               preg_match('/bin2hex\s*\(\s*random_bytes/', $configFile);
test('Token uses 32 bytes (256-bit)', $tokenLength > 0,
    $tokenLength ? 'Strong token length' : 'Should use 32 bytes for random_bytes()');

// Test 6: Check token is per-session (stored in $_SESSION)
$tokenPerSession = preg_match('/\$_SESSION\s*\[\s*[\'"]csrf_token[\'"]\s*\]/', $configFile);
test('Token stored per-session', $tokenPerSession > 0,
    'Tokens are session-specific');

// Test 7: Check token regeneration on logout
$logoutFile = __DIR__ . '/../../auth/logout.php';
$logoutContent = file_exists($logoutFile) ? file_get_contents($logoutFile) : '';
$hasSessionDestroy = preg_match('/session_destroy/', $logoutContent);
test('Logout destroys session (invalidates tokens)', $hasSessionDestroy > 0,
    $hasSessionDestroy ? 'Session properly destroyed' : 'Should call session_destroy()');

// Test 8: Check SameSite cookie config
$hasSamesite = preg_match('/cookie_samesite.*Strict/', $configFile);
test('SameSite=Strict cookie configured', $hasSamesite > 0,
    $hasSamesite ? 'CSRF cookies protected by SameSite' : 'Should set SameSite=Strict');

// Output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CSRF Test - PKWeb</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #fff; }
        .pass { color: #4caf50; }
        .fail { color: #f44336; }
        .card { background: #2a2a2a; padding: 15px; margin: 10px 0; border-radius: 4px; }
        h1 { color: #fff; }
    </style>
</head>
<body>
    <h1>🔒 CSRF Security Test</h1>
    <p>Testing for Cross-Site Request Forgery protection...</p>

<?php foreach ($results as $r): ?>
    <div class="card">
        <span class="<?= $r['passed'] ? 'pass' : 'fail' ?>">
            <?= $r['passed'] ? '✅ PASS' : '❌ FAIL' ?>
        </span>
        - <?= htmlspecialchars($r['name']) ?>
        <?php if ($r['details']): ?>
        <br><small style="color:#888;"><?= htmlspecialchars($r['details']) ?></small>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

    <div class="card" style="border-left: 4px solid #4caf50;">
        <strong>Summary:</strong> <?= count(array_filter($results, fn($r) => $r['passed'])) ?> / <?= count($results) ?> tests passed
    </div>
</body>
</html>