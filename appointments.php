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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_appointment'])) {
        try {
            $query = "INSERT INTO appointments (user_id, title, doctor_name, appointment_date, start_time, end_time, location, notes) 
                     VALUES (:user_id, :title, :doctor_name, :appointment_date, :start_time, :end_time, :location, :notes)";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->bindParam(":title", $_POST['title']);
            $stmt->bindParam(":doctor_name", $_POST['doctor_name']);
            $stmt->bindParam(":appointment_date", $_POST['appointment_date']);
            $stmt->bindParam(":start_time", $_POST['start_time']);
            $end_time = !empty($_POST['end_time']) ? $_POST['end_time'] : date('H:i:s', strtotime($_POST['start_time'] . ' +30 minutes'));
            $stmt->bindParam(":end_time", $end_time);
            $location = !empty($_POST['location']) ? $_POST['location'] : null;
            $stmt->bindParam(":location", $location);
            $notes = !empty($_POST['notes']) ? $_POST['notes'] : null;
            $stmt->bindParam(":notes", $notes);
            
            $stmt->execute();
            
            $_SESSION['success'] = "Appointment scheduled successfully!";
            header("Location: appointments.php");
            exit();
            
        } catch (Exception $e) {
            $error = "Error scheduling appointment: " . $e->getMessage();
        }
    } elseif (isset($_POST['delete_appointment'])) {
        try {
            $appointment_id = $_POST['appointment_id'];
            
            // Verify that the appointment belongs to the current user
            $query = "SELECT id FROM appointments WHERE id = :id AND user_id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":id", $appointment_id);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                // Delete the appointment
                $query = "DELETE FROM appointments WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":id", $appointment_id);
                $stmt->execute();
                
                $_SESSION['success'] = "Appointment deleted successfully!";
            } else {
                $error = "Appointment not found or you don't have permission to delete it.";
            }
            
            header("Location: appointments.php");
            exit();
            
        } catch (Exception $e) {
            $error = "Error deleting appointment: " . $e->getMessage();
        }
    }
}

// Get user's upcoming appointments (next 30 days)
$query = "SELECT * FROM appointments 
          WHERE user_id = :user_id 
          AND (appointment_date > CURDATE() OR (appointment_date = CURDATE() AND end_time > CURTIME()))
          ORDER BY appointment_date ASC, start_time ASC";
$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $user_id);
$stmt->execute();
$upcoming_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's past appointments (older than today)
$query = "SELECT * FROM appointments 
          WHERE user_id = :user_id 
          AND (appointment_date < CURDATE() OR (appointment_date = CURDATE() AND end_time < CURTIME()))
          ORDER BY appointment_date DESC, start_time DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $user_id);
