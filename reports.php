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
        
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8fafc;
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
            padding: 1rem 1.25rem;
        }
        .card-title {
            color: #4e73df;
            font-weight: 600;
            margin-bottom: 0;
        }
        .btn-primary {
            background-color: #4e73df;
            border-color: #4e73df;
        }
        .btn-primary:hover {
            background-color: #2e59d9;
            border-color: #2653d4;
        }
        .table th {
            border-top: none;
            font-weight: 600;
            color: #5a5c69;
        }
        .badge {
            font-weight: 500;
            padding: 0.35em 0.65em;
        }
        .bg-primary {
            background-color: #4e73df !important;
        }
        .bg-success {
            background-color: #1cc88a !important;
        }
        .bg-info {
            background-color: #36b9cc !important;
        }
        .bg-warning {
            background-color: #f6c23e !important;
        }
        .bg-danger {
            background-color: #e74a3b !important;
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
                        <li><a class="dropdown-item" href="#">Last 90 Days</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#">Custom Range</a></li>
                    </ul>
                </div>
                <a href="generate_pdf.php" class="btn btn-primary" target="_blank">
                    <i class="fas fa-file-pdf me-2"></i>Export as PDF
                </a>
            </div>
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
                                    Days Since Last Severe</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">5 Days</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
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
                        <h6 class="m-0 font-weight-bold text-primary">Symptom Frequency</h6>
                        <div class="dropdown no-arrow">
                            <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="dropdownMenuLink">
                                <li><a class="dropdown-item" href="#">View Details</a></li>
                                <li><a class="dropdown-item" href="#">Export Data</a></li>
                                <li><a class="dropdown-item" href="#">Print</a></li>
                            </ul>
                        </div>
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
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Severity Distribution</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-pie pt-4 pb-2">
                            <canvas id="severityPieChart"></canvas>
                        </div>
                        <div class="mt-4 text-center small">
                            <span class="me-2">
                                <i class="fas fa-circle text-primary"></i> Mild
                            </span>
                            <span class="me-2">
                                <i class="fas fa-circle text-success"></i> Moderate
                            </span>
                            <span class="me-2">
                                <i class="fas fa-circle text-warning"></i> Severe
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Symptom Trends -->
        <div class="row
            <div class="col-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Symptom Trends</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-area">
                            <canvas id="symptomTrendsChart"></canvas>
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
                    <table class="table table-hover" id="recentSymptomsTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Symptom</th>
                                <th>Severity</th>
                                <th>Duration</th>
                                <th>Notes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>May 30, 2023</td>
                                <td>Headache</td>
                                <td><span class="badge bg-warning">Moderate</span></td>
                                <td>4 hours</td>
                                <td>Started in the afternoon</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary">View</button>
                                </td>
                            </tr>
                            <tr>
                                <td>May 28, 2023</td>
                                <td>Fatigue</td>
                                <td><span class="badge bg-info">Mild</span></td>
                                <td>All day</td>
                                <td>After long day at work</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary">View</button>
                                </td>
                            </tr>
                            <tr>
                                <td>May 25, 2023</td>
                                <td>Nausea</td>
                                <td><span class="badge bg-danger">Severe</span></td>
                                <td>2 hours</td>
                                <td>After lunch</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary">View</button>
                                </td>
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

<!-- Page level custom scripts -->
<script>
    // Set new default font family and font color to mimic Bootstrap's default styling
    Chart.defaults.global.defaultFontFamily = 'Nunito', '-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
    Chart.defaults.global.defaultFontColor = '#858796';

    // Area Chart - Symptom Frequency
    var ctx = document.getElementById("symptomFrequencyChart");
    var myLineChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
            datasets: [{
                label: "Headache",
                lineTension: 0.3,
                backgroundColor: "rgba(78, 115, 223, 0.05)",
                borderColor: "rgba(78, 115, 223, 1)",
                pointRadius: 3,
                pointBackgroundColor: "rgba(78, 115, 223, 1)",
                pointBorderColor: "rgba(78, 115, 223, 1)",
                pointHoverRadius: 3,
                pointHoverBackgroundColor: "rgba(78, 115, 223, 1)",
                pointHoverBorderColor: "rgba(78, 115, 223, 1)",
                pointHitRadius: 10,
                pointBorderWidth: 2,
                data: [0, 10, 5, 15, 10, 20, 15, 25, 20, 30, 25, 40],
            }],
        },
        options: {
            maintainAspectRatio: false,
            layout: {
                padding: {
                    left: 10,
                    right: 25,
                    top: 25,
                    bottom: 0
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false,
                        drawBorder: false
                    },
                    ticks: {
                        maxTicksLimit: 7
                    }
                },
                y: {
                    ticks: {
                        maxTicks: 5,
                        padding: 10,
                    },
                    grid: {
                        color: "rgb(234, 236, 244)",
                        zeroLineColor: "rgb(234, 236, 244)",
                        drawBorder: false,
                        borderDash: [2],
                        zeroLineBorderDash: [2]
                    },
                },
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: "rgb(255,255,255)",
                    bodyColor: "#858796",
                    titleMarginBottom: 10,
                    titleColor: '#6e707e',
                    titleFontSize: 14,
                    borderColor: '#dddfeb',
                    borderWidth: 1,
                    xPadding: 15,
                    yPadding: 15,
                    displayColors: false,
                    intersect: false,
                    mode: 'index',
                    caretPadding: 10,
                }
            }
        }
    });

    // Pie Chart - Severity Distribution
    var ctx = document.getElementById("severityPieChart");
    var myPieChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ["Mild", "Moderate", "Severe"],
            datasets: [{
                data: [55, 30, 15],
                backgroundColor: ['#4e73df', '#1cc88a', '#f6c23e'],
                hoverBackgroundColor: ['#2e59d9', '#17a673', '#dda20a'],
                hoverBorderColor: "rgba(234, 236, 244, 1)",
            }],
        },
        options: {
            maintainAspectRatio: false,
            tooltips: {
                backgroundColor: "rgb(255,255,255)",
                bodyFontColor: "#858796",
                borderColor: '#dddfeb',
                borderWidth: 1,
                xPadding: 15,
                yPadding: 15,
                displayColors: false,
                caretPadding: 10,
            },
            legend: {
                display: false
            },
            cutout: '70%',
        },
    });

    // Line Chart - Symptom Trends
    var ctx = document.getElementById("symptomTrendsChart");
    var myLineChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun"],
            datasets: [
                {
                    label: "Headache",
                    borderColor: "#4e73df",
                    backgroundColor: "transparent",
                    pointRadius: 3,
                    pointBackgroundColor: "#4e73df",
                    pointBorderColor: "#4e73df",
                    pointHoverRadius: 3,
                    pointHoverBackgroundColor: "#4e73df",
                    pointHoverBorderColor: "#4e73df",
                    pointHitRadius: 10,
                    pointBorderWidth: 2,
                    data: [0, 10, 5, 15, 10, 20],
                },
                {
                    label: "Fatigue",
                    borderColor: "#1cc88a",
                    backgroundColor: "transparent",
                    pointRadius: 3,
                    pointBackgroundColor: "#1cc88a",
                    pointBorderColor: "#1cc88a",
                    pointHoverRadius: 3,
                    pointHoverBackgroundColor: "#1cc88a",
                    pointHoverBorderColor: "#1cc88a",
                    pointHitRadius: 10,
                    pointBorderWidth: 2,
                    data: [5, 15, 10, 5, 15, 10],
                },
                {
                    label: "Nausea",
                    borderColor: "#f6c23e",
                    backgroundColor: "transparent",
                    pointRadius: 3,
                    pointBackgroundColor: "#f6c23e",
                    pointBorderColor: "#f6c23e",
                    pointHoverRadius: 3,
                    pointHoverBackgroundColor: "#f6c23e",
                    pointHoverBorderColor: "#f6c23e",
                    pointHitRadius: 10,
                    pointBorderWidth: 2,
                    data: [10, 5, 15, 10, 5, 15],
                }
            ],
        },
        options: {
            maintainAspectRatio: false,
            layout: {
                padding: {
                    left: 10,
                    right: 25,
                    top: 25,
                    bottom: 0
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false,
                        drawBorder: false
                    },
                    ticks: {
                        maxTicksLimit: 6
                    }
                },
                y: {
                    ticks: {
                        maxTicks: 5,
                        padding: 10,
                    },
                    grid: {
                        color: "rgb(234, 236, 244)",
                        zeroLineColor: "rgb(234, 236, 244)",
                        drawBorder: false,
                        borderDash: [2],
                        zeroLineBorderDash: [2]
                    },
                },
            },
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    backgroundColor: "rgb(255,255,255)",
                    bodyColor: "#858796",
                    titleMarginBottom: 10,
                    titleColor: '#6e707e',
                    titleFontSize: 14,
                    borderColor: '#dddfeb',
                    borderWidth: 1,
                    xPadding: 15,
                    yPadding: 15,
                    displayColors: true,
                    intersect: false,
                    mode: 'index',
                    caretPadding: 10,
                }
            }
        }
    });
</script>

<?php
// Include footer
include_once "includes/footer.php";
?>
