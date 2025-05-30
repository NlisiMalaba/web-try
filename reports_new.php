<?php
session_start();

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
$stmt->bindParam(":id", $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Reports - HealthAssist Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
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
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: var(--shadow);
            margin-bottom: 1.5rem;
            background: var(--white);
        }
        
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 1.5rem;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #4338ca;
            border-color: #4338ca;
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
                    <input type="text" placeholder="Search reports...">
                </div>
                <div class="user-info">
                    <div class="notifications">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge">3</span>
                    </div>
                    <div class="user-avatar">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['first_name'] . ' ' . $user['last_name']); ?>" alt="User Avatar">
                        <span><?php echo htmlspecialchars($user['first_name']); ?></span>
                    </div>
                </div>
            </header>
            
            <div class="content">
                <div class="page-header">
                    <h1>Health Reports</h1>
                    <p>View and export your health reports</p>
                </div>
                
                <div class="container-fluid p-4">
                    <!-- Export Button -->
                    <div class="d-flex justify-content-end mb-4">
                        <a href="generate_pdf.php" class="btn btn-primary" target="_blank">
                            <i class="fas fa-file-pdf me-2"></i>Export as PDF
                        </a>
                    </div>

                    <!-- Stats Cards -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Symptoms Tracked</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">47</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-notes-medical fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Average Severity</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">2.8/5.0</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Most Common Symptom</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">Headache</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Last Updated</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">2 days ago</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row -->
                    <div class="row">
                        <!-- Symptom Frequency Chart -->
                        <div class="col-xl-8 col-lg-7">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Symptom Frequency (Last 30 Days)</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-area">
                                        <canvas id="symptomFrequencyChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Severity Distribution -->
                        <div class="col-xl-4 col-lg-5">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Severity Distribution</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-pie pt-4 pb-2">
                                        <canvas id="severityPieChart"></canvas>
                                    </div>
                                    <div class="mt-4 text-center small">
                                        <span class="mr-2">
                                            <i class="fas fa-circle text-primary"></i> Mild
                                        </span>
                                        <span class="mr-2">
                                            <i class="fas fa-circle text-success"></i> Moderate
                                        </span>
                                        <span class="mr-2">
                                            <i class="fas fa-circle text-warning"></i> Severe
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Symptoms -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Recent Symptoms</h6>
                            <a href="symptoms.php" class="btn btn-sm btn-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Symptom</th>
                                            <th>Severity</th>
                                            <th>Duration</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>2023-06-15</td>
                                            <td>Headache</td>
                                            <td><span class="badge bg-warning">Moderate</span></td>
                                            <td>4 hours</td>
                                            <td>Persistent pain behind eyes</td>
                                        </tr>
                                        <tr>
                                            <td>2023-06-14</td>
                                            <td>Fatigue</td>
                                            <td><span class="badge bg-primary">Mild</span></td>
                                            <td>All day</td>
                                            <td>Low energy levels</td>
                                        </tr>
                                        <tr>
                                            <td>2023-06-13</td>
                                            <td>Nausea</td>
                                            <td><span class="badge bg-danger">Severe</span></td>
                                            <td>2 hours</td>
                                            <td>After meals</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div> <!-- Close container-fluid -->
            </div> <!-- Close content -->
        </main> <!-- Close main-content -->
    </div> <!-- Close dashboard-container -->

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Flatpickr -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <!-- Toastr -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <script>
        // Initialize date picker
        flatpickr("input[type=datetime-local]", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
        });

        // Symptom Frequency Chart
        const ctx1 = document.getElementById('symptomFrequencyChart').getContext('2d');
        const symptomFrequencyChart = new Chart(ctx1, {
            type: 'line',
            data: {
                labels: ['Jun 1', 'Jun 5', 'Jun 10', 'Jun 15', 'Jun 20', 'Jun 25', 'Jun 30'],
                datasets: [{
                    label: 'Headache',
                    data: [3, 2, 4, 3, 5, 4, 3],
                    borderColor: '#4e73df',
                    backgroundColor: 'rgba(78, 115, 223, 0.05)',
                    tension: 0.3,
                    fill: true
                }, {
                    label: 'Fatigue',
                    data: [2, 3, 2, 1, 4, 3, 2],
                    borderColor: '#1cc88a',
                    backgroundColor: 'rgba(28, 200, 138, 0.05)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });

        // Severity Distribution Pie Chart
        const ctx2 = document.getElementById('severityPieChart').getContext('2d');
        const severityPieChart = new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: ['Mild', 'Moderate', 'Severe'],
                datasets: [{
                    data: [15, 20, 12],
                    backgroundColor: ['#4e73df', '#f6c23e', '#e74a3b'],
                    hoverBackgroundColor: ['#2e59d9', '#dda20a', '#be2617'],
                    hoverBorderColor: 'rgba(234, 236, 244, 1)',
                }],
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: "rgb(255,255,255)",
                        bodyColor: '#858796',
                        borderColor: '#dddfeb',
                        borderWidth: 1,
                        xPadding: 15,
                        yPadding: 15,
                        displayColors: false
                    }
                },
                cutout: '70%',
            },
        });
    </script>

    <?php include_once "includes/footer.php"; ?>
</body>
</html>
