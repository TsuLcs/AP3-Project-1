<?php
$page_title = "User Management";
include '../includes/head.php';
require_once '../data/dbconfig.php';

// Check access - Only Super Admin can access
if (!isset($_SESSION['user_is_superadmin']) || !$_SESSION['user_is_superadmin']) {
    header("Location: ../pages/login.php");
    exit();
}

$action = $_GET['action'] ?? 'list';
$message = '';

// Handle role assignment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_role'])) {
    $user_id = $_POST['user_id'];
    $role_type = $_POST['role_type'];
    
    try {
        $pdo->beginTransaction();
        
        // First, clear all role references in USER table before deleting role records
        $clear_roles = $pdo->prepare("UPDATE USER SET PAT_ID = NULL, STAFF_ID = NULL, DOC_ID = NULL, USER_IS_SUPERADMIN = FALSE WHERE USER_ID = ?");
        $clear_roles->execute([$user_id]);
        
        // Now safely delete any existing role records (foreign key constraints are cleared)
        $delete_staff = $pdo->prepare("DELETE FROM STAFF WHERE STAFF_ID = ?");
        $delete_staff->execute([$user_id]);
        
        $delete_doctor = $pdo->prepare("DELETE FROM DOCTOR WHERE DOC_ID = ?");
        $delete_doctor->execute([$user_id]);
        
        $delete_patient = $pdo->prepare("DELETE FROM PATIENT WHERE PAT_ID = ?");
        $delete_patient->execute([$user_id]);
        
        // Now assign the new role
        if ($role_type === 'staff') {
            // Get user details to populate staff record
            $user_stmt = $pdo->prepare("SELECT USER_NAME FROM USER WHERE USER_ID = ?");
            $user_stmt->execute([$user_id]);
            $user_data = $user_stmt->fetch();
            
            // Create staff record
            $staff_stmt = $pdo->prepare("INSERT INTO STAFF (STAFF_ID, STAFF_FIRST_NAME, STAFF_LAST_NAME, STAFF_EMAIL, STAFF_POSITION) VALUES (?, ?, ?, ?, ?)");
            $staff_stmt->execute([
                $user_id,
                'Staff', // Default first name
                'Member', // Default last name
                $user_data['USER_NAME'] . '@medicare.com', // Default email
                'Staff Member' // Default position
            ]);
            
            // Update user role - set STAFF_ID
            $update_stmt = $pdo->prepare("UPDATE USER SET STAFF_ID = ? WHERE USER_ID = ?");
            $update_stmt->execute([$user_id, $user_id]);
            
        } elseif ($role_type === 'doctor') {
            // Get user details to populate doctor record
            $user_stmt = $pdo->prepare("SELECT USER_NAME FROM USER WHERE USER_ID = ?");
            $user_stmt->execute([$user_id]);
            $user_data = $user_stmt->fetch();
            
            // Create doctor record
            $doctor_stmt = $pdo->prepare("INSERT INTO DOCTOR (DOC_ID, DOC_FIRST_NAME, DOC_LAST_NAME, DOC_CONTACT_NUM, DOC_EMAIL) VALUES (?, ?, ?, ?, ?)");
            $doctor_stmt->execute([
                $user_id,
                'Doctor', // Default first name
                'Name', // Default last name
                '000-000-0000', // Default contact
                $user_data['USER_NAME'] . '@medicare.com' // Default email
            ]);
            
            // Update user role - set DOC_ID
            $update_stmt = $pdo->prepare("UPDATE USER SET DOC_ID = ? WHERE USER_ID = ?");
            $update_stmt->execute([$user_id, $user_id]);
            
        } elseif ($role_type === 'patient') {
            // Get user details to populate patient record
            $user_stmt = $pdo->prepare("SELECT USER_NAME FROM USER WHERE USER_ID = ?");
            $user_stmt->execute([$user_id]);
            $user_data = $user_stmt->fetch();
            
            // Create patient record
            $patient_stmt = $pdo->prepare("INSERT INTO PATIENT (PAT_ID, PAT_FIRST_NAME, PAT_LAST_NAME, PAT_EMAIL, PAT_CONTACT_NUM) VALUES (?, ?, ?, ?, ?)");
            $patient_stmt->execute([
                $user_id,
                'Patient', // Default first name
                'User', // Default last name
                $user_data['USER_NAME'] . '@medicare.com', // Default email
                '000-000-0000' // Default contact
            ]);
            
            // Update user role - set PAT_ID
            $update_stmt = $pdo->prepare("UPDATE USER SET PAT_ID = ? WHERE USER_ID = ?");
            $update_stmt->execute([$user_id, $user_id]);
        }
        
        $pdo->commit();
        $success_message = "Role assigned successfully!";
        
        // Refresh to show updated data
        header("Location: user_manage.php");
        exit();
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error_message = "Error assigning role: " . $e->getMessage();
    }
}

