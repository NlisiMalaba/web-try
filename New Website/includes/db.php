<?php
/**
 * Database helper functions
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Get a database connection
 * @return PDO Database connection
 */
function getDBConnection() {
    static $db = null;
    
    if ($db === null) {
        try {
            $database = new Database();
            $db = $database->getConnection();
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $db->exec("SET NAMES 'utf8'");
            $db->exec("SET time_zone = '+00:00'");
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }
    
    return $db;
}

/**
 * Execute a query with parameters
 * @param string $sql SQL query
 * @param array $params Parameters for the query
 * @return PDOStatement|false PDO statement or false on failure
 */
function executeQuery($sql, $params = []) {
    try {
        $db = getDBConnection();
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Query failed: " . $e->getMessage() . "\nSQL: " . $sql);
        return false;
    }
}

/**
 * Fetch a single row from the database
 * @param string $sql SQL query
 * @param array $params Parameters for the query
 * @return array|false Associative array of the row or false if not found
 */
function fetchOne($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt ? $stmt->fetch() : false;
}

/**
 * Fetch all rows from the database
 * @param string $sql SQL query
 * @param array $params Parameters for the query
 * @return array Array of associative arrays
 */
function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt ? $stmt->fetchAll() : [];
}

/**
 * Insert a row into a table
 * @param string $table Table name
 * @param array $data Associative array of column => value
 * @return int|false ID of the inserted row or false on failure
 */
function insert($table, $data) {
    if (empty($data)) {
        return false;
    }
    
    $columns = array_keys($data);
    $placeholders = array_map(function($col) { 
        return ":$col"; 
    }, $columns);
    
    $sql = "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) 
            VALUES (" . implode(', ', $placeholders) . ")";
    
    $db = getDBConnection();
    $stmt = $db->prepare($sql);
    
    try {
        $db->beginTransaction();
        $stmt->execute($data);
        $id = $db->lastInsertId();
        $db->commit();
        return $id;
    } catch (PDOException $e) {
        $db->rollBack();
        error_log("Insert failed: " . $e->getMessage() . "\nSQL: " . $sql);
        return false;
    }
}

/**
 * Update rows in a table
 * @param string $table Table name
 * @param array $data Associative array of column => value
 * @param string $where WHERE clause (without the WHERE keyword)
 * @param array $params Parameters for the WHERE clause
 * @return int|false Number of affected rows or false on failure
 */
function update($table, $data, $where, $params = []) {
    if (empty($data)) {
        return false;
    }
    
    $set = [];
    foreach (array_keys($data) as $column) {
        $set[] = "`$column` = :$column";
    }
    
    $sql = "UPDATE `$table` SET " . implode(', ', $set) . " WHERE $where";
    
    // Merge data and where params
    $params = array_merge($data, $params);
    
    $stmt = executeQuery($sql, $params);
    return $stmt ? $stmt->rowCount() : false;
}

/**
 * Delete rows from a table
 * @param string $table Table name
 * @param string $where WHERE clause (without the WHERE keyword)
 * @param array $params Parameters for the WHERE clause
 * @return int|false Number of affected rows or false on failure
 */
function delete($table, $where, $params = []) {
    $sql = "DELETE FROM `$table` WHERE $where";
    $stmt = executeQuery($sql, $params);
    return $stmt ? $stmt->rowCount() : false;
}

/**
 * Check if a record exists in a table
 * @param string $table Table name
 * @param string $where WHERE clause (without the WHERE keyword)
 * @param array $params Parameters for the WHERE clause
 * @return bool True if record exists, false otherwise
 */
function exists($table, $where, $params = []) {
    $sql = "SELECT 1 FROM `$table` WHERE $where LIMIT 1";
    $result = fetchOne($sql, $params);
    return !empty($result);
}

/**
 * Get the count of records in a table
 * @param string $table Table name
 * @param string $where Optional WHERE clause (without the WHERE keyword)
 * @param array $params Parameters for the WHERE clause
 * @return int Number of records
 */
function countRows($table, $where = '1', $params = []) {
    $sql = "SELECT COUNT(*) as count FROM `$table` WHERE $where";
    $result = fetchOne($sql, $params);
    return (int)($result['count'] ?? 0);
}
