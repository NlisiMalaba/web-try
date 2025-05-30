<?php
// Disable error display in production
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');
error_reporting(E_ALL);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Get user data
$user_id = $_SESSION['user_id'];
$query = "SELECT first_name, last_name, email FROM users WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(":id", $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Initialize variables
$error = '';
$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';
unset($_SESSION['success']);

/**
 * Get CSS class for severity level
 */
function getSeverityClass($severity) {
    $classes = [
        1 => 'bg-success',
        2 => 'bg-info',
        3 => 'bg-warning',
        4 => 'bg-orange',
        5 => 'bg-danger'
    ];
    return $classes[$severity] ?? 'bg-secondary';
}

/**
 * Get text description for severity level
 */
function getSeverityText($severity) {
    $levels = [
        1 => 'Mild',
        2 => 'Moderate',
        3 => 'Moderately Severe',
        4 => 'Severe',
        5 => 'Very Severe'
    ];
    return $levels[$severity] ?? 'Unknown';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_symptom'])) {
        try {
            // Validate input
            $symptom_name = trim($_POST['symptom_name']);
            $severity = (int)$_POST['severity'];
            $duration = !empty($_POST['duration']) ? trim($_POST['duration']) : null;
            $notes = !empty($_POST['notes']) ? trim($_POST['notes']) : null;
            
            if (empty($symptom_name)) {
                throw new Exception("Symptom name is required.");
            }
            
            if ($severity < 1 || $severity > 5) {
                throw new Exception("Please select a valid severity level (1-5).");
            }
            
            // Check if symptoms table exists
            $table_check = $db->query("SHOW TABLES LIKE 'symptoms'");
            if ($table_check->rowCount() == 0) {
                // Table doesn't exist, create it
                $sql = file_get_contents('sql/fix_symptoms_table.sql');
                $db->exec($sql);
            }
            
            $query = "INSERT INTO symptoms (user_id, symptom_name, severity, duration, notes) 
                     VALUES (:user_id, :symptom_name, :severity, :duration, :notes)";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $stmt->bindParam(":symptom_name", $symptom_name);
            $stmt->bindParam(":severity", $severity, PDO::PARAM_INT);
            $stmt->bindValue(":duration", $duration, is_null($duration) ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(":notes", $notes, is_null($notes) ? PDO::PARAM_NULL : PDO::PARAM_STR);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Symptom logged successfully!";
                header("Location: symptoms.php");
                exit();
            } else {
                $errorInfo = $stmt->errorInfo();
                throw new Exception("Failed to log symptom. Error: " . ($errorInfo[2] ?? 'Unknown error'));
            }
            
        } catch (Exception $e) {
            $error = "Error logging symptom: " . $e->getMessage();
            error_log($error);
        }
    } elseif (isset($_POST['delete_symptom'])) {
        try {
            $symptom_id = (int)$_POST['symptom_id'];
            
            // Verify that the symptom belongs to the current user
            $query = "SELECT id FROM symptoms WHERE id = :id AND user_id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":id", $symptom_id, PDO::PARAM_INT);
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                // Delete the symptom
                $query = "DELETE FROM symptoms WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":id", $symptom_id, PDO::PARAM_INT);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Symptom deleted successfully!";
                } else {
                    $errorInfo = $stmt->errorInfo();
                    throw new Exception("Failed to delete symptom. Error: " . ($errorInfo[2] ?? 'Unknown error'));
                }
            } else {
                throw new Exception("Symptom not found or you don't have permission to delete it.");
            }
            
            header("Location: symptoms.php");
            exit();
            
        } catch (Exception $e) {
            $error = "Error deleting symptom: " . $e->getMessage();
            error_log($error);
        }
    }
}

// Get user's symptoms (last 30 days by default)
try {
    $query = "SELECT * FROM symptoms 
              WHERE user_id = :user_id 
              AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
              ORDER BY created_at DESC, id DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $symptoms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get symptom frequency for the chart (last 30 days)
    $query = "SELECT 
                symptom_name, 
                COUNT(*) as count,
                ROUND(AVG(severity), 1) as avg_severity
              FROM symptoms 
              WHERE user_id = :user_id 
                AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
              GROUP BY symptom_name 
              ORDER BY count DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $symptom_frequency = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    error_log($error);
    $symptoms = [];
    $symptom_frequency = [];
}

