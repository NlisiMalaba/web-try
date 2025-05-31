<?php if (isset($_SESSION['token'])): ?>
<meta name="auth-token" content="<?php echo htmlspecialchars($_SESSION['token']); ?>">
<?php endif; ?><?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Set the authentication token in localStorage
if (isset($_SESSION['token'])) {
    echo '<script>localStorage.setItem("token", "' . $_SESSION['token'] . '");</script>';
}

require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Get user data
$user_id = $_SESSION['user_id'];
$query = "SELECT first_name, last_name, email FROM users WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(":id", $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Metrics - HealthAssist Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="assets/css/wearable-simulation.css">
    <link rel="stylesheet" href="assets/css/anomaly-alerts.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
    // Set auth token from PHP session to localStorage
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (isset($_SESSION['token'])): ?>
            localStorage.setItem('token', '<?php echo $_SESSION['token']; ?>');
        <?php endif; ?>
        
        // Initialize toastr
        toastr.options = {
            "closeButton": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "timeOut": 3000
        };
    });
    </script>
    <style>
        :root {
            --white: #ffffff;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --border-color: #e2e8f0;
            --text-primary: #1a202c;
            --text-secondary: #4a5568;
            --primary-color: #4f46e5;
            --primary-light: #eef2ff;
            --danger: #ef4444;
            --transition: all 0.2s ease-in-out;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8fafc;
            color: var(--text-primary);
        }
        
        .metrics-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .add-metric-card, .metric-summary-card {
            background: var(--white);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--shadow);
        }
        
        .add-metric-form .form-group {
            margin-bottom: 1rem;
        }
        
        .add-metric-form label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-primary);
        }
        
        .add-metric-form input[type="text"],
        .add-metric-form input[type="number"],
        .add-metric-form select,
        .add-metric-form textarea {
            width: 100%;
            padding: 0.6rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 0.95rem;
            transition: var(--transition);
        }
        
        .add-metric-form input:focus,
        .add-metric-form select:focus,
        .add-metric-form textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .metric-summary {
            margin-top: 1.5rem;
        }
        
        .metric-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .metric-item:last-child {
            border-bottom: none;
        }
        
        .metric-value {
            font-weight: 600;
        }
        
        .metrics-history {
            margin-top: 2rem;
        }
        
        .chart-container {
            height: 300px;
            margin-top: 1.5rem;
            position: relative;
        }
        
        @media (max-width: 1024px) {
            .metrics-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Include the sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="main-content">
            <header class="top-bar">
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search metrics...">
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
                <!-- Anomaly Alerts Section -->
                <div id="anomalyAlerts" class="anomaly-alerts" style="display: none;">
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Anomaly Detected!</strong>
                        <span id="anomalyMessage">Unusual health metrics detected. Please review your recent data.</span>
                        <button type="button" class="close" onclick="document.getElementById('anomalyAlerts').style.display='none';">&times;</button>
                    </div>
                </div>
                
                <div class="page-header">
                    <h1>Health Metrics</h1>
                    <p>Track and monitor your health metrics over time</p>
                </div>
                
                <!-- Wearable Device Simulation Section -->
                <div class="wearable-simulation-section">
                    <h2>Wearable Device Simulation</h2>
                    <div class="simulation-status" id="simulationStatusContainer">
                        <div class="status-indicator" id="simulationStatus">
                            <i class="fas fa-circle"></i>
                            <span>Stopped</span>
                        </div>
                        <div class="button-group">
                            <button id="startSimulation" class="btn primary" title="Start generating simulated health data">
                                <i class="fas fa-play"></i> Start Simulation
                            </button>
                            <button id="stopSimulation" class="btn danger" disabled title="Stop generating simulated health data">
                                <i class="fas fa-stop"></i> Stop Simulation
                            </button>
                        </div>
                    </div>
                    
                    <div class="simulation-metrics">
                        <div class="metric-card">
                            <h3>Latest Simulated Metrics</h3>
                            <div class="metric-item">
                                <span>Heart Rate</span>
                                <span class="metric-value" id="simulatedHeartRate">-- bpm</span>
                            </div>
                            <div class="metric-item">
                                <span>Blood Pressure</span>
                                <span class="metric-value" id="simulatedBloodPressure">--/-- mmHg</span>
                            </div>
                            <div class="metric-item">
                                <span>Oxygen Level</span>
                                <span class="metric-value" id="simulatedOxygenLevel">-- %</span>
                            </div>
                            <div class="metric-item">
                                <span>Temperature</span>
                                <span class="metric-value" id="simulatedTemperature">-- °C</span>
                            </div>
                            <div class="metric-item">
                                <span>Steps</span>
                                <span class="metric-value" id="simulatedSteps">-- steps</span>
                            </div>
                            <div class="metric-item">
                                <span>Calories Burned</span>
                                <span class="metric-value" id="simulatedCalories">-- calories</span>
                            </div>
                            <div class="metric-item">
                                <span>Sleep Duration</span>
                                <span class="metric-value" id="simulatedSleep">-- hours</span>
                            </div>
                            <div class="metric-item">
                                <span>Stress Level</span>
                                <span class="metric-value" id="simulatedStress">--/5</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="metrics-container">
                    <!-- Add Metric Card -->
                    <div class="add-metric-card">
                        <h2>Add New Metric</h2>
                        <form id="addMetricForm" class="add-metric-form">
                            <div class="form-group">
                                <label for="metricType">Metric Type</label>
                                <select id="metricType" name="metric_type" required>
                                    <option value="">Select a metric type</option>
                                    <option value="blood_pressure">Blood Pressure</option>
                                    <option value="glucose">Blood Glucose</option>
                                    <option value="weight">Weight</option>
                                    <option value="heart_rate">Heart Rate</option>
                                    <option value="oxygen_level">Oxygen Level</option>
                                    <option value="temperature">Temperature</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            
                            <div id="bloodPressureFields" class="metric-fields" style="display: none;">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="systolic">Systolic (mmHg)</label>
                                        <input type="number" id="systolic" name="systolic" min="50" max="250" step="1">
                                    </div>
                                    <div class="form-group">
                                        <label for="diastolic">Diastolic (mmHg)</label>
                                        <input type="number" id="diastolic" name="diastolic" min="30" max="150" step="1">
                                    </div>
                                </div>
                            </div>
                            
                            <div id="singleValueFields" class="metric-fields" style="display: none;">
                                <div class="form-group">
                                    <label for="value">Value</label>
                                    <input type="number" id="value" name="value" step="0.1">
                                </div>
                                <div class="form-group">
                                    <label for="unit">Unit</label>
                                    <input type="text" id="unit" name="unit" placeholder="e.g., bpm, °C, mg/dL">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="recordedAt">Date & Time</label>
                                <input type="datetime-local" id="recordedAt" name="recorded_at" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="notes">Notes (Optional)</label>
                                <textarea id="notes" name="notes" rows="2"></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add Metric
                            </button>
                        </form>
                    </div>
                    
                    <!-- Metric Summary Card -->
                    <div class="metric-summary-card">
                        <h2>Recent Metrics</h2>
                        <div class="metric-summary">
                            <div class="metric-item">
                                <span>Blood Pressure</span>
                                <span class="metric-value" id="recentBp">--/--</span>
                            </div>
                            <div class="metric-item">
                                <span>Heart Rate</span>
                                <span class="metric-value" id="recentHr">-- bpm</span>
                            </div>
                            <div class="metric-item">
                                <span>Weight</span>
                                <span class="metric-value" id="recentWeight">-- kg</span>
                            </div>
                            <div class="metric-item">
                                <span>Glucose</span>
                                <span class="metric-value" id="recentGlucose">-- mg/dL</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Wearable Simulation -->
                <div class="wearable-simulation-section">
                    <div class="simulation-status">
                        <h2>Wearable Device Simulation</h2>
                        <div class="status-indicator">
                            <i class="fas fa-circle"></i>
                            <span id="simulationStatus">Status: <span>Stopped</span></span>
                        </div>
                    </div>

                    <div class="button-group">
                        <button id="startSimulation" class="btn primary">
                            <i class="fas fa-play"></i> Start Simulation
                        </button>
                        <button id="stopSimulation" class="btn danger" disabled>
                            <i class="fas fa-stop"></i> Stop Simulation
                        </button>
                    </div>

                    <div class="simulation-metrics">
                        <h3>Current Metrics</h3>
                        <div class="metric-card">
                            <div class="metric-item">
                                <span>Heart Rate</span>
                                <span id="heartRate" class="metric-value">-- bpm</span>
                            </div>
                            <div class="metric-item">
                                <span>Blood Pressure</span>
                                <span id="bloodPressure" class="metric-value">--/-- mmHg</span>
                            </div>
                            <div class="metric-item">
                                <span>Oxygen Level</span>
                                <span id="oxygenLevel" class="metric-value">-- %</span>
                            </div>
                            <div class="metric-item">
                                <span>Temperature</span>
                                <span id="temperature" class="metric-value">-- °C</span>
                            </div>
                            <div class="metric-item">
                                <span>Steps</span>
                                <span id="steps" class="metric-value">-- steps</span>
                            </div>
                            <div class="metric-item">
                                <span>Calories</span>
                                <span id="calories" class="metric-value">-- calories</span>
                            </div>
                            <div class="metric-item">
                                <span>Sleep</span>
                                <span id="sleep" class="metric-value">-- hours</span>
                            </div>
                            <div class="metric-item">
                                <span>Stress Level</span>
                                <span id="stress" class="metric-value">--/5</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="simulation-charts" style="margin-top: 2rem;">
                        <h3>Real-time Monitoring</h3>
                        <div class="chart-container" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                            <div>
                                <h4>Heart Rate</h4>
                                <canvas id="heartRateChart" height="150"></canvas>
                            </div>
                            <div>
                                <h4>Blood Pressure</h4>
                                <canvas id="bloodPressureChart" height="150"></canvas>
                            </div>
                            <div>
                                <h4>Oxygen Level</h4>
                                <canvas id="oxygenLevelChart" height="150"></canvas>
                            </div>
                            <div>
                                <h4>Temperature</h4>
                                <canvas id="temperatureChart" height="150"></canvas>
                            </div>
                            <div>
                                <h4>Steps</h4>
                                <canvas id="stepsChart" height="150"></canvas>
                            </div>
                            <div>
                                <h4>Calories</h4>
                                <canvas id="caloriesChart" height="150"></canvas>
                            </div>
                            <div>
                                <h4>Sleep</h4>
                                <canvas id="sleepChart" height="150"></canvas>
                            </div>
                            <div>
                                <h4>Stress Level</h4>
                                <canvas id="stressChart" height="150"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Metrics History -->
                <div class="metrics-history">
                    <div class="section-header">
                        <h2>Metrics History</h2>
                        <div class="date-range">
                            <select id="timeRange" class="time-filter">
                                <option value="7">Last 7 days</option>
                                <option value="30" selected>Last 30 days</option>
                                <option value="90">Last 90 days</option>
                                <option value="365">Last year</option>
                                <option value="custom">Custom Range</option>
                            </select>
                            <div id="customDateRange" style="display: none; margin-top: 0.5rem;">
                                <input type="date" id="startDate" class="date-input">
                                <span style="margin: 0 0.5rem;">to</span>
                                <input type="date" id="endDate" class="date-input">
                                <button id="applyDateRange" class="btn btn-sm" style="margin-left: 0.5rem;">Apply</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="chart-container">
                        <canvas id="metricsChart"></canvas>
                    </div>
                    
                    <div class="metrics-table" style="margin-top: 2rem;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Metric</th>
                                    <th>Value</th>
                                    <th>Notes</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="metricsTableBody">
                                <!-- Metrics will be loaded here via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Flatpickr for date/time picker -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <!-- Toastr JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <!-- Auth Token for JavaScript -->
    <?php if (isset($_SESSION['token'])): ?>
    <meta name="auth-token" content="<?php echo htmlspecialchars($_SESSION['token']); ?>">
    <?php endif; ?>
    
    <!-- Health Metrics Charts -->
    <script src="assets/js/health-metrics-charts.js"></script>
    
    <script>
        // Initialize Flatpickr for datetime inputs
        flatpickr("input[type=datetime-local]", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
        });

        // Handle form submission
        document.getElementById('addMetricForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const metricType = formData.get('metric_type');
            const data = {
                metric_type: metricType,
                recorded_at: formData.get('recorded_at').replace('T', ' ') + ':00'
            };
            
            if (metricType === 'blood_pressure') {
                data.value1 = formData.get('systolic');
                data.value2 = formData.get('diastolic');
                data.unit = 'mmHg';
            } else {
                data.value1 = formData.get('value');
                data.unit = formData.get('unit');
            }
            
            const notes = formData.get('notes');
            if (notes) data.notes = notes;
            
            // Send data to API
            fetch('api/health_metrics.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                }
                
                // Show success message
                toastr.success('Metric added successfully!');
                
                // Reset form
                this.reset();
                document.getElementById('bloodPressureFields').style.display = 'none';
                document.getElementById('singleValueFields').style.display = 'none';
                
                // Refresh metrics
                loadMetrics();
                loadRecentMetrics();
            })
            .catch(error => {
                console.error('Error:', error);
                toastr.error('Failed to add metric: ' + error.message);
            });
        });
        
        // Initialize metrics chart
        let metricsChart;
        
        function initMetricsChart(labels, datasets) {
            const ctx = document.getElementById('metricsChart').getContext('2d');
            
            if (metricsChart) {
                metricsChart.destroy();
            }
            
            metricsChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        },
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false
                        }
                    }
                }
            });
        }
        
        // Load metrics data
        function loadMetrics() {
            const timeRange = document.getElementById('timeRange').value;
            let url = 'api/health_metrics.php';
            
            if (timeRange === 'custom') {
                const startDate = document.getElementById('startDate').value;
                const endDate = document.getElementById('endDate').value;
                
                if (!startDate || !endDate) {
                    toastr.warning('Please select a date range');
                    return;
                }
                
                url += `?start_date=${startDate}&end_date=${endDate}`;
            } else if (timeRange !== 'all') {
                url += `?start_date=${getDateDaysAgo(timeRange)}`;
            }
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    
                    updateMetricsTable(data.data);
                    updateMetricsChart(data.data);
                })
                .catch(error => {
                    console.error('Error loading metrics:', error);
                    toastr.error('Failed to load metrics: ' + error.message);
                });
        }
        
        // Load recent metrics for the summary
        function loadRecentMetrics() {
            fetch('api/health_metrics.php?limit=10')
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    
                    updateRecentMetrics(data.data);
                })
                tr.innerHTML = '<td colspan="5" class="text-center">No metrics found for the selected period</td>';
                tbody.appendChild(tr);
                return;
            }
            
            metrics.forEach(metric => {
                const tr = document.createElement('tr');
                const date = new Date(metric.recorded_at);
                let value;
                
                if (metric.metric_type === 'blood_pressure') {
                    value = `${metric.value1}/${metric.value2} ${metric.unit || ''}`;
                } else {
                    value = `${metric.value1} ${metric.unit || ''}`;
                }
                
                tr.innerHTML = `
                    <td>${formatDateTime(date)}</td>
                    <td>${formatMetricType(metric.metric_type)}</td>
                    <td>${value}</td>
                    <td>${metric.notes || '--'}</td>
                    <td class="actions">
                        <button class="btn-icon" onclick="editMetric(${metric.id})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-icon btn-danger" onclick="deleteMetric(${metric.id})" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;
                
                tbody.appendChild(tr);
            });
        }
        
        // Update metrics chart
        function updateMetricsChart(metrics) {
            if (metrics.length === 0) {
                document.getElementById('metricsChart').style.display = 'none';
                return;
            }
            
            document.getElementById('metricsChart').style.display = 'block';
            
            // Group metrics by type
            const metricsByType = {};
            
            metrics.forEach(metric => {
                if (!metricsByType[metric.metric_type]) {
                    metricsByType[metric.metric_type] = [];
                }
                metricsByType[metric.metric_type].push(metric);
            });
            
            // Prepare chart data
            const labels = [...new Set(metrics.map(m => formatDate(new Date(m.recorded_at))))];
            const datasets = [];
            
            // Colors for different metric types
            const colors = {
                blood_pressure: { bg: 'rgba(239, 68, 68, 0.1)', border: '#ef4444' },
                glucose: { bg: 'rgba(59, 130, 246, 0.1)', border: '#3b82f6' },
                weight: { bg: 'rgba(16, 185, 129, 0.1)', border: '#10b981' },
                heart_rate: { bg: 'rgba(245, 158, 11, 0.1)', border: '#f59e0b' },
                oxygen_level: { bg: 'rgba(139, 92, 246, 0.1)', border: '#8b5cf6' },
                temperature: { bg: 'rgba(20, 184, 166, 0.1)', border: '#14b8a6' },
                other: { bg: 'rgba(107, 114, 128, 0.1)', border: '#6b7280' }
            };
            
            // Create datasets for each metric type
            Object.entries(metricsByType).forEach(([type, metrics]) => {
                const color = colors[type] || colors.other;
                
                if (type === 'blood_pressure') {
                    // For blood pressure, we'll create two datasets (systolic and diastolic)
                    datasets.push({
                        label: 'Systolic',
                        data: labels.map(date => {
                            const metric = metrics.find(m => formatDate(new Date(m.recorded_at)) === date);
                            return metric ? metric.value1 : null;
                        }),
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        tension: 0.3,
                        fill: true
                    });
                    
                    datasets.push({
                        label: 'Diastolic',
                        data: labels.map(date => {
                            const metric = metrics.find(m => formatDate(new Date(m.recorded_at)) === date);
                            return metric ? metric.value2 : null;
                        }),
                        borderColor: '#f97316',
                        backgroundColor: 'rgba(249, 115, 22, 0.1)',
                        tension: 0.3,
                        fill: true
                    });
                } else {
                    // For other metrics, just use value1
                    datasets.push({
                        label: formatMetricType(type),
                        data: labels.map(date => {
                            const metric = metrics.find(m => formatDate(new Date(m.recorded_at)) === date);
                            return metric ? metric.value1 : null;
                        }),
                        borderColor: color.border,
                        backgroundColor: color.bg,
                        tension: 0.3,
                        fill: true
                    });
                }
            });
            
            initMetricsChart(labels, datasets);
        }
        
        // Update recent metrics in the summary
        function updateRecentMetrics(metrics) {
            const recentMetrics = {
                blood_pressure: metrics.find(m => m.metric_type === 'blood_pressure'),
                heart_rate: metrics.find(m => m.metric_type === 'heart_rate'),
                weight: metrics.find(m => m.metric_type === 'weight'),
                glucose: metrics.find(m => m.metric_type === 'glucose')
            };
            
            // Update the UI
            if (recentMetrics.blood_pressure) {
                document.getElementById('recentBp').textContent = 
                    `${recentMetrics.blood_pressure.value1}/${recentMetrics.blood_pressure.value2} mmHg`;
            }
            
            if (recentMetrics.heart_rate) {
                document.getElementById('recentHr').textContent = 
                    `${recentMetrics.heart_rate.value1} ${recentMetrics.heart_rate.unit || 'bpm'}`;
            }
            
            if (recentMetrics.weight) {
                document.getElementById('recentWeight').textContent = 
                    `${recentMetrics.weight.value1} ${recentMetrics.weight.unit || 'kg'}`;
            }
            
            if (recentMetrics.glucose) {
                document.getElementById('recentGlucose').textContent = 
                    `${recentMetrics.glucose.value1} ${recentMetrics.glucose.unit || 'mg/dL'}`;
            }
        }
        
        // Helper functions
        function formatDate(date) {
            return date.toISOString().split('T')[0];
        }
        
        function formatDateTime(date) {
            return date.toLocaleString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        function formatMetricType(type) {
            const types = {
                blood_pressure: 'Blood Pressure',
                glucose: 'Blood Glucose',
                weight: 'Weight',
                heart_rate: 'Heart Rate',
                oxygen_level: 'Oxygen Level',
                temperature: 'Temperature',
                other: 'Other'
            };
            
            return types[type] || type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        }
        
        function getDateDaysAgo(days) {
            const date = new Date();
            date.setDate(date.getDate() - days);
            return date.toISOString().split('T')[0];
        }
        
        // Time range change handler
        document.getElementById('timeRange').addEventListener('change', function() {
            const customDateRange = document.getElementById('customDateRange');
            
            if (this.value === 'custom') {
                customDateRange.style.display = 'block';
            } else {
                customDateRange.style.display = 'none';
                loadMetrics();
            }
        });
        
        // Apply custom date range
        document.getElementById('applyDateRange').addEventListener('click', function() {
            loadMetrics();
        });
        
        // Initialize date inputs for custom range
        const today = new Date();
        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(today.getDate() - 30);
        
        document.getElementById('startDate').valueAsDate = thirtyDaysAgo;
        document.getElementById('endDate').valueAsDate = today;
        
        // Load initial data
        document.addEventListener('DOMContentLoaded', function() {
            loadMetrics();
            loadRecentMetrics();
            
            // Set current date and time for the form
            const now = new Date();
            const timezoneOffset = now.getTimezoneOffset() * 60000; // Convert minutes to milliseconds
            const localISOTime = (new Date(now - timezoneOffset)).toISOString().slice(0, 16);
            document.getElementById('recordedAt').value = localISOTime;
        });
        
        // Functions for edit and delete actions
        function editMetric(id) {
            // Implementation for editing a metric
            toastr.info('Edit functionality will be implemented soon');
        }
        
        function deleteMetric(id) {
            if (confirm('Are you sure you want to delete this metric?')) {
                fetch(`api/health_metrics.php?id=${id}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    
                    toastr.success('Metric deleted successfully');
                    loadMetrics();
                    loadRecentMetrics();
                })
                .catch(error => {
                    console.error('Error deleting metric:', error);
                    toastr.error('Failed to delete metric: ' + error.message);
                });
            }
        }
    </script>
    <script>
        // Initialize metrics chart
        let metricsChart;
        
        // Initialize the page
        document.addEventListener('DOMContentLoaded', function() {
            // Set current date and time
            const now = new Date();
            const timezoneOffset = now.getTimezoneOffset() * 60000;
            const localISOTime = (new Date(now - timezoneOffset)).toISOString().slice(0, 16);
            document.getElementById('recordedAt').value = localISOTime;
            
            // Load initial metrics
            loadMetrics();
        });
        
        // Handle metric type change
        document.getElementById('metricType').addEventListener('change', function() {
            const type = this.value;
            const bloodPressureFields = document.getElementById('bloodPressureFields');
            const singleValueFields = document.getElementById('singleValueFields');
            const unitField = document.getElementById('unit');
            
            // Hide all fields first
            bloodPressureFields.style.display = 'none';
            singleValueFields.style.display = 'none';
            
            // Show relevant fields based on selection
            if (type === 'blood_pressure') {
                bloodPressureFields.style.display = 'block';
                document.getElementById('systolic').required = true;
                document.getElementById('diastolic').required = true;
            } else if (type) {
                singleValueFields.style.display = 'block';
                document.getElementById('value').required = true;
                
                // Set default units based on metric type
                switch(type) {
                    case 'glucose':
                        unitField.value = 'mg/dL';
                        break;
                    case 'weight':
                        unitField.value = 'kg';
                        break;
                    case 'heart_rate':
                        unitField.value = 'bpm';
                        break;
                    case 'oxygen_level':
                        unitField.value = '%';
                        break;
                    case 'temperature':
                        unitField.value = '°C';
                        break;
                    default:
                        unitField.value = '';
                }
            }
        });
        
        // Load metrics from the API
        function loadMetrics() {
            fetch('api/health_metrics.php')
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    updateRecentMetrics(data.data);
                    // Use the chart functions from the global HealthMetricsCharts object
                    if (window.HealthMetricsCharts && typeof window.HealthMetricsCharts.updateMetricsChart === 'function') {
                        window.HealthMetricsCharts.updateMetricsChart(data.data);
                    } else {
                        console.warn('HealthMetricsCharts.updateMetricsChart is not available');
                    }
                })
                .catch(error => {
                    console.error('Error loading metrics:', error);
                    toastr.error('Failed to load metrics: ' + error.message);
                });
        }
        
        // Update recent metrics in the summary
        function updateRecentMetrics(metrics) {
            const recentMetrics = {
                blood_pressure: metrics.find(m => m.metric_type === 'blood_pressure'),
                heart_rate: metrics.find(m => m.metric_type === 'heart_rate'),
                weight: metrics.find(m => m.metric_type === 'weight'),
                glucose: metrics.find(m => m.metric_type === 'glucose')
            };
            
            // Update the UI
            if (recentMetrics.blood_pressure) {
                document.getElementById('recentBp').textContent = 
                    `${recentMetrics.blood_pressure.value1}/${recentMetrics.blood_pressure.value2} mmHg`;
            }
            
            if (recentMetrics.heart_rate) {
                document.getElementById('recentHr').textContent = 
                    `${recentMetrics.heart_rate.value1} ${recentMetrics.heart_rate.unit || 'bpm'}`;
            }
            
            if (recentMetrics.weight) {
                document.getElementById('recentWeight').textContent = 
                    `${recentMetrics.weight.value1} ${recentMetrics.weight.unit || 'kg'}`;
            }
            
            if (recentMetrics.glucose) {
                document.getElementById('recentGlucose').textContent = 
                    `${recentMetrics.glucose.value1} ${recentMetrics.glucose.unit || 'mg/dL'}`;
            }
        }
        
        // Check for anomalies in recent metrics
        async function checkForAnomalies() {
            try {
                const response = await fetch('api/health_metrics.php?recent=5');
                const data = await response.json();
                
                if (data.success && data.data) {
                    const anomalies = [];
                    
                    // Check each metric for anomalies
                    data.data.forEach(metric => {
                        if (metric.is_anomaly) {
                            const metricName = metric.metric_type.replace(/_/g, ' ');
                            anomalies.push(metricName);
                            
                            // Log the anomaly for debugging
                            console.log(`Anomaly detected in ${metricName}:`, metric);
                        }
                    });
                    
                    // If we found anomalies, show the alert
                    if (anomalies.length > 0) {
                        const alertElement = document.getElementById('anomalyAlerts');
                        const messageElement = document.getElementById('anomalyMessage');
                        
                        // Update the message with the list of anomalies
                        let message = 'Potential health anomalies detected in: ';
                        message += anomalies.join(', ');
                        messageElement.textContent = message;
                        
                        // Show the alert
                        alertElement.style.display = 'block';
                        
                        // Add critical class if multiple anomalies found
                        if (anomalies.length > 1) {
                            alertElement.querySelector('.alert').classList.add('alert-critical');
                        }
                        
                        // Log to console for debugging
                        console.log('Anomalies detected:', anomalies);
                    }
                }
            } catch (error) {
                console.error('Error checking for anomalies:', error);
            }
        }
        
        // Call checkForAnomalies when the page loads
        document.addEventListener('DOMContentLoaded', function() {
            checkForAnomalies();
            
            // Also check every 5 minutes
            setInterval(checkForAnomalies, 5 * 60 * 1000);
        });
        
        // Initialize toastr
        toastr.options = {
            "closeButton": true,
            "progressBar": true,
            "positionClass": "toast-top-right"
        };
    </script>
    
    <!-- Wearable Simulation Script -->
    <script src="assets/js/wearable.js"></script>
    
    <script>
        // Make sure the wearable simulation is properly initialized
        document.addEventListener('DOMContentLoaded', function() {
            // Set initial state
            const simulationStatus = document.getElementById('simulationStatus');
            if (simulationStatus) {
                const statusSpan = simulationStatus.querySelector('span');
                const statusIcon = simulationStatus.querySelector('i');
                
                // Set initial state to stopped
                statusSpan.textContent = 'Stopped';
                statusIcon.className = 'fas fa-circle';
                statusIcon.style.color = '#f44336';
                
                // Enable/disable buttons
                const startBtn = document.getElementById('startSimulation');
                const stopBtn = document.getElementById('stopSimulation');
                
                if (startBtn) startBtn.disabled = false;
                if (stopBtn) stopBtn.disabled = true;
            }
        });
    </script>
</body>
</html>
