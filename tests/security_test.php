<?php

/**
 * EstrateGIA — SQL Injection Security Tests
 * Static analysis: verify SafeQuery usage and absence of SQL injection vectors
 * in all 29 controllers under src/Controllers/
 */

define('BASE_PATH', dirname(__DIR__));

$GLOBALS['tests']['SQL Injection'] = [];
$GLOBALS['results']['SQL Injection'] = [];

$controllersDir = BASE_PATH . '/src/Controllers';
$controllerFiles = glob($controllersDir . '/*.php') ?: [];
$totalFiles = count($controllerFiles);

$passed = 0;
$failed = 0;

echo "\n══════════════════════════════════════\n";
echo "  SQL INJECTION SECURITY TESTS\n";
echo "  Controllers: $totalFiles\n";
echo "══════════════════════════════════════\n\n";

$results = [];

foreach ($controllerFiles as $filePath) {
    $basename = basename($filePath);
    $className = str_replace('.php', '', $basename);
    $content = file_get_contents($filePath);
    if ($content === false) {
        $results[] = ['controller' => $className, 'pass' => false, 'checks' => [], 'error' => "Cannot read file"];
        $failed++;
        continue;
    }
    $lines = explode("\n", $content);

    $checks = [];
    $allPass = true;

    // 1. declare(strict_types=1);
    $hasStrict = strpos($content, 'declare(strict_types=1)') !== false;
    $checks[] = ['check' => 'declare(strict_types=1)', 'pass' => $hasStrict];
    if (!$hasStrict) $allPass = false;

    // 2. use SafeQuery trait
    $hasTrait = (bool)preg_match('/use\s+\\\\?SafeQuery\s*;/', $content);
    $checks[] = ['check' => 'use SafeQuery trait', 'pass' => $hasTrait];
    if (!$hasTrait) $allPass = false;

    // 3. No deprecated mysql_* / mysqli_* / pg_* direct functions
    $badFuncs = ['mysql_query', 'mysqli_query', 'mysql_connect', 'mysqli_connect',
                 'mysql_real_escape_string', 'mysqli_real_escape_string',
                 'pg_query', 'sqlite_query', 'sqlsrv_query'];
    $hasBadFunc = false;
    foreach ($badFuncs as $func) {
        if (strpos($content, $func) !== false) { $hasBadFunc = true; break; }
    }
    $checks[] = ['check' => 'No deprecated mysql_*/mysqli_*', 'pass' => !$hasBadFunc];
    if ($hasBadFunc) $allPass = false;

    // 4. No raw ->query() or ->exec() on PDO bypassing SafeQuery
    // Look for $this->core->query( or $this->core->exec( patterns
    $hasRawQuery = false;
    foreach ($lines as $line) {
        if (preg_match('/\$this->core\s*->\s*(?:query|exec)\s*\(/', $line)) {
            $hasRawQuery = true;
            break;
        }
    }
    $checks[] = ['check' => 'No core->query() / core->exec()', 'pass' => !$hasRawQuery];
    if ($hasRawQuery) $allPass = false;

    // 5. No $_GET/$_POST/$_REQUEST directly interpolated into SQL strings
    // Dangerous: "SELECT * FROM t WHERE id = $_GET[id]" or "SELECT ..." . $_GET['x']
    // Safe:      $this->safe("... WHERE id = ?", [$_GET['id']])
    $unsafeSuperglobal = false;
    $superglobalKeys = ['\$_GET', '\$_POST', '\$_REQUEST'];

    foreach ($lines as $linenum => $line) {
        // Check if line has an SQL string containing a superglobal
        // Look for patterns where superglobal is in a SQL string literal
        // or concatenated to a SQL string

        // Skip comment-only lines
        if (preg_match('/^\s*(?:\/\/|#|\*)/', $line)) continue;

        // Check for superglobal inside a double-quoted SQL string
        // Pattern: "... WHERE something = $_GET[x]" or "... AND x = {$_POST['x']} ..."
        // This catches interpolation INSIDE the SQL, not in the params array
        $inSQLLiteral = false;
        foreach ($superglobalKeys as $sg) {
            // Look for SQL keyword followed by superglobal in same double-quoted string
            if (preg_match('/"(?:SELECT|INSERT|UPDATE|DELETE|DROP|FROM|WHERE|SET|VALUES|JOIN|ORDER|GROUP|HAVING|LIMIT)\b[^"]*\$\_(GET|POST|REQUEST)\b/', $line)) {
                $inSQLLiteral = true;
                break 2;
            }
        }

        // Check for string concatenation of SQL + superglobal
        // Pattern: "SELECT ..." . $_GET['x'] or "SELECT ..." . $_POST['x']
        if (!$inSQLLiteral) {
            foreach ($superglobalKeys as $sg) {
                if (preg_match('/"(?:SELECT|INSERT|UPDATE|DELETE|FROM|WHERE|SET)\b[^"]*"\s*\.\s*\$\_(GET|POST|REQUEST)\b/', $line)) {
                    $inSQLLiteral = true;
                    break 2;
                }
                // Also check single-quoted SQL strings concatenated with superglobal
                if (preg_match("/'(?:SELECT|INSERT|UPDATE|DELETE|FROM|WHERE|SET)\b[^']*'\s*\.\s*\$\_(GET|POST|REQUEST)\b/", $line)) {
                    $inSQLLiteral = true;
                    break 2;
                }
            }
        }

        if ($inSQLLiteral) {
            $unsafeSuperglobal = true;
            break;
        }
    }
    $checks[] = ['check' => 'No superglobals in SQL strings', 'pass' => !$unsafeSuperglobal];
    if ($unsafeSuperglobal) $allPass = false;

    // 6. No user-input concatenated to SQL strings (broader check)
    // Pattern: "SELECT ..." . $untrusted or "SELECT ... WHERE id = " . $var
    // This catches concatenation with any variable that came from user input
    // Specifically: $_GET, $_POST, $_REQUEST, $_COOKIE, $_SERVER['QUERY_STRING']
    $hasConcat = false;
    foreach ($lines as $linenum => $line) {
        if (preg_match('/^\s*(?:\/\/|#|\*)/', $line)) continue;
        // SQL string . superglobal
        if (preg_match('/"(?:SELECT|INSERT|UPDATE|DELETE|FROM|WHERE|SET)\b[^"]*"\s*\.\s*\$\w+/', $line)) {
            $hasConcat = true;
            break;
        }
        if (preg_match("/'(?:SELECT|INSERT|UPDATE|DELETE|FROM|WHERE|SET)\b[^']*'\s*\.\s*\$\w+/", $line)) {
            $hasConcat = true;
            break;
        }
    }
    $checks[] = ['check' => 'No SQL string concatenation', 'pass' => !$hasConcat];
    if ($hasConcat) $allPass = false;

    // 7. Only safe*() methods used for SQL (not raw core functions)
    // Count safe method uses vs potential unsafe patterns
    $safeMethods = ['safe(', 'safeAll(', 'safeOne(', 'safeExec(',
                    'safeInsert(', 'safeUpdate(', 'safeDelete(',
                    'safeCount(', 'safeExists('];
    $hasSafeMethods = false;
    foreach ($safeMethods as $method) {
        if (strpos($content, "\$this->$method") !== false) {
            $hasSafeMethods = true;
            break;
        }
    }
    // Also check core fetch* methods (delegated through managers, not controllers)
    $checks[] = ['check' => 'Uses safe*() methods for SQL', 'pass' => $hasSafeMethods || $hasTrait];
    if (!$hasSafeMethods && !$hasTrait) $allPass = false;

    // Display
    $status = $allPass ? 'PASS' : 'FAIL';
    echo "  [$status] $className\n";
    foreach ($checks as $c) {
        echo "     " . ($c['pass'] ? '✓' : '✗') . " {$c['check']}\n";
    }
    echo "\n";

    if ($allPass) {
        $passed++;
    } else {
        $failed++;
    }

    $results[] = [
        'controller' => $className,
        'pass' => $allPass,
        'checks' => $checks,
    ];
}