// Start output buffering
ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Symptom Tracker - HealthAssist Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/symptoms.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="main-content">
            <header class="top-bar">
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search...">
                </div>
                
                <div class="user-menu">
                    <div class="notifications">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge">3</span>
                    </div>
                    <div class="user-profile">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['first_name'] . ' ' . $user['last_name']); ?>" alt="Profile" class="avatar">
                        <span><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></span>
                    </div>
                </div>
            </header>
            
            <div class="content">
                <div class="welcome-banner">
                    <h1>Symptom Tracker</h1>
                    <p>Track and manage your health symptoms</p>
                </div>
                
                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: #e0f2fe;">
                            <i class="fas fa-notes-medical" style="color: #0ea5e9;"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Total Symptoms</h3>
                            <p class="stat-value"><?php echo count($symptoms); ?></p>
                            <p class="stat-change positive">
                                <i class="fas fa-arrow-up"></i> Track your symptoms
                            </p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: #fef3c7;">
                            <i class="fas fa-chart-line" style="color: #f59e0b;"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Severity</h3>
                            <p class="stat-value">
                                <?php 
                                $avgSeverity = !empty($symptoms) ? array_sum(array_column($symptoms, 'severity')) / count($symptoms) : 0;
                                echo number_format($avgSeverity, 1); 
                                ?>
                            </p>
                            <p class="stat-change">
                                Average severity (1-5)
                            </p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: #d1fae5;">
                            <i class="fas fa-calendar-alt" style="color: #10b981;"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Last Recorded</h3>
                            <p class="stat-value">
                                <?php 
                                echo !empty($symptoms) ? date('M j', strtotime($symptoms[0]['created_at'])) : 'N/A'; 
                                ?>
                            </p>
                            <p class="stat-change">
                                Most recent entry
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Symptoms List -->
                <div class="recent-activity">
                    <div class="activity-header">
                        <h2>Recent Symptoms</h2>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addSymptomModal">
                            <i class="fas fa-plus me-1"></i> Add New
                        </button>
                    </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                    
                    <div class="activity-list">
                        <?php if (empty($symptoms)): ?>
                            <div class="alert alert-info mb-0">No symptoms recorded yet. Click the button above to add one.</div>
                        <?php else: ?>
                            <?php foreach ($symptoms as $symptom): ?>
                                <div class="activity-item">
                                    <div class="activity-icon" style="background-color: #e0f2fe;">
                                        <i class="fas fa-notes-medical" style="color: #0ea5e9;"></i>
                                    </div>
                                    <div class="activity-details">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h4>
                                                <?php echo htmlspecialchars(ucfirst($symptom['symptom_name'])); ?>
                                                <span class="badge ms-2 bg-<?php echo getSeverityClass($symptom['severity']); ?>">
                                                    <?php echo getSeverityText($symptom['severity']); ?>
                                                </span>
                                            </h4>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#editSymptomModal<?php echo $symptom['id']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteSymptom(<?php echo $symptom['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <p class="mb-1">
                                            <?php if (!empty($symptom['notes'])): ?>
                                                <?php echo htmlspecialchars($symptom['notes']); ?>
                                            <?php else: ?>
                                                <span class="text-muted">No additional notes</span>
                                            <?php endif; ?>
                                        </p>
                                        <span class="activity-time">
                                            <?php echo date('M j, Y g:i A', strtotime($symptom['created_at'])); ?>
                                        </span>
                                    </div>
                                </div>

                                <!-- Edit Symptom Modal -->
                                <div class="modal fade" id="editSymptomModal<?php echo $symptom['id']; ?>" tabindex="-1" aria-labelledby="editSymptomModalLabel" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="editSymptomModalLabel">Edit Symptom</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form action="symptoms.php" method="post">
                                                <div class="modal-body">
                                                    <input type="hidden" name="action" value="update">
                                                    <input type="hidden" name="id" value="<?php echo $symptom['id']; ?>">
                                                    
                                                    <div class="mb-3">
                                                        <label for="editSymptom" class="form-label">Symptom</label>
                                                        <input type="text" class="form-control" id="editSymptom" name="symptom_name" value="<?php echo htmlspecialchars($symptom['symptom_name']); ?>" required>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="editSeverity" class="form-label">Severity</label>
                                                        <select class="form-select" id="editSeverity" name="severity" required>
                                                            <option value="1" <?php echo $symptom['severity'] == 1 ? 'selected' : ''; ?>>1 - Mild</option>
                                                            <option value="2" <?php echo $symptom['severity'] == 2 ? 'selected' : ''; ?>>2 - Moderate</option>
                                                            <option value="3" <?php echo $symptom['severity'] == 3 ? 'selected' : ''; ?>>3 - Moderate-Severe</option>
                                                            <option value="4" <?php echo $symptom['severity'] == 4 ? 'selected' : ''; ?>>4 - Severe</option>
                                                            <option value="5" <?php echo $symptom['severity'] == 5 ? 'selected' : ''; ?>>5 - Very Severe</option>
                                                        </select>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="editDate" class="form-label">Date</label>
                                                        <input type="datetime-local" class="form-control" id="editDate" name="date" value="<?php echo date('Y-m-d\TH:i', strtotime($symptom['created_at'])); ?>" required>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="editNotes" class="form-label">Notes</label>
                                                        <textarea class="form-control" id="editNotes" name="notes" rows="3"><?php echo htmlspecialchars($symptom['notes']); ?></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div> <!-- End .activity-list -->
                </div> <!-- End .recent-activity -->
                
                <!-- Symptoms Chart -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Symptom Frequency (Last 30 Days)</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="symptomsChart" height="300"></canvas>
                    </div>
                </div>
            </div> <!-- End .content -->

            <!-- Add Symptom Modal -->
            <div class="modal fade" id="addSymptomModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header border-0 pb-0">
                            <div class="w-100 text-center">
                                <div class="icon-circle bg-soft-primary text-primary mx-auto mb-3">
                                    <i class="fas fa-plus"></i>
                                </div>
                                <h5 class="modal-title fw-bold">Track New Symptom</h5>
                                <p class="text-muted small mb-0">Record your symptoms to track your health</p>
                            </div>
                            <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="POST" action="" id="symptomForm">
                            <div class="modal-body pt-0 px-4">
                                <!-- Symptom Name -->
                                <div class="mb-4">
                                    <label for="symptom_name" class="form-label fw-medium text-dark mb-2">
                                        What's bothering you? <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0">
                                            <i class="fas fa-notes-medical text-muted"></i>
                                        </span>
                                        <input type="text" class="form-control border-start-0 ps-2" id="symptom_name" 
                                               name="symptom_name" required placeholder="E.g., Headache, Fever, Nausea">
                                    </div>
                                </div>

                                <!-- Severity -->
                                <div class="mb-4">
                                    <label class="form-label fw-medium text-dark mb-2">
                                        How severe is it? <span class="text-danger">*</span>
                                    </label>
                                    <div class="d-flex flex-column gap-2">
                                        <?php 
                                        $severityDescriptions = [
                                            1 => 'Mild - Barely noticeable',
                                            2 => 'Moderate - Noticeable but not distracting',
                                            3 => 'Uncomfortable - Distracting but can continue daily activities',
                                            4 => 'Severe - Hard to ignore, affects daily activities',
                                            5 => 'Very Severe - Unable to perform daily activities'
                                        ];
                                        for($i = 1; $i <= 5; $i++): 
                                        ?>
                                        <div class="form-check m-0">
                                            <input class="form-check-input d-none" type="radio" 
                                                   name="severity" id="severity<?php echo $i; ?>" 
                                                   value="<?php echo $i; ?>" <?php echo $i == 1 ? 'checked' : ''; ?> required>
                                            <label class="form-check-label w-100 p-3 border rounded-3 cursor-pointer severity-option" 
                                                   for="severity<?php echo $i; ?>">
                                                <div class="d-flex align-items-center">
                                                    <span class="badge rounded-pill me-3 d-flex align-items-center justify-content-center <?php echo getSeverityClass($i); ?>" 
                                                          style="width: 28px; height: 28px; font-size: 0.8rem;">
                                                        <?php echo $i; ?>
                                                    </span>
                                                    <div class="d-flex flex-column">
                                                        <span class="fw-medium"><?php echo getSeverityText($i); ?></span>
                                                        <small class="text-muted"><?php echo $severityDescriptions[$i]; ?></small>
                                                    </div>
                                                    <i class="fas fa-check-circle text-primary ms-auto opacity-0"></i>
                                                </div>
                                            </label>
                                        </div>
                                        <?php endfor; ?>
                                    </div>
                                </div>

                                <!-- Duration -->
                                <div class="mb-4">
                                    <label for="duration" class="form-label fw-medium text-dark mb-2">
                                        How long have you had it?
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0">
                                            <i class="far fa-clock text-muted"></i>
                                        </span>
                                        <input type="text" class="form-control border-start-0 ps-2" id="duration" 
                                               name="duration" placeholder="E.g., 2 hours, 30 minutes, 1 day">
                                    </div>
                                </div>

                                <!-- Notes -->
                                <div class="mb-4">
                                    <label for="notes" class="form-label fw-medium text-dark mb-2">
                                        Additional Notes
                                    </label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3" 
                                              placeholder="Any triggers, patterns, or other details about your symptom"></textarea>
                                    <small class="text-muted">You can add more details about when it started, what makes it better/worse, etc.</small>
                                </div>
                            </div>
                            <div class="modal-footer border-0 pt-0 pb-4 px-4">
                                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-1"></i> Cancel
                                </button>
                                <button type="submit" name="add_symptom" class="btn btn-primary rounded-pill px-4">
                                    <i class="fas fa-plus me-1"></i> Add Symptom
                                </button>
                        </form>
                    </div>
                </div>
            </div>

            <style>
                /* Modal styling */
                .modal-content {
                    border: none;
                    border-radius: 12px;
                    overflow: hidden;
                }
                
                .modal-header {
                    background: linear-gradient(135deg, #f8f9fc 0%, #f1f5ff 100%);
                    padding: 2rem 1.5rem 1.5rem;
                }
                
                .modal-title {
                    font-size: 1.4rem;
                    color: #2c3e50;
                }
                
                .icon-circle {
                    width: 60px;
                    height: 60px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 1.5rem;
                    box-shadow: 0 4px 10px rgba(13, 110, 253, 0.2);
                }
                
                .bg-soft-primary {
                    background-color: rgba(13, 110, 253, 0.1) !important;
                }
                
                /* Form elements */
                .form-label {
                    font-size: 0.875rem;
                    color: #4a5568;
                    font-weight: 500;
                }
                
                .form-control, .form-select {
                    padding: 0.65rem 1rem;
                    border-radius: 8px;
                    border: 1px solid #e2e8f0;
                    transition: all 0.2s ease;
                }
                
                .form-control:focus, .form-select:focus {
                    border-color: #86b7fe;
                    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
                }
                
                .input-group-text {
                    background-color: #f8fafc;
                    border: 1px solid #e2e8f0;
                    color: #94a3b8;
                }
                
                /* Severity options */
                .severity-option {
                    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
                    border: 1px solid #e2e8f0;
                    background-color: #fff;
                    border-radius: 10px;
                    overflow: hidden;
                }
                
                .severity-option:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
                    border-color: #cbd5e1;
                }
                
                .form-check-input:checked + .severity-option {
                    background-color: #f0f7ff;
                    border-color: #3b82f6;
                    box-shadow: 0 0 0 1px #3b82f6;
                }
                
                .form-check-input:checked + .severity-option .badge {
                    transform: scale(1.1);
                }
                
                .severity-option .badge {
                    transition: all 0.2s ease;
                    width: 28px !important;
                    height: 28px !important;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-weight: 600;
                    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                }
                
                /* Buttons */
                .btn {
                    font-weight: 500;
                    padding: 0.5rem 1.25rem;
                    transition: all 0.2s ease;
                }
                
                .btn-primary {
                    background-color: #3b82f6;
                    border-color: #3b82f6;
                }
                
                .btn-primary:hover {
                    background-color: #2563eb;
                    border-color: #2563eb;
                    transform: translateY(-1px);
                    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);
                }
                
                .btn-outline-secondary {
                    color: #64748b;
                    border-color: #e2e8f0;
                }
                
                .btn-outline-secondary:hover {
                    background-color: #f8fafc;
                    border-color: #cbd5e1;
                    color: #475569;
                }
            </style>

            <script>
                // Initialize tooltips
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });

                // Form validation
                (function () {
                    'use strict';
                    var form = document.getElementById('symptomForm');
                    if (form) {
                        form.addEventListener('submit', function (event) {
                            if (!form.checkValidity()) {
                                event.preventDefault();
                                event.stopPropagation();
                            }
                            form.classList.add('was-validated');
                        }, false);
                    }
                })();

                // Update severity selection UI
                document.addEventListener('DOMContentLoaded', function() {
                    const severityOptions = document.querySelectorAll('.severity-option');
                    
                    // Initialize first option as selected
                    if (severityOptions.length > 0) {
                        const firstOption = severityOptions[0];
                        firstOption.style.backgroundColor = '#f0f7ff';
                        firstOption.style.borderColor = '#86b7fe';
                        const firstCheck = firstOption.querySelector('.fa-check');
                        if (firstCheck) firstCheck.classList.remove('opacity-0');
                    }

                    // Handle severity selection
                    severityOptions.forEach(option => {
                        option.addEventListener('click', function() {
                            const input = document.querySelector(`#${this.getAttribute('for')}`);
                            if (input) {
                                input.checked = true;
                                // Update UI for all options
                                severityOptions.forEach(opt => {
                                    opt.style.backgroundColor = '';
                                    opt.style.borderColor = '#dee2e6';
                                    const check = opt.querySelector('.fa-check');
                                    if (check) check.classList.add('opacity-0');
                                });
                                // Highlight selected option
                                this.style.backgroundColor = '#f0f7ff';
                                this.style.borderColor = '#86b7fe';
                                const check = this.querySelector('.fa-check');
                                if (check) check.classList.remove('opacity-0');
                            }
                        });
                    });
                });
            </script>

            <!-- Chart.js Script -->
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Initialize tooltips
                    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                        return new bootstrap.Tooltip(tooltipTriggerEl);
                    });

                    <?php if (!empty($symptom_frequency)): ?>
                    // Initialize symptoms chart
                    var ctx = document.getElementById('symptomsChart').getContext('2d');
                    var symptomNames = <?php echo json_encode(array_column($symptom_frequency, 'symptom_name')); ?>;
                    var symptomCounts = <?php echo json_encode(array_column($symptom_frequency, 'count')); ?>;
                    var avgSeverities = <?php echo json_encode(array_column($symptom_frequency, 'avg_severity')); ?>;

                    // Create gradient for chart
                    var gradient = ctx.createLinearGradient(0, 0, 0, 300);
                    gradient.addColorStop(0, 'rgba(54, 162, 235, 0.7)');
                    gradient.addColorStop(1, 'rgba(54, 162, 235, 0.1)');

                    var chartData = {
                        labels: symptomNames,
                        datasets: [{
                            label: 'Frequency',
                            data: symptomCounts,
                            backgroundColor: gradient,
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1,
                            borderRadius: 4,
                            borderSkipped: false,
                            barPercentage: 0.8
                        }]
                    };
                    
                    new Chart(ctx, {
                        type: 'bar',
                        data: chartData,
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                    titleFont: { size: 14, weight: 'bold' },
                                    bodyFont: { size: 13 },
                                    padding: 12,
                                    displayColors: false,
                                    callbacks: {
                                        label: function(context) {
                                            var label = context.label || '';
                                            var value = context.raw || 0;
                                            var avg = avgSeverities[context.dataIndex];
                                            var severityText = ['Mild', 'Moderate', 'Moderately Severe', 'Severe', 'Very Severe'][Math.round(avg) - 1] || 'Unknown';
                                            return [
                                                `${label}: ${value} time${value !== 1 ? 's' : ''}`,
                                                `Avg. Severity: ${avg} (${severityText})`
                                            ];
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1,
                                        precision: 0
                                    },
                                    grid: {
                                        display: true,
                                        drawBorder: false
                                    }
                                },
                                x: {
                                    grid: {
                                        display: false,
                                        drawBorder: false
                                    }
                                }
                            }
                        }
                    });
                    <?php endif; ?>

                    // Handle form submission
                    const form = document.getElementById('symptomForm');
                    if (form) {
                        form.addEventListener('submit', function(e) {
                            const symptomName = document.getElementById('symptom_name').value.trim();
                            const severity = document.getElementById('severity').value;
                            
                            if (!symptomName) {
                                e.preventDefault();
                                alert('Please enter a symptom name');
                                return false;
                            }
                            
                            if (!severity) {
                                e.preventDefault();
                                alert('Please select a severity level');
                                return false;
                            }
                            
                            return true;
                        });
                    }
                });
            </script>

            </div> <!-- End .content -->
        </main> <!-- End .main-content -->
    </div> <!-- End .dashboard-container -->

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Delete symptom function
            window.deleteSymptom = function(id) {
                if (confirm('Are you sure you want to delete this symptom?')) {
                    const form = document.createElement('form');
                    form.method = 'post';
                    form.action = 'symptoms.php';
                    
                    const inputAction = document.createElement('input');
                    inputAction.type = 'hidden';
                    inputAction.name = 'action';
                    inputAction.value = 'delete';
                    
                    const inputId = document.createElement('input');
                    inputId.type = 'hidden';
                    inputId.name = 'id';
                    inputId.value = id;
                    
                    form.appendChild(inputAction);
                    form.appendChild(inputId);
                    document.body.appendChild(form);
                    form.submit();
                }
            };
        });
    </script>
</body>
</html>
<?php
// End output buffering and flush
if (ob_get_level() > 0) {
    ob_end_flush();
}
?>
