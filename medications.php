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

// Get user data with notification settings
$user_id = $_SESSION['user_id'];
$query = "SELECT first_name, last_name, email, phone_number, sms_notifications_enabled, timezone 
          FROM users WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(":id", $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Set default timezone if not set
if (empty($user['timezone'])) {
    $user['timezone'] = 'UTC';
}

// Handle notification settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_notification_settings'])) {
    try {
        $phone_number = !empty($_POST['phone_number']) ? preg_replace('/[^0-9+]/', '', $_POST['phone_number']) : null;
        $sms_enabled = isset($_POST['sms_notifications_enabled']) ? 1 : 0;
        $timezone = $_POST['timezone'] ?? 'UTC';
        
        $query = "UPDATE users SET 
                 phone_number = :phone_number,
                 sms_notifications_enabled = :sms_enabled,
                 timezone = :timezone
                 WHERE id = :user_id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":phone_number", $phone_number);
        $stmt->bindParam(":sms_enabled", $sms_enabled, PDO::PARAM_INT);
        $stmt->bindParam(":timezone", $timezone);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        
        // Update user session data
        $user['phone_number'] = $phone_number;
        $user['sms_notifications_enabled'] = $sms_enabled;
        $user['timezone'] = $timezone;
        
        $_SESSION['success'] = "Notification settings updated successfully!";
        header("Location: medications.php");
        exit();
        
    } catch (Exception $e) {
        $error = "Error updating notification settings: " . $e->getMessage();
    }
}

// Handle reminder creation/update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_reminder'])) {
    try {
        $medication_id = $_POST['medication_id'];
        $reminder_time = $_POST['reminder_time'];
        $days = isset($_POST['days']) ? implode(',', $_POST['days']) : '';
        
        // Verify medication belongs to user
        $query = "SELECT id FROM medications WHERE id = :id AND user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $medication_id);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            throw new Exception("Medication not found or access denied");
        }
        
        if (empty($days)) {
            throw new Exception("Please select at least one day for the reminder");
        }
        
        // Check if reminder already exists
        $query = "SELECT id FROM medication_reminders 
                 WHERE medication_id = :medication_id AND reminder_time = :reminder_time";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":medication_id", $medication_id);
        $stmt->bindParam(":reminder_time", $reminder_time);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            // Update existing reminder
            $query = "UPDATE medication_reminders 
                     SET days_of_week = :days, 
                         is_active = 1,
                         updated_at = NOW()
                     WHERE medication_id = :medication_id 
                     AND reminder_time = :reminder_time";
        } else {
            // Create new reminder
            $query = "INSERT INTO medication_reminders 
                     (medication_id, user_id, reminder_time, days_of_week, timezone)
                     VALUES (:medication_id, :user_id, :reminder_time, :days, :timezone)";
        }
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":medication_id", $medication_id);
        $stmt->bindParam(":reminder_time", $reminder_time);
        $stmt->bindParam(":days", $days);
        
        if (strpos($query, 'INSERT') !== false) {
            $stmt->bindParam(":user_id", $user_id);
            $stmt->bindParam(":timezone", $user['timezone']);
        }
        
        $stmt->execute();
        
        $_SESSION['success'] = "Reminder saved successfully!";
        header("Location: medications.php");
        exit();
        
    } catch (Exception $e) {
        $error = "Error saving reminder: " . $e->getMessage();
    }
}

