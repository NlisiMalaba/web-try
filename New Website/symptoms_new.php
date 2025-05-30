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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar bg-dark text-white" id="sidebar">
        <div class="sidebar-header p-3">
            <a href="dashboard.php" class="text-white text-decoration-none fs-4 fw-bold">HealthAssist Pro</a>
        </div>
        
        <nav class="nav flex-column p-3">
            <a href="dashboard.php" class="nav-link text-white mb-2">
                <i class="fas fa-home me-2"></i>
                <span>Dashboard</span>
            </a>
            <a href="symptoms.php" class="nav-link active bg-primary text-white mb-2">
                <i class="fas fa-notes-medical me-2"></i>
                <span>Symptom Tracker</span>
            </a>
            <a href="health_metrics.php" class="nav-link text-white mb-2">
                <i class="fas fa-heartbeat me-2"></i>
                <span>Health Metrics</span>
            </a>
            <a href="medications.php" class="nav-link text-white mb-2">
                <i class="fas fa-pills me-2"></i>
                <span>Medications</span>
            </a>
            <a href="appointments.php" class="nav-link text-white mb-2">
                <i class="far fa-calendar-check me-2"></i>
                <span>Appointments</span>
            </a>
            <a href="profile.php" class="nav-link text-white mb-2">
                <i class="far fa-user me-2"></i>
                <span>Profile</span>
            </a>
        </nav>
        
        <div class="sidebar-footer p-3 mt-auto">
            <a href="logout.php" class="btn btn-outline-light w-100">
                <i class="fas fa-sign-out-alt me-2"></i> Logout
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-grow-1 d-flex flex-column" style="min-height: 100vh;">
        <header class="bg-white shadow-sm py-3">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="search-bar w-50">
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-end-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" class="form-control border-start-0" placeholder="Search...">
                        </div>
                    </div>
                    <div class="user-info d-flex align-items-center">
                        <div class="me-3 text-end d-none d-md-block">
                            <div class="fw-medium"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                            <div class="small text-muted"><?php echo htmlspecialchars($user['email']); ?></div>
                        </div>
                        <div class="user-avatar">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" 
                                 style="width: 40px; height: 40px; font-size: 18px;">
                                <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-grow-1 p-4">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0">Symptom Tracker</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSymptomModal">
                        <i class="fas fa-plus me-2"></i>Add New Symptom
                    </button>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-8">
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                                <h5 class="mb-0">Recent Symptoms</h5>
                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addSymptomModal">
                                    <i class="fas fa-plus me-1"></i> Add New Symptom
                                </button>
                            </div>
                            <div class="card-body p-0">
                                <?php if (empty($symptoms)): ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-clipboard-list fa-4x text-muted mb-3"></i>
                                        <h5 class="text-muted">No symptoms logged yet</h5>
                                        <p class="text-muted">Click the "Add New Symptom" button to get started</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Date & Time</th>
                                                    <th>Symptom</th>
                                                    <th class="text-center">Severity</th>
                                                    <th>Duration</th>
                                                    <th>Notes</th>
                                                    <th class="text-end">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($symptoms as $symptom): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="fw-medium"><?php echo date('M j, Y', strtotime($symptom['created_at'])); ?></div>
                                                            <div class="small text-muted"><?php echo date('g:i A', strtotime($symptom['created_at'])); ?></div>
                                                        </td>
                                                        <td class="fw-medium"><?php echo htmlspecialchars($symptom['symptom_name']); ?></td>
                                                        <td class="text-center">
                                                            <span class="badge rounded-pill py-1 px-2 <?php echo getSeverityClass($symptom['severity']); ?>"
                                                                  data-bs-toggle="tooltip" data-bs-placement="top" 
                                                                  title="<?php echo getSeverityText($symptom['severity']); ?>">
                                                                <?php echo $symptom['severity']; ?>
                                                            </span>
                                                        </td>
                                                        <td class="text-nowrap">
                                                            <?php if ($symptom['duration']): ?>
                                                                <i class="far fa-clock text-muted me-1"></i>
                                                                <?php echo htmlspecialchars($symptom['duration']); ?>
                                                            <?php else: ?>
                                                                <span class="text-muted">N/A</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="text-truncate" style="max-width: 200px;" 
                                                            data-bs-toggle="tooltip" data-bs-placement="top" 
                                                            title="<?php echo htmlspecialchars($symptom['notes']); ?>">
                                                            <?php echo $symptom['notes'] ? nl2br(htmlspecialchars($symptom['notes'])) : '<span class="text-muted">N/A</span>'; ?>
                                                        </td>
                                                        <td class="text-end">
                                                            <form method="POST" class="d-inline" 
                                                                  onsubmit="return confirm('Are you sure you want to delete this symptom?');">
                                                                <input type="hidden" name="symptom_id" value="<?php echo $symptom['id']; ?>">
                                                                <button type="submit" name="delete_symptom" class="btn btn-sm btn-outline-danger" data-bs-toggle="tooltip" title="Delete">
                                                                    <i class="far fa-trash-alt"></i>
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <?php if (!empty($symptom_frequency)): ?>
                            <div class="card shadow-sm mb-4">
                                <div class="card-header bg-white py-3">
                                    <h5 class="mb-0">Symptom Frequency</h5>
                                    <p class="text-muted small mb-0">Last 30 days</p>
                                </div>
                                <div class="card-body">
                                    <div style="height: 300px;">
                                        <canvas id="symptomsChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="card shadow-sm">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0">Severity Guide</h5>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush">
                                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <div class="d-flex align-items-center">
                                            <span class="badge rounded-pill bg-success me-2" style="width: 20px; height: 20px;"></span>
                                            <span>1 - Mild</span>
                                        </div>
                                        <small class="text-muted">Noticeable but not disruptive</small>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <div class="d-flex align-items-center">
                                            <span class="badge rounded-pill bg-info me-2" style="width: 20px; height: 20px;"></span>
                                            <span>2 - Moderate</span>
                                        </div>
                                        <small class="text-muted">Mildly disruptive</small>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <div class="d-flex align-items-center">
                                            <span class="badge rounded-pill bg-warning me-2" style="width: 20px; height: 20px;"></span>
                                            <span>3 - Moderately Severe</span>
                                        </div>
                                        <small class="text-muted">Disruptive but manageable</small>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <div class="d-flex align-items-center">
                                            <span class="badge rounded-pill bg-orange me-2" style="width: 20px; height: 20px;"></span>
                                            <span>4 - Severe</span>
                                        </div>
                                        <small class="text-muted">Significantly impacts daily activities</small>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <div class="d-flex align-items-center">
                                            <span class="badge rounded-pill bg-danger me-2" style="width: 20px; height: 20px;"></span>
                                            <span>5 - Very Severe</span>
                                        </div>
                                        <small class="text-muted">Unable to perform daily activities</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Symptom Modal -->
            <div class="modal fade" id="addSymptomModal" tabindex="-1" aria-labelledby="addSymptomModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-light">
                            <h5 class="modal-title" id="addSymptomModalLabel">Add New Symptom</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="POST" action="" id="symptomForm">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="symptom_name" class="form-label">Symptom Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="symptom_name" name="symptom_name" required 
                                           placeholder="E.g., Headache, Fever, Nausea">
                                </div>
                                <div class="mb-3">
                                    <label for="severity" class="form-label">Severity <span class="text-danger">*</span></label>
                                    <select class="form-select" id="severity" name="severity" required>
                                        <option value="">Select severity level</option>
                                        <option value="1">1 - Mild (Noticeable but not disruptive)</option>
                                        <option value="2">2 - Moderate (Mildly disruptive to daily activities)</option>
                                        <option value="3">3 - Moderately Severe (Disruptive but can continue daily activities)</option>
                                        <option value="4">4 - Severe (Significantly impacts daily activities)</option>
                                        <option value="5">5 - Very Severe (Unable to perform daily activities)</option>
                                    </select>
                                    <div class="form-text">How severe is this symptom on a scale of 1 to 5?</div>
                                </div>
                                <div class="mb-3">
                                    <label for="duration" class="form-label">Duration</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="far fa-clock"></i>
                                        </span>
                                        <input type="text" class="form-control" id="duration" name="duration" 
                                               placeholder="E.g., 2 hours, 30 minutes, 1 day">
                                    </div>
                                    <div class="form-text">How long have you been experiencing this symptom?</div>
                                </div>
                                <div class="mb-0">
                                    <label for="notes" class="form-label">Additional Notes</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3" 
                                              placeholder="Any additional details about your symptom, such as triggers, patterns, or related symptoms"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer bg-light">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-1"></i> Cancel
                                </button>
                                <button type="submit" name="add_symptom" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Save Symptom
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

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

            <!-- Bootstrap Bundle with Popper -->
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
        </main>
    </div>
</body>
</html>
<?php
// End output buffering and flush
if (ob_get_level() > 0) {
    ob_end_flush();
}
?>