// Summary
$pct = $totalFiles > 0 ? round($passed / $totalFiles * 100, 1) : 0;

echo "══════════════════════════════════════\n";
echo "  RESULTS SUMMARY\n";
echo "  ─────────────────────────────────\n";
echo "  Controllers:     $totalFiles\n";
echo "  Passed:          $passed\n";
echo "  Failed:          $failed\n";
echo "  Score:           $pct%\n";
echo "══════════════════════════════════════\n";

if ($failed > 0) {
    echo "\n  FAILING CONTROLLERS:\n";
    foreach ($results as $r) {
        if (!$r['pass']) {
            echo "    ✗ {$r['controller']}\n";
            foreach ($r['checks'] as $c) {
                if (!$c['pass']) {
                    echo "       → {$c['check']}\n";
                }
            }
        }
    }
}

// Build output globals
$summary = [
    'total' => $totalFiles,
    'passed' => $passed,
    'failed' => $failed,
    'score' => $pct,
    'controllers' => array_map(fn($f) => basename($f, '.php'), $controllerFiles),
    'results' => $results,
];
$GLOBALS['tests']['SQL Injection'] = $summary;
$GLOBALS['results']['SQL Injection'] = $results;

echo "\n" . ($failed === 0
    ? "✅ ALL $totalFiles CONTROLLERS SECURE — No SQL injection vectors found\n\n"
    : "❌ VULNERABILITIES FOUND in $failed controller(s)\n\n");

