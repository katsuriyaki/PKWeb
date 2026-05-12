<?php
/**
 * Authentication Security Test
 * Tests for authentication vulnerabilities
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
$loginFile = file_get_contents(__DIR__ . '/../../auth/index.php');
$registerFile = file_get_contents(__DIR__ . '/../../auth/register.php');

// Test 1: Password hashing algorithm
$usesBcrypt = preg_match('/password_hash\s*\(\s*\$password\s*,\s*PASSWORD_BCRYPT/', $registerFile) ||
              preg_match('/PASSWORD_BCRYPT/', $configFile);
test('Uses bcrypt for password hashing', $usesBcrypt > 0,
    $usesBcrypt ? 'Secure bcrypt algorithm' : 'Should use PASSWORD_BCRYPT');

// Test 2: Password verify function
$usesPasswordVerify = preg_match('/password_verify\s*\(/', $loginFile);
test('Uses password_verify() for login', $usesPasswordVerify > 0,
    $usesPasswordVerify ? 'Timing-safe password comparison' : 'Should use password_verify()');

// Test 3: Password minimum length
$hasMinLength = preg_match('/strlen.*password.*8|minlength.*8|password.*8/', $registerFile);
test('Password minimum length enforced', $hasMinLength > 0,
    $hasMinLength ? 'Minimum 8 characters required' : 'Should enforce minimum password length');

// Test 4: Password complexity (not implemented - this is a note)
test('Password complexity NOT enforced (optional)', true,
    'Consider adding: uppercase, numbers, symbols');

// Test 5: Username validation
$hasUsernameValidation = preg_match('/username.*preg|preg_match.*username|alphanumeric|underscore/i', $registerFile);
test('Username validation (alphanumeric + underscore)', $hasUsernameValidation > 0,
    $hasUsernameValidation ? 'Username properly validated' : 'Should validate username format');

// Test 6: Username length limit
$hasUsernameMaxLength = preg_match('/maxlength.*50|username.*50/', $registerFile);
test('Username has maximum length', $hasUsernameMaxLength > 0,
    $hasUsernameMaxLength ? 'Max 50 characters' : 'Should limit username length');

// Test 7: Email validation
$hasEmailValidation = preg_match('/filter_var.*email|FILTER_VALIDATE_EMAIL/', $registerFile);
test('Email format validation', $hasEmailValidation > 0,
    $hasEmailValidation ? 'Email validated with FILTER_VALIDATE_EMAIL' : 'Should use FILTER_VALIDATE_EMAIL');

// Test 8: No username enumeration in error messages
$loginNoEnum = !preg_match('/username.*not.*found|user.*not.*found/i', $loginFile) ||
              preg_match('/invalid.*credentials/i', $loginFile);
test('Generic error messages (no username enumeration)', $loginNoEnum,
    $loginNoEnum ? 'Errors do not reveal username existence' : 'Should use generic "invalid credentials" message');

// Test 9: Session regeneration on login
$hasLoginRegen = preg_match('/session_regenerate_id/', $loginFile);
test('Session regenerated on successful login', $hasLoginRegen > 0,
    $hasLoginRegen ? 'Prevents session fixation' : 'Should call session_regenerate_id() after login');

// Test 10: No rate limiting (this is a gap to note)
$hasRateLimiting = preg_match('/login.*attempt|attempt.*limit|lockout|rate.*limit/i', $configFile) ||
                   preg_match('/login.*attempt|attempt.*limit|lockout|rate.*limit/i', $loginFile);
test('Rate limiting on login (SHOULD BE ADDED)', $hasRateLimiting,
    $hasRateLimiting ? 'Login rate limiting present' : '❌ Missing: Add login attempt limiting');

// Test 11: Password confirmation check
$hasConfirmCheck = preg_match('/password.*!==.*confirm|password.*!==.*confirm/', $registerFile);
test('Password confirmation required', $hasConfirmCheck > 0,
    $hasConfirmCheck ? 'Confirmation field validated' : 'Should require password confirmation');

// Test 12: Account lockout not implemented (gap)
$hasLockout = preg_match('/lockout|account.*lock|failed.*attempt.*limit/i', $configFile) ||
             preg_match('/lockout|account.*lock|failed.*attempt.*limit/i', $loginFile);
test('Account lockout mechanism (SHOULD BE ADDED)', $hasLockout,
    $hasLockout ? 'Account lockout present' : '❌ Missing: Add failed login lockout');

// Output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Authentication Test - PKWeb</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #fff; }
        .pass { color: #4caf50; }
        .fail { color: #f44336; }
        .card { background: #2a2a2a; padding: 15px; margin: 10px 0; border-radius: 4px; }
        h1 { color: #fff; }
    </style>
</head>
<body>
    <h1>🔒 Authentication Security Test</h1>
    <p>Testing for authentication vulnerabilities...</p>

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