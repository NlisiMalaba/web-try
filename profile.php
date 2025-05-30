<?php
session_start();
require_once 'includes/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/profile_pics/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
        $new_filename = 'user_' . $user_id . '_' . time() . '.' . $file_extension;
        $target_file = $upload_dir . $new_filename;
        
        // Check if file is an actual image
        $check = getimagesize($_FILES['profile_picture']['tmp_name']);
        if ($check !== false) {
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
                // Update database with new profile picture path
                $stmt = $pdo->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                if ($stmt->execute([$new_filename, $user_id])) {
                    $success_message = 'Profile picture updated successfully.';
                    $_SESSION['profile_image'] = $new_filename;
                } else {
                    $error_message = 'Error updating profile picture in database.';
                }
            } else {
                $error_message = 'Error uploading file.';
            }
        } else {
            $error_message = 'File is not a valid image.';
        }
    }
    
    // Handle profile information update
    if (isset($_POST['update_profile'])) {
        $first_name = $_POST['first_name'] ?? '';
        $last_name = $_POST['last_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $date_of_birth = $_POST['date_of_birth'] ?? '';
        $gender = $_POST['gender'] ?? '';
        $blood_type = $_POST['blood_type'] ?? '';
        $address = $_POST['address'] ?? '';
        
        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, date_of_birth = ?, gender = ?, blood_type = ?, address = ? WHERE id = ?");
        if ($stmt->execute([$first_name, $last_name, $email, $phone, $date_of_birth, $gender, $blood_type, $address, $user_id])) {
            $success_message = $success_message ? $success_message . ' Profile information updated successfully.' : 'Profile information updated successfully.';
            // Update session variables
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            $_SESSION['email'] = $email;
        } else {
            $error_message = $error_message ? $error_message . ' Error updating profile information.' : 'Error updating profile information.';
        }
    }
    
    // Handle password change
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error_message = $error_message ? $error_message . ' All password fields are required.' : 'All password fields are required.';
        } elseif ($new_password !== $confirm_password) {
            $error_message = $error_message ? $error_message . ' New passwords do not match.' : 'New passwords do not match.';
        } else {
            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($current_password, $user['password'])) {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                if ($stmt->execute([$hashed_password, $user_id])) {
                    $success_message = $success_message ? $success_message . ' Password updated successfully.' : 'Password updated successfully.';
                } else {
                    $error_message = $error_message ? $error_message . ' Error updating password.' : 'Error updating password.';
                }
            } else {
                $error_message = $error_message ? $error_message . ' Current password is incorrect.' : 'Current password is incorrect.';
            }
        }
    }
}

// Get current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: logout.php');
    exit();
}

// Set default values if not set
$user['profile_image'] = $user['profile_image'] ?? '';
$user['first_name'] = $user['first_name'] ?? '';
$user['last_name'] = $user['last_name'] ?? '';
$user['email'] = $user['email'] ?? '';
$user['phone'] = $user['phone'] ?? '';
$user['date_of_birth'] = $user['date_of_birth'] ?? '';
$user['gender'] = $user['gender'] ?? '';
$user['blood_type'] = $user['blood_type'] ?? '';
$user['address'] = $user['address'] ?? '';

// Include header
$page_title = 'My Profile - HealthAssist Pro';
include 'includes/header.php';
?>

