<?php
/**
 * Session Security Test
 * Tests for session management vulnerabilities
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

// Test 1: HttpOnly cookie
$hasHttpOnly = preg_match('/cookie_httponly/i', $configFile);
test('HttpOnly cookie enabled', $hasHttpOnly > 0,
    $hasHttpOnly ? 'JS cannot access session cookies' : 'Should set session.cookie_httponly = 1');

// Test 2: Secure cookie (HTTPS)
$hasSecure = preg_match('/cookie_secure/i', $configFile);
test('Secure cookie configured', $hasSecure > 0,
    $hasSecure ? 'Cookies secured on HTTPS' : 'Should enable secure on HTTPS');

// Test 3: Strict mode
$hasStrictMode = preg_match('/use_strict_mode/i', $configFile);
test('Session strict mode enabled', $hasStrictMode > 0,
    $hasStrictMode ? 'Prevents session hijacking via session fixation' : 'Should enable strict mode');

// Test 4: SameSite attribute
$hasSameSite = preg_match('/cookie_samesite.*Strict/', $configFile);
test('SameSite=Strict cookie set', $hasSameSite > 0,
    $hasSameSite ? 'CSRF protection via SameSite' : 'Should set to Strict');

// Test 5: Session regeneration
$hasRegeneration = preg_match('/session_regenerate_id/', $configFile);
test('Session ID regeneration configured', $hasRegeneration > 0,
    $hasRegeneration ? 'Prevents session fixation attacks' : 'Should call session_regenerate_id()');

// Test 6: Regeneration on timeout
$hasTimeoutRegen = preg_match('/session_regenerate_id.*true/', $configFile);
test('Session regenerated after timeout', $hasTimeoutRegen > 0,
    $hasTimeoutRegen ? 'Periodic session rotation active' : 'Should regenerate after timeout');

// Test 7: No session ID in URL
$noUrlSession = !preg_match('/session_id\s*\(|SID|trans_sid/', $configFile);
test('Session ID not exposed in URL', $noUrlSession,
    $noUrlSession ? 'No session leakage in URLs' : 'Should not use SID');

// Test 8: Only cookies
$onlyCookies = preg_match("/use_only_cookies.*1/", $configFile);
test('Use only cookies (no URL rewriting)', $onlyCookies > 0,
    $onlyCookies ? 'Sessions managed via cookies only' : 'Consider enabling use_only_cookies');

// Test 9: Check auth files for session handling
$authFiles = ['auth/index.php', 'auth/register.php'];
$authRegen = 0;
foreach ($authFiles as $file) {
    $path = __DIR__ . '/../../' . $file;
    if (file_exists($path) && preg_match('/session_regenerate_id/', file_get_contents($path))) {
        $authRegen++;
    }
}
test('Auth pages regenerate session on login', $authRegen === count($authFiles),
    $authRegen . ' / ' . count($authFiles) . ' auth pages regenerate');

// Test 10: Logout destroys session
$logoutFile = __DIR__ . '/../../auth/logout.php';
$logoutContent = file_exists($logoutFile) ? file_get_contents($logoutFile) : '';
$hasDestroy = preg_match('/session_destroy/', $logoutContent);
$hasClear = preg_match('/\$_SESSION\s*=\s*\[\]/', $logoutContent);
test('Logout clears and destroys session', $hasDestroy && $hasClear,
    ($hasDestroy && $hasClear) ? 'Proper session cleanup on logout' : 'Should clear and destroy session');

// Output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Session Security Test - PKWeb</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #fff; }
        .pass { color: #4caf50; }
        .fail { color: #f44336; }
        .card { background: #2a2a2a; padding: 15px; margin: 10px 0; border-radius: 4px; }
        h1 { color: #fff; }
    </style>
</head>
<body>
    <h1>🔒 Session Security Test</h1>
    <p>Testing for session management vulnerabilities...</p>

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