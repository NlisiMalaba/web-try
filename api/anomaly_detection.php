<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Database.php';

/**
 * Lightweight anomaly detection using Z-score and range-based methods
 */
class AnomalyDetector {
    private $db;
    private $zScoreThreshold = 2.5; // Adjust this threshold based on your needs
    
    // Normal ranges for health metrics
    private $metricRanges = [
        'heart_rate' => ['min' => 60, 'max' => 100],
        'systolic' => ['min' => 90, 'max' => 140],
        'diastolic' => ['min' => 60, 'max' => 90],
        'oxygen_level' => ['min' => 95, 'max' => 100],
        'temperature' => ['min' => 36.1, 'max' => 37.2],
        'stress_level' => ['min' => 1, 'max' => 5]
    ];
    
    // Weights for each metric (sum should be 1.0)
    private $metricWeights = [
        'heart_rate' => 0.2,
        'systolic' => 0.2,
        'diastolic' => 0.2,
        'oxygen_level' => 0.2,
        'temperature' => 0.1,
        'stress_level' => 0.1
    ];
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Detect anomalies in health metrics
     */
    public function detectAnomalies($userId, $metrics) {
        if (empty($metrics)) {
            return [
                'scores' => [],
                'is_anomaly' => []
            ];
        }
        
        $results = [
            'scores' => [],
            'is_anomaly' => []
        ];
        
        foreach ($metrics as $metric) {
            $anomalyScore = $this->calculateAnomalyScore($metric);
            $isAnomaly = $anomalyScore > $this->zScoreThreshold ? 1 : 0;
            
            $results['scores'][] = $anomalyScore;
            $results['is_anomaly'][] = $isAnomaly;
        }
        
        return $results;
    }
    
    /**
     * Calculate anomaly score for a single metric set
     */
    private function calculateAnomalyScore($metrics) {
        $totalScore = 0;
        $totalWeight = 0;
        
        foreach ($this->metricWeights as $metric => $weight) {
            if (!isset($metrics[$metric])) continue;
            
            $value = $metrics[$metric];
            $range = $this->metricRanges[$metric];
            $normalized = $this->normalizeValue($value, $range['min'], $range['max']);
            
            // Calculate deviation from normal range (0-1, where 0.5 is the middle of the range)
            $deviation = abs($normalized - 0.5) * 2; // Convert to 0-1 range where 1 is max deviation
            
            // Add weighted score
            $totalScore += $deviation * $weight;
            $totalWeight += $weight;
        }
        
        // Normalize score based on total weight used
        $finalScore = $totalWeight > 0 ? ($totalScore / $totalWeight) * 10 : 0;
        
        // Apply Z-score like scaling
        return $finalScore * 2; // Scale to make scores more pronounced
    }
    
    /**
     * Normalize value to 0-1 range based on min-max scaling
     */
    /**
     * Normalize a value to 0-1 range based on min-max scaling
     */
    private function normalizeValue($value, $min, $max) {
        if ($max <= $min) return 0.5; // Avoid division by zero
        return max(0, min(1, ($value - $min) / ($max - $min)));
    }

    /**
     * Normalize metrics to a common scale for analysis
     */
    private function normalizeMetrics($metrics) {
        $normalized = [];
        
        foreach ($metrics as $metric) {
            $normalized[] = [
                'heart_rate' => $this->normalizeValue($metric['heart_rate'], 40, 200),
                'systolic' => $this->normalizeValue($metric['systolic'], 80, 200),
                'diastolic' => $this->normalizeValue($metric['diastolic'], 40, 120),
                'oxygen_level' => $this->normalizeValue($metric['oxygen_level'], 70, 100),
                'temperature' => $this->normalizeValue($metric['temperature'], 35, 42),
                'stress_level' => $this->normalizeValue($metric['stress_level'], 1, 5)
            ];
        }
        
        return $normalized;
    }
}

// Main execution
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = $input['user_id'] ?? null;
    $metrics = $input['metrics'] ?? [];
    
    if (!$userId || empty($metrics)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required parameters']);
        exit;
    }
    
    try {
        // Get database connection
        require_once __DIR__ . '/../includes/Database.php';
        $database = new Database();
        $db = $database->getConnection();
        
        $detector = new AnomalyDetector($db);
        $result = $detector->detectAnomalies($userId, $metrics);
        
        echo json_encode([
            'success' => true,
            'data' => $result
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'An error occurred: ' . $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed'
    ]);
}
