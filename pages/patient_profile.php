<?php
$page_title = "My Profile";
include '../includes/head.php';
require_once '../data/dbconfig.php';

// Check if user is patient
if (!isset($_SESSION['pat_id'])) {
    header("Location: login.php");
    exit();
}

$patient_id = $_SESSION['pat_id'];
$success_message = '';
$error_message = '';

// Get patient data
$stmt = $pdo->prepare("SELECT * FROM PATIENT WHERE PAT_ID = ?");
$stmt->execute([$patient_id]);
$patient = $stmt->fetch();

if (!$patient) {
    die("Patient not found!");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $last_name = trim($_POST['last_name']);
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $contact_num = trim($_POST['contact_num']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);

    try {
        // Update patient information
        $update_stmt = $pdo->prepare("
            UPDATE PATIENT 
            SET PAT_FIRST_NAME = ?, PAT_MIDDLE_NAME = ?, PAT_LAST_NAME = ?, 
                PAT_DOB = ?, PAT_GENDER = ?, PAT_CONTACT_NUM = ?, 
                PAT_EMAIL = ?, PAT_ADDRESS = ?, PAT_UPDATED_AT = CURRENT_TIMESTAMP 
            WHERE PAT_ID = ?
        ");
        
        $update_stmt->execute([
            $first_name, $middle_name, $last_name, $dob, $gender, 
            $contact_num, $email, $address, $patient_id
        ]);
        
        $success_message = "Profile updated successfully!";
        
        // Refresh patient data
        $stmt->execute([$patient_id]);
        $patient = $stmt->fetch();
        
    } catch (PDOException $e) {
        $error_message = "Error updating profile: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - MediCare Clinic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-user-edit me-2"></i>My Profile
                    </h1>
                </div>

                <!-- Success/Error Messages -->
                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-8">
                        <div class="card shadow">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-user me-2"></i>Personal Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" id="profileForm">
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                                   value="<?php echo htmlspecialchars($patient['PAT_FIRST_NAME']); ?>" required>
                                        </div>
                                        
                                        <div class="col-md-4 mb-3">
                                            <label for="middle_name" class="form-label">Middle Name</label>
                                            <input type="text" class="form-control" id="middle_name" name="middle_name" 
                                                   value="<?php echo htmlspecialchars($patient['PAT_MIDDLE_NAME'] ?? ''); ?>">
                                        </div>
                                        
                                        <div class="col-md-4 mb-3">
                                            <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                                   value="<?php echo htmlspecialchars($patient['PAT_LAST_NAME']); ?>" required>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="dob" class="form-label">Date of Birth</label>
                                            <input type="date" class="form-control" id="dob" name="dob" 
                                                   value="<?php echo htmlspecialchars($patient['PAT_DOB'] ?? ''); ?>">
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="gender" class="form-label">Gender</label>
                                            <select class="form-select" id="gender" name="gender">
                                                <option value="">Select Gender</option>
                                                <option value="Male" <?php echo ($patient['PAT_GENDER'] ?? '') == 'Male' ? 'selected' : ''; ?>>Male</option>
                                                <option value="Female" <?php echo ($patient['PAT_GENDER'] ?? '') == 'Female' ? 'selected' : ''; ?>>Female</option>
                                                <option value="Other" <?php echo ($patient['PAT_GENDER'] ?? '') == 'Other' ? 'selected' : ''; ?>>Other</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="contact_num" class="form-label">Contact Number <span class="text-danger">*</span></label>
                                            <input type="tel" class="form-control" id="contact_num" name="contact_num" 
                                                   value="<?php echo htmlspecialchars($patient['PAT_CONTACT_NUM']); ?>" required>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?php echo htmlspecialchars($patient['PAT_EMAIL']); ?>" required>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="address" class="form-label">Address</label>
                                        <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($patient['PAT_ADDRESS'] ?? ''); ?></textarea>
                                    </div>

                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <button type="reset" class="btn btn-secondary me-md-2">
                                            <i class="fas fa-undo me-1"></i>Reset
                                        </button>
                                        <button type="submit" name="update_profile" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i>Update Profile
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <!-- Profile Summary Card -->
                        <div class="card shadow">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-info-circle me-2"></i>Profile Summary
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="text-center mb-4">
                                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" 
                                         style="width: 80px; height: 80px;">
                                        <i class="fas fa-user text-white fa-2x"></i>
                                    </div>
                                    <h5><?php echo htmlspecialchars($patient['PAT_FIRST_NAME'] . ' ' . $patient['PAT_LAST_NAME']); ?></h5>
                                    <p class="text-muted">Patient</p>
                                </div>
                                
                                <div class="profile-details">
                                    <div class="d-flex justify-content-between border-bottom py-2">
                                        <span class="fw-bold">Patient ID:</span>
                                        <span class="text-muted"><?php echo $patient['PAT_ID']; ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between border-bottom py-2">
                                        <span class="fw-bold">Member Since:</span>
                                        <span class="text-muted"><?php echo date('M j, Y', strtotime($patient['PAT_CREATED_AT'])); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between border-bottom py-2">
                                        <span class="fw-bold">Last Updated:</span>
                                        <span class="text-muted"><?php echo date('M j, Y', strtotime($patient['PAT_UPDATED_AT'])); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="card shadow mt-4">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0">
                                    <i class="fas fa-bolt me-2"></i>Quick Actions
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="patient_appointments.php" class="btn btn-outline-primary text-start">
                                        <i class="fas fa-list-alt me-2"></i>View My Appointments
                                    </a>
                                    <a href="../modules/appointment_manage.php" class="btn btn-outline-success text-start">
                                        <i class="fas fa-calendar-plus me-2"></i>Book New Appointment
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const contact = document.getElementById('contact_num').value;
            
            // Basic email validation
            if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                alert('Please enter a valid email address.');
                e.preventDefault();
                return;
            }
            
            // Basic phone validation (adjust pattern as needed)
            if (!contact.match(/^[\d\s\-\+\(\)]{10,}$/)) {
                alert('Please enter a valid contact number.');
                e.preventDefault();
                return;
            }
        });
    </script>
</body>
</html>