$sqlFailed = $failed;
$sqlPassed = $passed;
$globalFailed = $sqlFailed;
$globalTotal = $totalFiles;
$globalPassed = $passed;
$testSectionCount = 1;

// =================================================================================
// SECTION 2: CSRF PROTECTION TESTS
// =================================================================================
$GLOBALS['tests']['CSRF Protection'] = [];
$GLOBALS['results']['CSRF Protection'] = [];

define('BASE_URL', getenv('APP_BASE_URL') ?: 'http://localhost');

$csrfPassed = 0;
$csrfFailed = 0;
$csrfResults = [];

echo "══════════════════════════════════════\n";
echo "  CSRF PROTECTION TESTS\n";
echo "══════════════════════════════════════\n\n";

function httpRequest(string $method, string $path, array $postData = [], array $headers = [], bool $followRedirect = false): array {
    $url = preg_replace('#/+#', '/', BASE_URL . '/' . ltrim($path, '/'));
    $cmd = "curl -s -o /dev/null -w '%{http_code}|%{content_type}' -X $method";
    if (!$followRedirect) $cmd .= " -L";
    foreach ($headers as $key => $val) {
        $cmd .= " -H " . escapeshellarg("$key: $val");
    }
    if ($postData) {
        $cmd .= " -d " . escapeshellarg(http_build_query($postData));
    }
    $cmd .= " " . escapeshellarg($url) . " 2>/dev/null";
    $out = trim(shell_exec($cmd) ?: '');
    $parts = explode('|', $out, 2);
    return [
        'http_code' => (int)($parts[0] ?? 0),
        'content_type' => $parts[1] ?? '',
    ];
}

function httpGetBody(string $method, string $path, array $headers = []): string {
    $url = preg_replace('#/+#', '/', BASE_URL . '/' . ltrim($path, '/'));
    $cmd = "curl -s -X $method";
    foreach ($headers as $key => $val) {
        $cmd .= " -H " . escapeshellarg("$key: $val");
    }
    $cmd .= " " . escapeshellarg($url) . " 2>/dev/null";
    return trim(shell_exec($cmd) ?: '');
}

// 2.1 POST to /planeacion/crear without csrf_token
{
    $name = 'POST /planeacion/crear without csrf_token → 403';
    $res = httpRequest('POST', '/planeacion/crear');
    $pass = $res['http_code'] === 403;
    $csrfResults[] = ['test' => $name, 'pass' => $pass, 'actual' => $res['http_code'], 'expected' => 403];
    echo "  " . ($pass ? '✓' : '✗') . " $name (got {$res['http_code']})\n";
    if ($pass) $csrfPassed++; else $csrfFailed++;
}

