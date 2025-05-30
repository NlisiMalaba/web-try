<?php
header('Content-Type: application/json');
require_once '../config/database.php';

// Enable CORS if needed
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

// Handle different request methods
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get health metrics
        $metric_type = isset($_GET['type']) ? $_GET['type'] : null;
        $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
        $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
        
        $query = "SELECT * FROM health_metrics WHERE user_id = :user_id";
        $params = [':user_id' => $user_id];
        
        if ($metric_type) {
            $query .= " AND metric_type = :metric_type";
            $params[':metric_type'] = $metric_type;
        }
        
        $query .= " AND DATE(recorded_at) BETWEEN :start_date AND :end_date";
        $params[':start_date'] = $start_date;
        $params[':end_date'] = $end_date;
        
        $query .= " ORDER BY recorded_at ASC";
        
        $stmt = $db->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $metrics = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // For demo purposes - add sample data if no metrics exist
        if (empty($metrics) && !isset($_GET['type']) && !isset($_GET['start_date'])) {
            $sampleData = generateSampleHealthMetrics($user_id);
            echo json_encode(['data' => $sampleData]);
        } else {
            echo json_encode(['data' => $metrics]);
        }
        break;
        
    case 'POST':
        // Add new health metric
        $data = json_decode(file_get_contents('php://input'), true);
        
        $required_fields = ['metric_type', 'recorded_at'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Missing required field: $field"]);
                exit();
            }
        }
        
        $query = "INSERT INTO health_metrics 
                 (user_id, metric_type, value1, value2, unit, notes, recorded_at) 
                 VALUES 
                 (:user_id, :metric_type, :value1, :value2, :unit, :notes, :recorded_at)";
        
        $stmt = $db->prepare($query);
        
        try {
            $stmt->execute([
                ':user_id' => $user_id,
                ':metric_type' => $data['metric_type'],
                ':value1' => $data['value1'] ?? null,
                ':value2' => $data['value2'] ?? null,
                ':unit' => $data['unit'] ?? null,
                ':notes' => $data['notes'] ?? null,
                ':recorded_at' => $data['recorded_at']
            ]);
            
            $metric_id = $db->lastInsertId();
            
            echo json_encode([
                'message' => 'Health metric added successfully',
                'id' => $metric_id
            ]);
            
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to add health metric: ' . $e->getMessage()]);
        }
        break;
        
    case 'DELETE':
        // Delete a health metric
        $metric_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if ($metric_id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid metric ID']);
            exit();
        }
        
        // First, verify the metric belongs to the user
        $query = "SELECT id FROM health_metrics WHERE id = :id AND user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->execute([':id' => $metric_id, ':user_id' => $user_id]);
        
        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Metric not found or access denied']);
            exit();
        }
        
        // Delete the metric
        $query = "DELETE FROM health_metrics WHERE id = :id";
        $stmt = $db->prepare($query);
        
        try {
            $stmt->execute([':id' => $metric_id]);
            echo json_encode(['message' => 'Metric deleted successfully']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete metric: ' . $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

/**
 * Generate sample health metrics for demo purposes
 */
function generateSampleHealthMetrics($user_id) {
    $metrics = [];
    $now = new DateTime();
    
    // Generate blood pressure data for the last 30 days
    for ($i = 30; $i >= 0; $i--) {
        $date = clone $now;
        $date->modify("-$i days");
        
        // Add blood pressure (systolic/diastolic)
        $metrics[] = [
            'id' => 'bp' . $i,
            'user_id' => $user_id,
            'metric_type' => 'blood_pressure',
            'value1' => rand(110, 140), // systolic
            'value2' => rand(70, 90),   // diastolic
            'unit' => 'mmHg',
            'notes' => $i % 5 === 0 ? 'Morning reading' : null,
            'recorded_at' => $date->format('Y-m-d') . ' ' . rand(8, 20) . ':' . str_pad(rand(0, 59), 2, '0', STR_PAD_LEFT) . ':00',
            'created_at' => $date->format('Y-m-d H:i:s'),
            'updated_at' => $date->format('Y-m-d H:i:s')
        ];
        
        // Add heart rate (every other day)
        if ($i % 2 === 0) {
            $metrics[] = [
                'id' => 'hr' . $i,
                'user_id' => $user_id,
                'metric_type' => 'heart_rate',
                'value1' => rand(65, 85),
                'value2' => null,
                'unit' => 'bpm',
                'notes' => null,
                'recorded_at' => $date->format('Y-m-d') . ' ' . rand(8, 20) . ':' . str_pad(rand(0, 59), 2, '0', STR_PAD_LEFT) . ':00',
                'created_at' => $date->format('Y-m-d H:i:s'),
                'updated_at' => $date->format('Y-m-d H:i:s')
            ];
        }
        
        // Add weight (once a week)
        if ($i % 7 === 0) {
            $metrics[] = [
                'id' => 'wt' . $i,
                'user_id' => $user_id,
                'metric_type' => 'weight',
                'value1' => 75 + (rand(-10, 10) / 10), // 74.0 - 76.0 kg
                'value2' => null,
                'unit' => 'kg',
                'notes' => 'Morning weight',
                'recorded_at' => $date->format('Y-m-d') . ' 08:' . str_pad(rand(0, 59), 2, '0', STR_PAD_LEFT) . ':00',
                'created_at' => $date->format('Y-m-d H:i:s'),
                'updated_at' => $date->format('Y-m-d H:i:s')
            ];
        }
        
        // Add glucose (every 3 days)
        if ($i % 3 === 0) {
            $metrics[] = [
                'id' => 'gl' . $i,
                'user_id' => $user_id,
                'metric_type' => 'glucose',
                'value1' => rand(80, 130),
                'value2' => null,
                'unit' => 'mg/dL',
                'notes' => 'Fasting glucose',
                'recorded_at' => $date->format('Y-m-d') . ' 07:' . str_pad(rand(0, 59), 2, '0', STR_PAD_LEFT) . ':00',
                'created_at' => $date->format('Y-m-d H:i:s'),
                'updated_at' => $date->format('Y-m-d H:i:s')
            ];
        }
    }
    
    return $metrics;
}
?>
