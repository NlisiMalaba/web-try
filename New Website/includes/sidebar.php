<!-- Sidebar -->
<aside class="sidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" class="logo">HealthAssist Pro</a>
    </div>
    
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        <a href="health_metrics.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'health_metrics.php' ? 'active' : ''; ?>">
            <i class="fas fa-heartbeat"></i>
            <span>Health Metrics</span>
        </a>
        <a href="medications.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'medications.php' ? 'active' : ''; ?>">
            <i class="fas fa-pills"></i>
            <span>Medications</span>
        </a>
        <a href="appointments.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'appointments.php' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-alt"></i>
            <span>Appointments</span>
        </a>
        <a href="symptoms.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'symptoms.php' ? 'active' : ''; ?>">
            <i class="fas fa-notes-medical"></i>
            <span>Symptom Tracker</span>
        </a>
        <a href="reports.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-bar"></i>
            <span>Reports</span>
        </a>
        <a href="messaging.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'messaging.php' ? 'active' : ''; ?>">
            <i class="fas fa-comments"></i>
            <span>Messages</span>
            <span class="message-badge">3</span>
        </a>
        <a href="profile.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : ''; ?>">
            <i class="fas fa-user"></i>
            <span>Profile</span>
        </a>
        <a href="chatbot.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'chatbot.php' ? 'active' : ''; ?>">
            <i class="fas fa-robot"></i>
            <span>Health Assistant</span>
            <span class="badge bg-primary rounded-pill ms-auto">New</span>
        </a>
    </nav>
    
    <div class="sidebar-footer">
        <a href="logout.php" class="nav-item">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</aside>
