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
                        
                        <?php if (empty($medications)): ?>
                            <div class="no-medications">
                                <i class="fas fa-pills" style="font-size: 2rem; margin-bottom: 1rem; color: #cbd5e1;"></i>
                                <p>No medications added yet. Add your first medication to get started.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($medications as $med): ?>
                                <div class="medication-card">
                                    <div class="medication-header">
                                        <h3 class="medication-name"><?php echo htmlspecialchars($med['name']); ?></h3>
                                        <span class="medication-dosage"><?php echo htmlspecialchars($med['dosage']); ?></span>
                                    </div>
                                    
                                    <div class="medication-meta">
                                        <span><i class="far fa-clock"></i> <?php echo htmlspecialchars($med['frequency']); ?></span>
                                        <?php if (!empty($med['prescribed_by'])): ?>
                                            <span><i class="fas fa-user-md"></i> <?php echo htmlspecialchars($med['prescribed_by']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if (!empty($med['notes'])): ?>
                                        <div class="medication-notes">
                                            <strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($med['notes'])); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="medication-actions">
                                        <button class="btn btn-edit">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <form action="medications.php" method="POST" style="display: inline;">
                                            <input type="hidden" name="medication_id" value="<?php echo $med['id']; ?>">
                                            <button type="submit" name="delete_medication" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this medication?');">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Set default start date to today
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('start_date').value = today;
        });
    </script>
</body>
</html>
