<?php
// Safe schema fixes: add missing indexes and foreign keys conditionally.
// Usage: php scripts/apply_schema_fixes.php

function loadEnvDatabaseUrl(string $path): ?string
{
    if (!file_exists($path)) return null;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), 'DATABASE_URL=') === 0) {
            return trim(substr(trim($line), strlen('DATABASE_URL=')), " \"'\r\n");
        }
    }
    return null;
}

$envPath = __DIR__ . '/../.env.local';
if (!file_exists($envPath)) {
    $envPath = __DIR__ . '/../.env';
}

$dbUrl = loadEnvDatabaseUrl($envPath);
if (!$dbUrl) {
    echo "DATABASE_URL not found in .env(.local)\n";
    exit(1);
}

$dbUrl = trim($dbUrl, " \"'\n\r");
$parts = parse_url($dbUrl);
if ($parts === false) {
    echo "Invalid DATABASE_URL: $dbUrl\n";
    exit(1);
}

$user = $parts['user'] ?? 'root';
$pass = $parts['pass'] ?? '';
$host = $parts['host'] ?? '127.0.0.1';
$port = $parts['port'] ?? 3306;
$dbName = ltrim($parts['path'] ?? '', '/');

$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $dbName);
try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    echo "DB connect error: " . $e->getMessage() . "\n";
    exit(1);
}

function hasColumn(PDO $pdo, string $db, string $table, string $column): bool
{
    $sql = "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?";
    return (bool) $pdo->prepare($sql)->execute([$db, $table, $column]) && (bool) $pdo->prepare($sql)->fetchColumn();
}

function columnExists(PDO $pdo, string $db, string $table, string $column): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $stmt->execute([$db, $table, $column]);
    return (int)$stmt->fetchColumn() > 0;
}

function indexExists(PDO $pdo, string $db, string $table, string $index): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?');
    $stmt->execute([$db, $table, $index]);
    return (int)$stmt->fetchColumn() > 0;
}

function fkExists(PDO $pdo, string $db, string $table, string $fk): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = "FOREIGN KEY"');
    $stmt->execute([$db, $table, $fk]);
    return (int)$stmt->fetchColumn() > 0;
}

$actions = [];

// Ensure project -> category index + FK
if (columnExists($pdo, $dbName, 'project', 'category_id')) {
    if (!indexExists($pdo, $dbName, 'project', 'IDX_2FB3D0EE12469DE2')) {
        $actions[] = "CREATE INDEX IDX_2FB3D0EE12469DE2 ON project (category_id)";
    }
    if (!fkExists($pdo, $dbName, 'project', 'FK_2FB3D0EE12469DE2')) {
        $actions[] = "ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EE12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)";
    }
}

// Ensure organisation -> communite index + FK
if (columnExists($pdo, $dbName, 'organisation', 'communite_id')) {
    if (!indexExists($pdo, $dbName, 'organisation', 'IDX_E6E132B4E7685898')) {
        $actions[] = "CREATE INDEX IDX_E6E132B4E7685898 ON organisation (communite_id)";
    }
    if (!fkExists($pdo, $dbName, 'organisation', 'FK_E6E132B4E7685898')) {
        $actions[] = "ALTER TABLE organisation ADD CONSTRAINT FK_E6E132B4E7685898 FOREIGN KEY (communite_id) REFERENCES communite (id)";
    }
}

// Ensure produit -> user FK if artist_id exists
if (columnExists($pdo, $dbName, 'produit', 'artist_id')) {
    if (!indexExists($pdo, $dbName, 'produit', 'IDX_29A5EC27B7970CF8')) {
        $actions[] = "CREATE INDEX IDX_29A5EC27B7970CF8 ON produit (artist_id)";
    }
    if (!fkExists($pdo, $dbName, 'produit', 'FK_29A5EC27B7970CF8')) {
        $actions[] = "ALTER TABLE produit ADD CONSTRAINT FK_29A5EC27B7970CF8 FOREIGN KEY (artist_id) REFERENCES `user` (id)";
    }
}

if (empty($actions)) {
    echo "No schema fixes required.\n";
    exit(0);
}

echo "The following safe actions will be executed:\n";
foreach ($actions as $a) echo " - $a\n";

$autoYes = in_array('--yes', $argv ?? []) || in_array('-y', $argv ?? []);
$confirm = 'n';
if ($autoYes) {
    $confirm = 'y';
} else {
    if (function_exists('readline')) {
        $confirm = readline("Proceed? (y/N): ");
    } else {
        // fallback to fgets
        echo "Proceed? (y/N): ";
        $handle = fopen('php://stdin', 'r');
        $confirm = fgets($handle);
        fclose($handle);
    }
}
if (strtolower(trim((string)$confirm)) !== 'y') {
    echo "Aborted by user.\n";
    exit(0);
}

foreach ($actions as $sql) {
    try {
        echo "Executing: $sql\n";
        $pdo->exec($sql);
    } catch (PDOException $e) {
        echo "Failed to execute [$sql]: " . $e->getMessage() . "\n";
    }
}

echo "Done.\n";
