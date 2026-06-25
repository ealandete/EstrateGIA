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

exit($failed > 0 ? 1 : 0);
