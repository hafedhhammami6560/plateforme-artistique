<?php
// Simple script to apply SQL file to database defined in .env (DATABASE_URL)
// Usage: php scripts/apply_sql.php migrations/sql/community_feedback.sql

if ($argc < 2) {
    echo "Usage: php scripts/apply_sql.php <sql-file>\n";
    exit(1);
}

$sqlFile = $argv[1];
if (!file_exists($sqlFile)) {
    echo "SQL file not found: $sqlFile\n";
    exit(1);
}

$envPath = __DIR__ . '/../.env';
if (!file_exists($envPath)) {
    echo ".env file not found at project root. Please ensure DATABASE_URL is available.\n";
    exit(1);
}

$env = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$dbUrl = null;
foreach ($env as $line) {
    if (strpos(trim($line), 'DATABASE_URL=') === 0) {
        $dbUrl = substr(trim($line), strlen('DATABASE_URL='));
        break;
    }
}

if ($dbUrl === null) {
    echo "DATABASE_URL not found in .env\n";
    exit(1);
}

// Remove surrounding quotes
$dbUrl = trim($dbUrl, " \"'\n\r");

// Parse DSN like: mysql://user:pass@host:port/dbname?serverVersion=...&charset=...
$parts = parse_url($dbUrl);
if ($parts === false || !isset($parts['scheme']) || ($parts['scheme'] !== 'mysql' && $parts['scheme'] !== 'mariadb')) {
    echo "Unsupported or invalid DATABASE_URL: $dbUrl\n";
    exit(1);
}

$user = $parts['user'] ?? 'root';
$pass = $parts['pass'] ?? '';
$host = $parts['host'] ?? '127.0.0.1';
$port = $parts['port'] ?? 3306;
$dbName = ltrim($parts['path'] ?? '', '/');

echo "Connecting to database $dbName@$host:$port as $user\n";

$sql = file_get_contents($sqlFile);
if ($sql === false) {
    echo "Failed to read SQL file\n";
    exit(1);
}

$mysqli = mysqli_init();
if (!$mysqli) {
    echo "Unable to initialize mysqli\n";
    exit(1);
}

if (!@$mysqli->real_connect($host, $user, $pass, $dbName, (int)$port)) {
    echo "Connection failed: " . mysqli_connect_error() . "\n";
    exit(1);
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    if ($mysqli->multi_query($sql)) {
        do {
            if ($result = $mysqli->store_result()) {
                $result->free();
            }
        } while ($mysqli->more_results() && $mysqli->next_result());
    }
    echo "SQL imported successfully.\n";
} catch (\Throwable $e) {
    echo "Error executing SQL: " . $e->getMessage() . "\n";
    exit(1);
}

$mysqli->close();
