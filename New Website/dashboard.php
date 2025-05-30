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
    <title>Dashboard - HealthAssist Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <a href="dashboard.php" class="logo">HealthAssist Pro</a>
            </div>
            
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item active">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="health_metrics.php" class="nav-item">
                    <i class="fas fa-heartbeat"></i>
                    <span>Health Metrics</span>
                </a>
                <a href="medications.php" class="nav-item">
                    <i class="fas fa-pills"></i>
                    <span>Medications</span>
                </a>
                <a href="appointments.php" class="nav-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Appointments</span>
                </a>
                <a href="symptoms_new.php" class="nav-item">
                    <i class="fas fa-notes-medical"></i>
                    <span>Symptom Tracker</span>
                </a>
                <a href="reports.php" class="nav-item">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
                <a href="messaging.php" class="nav-item">
                    <i class="fas fa-comments"></i>
                    <span>Messages</span>
                    <span class="message-badge">3</span>
                </a>
                <a href="profile.php" class="nav-item">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <a href="logout.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>
        
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
                    <h1>Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>!</h1>
                    <p>Here's an overview of your health journey</p>
                </div>
                
                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: #e0f2fe;">
                            <i class="fas fa-heartbeat" style="color: #0ea5e9;"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Heart Rate</h3>
                            <p class="stat-value">72 <span>bpm</span></p>
                            <p class="stat-change positive">
                                <i class="fas fa-arrow-down"></i> 5% from last week
                            </p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: #fef3c7;">
                            <i class="fas fa-walking" style="color: #f59e0b;"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Steps</h3>
                            <p class="stat-value">8,542</p>
                            <p class="stat-change positive">
                                <i class="fas fa-arrow-up"></i> 12% from yesterday
                            </p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: #d1fae5;">
                            <i class="fas fa-moon" style="color: #10b981;"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Sleep</h3>
                            <p class="stat-value">7.5 <span>hours</span></p>
                            <p class="stat-change negative">
                                <i class="fas fa-arrow-down"></i> 0.5h from average
                            </p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: #fee2e2;">
                            <i class="fas fa-weight" style="color: #ef4444;"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Weight</h3>
                            <p class="stat-value">68 <span>kg</span></p>
                            <p class="stat-change positive">
                                <i class="fas fa-arrow-down"></i> 1.2kg this month
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Health Summary -->
                <div class="health-summary">
                    <div class="summary-header">
                        <h2>Health Summary</h2>
                        <select class="time-filter">
                            <option>Last 7 days</option>
                            <option>Last 30 days</option>
                            <option>Last 3 months</option>
                            <option>Last year</option>
                        </select>
                    </div>
                    
                    <div class="summary-chart">
                        <!-- Chart will be rendered here with Chart.js -->
                        <canvas id="healthChart"></canvas>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="recent-activity">
                    <div class="activity-header">
                        <h2>Recent Activity</h2>
                        <a href="#" class="view-all">View All</a>
                    </div>
                    
                    <div class="activity-list">
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-pills"></i>
                            </div>
                            <div class="activity-details">
                                <h4>Medication Taken</h4>
                                <p>You took your daily medication</p>
                                <span class="activity-time">2 hours ago</span>
                            </div>
                        </div>
                        
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-walking"></i>
                            </div>
                            <div class="activity-details">
                                <h4>Daily Goal Reached</h4>
                                <p>You've reached your step goal of 8,000 steps</p>
                                <span class="activity-time">5 hours ago</span>
                            </div>
                        </div>
                        
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-notes-medical"></i>
                            </div>
                            <div class="activity-details">
                                <h4>Symptom Logged</h4>
                                <p>You logged a headache in your symptom tracker</p>
                                <span class="activity-time">Yesterday</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Initialize health chart
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('healthChart').getContext('2d');
            const healthChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [
                        {
                            label: 'Heart Rate (bpm)',
                            data: [72, 71, 70, 73, 72, 71, 72],
                            borderColor: '#0ea5e9',
                            backgroundColor: 'rgba(14, 165, 233, 0.1)',
                            tension: 0.3,
                            fill: true
                        },
                        {
                            label: 'Steps (thousands)',
                            data: [7.2, 8.1, 7.8, 8.5, 8.0, 6.5, 7.0],
                            borderColor: '#f59e0b',
                            backgroundColor: 'rgba(245, 158, 11, 0.1)',
                            tension: 0.3,
                            fill: true
                        },
                        {
                            label: 'Sleep (hours)',
                            data: [7.5, 7.0, 7.8, 7.2, 7.5, 8.0, 7.5],
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            tension: 0.3,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false
                        }
                    },
                    interaction: {
                        mode: 'nearest',
                        axis: 'x',
                        intersect: false
                    }
                }
            });
        });
    </script>
</body>
</html>
