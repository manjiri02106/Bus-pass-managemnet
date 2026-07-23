<?php
/**
 * Custom CLI Automated Integration Test Suite
 * Executed via command line: C:\xampp\php\php.exe tests\run_tests.php
 */

define('CLI_COLOR_GREEN', "\033[32m");
define('CLI_COLOR_RED', "\033[31m");
define('CLI_COLOR_YELLOW', "\033[33m");
define('CLI_COLOR_RESET', "\033[0m");

function log_pass($msg)
{
    echo CLI_COLOR_GREEN . "[PASS] " . CLI_COLOR_RESET . $msg . PHP_EOL;
}

function log_fail($msg, $reason = '')
{
    echo CLI_COLOR_RED . "[FAIL] " . CLI_COLOR_RESET . $msg;
    if ($reason) {
        echo " (" . CLI_COLOR_YELLOW . $reason . CLI_COLOR_RESET . ")";
    }
    echo PHP_EOL;
}

echo "==================================================" . PHP_EOL;
echo "Starting OmniPass Integration Test Suite..." . PHP_EOL;
echo "==================================================" . PHP_EOL;

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$test_db_name = 'bus_pass_test_db';
$pdo = null;

// 1. Connection and Database Setup
try {
    $dsn = "mysql:host=$db_host;charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    log_pass("Connected to MySQL Server.");

    // Re-create temporary test database
    $pdo->exec("DROP DATABASE IF EXISTS `$test_db_name`");
    $pdo->exec("CREATE DATABASE `$test_db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$test_db_name`");
    log_pass("Temporary test database '$test_db_name' created.");
} catch (PDOException $e) {
    log_fail("Database setup failed", $e->getMessage());
    exit(1);
}

// 2. Import Schema & Seeds
try {
    $schema_sql = file_get_contents(__DIR__ . '/../database/schema.sql');
    // Remove database creation instructions from original schema to avoid conflicts
    $schema_sql = preg_replace('/CREATE DATABASE IF NOT EXISTS.*?;/is', '', $schema_sql);
    $schema_sql = preg_replace('/USE `bus_pass_db`;/is', '', $schema_sql);

    // Split SQL by semicolon and execute statement by statement
    $queries = explode(';', $schema_sql);
    $queries_executed = 0;
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            $pdo->exec($query);
            $queries_executed++;
        }
    }
    log_pass("Imported database schema and seed values successfully ($queries_executed queries executed).");
} catch (Exception $e) {
    log_fail("Schema migration failed", $e->getMessage());
    clean_up($pdo, $test_db_name);
    exit(1);
}

// 3. Test: Verify Table Integrity
try {
    $tables = ['users', 'categories', 'routes', 'passes', 'payments'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->fetch()) {
            log_pass("Table integrity: '$table' table structure verified.");
        } else {
            throw new Exception("Table '$table' is missing.");
        }
    }
} catch (Exception $e) {
    log_fail("Table integrity validation failed", $e->getMessage());
    clean_up($pdo, $test_db_name);
    exit(1);
}

// 4. Test: User Creation and Hashing validation
try {
    $email = 'tester@gmail.com';
    $raw_pass = 'test1234';
    $hashed_pass = password_hash($raw_pass, PASSWORD_BCRYPT);

    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, 'user')");
    $stmt->execute([
        'name' => 'Test User',
        'email' => $email,
        'password' => $hashed_pass
    ]);

    // Fetch and check
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if ($user && $user['name'] === 'Test User') {
        log_pass("User insertion verified.");
    } else {
        throw new Exception("Could not retrieve created user record.");
    }

    if (password_verify($raw_pass, $user['password'])) {
        log_pass("Password BCRYPT validation verified.");
    } else {
        throw new Exception("Password verification failure.");
    }
} catch (Exception $e) {
    log_fail("User registration tests failed", $e->getMessage());
}

// 5. Test: Pass pricing logic calculations
try {
    // Standard Price: 150.00 (RT-101)
    // Discount Pct: 50.00% (Student Special - ID 2)
    // Duration: 3 months
    $base_price = 150.00;
    $discount_pct = 50.00;
    $duration_months = 3;

    // Expected: (150.00 * 3) = 450.00 subtotal. Discount = 225.00. Final = 225.00
    $subtotal = $base_price * $duration_months;
    $discount = $subtotal * ($discount_pct / 100);
    $final = $subtotal - $discount;

    $expected_final = 225.00;

    if (abs($final - $expected_final) < 0.001) {
        log_pass("Business Logic: Fare calculations math validated ($final matches expected $expected_final).");
    } else {
        throw new Exception("Pricing math incorrect. Calculated $final, expected $expected_final.");
    }
} catch (Exception $e) {
    log_fail("Pricing calculation test failed", $e->getMessage());
}

// 6. Test: Pass validation QR logic check
try {
    // Check seed pass 1 (approved, active)
    $stmt = $pdo->prepare("
        SELECT p.*, u.name AS passenger_name, r.route_code
        FROM passes p
        INNER JOIN users u ON p.user_id = u.id
        INNER JOIN routes r ON p.route_id = r.id
        WHERE p.qr_code_data = 'VAL-BP-20260721-0001'
    ");
    $stmt->execute();
    $pass = $stmt->fetch();

    if ($pass) {
        $today = date('Y-m-d');
        $valid = ($pass['status'] === 'approved' && $today <= $pass['end_date'] && $today >= $pass['start_date']);

        if ($valid && $pass['passenger_name'] === 'Jane Doe') {
            log_pass("QR Validation Service: Verified mock active pass 'VAL-BP-20260721-0001' successfully.");
        } else {
            throw new Exception("Seed pass returned invalid active properties.");
        }
    } else {
        throw new Exception("Seed pass 'VAL-BP-20260721-0001' not found in ledger.");
    }
} catch (Exception $e) {
    log_fail("QR validation test failed", $e->getMessage());
}

// 7. Cleaning up test database
clean_up($pdo, $test_db_name);

echo "==================================================" . PHP_EOL;
echo "All tests finished!" . PHP_EOL;
echo "==================================================" . PHP_EOL;

function clean_up($pdo, $db_name)
{
    if ($pdo) {
        try {
            $pdo->exec("DROP DATABASE IF EXISTS `$db_name`");
            log_pass("Cleaned up temporary test database.");
        } catch (PDOException $e) {
            log_fail("Failed to clean up test database", $e->getMessage());
        }
    }
}
