<?php

/**
 * Verify if user is logged in
 * @return array|false Returns user data if logged in, false otherwise
 */
function verifyToken() {
    // Check if user is logged in via session
    if (isset($_SESSION['user_id'])) {
        return [
            'id' => $_SESSION['user_id'],
            'email' => $_SESSION['email'] ?? '',
            'first_name' => $_SESSION['first_name'] ?? '',
            'last_name' => $_SESSION['last_name'] ?? ''
        ];
    }
    
    // Check for API token in headers (for API requests)
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    if (strpos($authHeader, 'Bearer ') === 0) {
        $token = substr($authHeader, 7);
        return validateApiToken($token);
    }
    
    return false;
}

/**
 * Validate API token
 * @param string $token API token to validate
 * @return array|false Returns user data if token is valid, false otherwise
 */
function validateApiToken($token) {
    if (empty($token)) {
        return false;
    }
    
    // In a real application, you would validate the token against your database
    // This is a simplified example
    require_once __DIR__ . '/../config/database.php';
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT id, email, first_name, last_name FROM users WHERE api_token = :token LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":token", $token);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Store user data in session for subsequent requests
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            
            return $user;
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
    }
    
    return false;
}

/**
 * Require authentication
 * Redirects to login page if user is not authenticated
 */
function requireAuth() {
    $user = verifyToken();
    if (!$user) {
        if (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            // API request - return JSON response
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            exit;
        } else {
            // Web request - redirect to login
            $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
            header('Location: /login.php');
            exit;
        }
    }
    return $user;
}

/**
 * Generate a secure API token
 * @return string Generated token
 */
function generateApiToken() {
    return bin2hex(random_bytes(32));
}

/**
 * Update user's API token
 * @param int $userId User ID
 * @return string|false Returns the new token on success, false on failure
 */
function updateUserApiToken($userId) {
    $token = generateApiToken();
    $hashedToken = password_hash($token, PASSWORD_DEFAULT);
    
    require_once __DIR__ . '/../config/database.php';
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "UPDATE users SET api_token = :token WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":token", $hashedToken);
        $stmt->bindParam(":id", $userId, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            return $token; // Return the unhashed token to the user
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
    }
    
    return false;
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
