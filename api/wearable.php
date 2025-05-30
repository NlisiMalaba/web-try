<?php
// Process control constants
if (!defined('SIGTERM')) {
    define('SIGTERM', 15);
}

if (!defined('WNOHANG')) {
    define('WNOHANG', 1);
}

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/process.php';

/**
 * Get health metrics for a user
 * @param int $userId User ID
 * @return array Response array
 */
function getHealthMetrics($userId) {
    global $db;
    
    $stmt = $db->prepare("
        SELECT * FROM health_metrics 
        WHERE user_id = :user_id 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    
    $stmt->execute([':user_id' => $userId]);
    $metrics = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$metrics) {
        return [
            'success' => false,
            'message' => 'No health metrics found'
        ];
    }
    
    return [
        'success' => true,
        'metrics' => $metrics
    ];
}

/**
 * Start health simulation for a user
 * @param int $userId User ID
 * @return array Response array
 */
function startSimulation($userId) {
    global $db;
    
    // Check if simulation already exists
    $stmt = $db->prepare("
        SELECT id FROM health_simulation 
        WHERE user_id = :user_id 
        AND status = 'running'
    ");
    
    $stmt->execute([':user_id' => $userId]);
    $runningSimulation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($runningSimulation) {
        return [
            'success' => false,
            'message' => 'Simulation already running'
        ];
    }
    
    // Insert new simulation
    $stmt = $db->prepare("
        INSERT INTO health_simulation (user_id, status, created_at, started_at) 
        VALUES (:user_id, 'running', NOW(), NOW())
    ");
    
    $stmt->execute([':user_id' => $userId]);
    $simulationId = $db->lastInsertId();
    
    // Start simulation process
    $process = new Process(function() use ($simulationId, $userId) {
        runSimulation($simulationId, $userId);
    });
    
    if (!$process->start()) {
        throw new Exception('Failed to start simulation process');
    }
    
    return [
        'success' => true,
        'message' => 'Simulation started successfully'
    ];
}

/**
 * Stop health simulation for a user
 * @param int $userId User ID
 * @return array Response array
 */
function stopSimulation($userId) {
    global $db;
    
    $stmt = $db->prepare("
        SELECT id, status FROM health_simulation 
        WHERE user_id = :user_id 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    
    $stmt->execute([':user_id' => $userId]);
    $simulation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$simulation) {
        return [
            'success' => false,
            'message' => 'No simulation found'
        ];
    }
    
    if ($simulation['status'] !== 'running') {
        return [
            'success' => false,
            'message' => 'Simulation is not running'
        ];
    }
    
    // Update simulation status
    $stmt = $db->prepare("
        UPDATE health_simulation 
        SET status = 'stopped', 
            stopped_at = NOW() 
        WHERE id = :id
    ");
    
    $stmt->execute([':id' => $simulation['id']]);
    
    return [
        'success' => true,
        'message' => 'Simulation stopped successfully'
    ];
}

/**
 * Get simulation status for a user
 * @param int $userId User ID
 * @return array Response array
 */
function getSimulationStatus($userId) {
    global $db;
    
    $stmt = $db->prepare("
        SELECT * FROM health_simulation 
        WHERE user_id = :user_id 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    
    $stmt->execute([':user_id' => $userId]);
    $simulation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$simulation) {
        return [
            'success' => false,
            'message' => 'No simulation found'
        ];
    }
    
    return [
        'success' => true,
        'simulation' => $simulation
    ];
}

/**
 * Generate simulated health metrics
 * @return array Simulated metrics
 */
function generateSimulatedMetrics() {
    return [
        'heart_rate' => rand(60, 100),
        'blood_pressure_systolic' => rand(110, 130),
        'blood_pressure_diastolic' => rand(70, 85),
        'oxygen_level' => rand(95, 100),
        'temperature' => rand(97, 99) + rand(0, 99) / 100,
        'step_count' => rand(500, 2000),
        'calories_burned' => rand(50, 200),
        'sleep_duration' => rand(6, 9),
        'stress_level' => rand(1, 5)
    ];
}

/**
 * Run simulation process
 * @param int $simulationId Simulation ID
 * @param int $userId User ID
 */
function runSimulation($simulationId, $userId) {
    global $db;
    
    // Generate metrics every 5 minutes
    while (true) {
        try {
            // Generate random but realistic health metrics
            $metrics = generateSimulatedMetrics();
            
            // Save heart rate
            $stmt = $db->prepare("INSERT INTO health_metrics (user_id, metric_type, value1, unit, recorded_at) VALUES (:user_id, 'heart_rate', :value, 'bpm', NOW())");
            $stmt->execute([
                ':user_id' => $userId,
                ':value' => $metrics['heart_rate']
            ]);
            
            // Save blood pressure
            $stmt = $db->prepare("INSERT INTO health_metrics (user_id, metric_type, value1, value2, unit, recorded_at) VALUES (:user_id, 'blood_pressure', :systolic, :diastolic, 'mmHg', NOW())");
            $stmt->execute([
                ':user_id' => $userId,
                ':systolic' => $metrics['blood_pressure_systolic'],
                ':diastolic' => $metrics['blood_pressure_diastolic']
            ]);
            
            // Save oxygen level
            $stmt = $db->prepare("INSERT INTO health_metrics (user_id, metric_type, value1, unit, recorded_at) VALUES (:user_id, 'oxygen_level', :value, '%', NOW())");
            $stmt->execute([
                ':user_id' => $userId,
                ':value' => $metrics['oxygen_level']
            ]);
            
            // Save temperature
            $stmt = $db->prepare("INSERT INTO health_metrics (user_id, metric_type, value1, unit, recorded_at) VALUES (:user_id, 'temperature', :value, '°C', NOW())");
            $stmt->execute([
                ':user_id' => $userId,
                ':value' => $metrics['temperature']
            ]);
            
            // Save step count in activity_logs
            $stmt = $db->prepare("INSERT INTO activity_logs (user_id, activity_type, value, unit, start_time, end_time) VALUES (:user_id, 'steps', :value, 'steps', NOW(), NOW())");
            $stmt->execute([
                ':user_id' => $userId,
                ':value' => $metrics['step_count']
            ]);
            
            // Save calories burned in activity_logs
            $stmt = $db->prepare("INSERT INTO activity_logs (user_id, activity_type, value, unit, duration_minutes, start_time, end_time) VALUES (:user_id, 'exercise', :value, 'calories', 30, NOW(), DATE_ADD(NOW(), INTERVAL 30 MINUTE))");
            $stmt->execute([
                ':user_id' => $userId,
                ':value' => $metrics['calories_burned']
            ]);
            
            // Save sleep duration in activity_logs
            $stmt = $db->prepare("INSERT INTO activity_logs (user_id, activity_type, value, unit, duration_minutes, start_time, end_time) VALUES (:user_id, 'sleep', :value, 'hours', :value * 60, DATE_SUB(NOW(), INTERVAL :value HOUR), NOW())");
            $stmt->execute([
                ':user_id' => $userId,
                ':value' => $metrics['sleep_duration']
            ]);
            
            // Save stress level in health_metrics
            $stmt = $db->prepare("INSERT INTO health_metrics (user_id, metric_type, value1, unit, recorded_at) VALUES (:user_id, 'stress_level', :value, 'scale', NOW())");
            $stmt->execute([
                ':user_id' => $userId,
                ':value' => $metrics['stress_level']
            ]);
            
            // Check if simulation is still running
            $stmt = $db->prepare("SELECT status FROM health_simulation WHERE id = :id");
            $stmt->execute([':id' => $simulationId]);
            $simulation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($simulation['status'] !== 'running') {
                break;
            }
            
            // Sleep for 5 minutes
            sleep(300);
            
        } catch (Exception $e) {
            error_log("Error in simulation: " . $e->getMessage());
            break;
        }
    }
    
    return true;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? '';
$response = ['success' => false, 'message' => 'Invalid action'];

// Verify user is logged in
$user = verifyToken();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$userId = $user['id'];

try {
    switch ($action) {
        case 'get_metrics':
            $response = getHealthMetrics($userId);
            break;
            
        case 'start_simulation':
            $response = startSimulation($userId);
            break;
            
        case 'stop_simulation':
            $response = stopSimulation($userId);
            break;
            
        case 'get_simulation_status':
            $response = getSimulationStatus($userId);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    error_log('Wearable API Error: ' . $e->getMessage());
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

echo json_encode($response);