$stmt->execute();
$past_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments - HealthAssist Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        .appointments-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .add-appointment-card, .appointment-list-card {
            background: #fff;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        .add-appointment-form .form-group {
            margin-bottom: 1rem;
        }
        
        .add-appointment-form label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #1a202c;
        }
        
        .add-appointment-form input[type="text"],
        .add-appointment-form input[type="date"],
        .add-appointment-form input[type="time"],
        .add-appointment-form select,
        .add-appointment-form textarea {
            width: 100%;
            padding: 0.6rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 0.95rem;
            transition: all 0.2s;
        }
        
        .add-appointment-form input:focus,
        .add-appointment-form select:focus,
        .add-appointment-form textarea:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .appointment-card {
            background: #fff;
            border-radius: 8px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            border-left: 4px solid #4f46e5;
            position: relative;
        }
        
        .appointment-card.past {
            opacity: 0.8;
            border-left-color: #94a3b8;
        }
        
        .appointment-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.5rem;
        }
        
        .appointment-title {
            font-weight: 600;
            color: #1e293b;
            font-size: 1.1rem;
            margin: 0;
        }
        
        .appointment-doctor {
            color: #4f46e5;
            font-weight: 500;
            margin: 0.25rem 0;
            display: flex;
            align-items: center;
        }
        
        .appointment-doctor i {
            margin-right: 0.5rem;
        }
        
        .appointment-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            font-size: 0.85rem;
            color: #64748b;
            margin: 0.75rem 0;
        }
        
        .appointment-meta-item {
            display: flex;
            align-items: center;
        }
        
        .appointment-meta-item i {
            margin-right: 0.5rem;
            color: #94a3b8;
        }
        
        .appointment-notes {
            font-size: 0.9rem;
            color: #475569;
            margin: 0.75rem 0 0;
            line-height: 1.5;
            padding: 0.75rem;
            background-color: #f8fafc;
            border-radius: 6px;
        }
        
        .appointment-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1rem;
            padding-top: 0.75rem;
            border-top: 1px solid #f1f5f9;
        }
        
        .btn {
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid transparent;
        }
        
        .btn i {
            margin-right: 0.3rem;
        }
        
        .btn-edit {
            background: #eef2ff;
            color: #4f46e5;
        }
        
        .btn-edit:hover {
            background: #e0e7ff;
        }
        
        .btn-delete {
            background: #fef2f2;
            color: #dc2626;
            border-color: #fecaca;
        }
        
        .btn-delete:hover {
            background: #fee2e2;
        }
        
        .btn-primary {
            background: #4f46e5;
            color: white;
            border: none;
            padding: 0.6rem 1.2rem;
        }
        
        .btn-primary:hover {
            background: #4338ca;
        }
        
        .no-appointments {
            text-align: center;
            padding: 2rem;
            color: #64748b;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px dashed #e2e8f0;
        }
        
        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
        }
        
        .section-title i {
            margin-right: 0.5rem;
            color: #4f46e5;
        }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .badge-today {
            background-color: #dbeafe;
            color: #1e40af;
        }
        
        @media (max-width: 1024px) {
            .appointments-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <header class="top-bar">
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search appointments...">
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
                <div class="page-header">
                    <h1>Appointments</h1>
                    <p>Manage your medical appointments and schedule new ones</p>
                </div>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <?php 
                        echo htmlspecialchars($_SESSION['success']); 
                        unset($_SESSION['success']);
                        ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <div class="appointments-container">
                    <!-- Add Appointment Form -->
                    <div class="add-appointment-card">
                        <h2 class="section-title">
                            <i class="fas fa-plus-circle"></i> Schedule New Appointment
                        </h2>
                        
                        <form action="appointments.php" method="POST" class="add-appointment-form">
                            <div class="form-group">
                                <label for="title">Appointment Title *</label>
                                <input type="text" id="title" name="title" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="doctor_name">Doctor/Provider Name *</label>
                                <input type="text" id="doctor_name" name="doctor_name" required>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="appointment_date">Date *</label>
                                    <input type="date" id="appointment_date" name="appointment_date" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="start_time">Start Time *</label>
                                    <input type="time" id="start_time" name="start_time" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="end_time">End Time</label>
                                    <input type="time" id="end_time" name="end_time">
                                    <small class="text-muted">Leave empty for 30-minute duration</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="location">Location</label>
                                    <input type="text" id="location" name="location" placeholder="e.g., City Hospital, Room 101">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="notes">Notes</label>
                                <textarea id="notes" name="notes" rows="3" placeholder="Any special instructions or notes"></textarea>
                            </div>
                            
                            <button type="submit" name="add_appointment" class="btn btn-primary">
                                <i class="fas fa-calendar-plus"></i> Schedule Appointment
                            </button>
                        </form>
                    </div>
                    
                    <!-- Upcoming Appointments -->
                    <div class="appointment-list-card">
                        <h2 class="section-title">
                            <i class="fas fa-calendar-alt"></i> Upcoming Appointments
                        </h2>
                        
                        <?php if (empty($upcoming_appointments)): ?>
                            <div class="no-appointments">
                                <i class="far fa-calendar-check" style="font-size: 2rem; margin-bottom: 1rem; color: #cbd5e1;"></i>
                                <p>No upcoming appointments. Schedule one now!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($upcoming_appointments as $appointment): 
                                $appointment_date = new DateTime($appointment['appointment_date']);
                                $today = new DateTime();
                                $is_today = $appointment_date->format('Y-m-d') === $today->format('Y-m-d');
                                
                                $start_datetime = new DateTime($appointment['appointment_date'] . ' ' . $appointment['start_time']);
                                $end_datetime = new DateTime($appointment['appointment_date'] . ' ' . $appointment['end_time']);
                            ?>
                                <div class="appointment-card <?php echo $is_today ? 'today' : ''; ?>">
                                    <div class="appointment-header">
                                        <h3 class="appointment-title"><?php echo htmlspecialchars($appointment['title']); ?></h3>
                                        <div class="appointment-date">
                                            <?php 
                                                if ($is_today) {
                                                    echo '<span class="badge badge-today">Today</span> ';
                                                }
                                                echo $start_datetime->format('M j, Y'); 
                                            ?>
                                        </div>
                                    </div>
                                    
                                    <div class="appointment-doctor">
                                        <i class="fas fa-user-md"></i>
                                        <?php echo htmlspecialchars($appointment['doctor_name']); ?>
                                    </div>
                                    
                                    <div class="appointment-meta">
                                        <div class="appointment-meta-item">
                                            <i class="far fa-clock"></i>
                                            <span><?php echo $start_datetime->format('g:i A') . ' - ' . $end_datetime->format('g:i A'); ?></span>
                                        </div>
                                        
                                        <?php if (!empty($appointment['location'])): ?>
                                            <div class="appointment-meta-item">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <span><?php echo htmlspecialchars($appointment['location']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if (!empty($appointment['notes'])): ?>
                                        <div class="appointment-notes">
                                            <?php echo nl2br(htmlspecialchars($appointment['notes'])); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="appointment-actions">
                                        <button class="btn btn-edit" onclick="editAppointment(<?php echo $appointment['id']; ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <form action="appointments.php" method="POST" style="display: inline;">
                                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                            <button type="submit" name="delete_appointment" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this appointment?');">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <!-- Past Appointments Section -->
                        <h2 class="section-title" style="margin-top: 2.5rem;">
                            <i class="fas fa-history"></i> Past Appointments
                        </h2>
                        
                        <?php if (empty($past_appointments)): ?>
                            <div class="no-appointments">
                                <i class="far fa-calendar-times" style="font-size: 2rem; margin-bottom: 1rem; color: #cbd5e1;"></i>
                                <p>No past appointments found.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($past_appointments as $appointment): 
                                $appointment_date = new DateTime($appointment['appointment_date']);
                                $start_datetime = new DateTime($appointment['appointment_date'] . ' ' . $appointment['start_time']);
                                $end_datetime = new DateTime($appointment['appointment_date'] . ' ' . $appointment['end_time']);
                            ?>
                                <div class="appointment-card past">
                                    <div class="appointment-header">
                                        <h3 class="appointment-title"><?php echo htmlspecialchars($appointment['title']); ?></h3>
                                        <div class="appointment-date">
                                            <?php echo $appointment_date->format('M j, Y'); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="appointment-doctor">
                                        <i class="fas fa-user-md"></i>
                                        <?php echo htmlspecialchars($appointment['doctor_name']); ?>
                                    </div>
                                    
                                    <div class="appointment-meta">
                                        <div class="appointment-meta-item">
                                            <i class="far fa-clock"></i>
                                            <span><?php echo $start_datetime->format('g:i A') . ' - ' . $end_datetime->format('g:i A'); ?></span>
                                        </div>
                                        
                                        <?php if (!empty($appointment['location'])): ?>
                                            <div class="appointment-meta-item">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <span><?php echo htmlspecialchars($appointment['location']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if (!empty($appointment['notes'])): ?>
                                        <div class="appointment-notes">
                                            <?php echo nl2br(htmlspecialchars($appointment['notes'])); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Set default date to today
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('appointment_date').min = today;
            
            // Set default end time to 30 minutes after start time
            const startTimeInput = document.getElementById('start_time');
            const endTimeInput = document.getElementById('end_time');
            
            startTimeInput.addEventListener('change', function() {
                if (!endTimeInput.value) {
                    const startTime = new Date(`2000-01-01T${this.value}`);
                    startTime.setMinutes(startTime.getMinutes() + 30);
                    
                    // Format the time to HH:MM
                    const hours = String(startTime.getHours()).padStart(2, '0');
                    const minutes = String(startTime.getMinutes()).padStart(2, '0');
                    endTimeInput.value = `${hours}:${minutes}`;
                }
            });
        });
        
        function editAppointment(id) {
            // Implement edit functionality
            alert('Edit functionality will be implemented in the next update. Appointment ID: ' + id);
        }
    </script>
</body>
</html>