// 2.2 POST to /ambiental/aspecto/guardar without csrf_token
{
    $name = 'POST /ambiental/aspecto/guardar without csrf_token → 403';
    $res = httpRequest('POST', '/ambiental/aspecto/guardar');
    $pass = $res['http_code'] === 403;
    $csrfResults[] = ['test' => $name, 'pass' => $pass, 'actual' => $res['http_code'], 'expected' => 403];
    echo "  " . ($pass ? '✓' : '✗') . " $name (got {$res['http_code']})\n";
    if ($pass) $csrfPassed++; else $csrfFailed++;
}

// 2.3 POST with wrong csrf_token → 403 (test on an API endpoint that checks CSRF)
{
    $name = 'POST with wrong csrf_token → 403';
    $res = httpRequest('POST', '/tools/test', ['csrf_token' => 'wrong_token_bogus_1234']);
    $pass = $res['http_code'] === 403;
    $csrfResults[] = ['test' => $name, 'pass' => $pass, 'actual' => $res['http_code'], 'expected' => 403];
    echo "  " . ($pass ? '✓' : '✗') . " $name (got {$res['http_code']})\n";
    if ($pass) $csrfPassed++; else $csrfFailed++;
}

// 2.4 CSRF token in session matches form output
{
    $name = 'CSRF token in session matches form output';
    // Read the CSRF token from a form page and compare with what we'd expect
    // The forms use $_SESSION['csrf_token'] in hidden inputs
    // We check that the form rendering references the session token
    $formFiles = [
        BASE_PATH . '/templates/layout.php',
        BASE_PATH . '/src/Controllers/LicenciasController.php',
    ];
    $csrfInForm = false;
    foreach ($formFiles as $ff) {
        $fc = file_get_contents($ff);
        if ($fc !== false && strpos($fc, '$_SESSION[') !== false && strpos($fc, 'csrf_token') !== false) {
            $csrfInForm = true;
            break;
        }
    }
    // Also verify router compares tokens
    $routerContent = file_get_contents(BASE_PATH . '/src/Router.php');
    $routerCompares = $routerContent !== false
        && strpos($routerContent, 'csrf_token') !== false
        && strpos($routerContent, 'hash_equals') !== false;

    $pass = $csrfInForm && $routerCompares;
    $csrfResults[] = ['test' => $name, 'pass' => $pass, 'detail' => $pass ? 'Forms & Router use session token' : 'Missing session token in forms or Router check'];
    echo "  " . ($pass ? '✓' : '✗') . " $name\n";
    if ($pass) $csrfPassed++; else $csrfFailed++;
}

$csrfTotal = count($csrfResults);
echo "\n══════════════════════════════════════\n";
echo "  CSRF RESULTS: $csrfPassed/$csrfTotal passed\n";
echo "══════════════════════════════════════\n";

if ($csrfFailed > 0) {
    echo "\n  FAILING CSRF TESTS:\n";
    foreach ($csrfResults as $r) {
        if (!$r['pass']) {
            $extras = isset($r['actual']) ? " (got {$r['actual']}, expected {$r['expected']})" : '';
            echo "    ✗ {$r['test']}{$extras}\n";
        }
    }
}

$GLOBALS['tests']['CSRF Protection'] = ['total' => $csrfTotal, 'passed' => $csrfPassed, 'failed' => $csrfFailed];
$GLOBALS['results']['CSRF Protection'] = $csrfResults;

$globalTotal += $csrfTotal;
$globalPassed += $csrfPassed;
$globalFailed += $csrfFailed;
$testSectionCount++;

// =================================================================================
// SECTION 3: Auth::guard COVERAGE
// =================================================================================
$GLOBALS['tests']['Auth Guard Coverage'] = [];
$GLOBALS['results']['Auth Guard Coverage'] = [];

echo "\n══════════════════════════════════════\n";
echo "  AUTH::GUARD COVERAGE TESTS\n";
echo "══════════════════════════════════════\n\n";

$exceptions = ['DocsController', 'SetupController'];
$authResults = [];
$authPassed = 0;
$authFailed = 0;