// Handle role removal (demote to basic user - NO ROLE)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['remove_role'])) {
    $user_id = $_POST['user_id'];
    
    try {
        $pdo->beginTransaction();
        
        // First, clear all role references in USER table
        $clear_roles = $pdo->prepare("UPDATE USER SET PAT_ID = NULL, STAFF_ID = NULL, DOC_ID = NULL, USER_IS_SUPERADMIN = FALSE WHERE USER_ID = ?");
        $clear_roles->execute([$user_id]);
        
        // Now safely delete role records (foreign key constraints are cleared)
        $delete_staff = $pdo->prepare("DELETE FROM STAFF WHERE STAFF_ID = ?");
        $delete_staff->execute([$user_id]);
        
        $delete_doctor = $pdo->prepare("DELETE FROM DOCTOR WHERE DOC_ID = ?");
        $delete_doctor->execute([$user_id]);
        
        $delete_patient = $pdo->prepare("DELETE FROM PATIENT WHERE PAT_ID = ?");
        $delete_patient->execute([$user_id]);
        
        $pdo->commit();
        $success_message = "User role removed successfully! User is now a basic user.";
        
        // Refresh to show updated data
        header("Location: user_manage.php");
        exit();
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error_message = "Error removing role: " . $e->getMessage();
    }
}

