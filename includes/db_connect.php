<?php
// Database configuration
$db_host = 'localhost';     // Database host
$db_name = 'healthassist';  // Database name
$db_user = 'root';          // Database username
$db_pass = '';              // Database password

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create a PDO instance
try {
    $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
    
    // Set timezone
    $pdo->exec("SET time_zone = '+02:00'");
    
} catch (PDOException $e) {
    // Log the error
    error_log("Database Connection Error: " . $e->getMessage());
    
    // Display a user-friendly error message
    die("<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; border: 1px solid #e74c3c; border-radius: 5px; background-color: #fde8e8; color: #c0392b;'>
            <h2>Database Connection Error</h2>
            <p>We're experiencing technical difficulties. Please try again later.</p>
            <p><small>Error: " . htmlspecialchars($e->getMessage()) . "</small></p>
        </div>");
}

// Function to execute a query with parameters
function executeQuery($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Query Error: " . $e->getMessage() . "\nQuery: " . $sql);
        throw $e;
    }
}

// Function to fetch a single row
function fetchOne($pdo, $sql, $params = []) {
    $stmt = executeQuery($pdo, $sql, $params);
    return $stmt->fetch();
}

// Function to fetch all rows
function fetchAll($pdo, $sql, $params = []) {
    $stmt = executeQuery($pdo, $sql, $params);
    return $stmt->fetchAll();
}

// Function to get the last inserted ID
function lastInsertId($pdo) {
    return $pdo->lastInsertId();
}

// Function to begin a transaction
function beginTransaction($pdo) {
    return $pdo->beginTransaction();
}

// Function to commit a transaction
function commit($pdo) {
    return $pdo->commit();
}

// Function to rollback a transaction
function rollback($pdo) {
    return $pdo->rollBack();
}

// Function to escape user input (alternative to PDO's prepare/execute)
function escape($pdo, $value) {
    return $pdo->quote($value);
}

// Function to check if a table exists
function tableExists($pdo, $table) {
    try {
        $result = $pdo->query("SHOW TABLES LIKE '" . $table . "'");
        return $result->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

// Function to get table columns
function getTableColumns($pdo, $table) {
    try {
        $stmt = $pdo->query("DESCRIBE `$table`");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        return [];
    }
}

// Close the connection when done (optional, as PHP will close it automatically)
// $pdo = null;
?>
