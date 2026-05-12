<?php
/**
 * Security Test Runner
 * Run all security tests and show summary
 */

$tests = [
    'sql_injection_test.php' => 'SQL Injection',
    'xss_test.php' => 'XSS Prevention',
    'csrf_test.php' => 'CSRF Protection',
    'session_test.php' => 'Session Security',
    'upload_test.php' => 'File Upload',
    'auth_test.php' => 'Authentication'
];

$results = [];

foreach ($tests as $file => $name) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        ob_start();
        // Capture only the results array from each test
        $content = file_get_contents($path);
        // Extract the $results array assignment and evaluate
        preg_match('/\$results\s*=\s*\[/', $content, $match);
        if ($match) {
            // Create isolated scope
            $testResults = [];
            eval(preg_replace('/<\?php.*?function test\(.*?\{.*?\}/s', '', file_get_contents($path)));
        }
        include $path;
        $output = ob_get_clean();

        // Count passes and fails from output
        $passCount = substr_count($output, '✅ PASS');
        $failCount = substr_count($output, '❌ FAIL');
        $results[$name] = [
            'pass' => $passCount,
            'fail' => $failCount,
            'output' => $output
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Test Suite - PKWeb</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'DM Sans', monospace;
            background: #0f0f0f;
            color: #fff;
            min-height: 100vh;
            padding: 40px;
        }
        h1 {
            font-family: 'DM Serif Display', serif;
            font-size: 32px;
            margin-bottom: 8px;
        }
        .subtitle { color: #888; margin-bottom: 40px; }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 24px;
        }
        .card {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 8px;
            overflow: hidden;
        }
        .card-header {
            padding: 16px 20px;
            border-bottom: 1px solid #333;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .card-title {
            font-weight: 600;
            font-size: 16px;
        }
        .score {
            font-size: 14px;
            padding: 4px 12px;
            border-radius: 20px;
            background: #2a2a2a;
        }
        .score.pass { color: #4caf50; }
        .score.fail { color: #f44336; }
        .card-body { padding: 0; }
        .result {
            padding: 12px 20px;
            border-bottom: 1px solid #222;
            font-size: 13px;
        }
        .result:last-child { border-bottom: none; }
        .result span { margin-right: 8px; }
        .pass-text { color: #4caf50; }
        .fail-text { color: #f44336; }
        .detail { color: #666; font-size: 12px; margin-left: 20px; }
        .summary {
            margin-top: 40px;
            padding: 24px;
            background: linear-gradient(135deg, #1a1a1a, #252525);
            border: 1px solid #333;
            border-radius: 8px;
        }
        .summary h2 {
            font-family: 'DM Serif Display', serif;
            font-size: 24px;
            margin-bottom: 16px;
        }
        .total {
            display: flex;
            gap: 40px;
            font-size: 18px;
        }
        .total-item { display: flex; align-items: center; gap: 8px; }
        .total-value { font-size: 28px; font-weight: 600; }
        .total-label { color: #888; }
        .links {
            margin-top: 20px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .links a {
            padding: 8px 16px;
            background: #2a2a2a;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            font-size: 13px;
            transition: background 0.2s;
        }
        .links a:hover { background: #333; }
    </style>
</head>
<body>
    <h1>🔒 PKWeb Security Test Suite</h1>
    <p class="subtitle">Automated security vulnerability testing</p>

    <div class="grid">
    <?php foreach ($results as $testName => $data): ?>
        <div class="card">
            <div class="card-header">
                <span class="card-title"><?= $testName ?></span>
                <span class="score <?= $data['fail'] === 0 ? 'pass' : 'fail' ?>">
                    <?= $data['pass'] ?>/<?= $data['pass'] + $data['fail'] ?>
                </span>
            </div>
            <div class="card-body">
                <?php
                // Extract results from HTML output
                preg_match_all('/(✅ PASS|❌ FAIL).*?-(.*?)(?:<br|<small|$)/s', $data['output'], $matches);
                foreach ($matches[0] as $i => $match) {
                    $isPass = strpos($match, '✅ PASS') !== false;
                    $name = trim($matches[2][$i]);
                    $detail = '';
                    if (preg_match('/<small[^>]*>(.*?)<\/small>/', $match, $d)) {
                        $detail = trim($d[1]);
                    }
                ?>
                <div class="result">
                    <span class="<?= $isPass ? 'pass-text' : 'fail-text' ?>">
                        <?= $isPass ? '✓' : '✗' ?>
                    </span>
                    <?= htmlspecialchars($name) ?>
                    <?php if ($detail): ?>
                    <div class="detail"><?= htmlspecialchars($detail) ?></div>
                    <?php endif; ?>
                </div>
                <?php } ?>
            </div>
        </div>
    <?php endforeach; ?>
    </div>

    <div class="summary">
        <h2>Overall Summary</h2>
        <div class="total">
            <?php
            $totalPass = array_sum(array_column($results, 'pass'));
            $totalFail = array_sum(array_column($results, 'fail'));
            ?>
            <div class="total-item">
                <span class="total-value pass-text"><?= $totalPass ?></span>
                <span class="total-label">Passed</span>
            </div>
            <div class="total-item">
                <span class="total-value fail-text"><?= $totalFail ?></span>
                <span class="total-label">Failed</span>
            </div>
            <div class="total-item">
                <span class="total-value"><?= $totalPass + $totalFail ?></span>
                <span class="total-label">Total Tests</span>
            </div>
        </div>
        <div class="links">
            <a href="sql_injection_test.php">SQL Injection</a>
            <a href="xss_test.php">XSS</a>
            <a href="csrf_test.php">CSRF</a>
            <a href="session_test.php">Session</a>
            <a href="upload_test.php">Uploads</a>
            <a href="auth_test.php">Auth</a>
        </div>
    </div>
</body>
</html>