// Handle reminder deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_reminder'])) {
    try {
        $reminder_id = $_POST['reminder_id'];
        
        // Verify reminder belongs to user
        $query = "DELETE mr FROM medication_reminders mr
                 JOIN medications m ON m.id = mr.medication_id
                 WHERE mr.id = :reminder_id AND m.user_id = :user_id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":reminder_id", $reminder_id);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        
        $_SESSION['success'] = "Reminder deleted successfully!";
        header("Location: medications.php");
        exit();
        
    } catch (Exception $e) {
        $error = "Error deleting reminder: " . $e->getMessage();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_medication'])) {
        try {
            // Insert medication
            $query = "INSERT INTO medications (user_id, name, dosage, frequency, start_date, end_date, prescribed_by, notes) 
                     VALUES (:user_id, :name, :dosage, :frequency, :start_date, :end_date, :prescribed_by, :notes)";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->bindParam(":name", $_POST['name']);
            $stmt->bindParam(":dosage", $_POST['dosage']);
            $stmt->bindParam(":frequency", $_POST['frequency']);
            $stmt->bindParam(":start_date", $_POST['start_date']);
            $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
            $stmt->bindParam(":end_date", $end_date);
            $prescribed_by = !empty($_POST['prescribed_by']) ? $_POST['prescribed_by'] : null;
            $stmt->bindParam(":prescribed_by", $prescribed_by);
            $notes = !empty($_POST['notes']) ? $_POST['notes'] : null;
            $stmt->bindParam(":notes", $notes);
            
            $stmt->execute();
            
            $_SESSION['success'] = "Medication added successfully!";
            header("Location: medications.php");
            exit();
            
        } catch (Exception $e) {
            $error = "Error adding medication: " . $e->getMessage();
        }
    } elseif (isset($_POST['delete_medication'])) {
        try {
            $medication_id = $_POST['medication_id'];
            
            // Verify that the medication belongs to the current user
            $query = "SELECT id FROM medications WHERE id = :id AND user_id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":id", $medication_id);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                // Delete the medication
                $query = "DELETE FROM medications WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":id", $medication_id);
                $stmt->execute();
                
                $_SESSION['success'] = "Medication deleted successfully!";
            } else {
                $error = "Medication not found or you don't have permission to delete it.";
            }
            
            header("Location: medications.php");
            exit();
            
        } catch (Exception $e) {
            $error = "Error deleting medication: " . $e->getMessage();
        }
    }
}