// Get all users with their roles
$users = $pdo->query("
    SELECT u.USER_ID, u.USER_NAME, u.USER_IS_SUPERADMIN, u.PAT_ID, u.STAFF_ID, u.DOC_ID,
           p.PAT_FIRST_NAME, p.PAT_LAST_NAME, p.PAT_EMAIL, p.PAT_CONTACT_NUM,
           s.STAFF_FIRST_NAME, s.STAFF_LAST_NAME, s.STAFF_POSITION, s.STAFF_EMAIL,
           d.DOC_FIRST_NAME, d.DOC_LAST_NAME, d.DOC_EMAIL,
           CASE 
               WHEN u.USER_IS_SUPERADMIN = 1 THEN 'Super Admin'
               WHEN u.STAFF_ID IS NOT NULL THEN 'Staff'
               WHEN u.DOC_ID IS NOT NULL THEN 'Doctor'
               WHEN u.PAT_ID IS NOT NULL THEN 'Patient'
               ELSE 'Basic User'
           END as user_role
    FROM USER u
    LEFT JOIN PATIENT p ON u.PAT_ID = p.PAT_ID
    LEFT JOIN STAFF s ON u.STAFF_ID = s.STAFF_ID
    LEFT JOIN DOCTOR d ON u.DOC_ID = d.DOC_ID
    ORDER BY u.USER_ID
")->fetchAll();
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
                        <i class="fas fa-user-shield me-2"></i>User Management
                    </h1>
                </div>

                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h6 class="m-0 font-weight-bold">
                            <i class="fas fa-users me-2"></i>All Users - Role Assignment
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="bg-light">
                                    <tr>
                                        <th>User ID</th>
                                        <th>Username</th>
                                        <th>Current Role</th>
                                        <th>Details</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?php echo $user['USER_ID']; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($user['USER_NAME']); ?></strong>
                                                <?php if ($user['USER_IS_SUPERADMIN']): ?>
                                                    <span class="badge bg-danger ms-1">Admin</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php
                                                    switch($user['user_role']) {
                                                        case 'Super Admin': echo 'danger'; break;
                                                        case 'Staff': echo 'warning'; break;
                                                        case 'Doctor': echo 'info'; break;
                                                        case 'Patient': echo 'success'; break;
                                                        default: echo 'secondary';
                                                    }
                                                ?>">
                                                    <?php echo $user['user_role']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php
                                                if ($user['user_role'] === 'Patient' && $user['PAT_FIRST_NAME']) {
                                                    echo htmlspecialchars($user['PAT_FIRST_NAME'] . ' ' . $user['PAT_LAST_NAME']);
                                                    echo '<br><small class="text-muted">' . htmlspecialchars($user['PAT_EMAIL']) . '</small>';
                                                } elseif ($user['user_role'] === 'Staff' && $user['STAFF_FIRST_NAME']) {
                                                    echo htmlspecialchars($user['STAFF_FIRST_NAME'] . ' ' . $user['STAFF_LAST_NAME']);
                                                    if ($user['STAFF_POSITION']) {
                                                        echo '<br><small class="text-muted">' . htmlspecialchars($user['STAFF_POSITION']) . '</small>';
                                                    }
                                                } elseif ($user['user_role'] === 'Doctor' && $user['DOC_FIRST_NAME']) {
                                                    echo 'Dr. ' . htmlspecialchars($user['DOC_FIRST_NAME'] . ' ' . $user['DOC_LAST_NAME']);
                                                    echo '<br><small class="text-muted">' . htmlspecialchars($user['DOC_EMAIL']) . '</small>';
                                                } else {
                                                    echo '<span class="text-muted">No role details</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php if (!$user['USER_IS_SUPERADMIN']): ?>
                                                    <div class="btn-group">
                                                        <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                                                            <i class="fas fa-user-cog me-1"></i>Manage Role
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <!-- Assign as Patient -->
                                                            <?php if ($user['user_role'] !== 'Patient'): ?>
                                                                <li>
                                                                    <form method="POST" class="d-inline">
                                                                        <input type="hidden" name="user_id" value="<?php echo $user['USER_ID']; ?>">
                                                                        <input type="hidden" name="role_type" value="patient">
                                                                        <button type="submit" name="assign_role" class="dropdown-item">
                                                                            <i class="fas fa-user-injured me-2 text-success"></i>Assign as Patient
                                                                        </button>
                                                                    </form>
                                                                </li>
                                                            <?php endif; ?>
                                                            
                                                            <!-- Assign as Staff -->
                                                            <?php if ($user['user_role'] !== 'Staff'): ?>
                                                                <li>
                                                                    <form method="POST" class="d-inline">
                                                                        <input type="hidden" name="user_id" value="<?php echo $user['USER_ID']; ?>">
                                                                        <input type="hidden" name="role_type" value="staff">
                                                                        <button type="submit" name="assign_role" class="dropdown-item">
                                                                            <i class="fas fa-users-cog me-2 text-warning"></i>Assign as Staff
                                                                        </button>
                                                                    </form>
                                                                </li>
                                                            <?php endif; ?>
                                                            
                                                            <!-- Assign as Doctor -->
                                                            <?php if ($user['user_role'] !== 'Doctor'): ?>
                                                                <li>
                                                                    <form method="POST" class="d-inline">
                                                                        <input type="hidden" name="user_id" value="<?php echo $user['USER_ID']; ?>">
                                                                        <input type="hidden" name="role_type" value="doctor">
                                                                        <button type="submit" name="assign_role" class="dropdown-item">
                                                                            <i class="fas fa-user-md me-2 text-info"></i>Assign as Doctor
                                                                        </button>
                                                                    </form>
                                                                </li>
                                                            <?php endif; ?>
                                                            
                                                            <!-- Remove All Roles -->
                                                            <?php if ($user['user_role'] !== 'Basic User'): ?>
                                                                <li><hr class="dropdown-divider"></li>
                                                                <li>
                                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to remove all roles from this user? They will become a basic user and all role-specific data will be deleted.');">
                                                                        <input type="hidden" name="user_id" value="<?php echo $user['USER_ID']; ?>">
                                                                        <button type="submit" name="remove_role" class="dropdown-item text-secondary">
                                                                            <i class="fas fa-user-times me-2"></i>Remove All Roles
                                                                        </button>
                                                                    </form>
                                                                </li>
                                                            <?php endif; ?>
                                                        </ul>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">Admin User</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Role Management Guide -->
                <div class="card mt-4">
                    <div class="card-header bg-light">
                        <h6 class="m-0 font-weight-bold">
                            <i class="fas fa-info-circle me-2"></i>Role Management Guide
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                        <i class="fas fa-user-injured"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Assign as Patient</h6>
                                        <small class="text-muted">Creates patient record and assigns role</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="bg-warning text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                        <i class="fas fa-users-cog"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Assign as Staff</h6>
                                        <small class="text-muted">Creates staff record and assigns role</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="bg-info text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                        <i class="fas fa-user-md"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Assign as Doctor</h6>
                                        <small class="text-muted">Creates doctor record and assigns role</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                        <i class="fas fa-user-times"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Remove Roles</h6>
                                        <small class="text-muted">User becomes basic user</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info mt-3">
                            <strong><i class="fas fa-lightbulb me-2"></i>How Role Assignment Works:</strong><br>
                            - <strong>Patient</strong>: Creates record in PATIENT table with default details<br>
                            - <strong>Staff</strong>: Creates record in STAFF table with default details<br>
                            - <strong>Doctor</strong>: Creates record in DOCTOR table with default details<br>
                            - <strong>Remove Roles</strong>: User becomes basic user and all role-specific data is deleted<br>
                            - <strong>Role Switching</strong>: Previous role records are automatically cleaned up
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 