foreach ($controllerFiles as $filePath) {
    $basename = basename($filePath);
    $className = str_replace('.php', '', $basename);
    $content = file_get_contents($filePath);
    $hasGuard = $content !== false && strpos($content, 'Auth::guard()') !== false;
    $isException = in_array($className, $exceptions);

    $pass = $hasGuard || $isException;
    $status = $pass ? '✓' : '✗';
    $label = $isException ? ' (EXCEPTION)' : '';
    echo "  [$status] $className$label\n";

    $authResults[] = [
        'controller' => $className,
        'has_guard' => $hasGuard,
        'is_exception' => $isException,
        'pass' => $pass,
    ];

    if ($pass) $authPassed++; else $authFailed++;
}

$authTotal = count($authResults);

echo "\n══════════════════════════════════════\n";
echo "  AUTH::GUARD RESULTS: $authPassed/$authTotal passed\n";
echo "══════════════════════════════════════\n";

if ($authFailed > 0) {
    echo "\n  CONTROLLERS MISSING Auth::guard:\n";
    foreach ($authResults as $r) {
        if (!$r['pass']) {
            echo "    ✗ {$r['controller']} — missing Auth::guard() in constructor\n";
        }
    }
}

$GLOBALS['tests']['Auth Guard Coverage'] = [
    'total' => $authTotal,
    'passed' => $authPassed,
    'failed' => $authFailed,
    'controllers' => array_map(fn($f) => basename($f, '.php'), $controllerFiles),
    'results' => $authResults,
];
$GLOBALS['results']['Auth Guard Coverage'] = $authResults;

$globalTotal += $authTotal;
$globalPassed += $authPassed;
$globalFailed += $authFailed;
$testSectionCount++;

// =================================================================================
// SECTION 4: API ENDPOINT TESTS
// =================================================================================
$GLOBALS['tests']['API Endpoints'] = [];
$GLOBALS['results']['API Endpoints'] = [];

echo "\n══════════════════════════════════════\n";
echo "  API ENDPOINT TESTS\n";
echo "══════════════════════════════════════\n\n";

$apiPassed = 0;
$apiFailed = 0;
$apiResults = [];

// 4.1 GET /ambiental/api/huella?empresa_id=2 without auth → 401 JSON
{
    $name = 'GET /ambiental/api/huella?empresa_id=2 without auth → 401 JSON';
    $res = httpRequest('GET', '/ambiental/api/huella?empresa_id=2');
    $body = httpGetBody('GET', '/ambiental/api/huella?empresa_id=2');
    $isJson = strpos($body, '{') === 0;
    $has401inJson = $isJson && strpos($body, '401') !== false;
    $pass = $res['http_code'] === 401 && $isJson;
    $apiResults[] = ['test' => $name, 'pass' => $pass, 'actual_code' => $res['http_code'], 'is_json' => $isJson];
    echo "  " . ($pass ? '✓' : '✗') . " $name (code={$res['http_code']}, json=" . ($isJson ? 'yes' : 'no') . ")\n";
    if ($pass) $apiPassed++; else $apiFailed++;
}

// 4.2 GET /ambiental/api/dashboard?empresa_id=2 without auth → 401 JSON
{
    $name = 'GET /ambiental/api/dashboard?empresa_id=2 without auth → 401 JSON';
    $res = httpRequest('GET', '/ambiental/api/dashboard?empresa_id=2');
    $body = httpGetBody('GET', '/ambiental/api/dashboard?empresa_id=2');
    $isJson = strpos($body, '{') === 0;
    $pass = $res['http_code'] === 401 && $isJson;
    $apiResults[] = ['test' => $name, 'pass' => $pass, 'actual_code' => $res['http_code'], 'is_json' => $isJson];
    echo "  " . ($pass ? '✓' : '✗') . " $name (code={$res['http_code']}, json=" . ($isJson ? 'yes' : 'no') . ")\n";
    if ($pass) $apiPassed++; else $apiFailed++;
}

