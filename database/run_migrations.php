<?php
/**
 * Database Migration Script
 * 
 * Run this script to apply all pending database migrations.
 * Make sure to configure your database connection in config/database.php
 */

// Load database configuration
require_once __DIR__ . '/../config/database.php';

// Create database connection
$database = new Database();
$db = $database->getConnection();

// Check if migrations table exists
$migrationsTableExists = false;
try {
    $db->query("SELECT 1 FROM migrations LIMIT 1");
    $migrationsTableExists = true;
} catch (PDOException $e) {
    // Table doesn't exist yet
    $migrationsTableExists = false;
}

echo "Starting database migrations...\n";

// Create migrations table if it doesn't exist
if (!$migrationsTableExists) {
    echo "Creating migrations table...\n";
    $createTableSql = "
        CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL,
            batch INT NOT NULL,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_migration (migration)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $db->exec($createTableSql);
    echo "Migrations table created.\n";
}

// Get all migration files in order
$migrationFiles = glob(__DIR__ . '/migrations/*.sql');
sort($migrationFiles);

// Get already executed migrations
$executedMigrations = [];
$stmt = $db->query("SELECT migration FROM migrations ORDER BY batch DESC, migration DESC");
if ($stmt) {
    $executedMigrations = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Get the current batch number
$batch = 1;
$stmt = $db->query("SELECT MAX(batch) as max_batch FROM migrations");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
if ($result && isset($result['max_batch'])) {
    $batch = (int)$result['max_batch'] + 1;
}

$migrationCount = 0;

try {
    foreach ($migrationFiles as $file) {
        $migrationName = basename($file);
        
        // Skip already executed migrations
        if (in_array($migrationName, $executedMigrations)) {
            echo "Skipping already executed migration: $migrationName\n";
            continue;
        }
        
        echo "Running migration: $migrationName\n";
        
        // Start transaction for this migration
        $db->beginTransaction();
        
        try {
            // Read and execute the SQL file
            $sql = file_get_contents($file);
            
            // Split the SQL into individual statements
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            
            foreach ($statements as $statement) {
                if (empty($statement)) continue;
                $db->exec($statement);
            }
            
            // Record the migration
            $stmt = $db->prepare("INSERT INTO migrations (migration, batch) VALUES (?, ?)");
            $stmt->execute([$migrationName, $batch]);
            
            // Commit this migration
            $db->commit();
            
            $migrationCount++;
            echo "Applied migration: $migrationName\n";
            
        } catch (Exception $e) {
            // Rollback this migration
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw new Exception("Failed to apply migration $migrationName: " . $e->getMessage());
        }
    }
    
    if ($migrationCount > 0) {
        echo "\nSuccessfully applied $migrationCount migration(s).\n";
    } else {
        echo "\nNo new migrations to apply. Database is up to date.\n";
    }
    
} catch (Exception $e) {
    // Rollback in case of error
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    echo "\nError applying migrations: " . $e->getMessage() . "\n";
    exit(1);
}
