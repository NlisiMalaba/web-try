<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Start transaction
    $db->beginTransaction();
    
    // Read the migration file
    $migration_sql = file_get_contents(__DIR__ . '/migrations/004_add_medication_reminders.sql');
    
    if ($migration_sql === false) {
        throw new Exception("Failed to read migration file");
    }
    
    // Split the SQL into individual statements
    $queries = array_filter(
        array_map('trim', 
            explode(';', $migration_sql)
        ),
        'strlen'
    );
    
    // Execute each query
    foreach ($queries as $query) {
        if (!empty($query)) {
            $stmt = $db->prepare($query);
            if (!$stmt->execute()) {
                throw new Exception("Error executing query: " . $query);
            }
        }
    }
    
    // Commit the transaction
    $db->commit();
    
    echo "Migration completed successfully!\n";
    
} catch (Exception $e) {
    // Rollback the transaction on error
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    die("Migration failed: " . $e->getMessage() . "\n");
}
