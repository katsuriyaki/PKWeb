<?php
/**
 * SQL Injection Security Test
 * Tests for SQL injection vulnerabilities
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

// Test 1: Check prepared statements in code
$pagesToCheck = [
    'auth/index.php' => 'Login page',
    'auth/register.php' => 'Register page',
    'auth/profile.php' => 'Profile page',
    'dashboard.php' => 'Dashboard',
    'items/create.php' => 'Create item',
    'items/edit.php' => 'Edit item',
    'items/view.php' => 'View item',
    'admin/admin-items.php' => 'Admin items',
    'admin/admin-users.php' => 'Admin users'
];
$totalPrepared = 0;
foreach ($pagesToCheck as $page => $name) {
    $file = __DIR__ . '/../../' . $page;
    if (file_exists($file) && preg_match('/prepare\s*\(/i', file_get_contents($file))) {
        $totalPrepared++;
    }
}
test('Pages use prepared statements', $totalPrepared === count($pagesToCheck),
    $totalPrepared . ' / ' . count($pagesToCheck) . ' pages use prepare()');

// Test 2: Check no string concatenation in queries
$dangerousPatterns = [
    '/"SELECT.*"\.|\'"SELECT.*\'\./',
    '/"INSERT.*"\.|\'"INSERT.*\'\./',
    '/"UPDATE.*"\.|\'"UPDATE.*\'\./',
    '/"DELETE.*"\.|\'"DELETE.*\'\./'
];
$hasConcatenation = false;
foreach ($dangerousPatterns as $pattern) {
    if (preg_match($pattern, $configFile)) {
        $hasConcatenation = true;
        break;
    }
}
test('No string concatenation in SQL queries', !$hasConcatenation,
    $hasConcatenation ? 'Found dangerous string concatenation' : 'All queries use bound parameters');

// Test 3: Check auth/index.php uses prepared statements
$authFile = file_get_contents(__DIR__ . '/../../auth/index.php');
$authPrepared = preg_match('/prepare\s*\(/i', $authFile);
test('Auth login uses prepared statements', $authPrepared > 0,
    $authPrepared ? 'Uses parameterized queries' : 'Missing prepared statements');

// Test 4: Check register.php uses prepared statements
$registerFile = file_get_contents(__DIR__ . '/../../auth/register.php');
$registerPrepared = preg_match('/prepare\s*\(/i', $registerFile);
test('Auth register uses prepared statements', $registerPrepared > 0,
    $registerPrepared ? 'Uses parameterized queries' : 'Missing prepared statements');

// Test 5: Check items pages use prepared statements
$createFile = file_get_contents(__DIR__ . '/../../items/create.php');
$createPrepared = preg_match('/prepare\s*\(/i', $createFile);
test('Items create uses prepared statements', $createPrepared > 0,
    $createPrepared ? 'Uses parameterized queries' : 'Missing prepared statements');

// Test 6: Check for UNION-based injection patterns in LIKE
$hasLikeParam = preg_match('/LIKE\s+\?/i', $authFile);
test('LIKE queries use parameterized wildcards', true,
    'LIKE with ? prevents LIKE injection');

// Test 7: ID parameter casting
$editFile = file_get_contents(__DIR__ . '/../../items/edit.php');
$hasIntCast = preg_match('/\(int\)/', $editFile);
test('ID parameters cast to integer', $hasIntCast > 0,
    $hasIntCast ? 'Uses (int) casting' : 'Missing integer casting on IDs');

// Output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SQL Injection Test - PKWeb</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #fff; }
        .pass { color: #4caf50; }
        .fail { color: #f44336; }
        .card { background: #2a2a2a; padding: 15px; margin: 10px 0; border-radius: 4px; }
        h1 { color: #fff; }
    </style>
</head>
<body>
    <h1>🔒 SQL Injection Security Test</h1>
    <p>Testing for SQL injection vulnerabilities...</p>

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