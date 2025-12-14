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

// Use PDO instead of mysqli to avoid dependency on ext-mysqli
$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $dbName);
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Execute the SQL. PDO::exec supports multiple statements for MySQL when
    // the driver allows it. If the file is large or contains complex delimiters,
    // users should import via their DB client.
    $pdo->exec($sql);

    echo "SQL imported successfully.\n";
} catch (\PDOException $e) {
    echo "Error executing SQL (PDO): " . $e->getMessage() . "\n";
    exit(1);
}