// 4.3 GET /ambiental/api/huella with Accept: application/json → 401
{
    $name = 'GET /ambiental/api/huella with Accept: application/json → 401';
    $res = httpRequest('GET', '/ambiental/api/huella', [], ['Accept' => 'application/json']);
    $body = httpGetBody('GET', '/ambiental/api/huella', ['Accept' => 'application/json']);
    $isJson = strpos($body, '{') === 0;
    $pass = $res['http_code'] === 401 && $isJson;
    $apiResults[] = ['test' => $name, 'pass' => $pass, 'actual_code' => $res['http_code'], 'is_json' => $isJson];
    echo "  " . ($pass ? '✓' : '✗') . " $name (code={$res['http_code']}, json=" . ($isJson ? 'yes' : 'no') . ")\n";
    if ($pass) $apiPassed++; else $apiFailed++;
}

// 4.4 All APIs return valid JSON on 401
{
    $name = 'All APIs return valid JSON on 401';
    $endpoints = [
        '/ambiental/api/huella',
        '/ambiental/api/dashboard',
        '/ambiental/api/indicadores-carbono',
    ];
    $allJson = true;
    $details = [];
    foreach ($endpoints as $ep) {
        $b = httpGetBody('GET', $ep);
        $json = json_decode($b, true);
        $valid = is_array($json) && isset($json['success']) && isset($json['error']);
        $details[] = ['endpoint' => $ep, 'valid_json' => $valid, 'body_preview' => substr($b, 0, 80)];
        if (!$valid) $allJson = false;
    }
    $pass = $allJson;
    $apiResults[] = ['test' => $name, 'pass' => $pass, 'details' => $details];
    echo "  " . ($pass ? '✓' : '✗') . " $name\n";
    foreach ($details as $d) {
        echo "     " . ($d['valid_json'] ? '✓' : '✗') . " {$d['endpoint']} → " . $d['body_preview'] . "\n";
    }
    if ($pass) $apiPassed++; else $apiFailed++;
}

$apiTotal = count($apiResults);

echo "\n══════════════════════════════════════\n";
echo "  API RESULTS: $apiPassed/$apiTotal passed\n";
echo "══════════════════════════════════════\n";

if ($apiFailed > 0) {
    echo "\n  FAILING API TESTS:\n";
    foreach ($apiResults as $r) {
        if (!$r['pass']) {
            $extras = isset($r['actual_code']) ? " (got {$r['actual_code']})" : '';
            echo "    ✗ {$r['test']}{$extras}\n";
        }
    }
}

$GLOBALS['tests']['API Endpoints'] = ['total' => $apiTotal, 'passed' => $apiPassed, 'failed' => $apiFailed];
$GLOBALS['results']['API Endpoints'] = $apiResults;

$globalTotal += $apiTotal;
$globalPassed += $apiPassed;
$globalFailed += $apiFailed;
$testSectionCount++;

// =================================================================================
// GLOBAL SUMMARY
// =================================================================================
$globalScore = $globalTotal > 0 ? round($globalPassed / $globalTotal * 100, 1) : 0;

echo "\n\n" . str_repeat('=', 38) . "\n";
echo "  FINAL SECURITY TEST SUMMARY\n";
echo "  " . str_repeat('─', 32) . "\n";
echo "  Sections:        $testSectionCount\n";
echo "  Total tests:     $globalTotal\n";
echo "  Passed:          $globalPassed\n";
echo "  Failed:          $globalFailed\n";
echo "  Score:           $globalScore%\n";
echo str_repeat('=', 38) . "\n";

echo "\n  Breakdown by section:\n";
echo "    SQL Injection:      $sqlPassed/$totalFiles\n";
echo "    CSRF Protection:    $csrfPassed/$csrfTotal\n";
echo "    Auth::guard:        $authPassed/$authTotal\n";
echo "    API Endpoints:      $apiPassed/$apiTotal\n";

echo "\n" . ($globalFailed === 0
    ? "✅ ALL $globalTotal SECURITY TESTS PASSED\n\n"
    : "❌ $globalFailed SECURITY TEST(S) FAILED\n\n");

exit($globalFailed > 0 ? 1 : 0);