<div class="dashboard-container">
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">My Profile</h1>
            </div>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($success_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <img src="<?php echo !empty($user['profile_image']) ? 'uploads/profile_pics/' . htmlspecialchars($user['profile_image']) : 'https://ui-avatars.com/api/?name=' . urlencode($user['first_name'] . '+' . $user['last_name']) . '&size=200&background=random'; ?>" 
                                     alt="Profile Picture" 
                                     class="rounded-circle img-thumbnail" 
                                     style="width: 150px; height: 150px; object-fit: cover;">
                            </div>
                            <h4 class="mb-1"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h4>
                            <p class="text-muted mb-3"><?php echo ucfirst(htmlspecialchars($user['role'] ?? 'User')); ?></p>
                            
                            <form action="profile.php" method="post" enctype="multipart/form-data" class="mb-3">
                                <div class="mb-3">
                                    <label for="profile_picture" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-camera me-1"></i> Change Photo
                                    </label>
                                    <input type="file" name="profile_picture" id="profile_picture" class="d-none" accept="image/*" onchange="this.form.submit()">
                                </div>
                            </form>
                            
                            <div class="d-flex flex-column">
                                <div class="mb-2">
                                    <i class="fas fa-envelope me-2 text-muted"></i>
                                    <span class="text-muted"><?php echo htmlspecialchars($user['email']); ?></span>
                                </div>
                                <?php if (!empty($user['phone'])): ?>
                                <div class="mb-2">
                                    <i class="fas fa-phone me-2 text-muted"></i>
                                    <span class="text-muted"><?php echo htmlspecialchars($user['phone']); ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($user['date_of_birth'])): ?>
                                <div class="mb-2">
                                    <i class="fas fa-birthday-cake me-2 text-muted"></i>
                                    <span class="text-muted"><?php echo date('F j, Y', strtotime($user['date_of_birth'])); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Personal Information</h5>
                        </div>
                        <div class="card-body">
                            <form action="profile.php" method="post">
                                <div class="row mb-3">
                                    <div class="col-md-6 mb-3">
                                        <label for="first_name" class="form-label">First Name</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="last_name" class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-4 mb-3">
                                        <label for="date_of_birth" class="form-label">Date of Birth</label>
                                        <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" value="<?php echo htmlspecialchars($user['date_of_birth']); ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="gender" class="form-label">Gender</label>
                                        <select class="form-select" id="gender" name="gender">
                                            <option value="" <?php echo empty($user['gender']) ? 'selected' : ''; ?>>Select Gender</option>
                                            <option value="male" <?php echo $user['gender'] === 'male' ? 'selected' : ''; ?>>Male</option>
                                            <option value="female" <?php echo $user['gender'] === 'female' ? 'selected' : ''; ?>>Female</option>
                                            <option value="other" <?php echo $user['gender'] === 'other' ? 'selected' : ''; ?>>Other</option>
                                            <option value="prefer_not_to_say" <?php echo $user['gender'] === 'prefer_not_to_say' ? 'selected' : ''; ?>>Prefer not to say</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="blood_type" class="form-label">Blood Type</label>
                                        <select class="form-select" id="blood_type" name="blood_type">
                                            <option value="" <?php echo empty($user['blood_type']) ? 'selected' : ''; ?>>Select Blood Type</option>
                                            <option value="A+" <?php echo $user['blood_type'] === 'A+' ? 'selected' : ''; ?>>A+</option>
                                            <option value="A-" <?php echo $user['blood_type'] === 'A-' ? 'selected' : ''; ?>>A-</option>
                                            <option value="B+" <?php echo $user['blood_type'] === 'B+' ? 'selected' : ''; ?>>B+</option>
                                            <option value="B-" <?php echo $user['blood_type'] === 'B-' ? 'selected' : ''; ?>>B-</option>
                                            <option value="AB+" <?php echo $user['blood_type'] === 'AB+' ? 'selected' : ''; ?>>AB+</option>
                                            <option value="AB-" <?php echo $user['blood_type'] === 'AB-' ? 'selected' : ''; ?>>AB-</option>
                                            <option value="O+" <?php echo $user['blood_type'] === 'O+' ? 'selected' : ''; ?>>O+</option>
                                            <option value="O-" <?php echo $user['blood_type'] === 'O-' ? 'selected' : ''; ?>>O-</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="address" class="form-label">Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="2"><?php echo htmlspecialchars($user['address']); ?></textarea>
                                </div>
                                
                                <div class="text-end">
                                    <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Change Password</h5>
                        </div>
                        <div class="card-body">
                            <form action="profile.php" method="post">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                <div class="text-end">
                                    <button type="submit" name="change_password" class="btn btn-primary">Update Password</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include 'includes/footer.php'; ?>

<script>
// Show password toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    // Add password toggle functionality
    const passwordFields = document.querySelectorAll('input[type="password"]');
    passwordFields.forEach(field => {
        const wrapper = document.createElement('div');
        wrapper.classList.add('input-group');
        
        const toggleBtn = document.createElement('button');
        toggleBtn.type = 'button';
        toggleBtn.classList.add('btn', 'btn-outline-secondary');
        toggleBtn.innerHTML = '<i class="fas fa-eye"></i>';
        toggleBtn.addEventListener('click', function() {
            const type = field.getAttribute('type') === 'password' ? 'text' : 'password';
            field.setAttribute('type', type);
            this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });
        
        // Wrap the input and button in the input group
        field.parentNode.insertBefore(wrapper, field);
        wrapper.appendChild(field);
        wrapper.appendChild(toggleBtn);
    });
    
    // Preview image before upload
    const profilePicInput = document.getElementById('profile_picture');
    if (profilePicInput) {
        profilePicInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.querySelector('.img-thumbnail');
                    if (img) {
                        img.src = e.target.result;
                    }
                }
                reader.readAsDataURL(file);
            }
        });
    }
});
</script>
