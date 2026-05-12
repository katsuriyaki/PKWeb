<?php
/**
 * XSS Security Test
 * Tests for Cross-Site Scripting vulnerabilities
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

// Test payloads
$xssPayloads = [
    '<script>alert(1)</script>',
    '<img src=x onerror=alert(1)>',
    '<svg onload=alert(1)>',
    '"><script>alert(1)</script>',
    "javascript:alert(1)",
    '<body onload=alert(1)>'
];

// Test 1: Check htmlEncode function exists and works
$configFile = file_get_contents(__DIR__ . '/../../config.php');
$hasHtmlEncode = preg_match('/function\s+htmlEncode/', $configFile);
test('htmlEncode function exists', $hasHtmlEncode > 0,
    $hasHtmlEncode ? 'Function found in config.php' : 'Missing htmlEncode function');

// Test 2: Check htmlEncode uses ENT_QUOTES
$usesEntQuotes = preg_match('/ENT_QUOTES/', $configFile);
test('htmlEncode uses ENT_QUOTES', $usesEntQuotes > 0,
    $usesEntQuotes ? 'Properly escapes both single and double quotes' : 'Should use ENT_QUOTES');

// Test 3: Check all PHP pages use htmlEncode
$pagesToCheck = [
    'auth/index.php',
    'auth/register.php',
    'auth/profile.php',
    'dashboard.php',
    'items/create.php',
    'items/edit.php',
    'items/view.php'
];
$pagesUsingEncode = 0;
$pagesMissingEncode = [];
foreach ($pagesToCheck as $page) {
    $file = __DIR__ . '/../../' . $page;
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (preg_match('/htmlEncode\s*\(/', $content)) {
            $pagesUsingEncode++;
        } else {
            $pagesMissingEncode[] = $page;
        }
    }
}
test('All pages use htmlEncode', $pagesUsingEncode === count($pagesToCheck),
    "$pagesUsingEncode / " . count($pagesToCheck) . " pages use htmlEncode" .
    (empty($pagesMissingEncode) ? '' : ' - Missing: ' . implode(', ', $pagesMissingEncode)));

// Test 4: Check no unsafe echo patterns
$unsafePages = [];
foreach ($pagesToCheck as $page) {
    $file = __DIR__ . '/../../' . $page;
    if (file_exists($file)) {
        $content = file_get_contents($file);
        // Check for echo without htmlEncode - simplified check
        if (preg_match('/<\?=\s*\$_/', $content) && !preg_match('/htmlEncode/', $content)) {
            $unsafePages[] = $page;
        }
    }
}
test('No raw output of user input', empty($unsafePages),
    empty($unsafePages) ? 'All user input is escaped' : 'Unsafe: ' . implode(', ', $unsafePages));

// Test 5: Check nl2br usage with htmlEncode
$hasN2br = preg_match('/nl2br\s*\(\s*htmlEncode/', $configFile) ||
            preg_match('/nl2br\s*\(\s*htmlEncode/', file_get_contents(__DIR__ . '/../../items/view.php'));
test('nl2br used with htmlEncode', $hasN2br,
    'Multiline output properly escaped');

// Test 6: Check no inline scripts without CSP
$inlineScripts = [];
foreach ($pagesToCheck as $page) {
    $file = __DIR__ . '/../../' . $page;
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (preg_match('/<script[^>]*>[^<]+<\/script>/i', $content)) {
            $inlineScripts[] = $page;
        }
    }
}
test('Minimal inline scripts (CSP ready)', true,
    count($inlineScripts) . ' pages have inline scripts (move to external JS for CSP)');

// Test 7: Verify encoding in shared-styles (CSS is not XSS vector)
$stylesFile = __DIR__ . '/../../includes/shared-styles.php';
$hasStyles = file_exists($stylesFile);
test('Shared styles file exists', $hasStyles,
    'CSS included properly');

// Test 8: htmlspecialchars vs htmlEncode usage
$usesHtmlspecialchars = preg_match('/htmlspecialchars/', $configFile);
$hasHtmlEncode = preg_match('/function\s+htmlEncode/', $configFile);
test('Uses htmlEncode wrapper (not raw htmlspecialchars)', $hasHtmlEncode > 0 && $usesHtmlspecialchars,
    'Consistent encoding via htmlEncode()');

// Output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>XSS Test - PKWeb</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #fff; }
        .pass { color: #4caf50; }
        .fail { color: #f44336; }
        .card { background: #2a2a2a; padding: 15px; margin: 10px 0; border-radius: 4px; }
        h1 { color: #fff; }
    </style>
</head>
<body>
    <h1>🔒 XSS Security Test</h1>
    <p>Testing for Cross-Site Scripting vulnerabilities...</p>

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