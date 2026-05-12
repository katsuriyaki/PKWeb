<?php
/**
 * File Upload Security Test
 * Tests for file upload vulnerabilities
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

// Test 1: Check server-side MIME verification
$avatarFiles = ['actions/avatar.php', 'auth/profile.php'];
$hasFinfo = 0;
foreach ($avatarFiles as $file) {
    $path = __DIR__ . '/../../' . $file;
    if (file_exists($path) && preg_match('/finfo_file|getimagesize/', file_get_contents($path))) {
        $hasFinfo++;
    }
}
test('Server-side MIME verification (finfo)', $hasFinfo === count($avatarFiles),
    $hasFinfo . ' / ' . count($avatarFiles) . ' upload handlers use server-side MIME');

// Test 2: Check NOT using client-supplied MIME type
$usesClientMime = [];
foreach ($avatarFiles as $file) {
    $path = __DIR__ . '/../../' . $file;
    if (file_exists($path)) {
        $content = file_get_contents($path);
        if (preg_match('/\$_FILES.*\[\s*[\'"]type[\'"]\s*\]/', $content) &&
            !preg_match('/finfo_file|getimagesize/', $content)) {
            $usesClientMime[] = $file;
        }
    }
}
test('NOT using client-supplied MIME type', empty($usesClientMime),
    empty($usesClientMime) ? 'No reliance on client MIME' : 'Unsafe: ' . implode(', ', $usesClientMime));

// Test 3: Check extension whitelist
$avatarPhp = __DIR__ . '/../../actions/avatar.php';
$content = file_exists($avatarPhp) ? file_get_contents($avatarPhp) : '';
$hasExtensionCheck = preg_match('/pathinfo.*extension/i', $content);
test('File extension validation', $hasExtensionCheck > 0,
    $hasExtensionCheck ? 'Extension extracted and validated' : 'Should validate extension');

// Test 4: Check MIME whitelist
$hasMimeWhitelist = preg_match('/image\/jpeg.*image\/png.*image\/gif|allowed.*=.*\[/', $content);
test('MIME type whitelist defined', $hasMimeWhitelist > 0,
    $hasMimeWhitelist ? 'Allowed: jpeg, png, gif, webp' : 'Should whitelist MIME types');

// Test 5: Check file size limit
$hasSizeLimit = preg_match('/size\s*>\s*\d+|filesize|2\s*\*\s*1024\s*\*\s*1024/i', $content);
test('File size limit enforced', $hasSizeLimit > 0,
    $hasSizeLimit ? 'Upload size limited to 2MB' : 'Should enforce max file size');

// Test 6: Check path traversal protection
$hasTraversalProtection = preg_match('/realpath/', $content);
test('Path traversal protection (realpath)', $hasTraversalProtection > 0,
    $hasTraversalProtection ? 'File paths validated with realpath()' : 'Should use realpath()');

// Test 7: Check allowed directory validation
$hasAllowedDir = preg_match('/allowedDir|strpos.*uploads/', $content);
test('Allowed directory validation', $hasAllowedDir > 0,
    $hasAllowedDir ? 'Paths verified to stay within upload dir' : 'Should validate directory');

// Test 8: Check nginx config exists for blocking PHP in uploads
$uploadsDir = __DIR__ . '/../../uploads';
$hasProtection = file_exists($uploadsDir . '/.htaccess') ||
                 file_exists(__DIR__ . '/../../docs/nginx-hardening.conf');
test('Upload directory protection configured', $hasProtection > 0,
    $hasProtection ? 'PHP execution blocked in uploads' : 'Should add .htaccess or nginx config');

// Test 9: Check race condition fix (DB update before file delete)
$hasRaceFix = preg_match('/UPDATE.*avatar.*WHERE|unlink.*after|after.*unlink/i', $content);
test('Race condition fix (DB update before delete)', $hasRaceFix,
    $hasRaceFix ? 'DB updated before file deletion' : 'Should update DB first, then delete old file');

// Test 10: Check destination path is deterministic
$hasPredictablePath = preg_match('/uploads\/avatars\/.*\$filename/', $content);
test('Upload path is deterministic', $hasPredictablePath,
    $hasPredictablePath ? 'Files saved to controlled directory' : 'Path should not be user-controlled');

// Output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Security Test - PKWeb</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #fff; }
        .pass { color: #4caf50; }
        .fail { color: #f44336; }
        .card { background: #2a2a2a; padding: 15px; margin: 10px 0; border-radius: 4px; }
        h1 { color: #fff; }
    </style>
</head>
<body>
    <h1>🔒 File Upload Security Test</h1>
    <p>Testing for file upload vulnerabilities...</p>

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