// Get user's medications
$query = "SELECT * FROM medications WHERE user_id = :user_id ORDER BY name ASC";
$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $user_id);
$stmt->execute();
$medications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medications - HealthAssist Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="assets/css/medications.css">
    <style>
        .medications-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .add-medication-card, .medication-list-card {
            background: #fff;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        .add-medication-form .form-group {
            margin-bottom: 1rem;
        }
        
        .add-medication-form label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #1a202c;
        }
        
        .add-medication-form input[type="text"],
        .add-medication-form input[type="number"],
        .add-medication-form input[type="date"],
        .add-medication-form select,
        .add-medication-form textarea {
            width: 100%;
            padding: 0.6rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 0.95rem;
            transition: all 0.2s;
        }
        
        .add-medication-form input:focus,
        .add-medication-form select:focus,
        .add-medication-form textarea:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .medication-card {
            background: #fff;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            border-left: 4px solid #4f46e5;
        }
        
        .medication-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .medication-name {
            font-weight: 600;
            color: #1e293b;
            font-size: 1.1rem;
        }
        
        .medication-dosage {
            color: #4f46e5;
            font-weight: 500;
        }
        
        .medication-meta {
            display: flex;
            gap: 1rem;
            font-size: 0.85rem;
            color: #64748b;
            margin: 0.5rem 0;
        }
        
        .medication-notes {
            font-size: 0.9rem;
            color: #475569;
            margin: 0.5rem 0;
            line-height: 1.5;
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
        
        .no-medications {
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
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }
        
        .section-title i {
            margin-right: 0.5rem;
            color: #4f46e5;
        }
        
        @media (max-width: 1024px) {
            .medications-container {
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
                    <input type="text" placeholder="Search medications...">
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
                    <h1>Medications</h1>
                    <p>Manage your medications and set up reminders</p>
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
                
                <div class="medications-container">
                    <!-- Add Medication Form -->
                    <div class="add-medication-card">
                        <h2 class="section-title">
                            <i class="fas fa-plus-circle"></i> Add New Medication
                        </h2>
                        
                        <form action="medications.php" method="POST" class="add-medication-form">
                            <div class="form-group">
                                <label for="name">Medication Name *</label>
                                <input type="text" id="name" name="name" required>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="dosage">Dosage *</label>
                                    <input type="text" id="dosage" name="dosage" placeholder="e.g., 10mg" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="frequency">Frequency *</label>
                                    <select id="frequency" name="frequency" required>
                                        <option value="">Select frequency</option>
                                        <option value="Once daily">Once daily</option>
                                        <option value="Twice daily">Twice daily</option>
                                        <option value="Three times daily">Three times daily</option>
                                        <option value="Four times daily">Four times daily</option>
                                        <option value="As needed">As needed</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="start_date">Start Date *</label>
                                    <input type="date" id="start_date" name="start_date" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="end_date">End Date (if applicable)</label>
                                    <input type="date" id="end_date" name="end_date">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="prescribed_by">Prescribed By (Optional)</label>
                                <input type="text" id="prescribed_by" name="prescribed_by" placeholder="Doctor's name">
                            </div>
                            
                            <div class="form-group">
                                <label for="notes">Notes (Optional)</label>
                                <textarea id="notes" name="notes" rows="3" placeholder="Any special instructions or notes"></textarea>
                            </div>
                            
                            <button type="submit" name="add_medication" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Medication
                            </button>
                        </form>
                    </div>
                    
                    <!-- Medication List -->
                    <div class="medication-list-card">
                        <h2 class="section-title">
                            <i class="fas fa-pills"></i> My Medications
                        </h2>
                        
                        <!-- Notification Settings -->
                        <div class="notification-settings">
                            <h3><i class="fas fa-bell"></i> Notification Settings</h3>
                            <form method="POST" class="notification-form">
                                <div class="form-group">
                                    <label for="phone_number">Phone Number (with country code):</label>
                                    <input type="tel" id="phone_number" name="phone_number" 
                                           value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>" 
                                           placeholder="+1234567890" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="timezone">Time Zone:</label>
                                    <select id="timezone" name="timezone" required>
                                        <?php
                                        $timezones = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
                                        foreach ($timezones as $tz) {
                                            $selected = ($tz === ($user['timezone'] ?? 'UTC')) ? 'selected' : '';
                                            echo "<option value=\"" . htmlspecialchars($tz) . "\" $selected>" . htmlspecialchars(str_replace('_', ' ', $tz)) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <div class="form-group checkbox-group">
                                    <input type="checkbox" id="sms_notifications_enabled" name="sms_notifications_enabled" 
                                           value="1" <?php echo !empty($user['sms_notifications_enabled']) ? 'checked' : ''; ?>>
                                    <label for="sms_notifications_enabled">Enable SMS Notifications</label>
                                </div>
                                
                                <button type="submit" name="update_notification_settings" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Settings
                                </button>
                            </form>
                        </div>
                        
                        <?php if (empty($medications)): ?>
                            <p class="no-data">No medications found. Add your first medication above.</p>
                        <?php else: ?>
                            <div class="medication-table-container">
                                <table class="medication-table">
                                    <thead>
                                        <tr>
                                            <th>Medication</th>
                                            <th>Dosage</th>
                                            <th>Frequency</th>
                                            <th>Reminders</th>
                                            <th>Start Date</th>
                                            <th>End Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        // Get reminders for all medications at once
                                        $medication_ids = array_column($medications, 'id');
                                        $placeholders = str_repeat('?,', count($medication_ids) - 1) . '?';
                                        $query = "SELECT * FROM medication_reminders WHERE medication_id IN ($placeholders) ORDER BY reminder_time";
                                        $stmt = $db->prepare($query);
                                        $stmt->execute($medication_ids);
                                        $all_reminders = $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);
                                        
                                        foreach ($medications as $medication): 
                                            $med_reminders = $all_reminders[$medication['id']] ?? [];
                                        ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($medication['name']); ?></td>
                                                <td><?php echo htmlspecialchars($medication['dosage']); ?></td>
                                                <td><?php echo htmlspecialchars($medication['frequency']); ?></td>
                                                <td class="reminders-cell">
                                                    <?php if (!empty($med_reminders)): ?>
                                                        <div class="reminders-list">
                                                            <?php foreach ($med_reminders as $reminder): 
                                                                $days = explode(',', $reminder['days_of_week']);
                                                                $day_names = [];
                                                                $day_map = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];
                                                                foreach ($days as $day) {
                                                                    if (isset($day_map[$day])) {
                                                                        $day_names[] = $day_map[$day];
                                                                    }
                                                                }
                                                                $time = new DateTime($reminder['reminder_time']);
                                                            ?>
                                                                <div class="reminder-item">
                                                                    <span class="reminder-time">
                                                                        <?php echo $time->format('g:i A'); ?>
                                                                    </span>
                                                                    <span class="reminder-days">
                                                                        <?php echo implode(', ', $day_names); ?>
                                                                    </span>
                                                                    <form method="POST" class="inline-form" onsubmit="return confirm('Are you sure you want to delete this reminder?');">
                                                                        <input type="hidden" name="reminder_id" value="<?php echo $reminder['id']; ?>">
                                                                        <button type="submit" name="delete_reminder" class="btn-icon btn-icon-sm" title="Delete Reminder">
                                                                            <i class="fas fa-times"></i>
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <button class="btn-text add-reminder-btn" data-medication-id="<?php echo $medication['id']; ?>" data-medication-name="<?php echo htmlspecialchars($medication['name']); ?>">
                                                        <i class="fas fa-plus"></i> Add Reminder
                                                    </button>
                                                </td>
                                                <td><?php echo date('M j, Y', strtotime($medication['start_date'])); ?></td>
                                                <td><?php echo $medication['end_date'] ? date('M j, Y', strtotime($medication['end_date'])) : 'Ongoing'; ?></td>
                                                <td class="actions-cell">
                                                    <button class="btn-icon" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <form method="POST" class="inline-form" onsubmit="return confirm('Are you sure you want to delete this medication and all its reminders?');">
                                                        <input type="hidden" name="medication_id" value="<?php echo $medication['id']; ?>">
                                                        <button type="submit" name="delete_medication" class="btn-icon" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Add Reminder Modal -->
                        <div id="reminderModal" class="modal">
                            <div class="modal-content">
                                <span class="close-modal">&times;</span>
                                <h3>Add Reminder for <span id="medicationName"></span></h3>
                                <form id="reminderForm" method="POST">
                                    <input type="hidden" name="medication_id" id="medicationId">
                                    
                                    <div class="form-group">
                                        <label for="reminder_time">Time:</label>
                                        <input type="time" id="reminder_time" name="reminder_time" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Repeat on:</label>
                                        <div class="days-checkbox-group">
                                            <?php 
                                            $days = [
                                                1 => 'Monday',
                                                2 => 'Tuesday',
                                                3 => 'Wednesday',
                                                4 => 'Thursday',
                                                5 => 'Friday',
                                                6 => 'Saturday',
                                                7 => 'Sunday'
                                            ];
                                            foreach ($days as $value => $label): 
                                            ?>
                                                <div class="day-checkbox">
                                                    <input type="checkbox" id="day_<?php echo $value; ?>" 
                                                           name="days[]" value="<?php echo $value; ?>"
                                                           <?php echo in_array($value, [1,2,3,4,5,6,7]) ? 'checked' : ''; ?>>
                                                    <label for="day_<?php echo $value; ?>"><?php echo substr($label, 0, 3); ?></label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="form-actions">
                                        <button type="button" class="btn btn-secondary close-modal-btn">Cancel</button>
                                        <button type="submit" name="save_reminder" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Save Reminder
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Set default start date to today
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('start_date').value = today;
            
            // Modal functionality
            const modal = document.getElementById('reminderModal');
            const addReminderBtns = document.querySelectorAll('.add-reminder-btn');
            const closeModalBtn = document.querySelector('.close-modal');
            const closeModalBtns = document.querySelectorAll('.close-modal-btn');
            const medicationNameSpan = document.getElementById('medicationName');
            const medicationIdInput = document.getElementById('medicationId');
            
            // Open modal when clicking Add Reminder button
            addReminderBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const medicationId = this.getAttribute('data-medication-id');
                    const medicationName = this.getAttribute('data-medication-name');
                    
                    medicationNameSpan.textContent = medicationName;
                    medicationIdInput.value = medicationId;
                    
                    // Set default time to next hour
                    const now = new Date();
                    const nextHour = new Date(now.getTime() + 60 * 60 * 1000);
                    const hours = String(nextHour.getHours()).padStart(2, '0');
                    const minutes = String(nextHour.getMinutes()).padStart(2, '0');
                    document.getElementById('reminder_time').value = `${hours}:${minutes}`;
                    
                    // Show modal
                    modal.style.display = 'flex';
                    document.body.style.overflow = 'hidden'; // Prevent scrolling
                });
            });
            
            // Close modal when clicking the X button
            closeModalBtn.addEventListener('click', closeModal);
            
            // Close modal when clicking Cancel button
            closeModalBtns.forEach(btn => {
                btn.addEventListener('click', closeModal);
            });
            
            // Close modal when clicking outside the modal content
            window.addEventListener('click', function(event) {
                if (event.target === modal) {
                    closeModal();
                }
            });
            
            // Form validation
            const reminderForm = document.getElementById('reminderForm');
            if (reminderForm) {
                reminderForm.addEventListener('submit', function(e) {
                    const checkboxes = this.querySelectorAll('input[type="checkbox"]:checked');
                    if (checkboxes.length === 0) {
                        e.preventDefault();
                        alert('Please select at least one day for the reminder.');
                    }
                });
            }
            
            // Phone number formatting
            const phoneInput = document.getElementById('phone_number');
            if (phoneInput) {
                phoneInput.addEventListener('input', function(e) {
                    // Only allow numbers, +, and backspace
                    this.value = this.value.replace(/[^0-9+]/g, '');
                });
            }
            
            function closeModal() {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto'; // Re-enable scrolling
            }
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('start_date').value = today;
        });
    </script>
</body>
</html>
