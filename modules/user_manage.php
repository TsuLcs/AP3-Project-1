<?php
$page_title = "User Management";
include '../includes/head.php';
require_once '../data/dbconfig.php';
require_once '../includes/auth.php';

// Check access - Only Super Admin can access
if (!isset($_SESSION['user_is_superadmin']) || !$_SESSION['user_is_superadmin']) {
    header("Location: ../pages/login.php");
    exit();
}

$action = $_GET['action'] ?? 'list';
$message = '';

// Function to generate unique contact number
function generateUniqueContactNumber($pdo, $type) {
    $base_number = '0900';
    $max_attempts = 100;

    for ($i = 0; $i < $max_attempts; $i++) {
        $random_suffix = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $contact_number = $base_number . $random_suffix;

        // Check if contact number exists in the appropriate table
        if ($type === 'doctor') {
            $check_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM doctor WHERE DOC_CONTACT_NUM = ?");
        } elseif ($type === 'patient') {
            $check_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM patient WHERE PAT_CONTACT_NUM = ?");
        } else {
            // For staff, contact number doesn't need to be unique based on your schema
            return $contact_number;
        }

        $check_stmt->execute([$contact_number]);
        $result = $check_stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] == 0) {
            return $contact_number;
        }
    }

    // If all attempts fail, generate based on timestamp
    return $base_number . str_pad(time() % 1000000, 6, '0', STR_PAD_LEFT);
}

// Function to generate unique email
function generateUniqueEmail($pdo, $username, $role_type) {
    $max_attempts = 100;

    // Different email patterns for different roles
    $email_patterns = [
        'staff' => [
            $username . '.staff@medicare.com',
            $username . '.staff' . time() . '@medicare.com',
            'staff.' . $username . '.' . mt_rand(1000, 9999) . '@medicare.com'
        ],
        'doctor' => [
            $username . '.doctor@medicare.com',
            'dr.' . $username . '@medicare.com',
            $username . '.dr' . mt_rand(1000, 9999) . '@medicare.com'
        ],
        'patient' => [
            $username . '.patient@medicare.com',
            $username . '.pat@medicare.com',
            'patient.' . $username . mt_rand(1000, 9999) . '@medicare.com'
        ]
    ];

    // Try each pattern until we find a unique one
    foreach ($email_patterns[$role_type] as $email_pattern) {
        if ($role_type === 'staff') {
            $check_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM staff WHERE STAFF_EMAIL = ?");
        } elseif ($role_type === 'doctor') {
            $check_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM doctor WHERE DOC_EMAIL = ?");
        } elseif ($role_type === 'patient') {
            $check_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM patient WHERE PAT_EMAIL = ?");
        }

        $check_stmt->execute([$email_pattern]);
        $result = $check_stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] == 0) {
            return $email_pattern;
        }
    }

    // If all patterns fail, generate completely random email
    return $username . '.' . $role_type . '.' . uniqid() . '@medicare.com';
}

// Handle role assignment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_role'])) {
    $user_id = $_POST['user_id'];
    $role_type = $_POST['role_type'];

    try {
        $pdo->beginTransaction();

        // First, clear all role references in USER table
        $clear_roles = $pdo->prepare("UPDATE user SET PAT_ID = NULL, STAFF_ID = NULL, DOC_ID = NULL, USER_IS_SUPERADMIN = FALSE WHERE USER_ID = ?");
        $clear_roles->execute([$user_id]);

        // Get user details for creating role records
        $user_stmt = $pdo->prepare("SELECT USER_NAME FROM user WHERE USER_ID = ?");
        $user_stmt->execute([$user_id]);
        $user_data = $user_stmt->fetch();
        $username = $user_data['USER_NAME'];

        // Now assign the new role
        if ($role_type === 'staff') {
            // Generate unique email for staff
            $staff_email = generateUniqueEmail($pdo, $username, 'staff');

            // Create staff record (let it auto-increment)
            $staff_stmt = $pdo->prepare("INSERT INTO staff (STAFF_FIRST_NAME, STAFF_LAST_NAME, STAFF_EMAIL, STAFF_POSITION) VALUES (?, ?, ?, ?)");
            $staff_stmt->execute([
                'Staff', // Default first name
                'Member', // Default last name
                $staff_email,
                'Staff Member'
            ]);

            $staff_id = $pdo->lastInsertId();

            // Update user with STAFF_ID
            $update_stmt = $pdo->prepare("UPDATE user SET STAFF_ID = ? WHERE USER_ID = ?");
            $update_stmt->execute([$staff_id, $user_id]);

        } elseif ($role_type === 'doctor') {
            // Generate unique contact number and email
            $contact_number = generateUniqueContactNumber($pdo, 'doctor');
            $doctor_email = generateUniqueEmail($pdo, $username, 'doctor');

            // Create doctor record (let it auto-increment)
            $doctor_stmt = $pdo->prepare("INSERT INTO doctor (DOC_FIRST_NAME, DOC_LAST_NAME, DOC_CONTACT_NUM, DOC_EMAIL) VALUES (?, ?, ?, ?)");
            $doctor_stmt->execute([
                'Doctor',
                'Name',
                $contact_number,
                $doctor_email
            ]);

            $doc_id = $pdo->lastInsertId();

            // Update user with DOC_ID
            $update_stmt = $pdo->prepare("UPDATE user SET DOC_ID = ? WHERE USER_ID = ?");
            $update_stmt->execute([$doc_id, $user_id]);

        } elseif ($role_type === 'patient') {
            // Generate unique contact number and email
            $contact_number = generateUniqueContactNumber($pdo, 'patient');
            $patient_email = generateUniqueEmail($pdo, $username, 'patient');

            // Create patient record (let it auto-increment)
            $patient_stmt = $pdo->prepare("INSERT INTO patient (PAT_FIRST_NAME, PAT_LAST_NAME, PAT_EMAIL, PAT_CONTACT_NUM, PAT_GENDER, PAT_DOB) VALUES (?, ?, ?, ?, ?, ?)");
            $patient_stmt->execute([
                'Patient',
                'User',
                $patient_email,
                $contact_number,
                'Other', // Default gender
                date('Y-m-d') // Default DOB (today)
            ]);

            $pat_id = $pdo->lastInsertId();

            // Update user with PAT_ID
            $update_stmt = $pdo->prepare("UPDATE user SET PAT_ID = ? WHERE USER_ID = ?");
            $update_stmt->execute([$pat_id, $user_id]);
        }

        $pdo->commit();
        $_SESSION['success'] = "Role assigned successfully!";
        header("Location: user_manage.php");
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error assigning role: " . $e->getMessage();
    }
}

// Handle role removal (demote to basic user)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['remove_role'])) {
    $user_id = $_POST['user_id'];

    try {
        $pdo->beginTransaction();

        // Get current user role data
        $user_stmt = $pdo->prepare("SELECT PAT_ID, STAFF_ID, DOC_ID FROM user WHERE USER_ID = ?");
        $user_stmt->execute([$user_id]);
        $user_data = $user_stmt->fetch();

        // Check if user has patient role and has appointments
        if ($user_data['PAT_ID']) {
            $check_appointments = $pdo->prepare("SELECT COUNT(*) as appointment_count FROM appointment WHERE PAT_ID = ?");
            $check_appointments->execute([$user_data['PAT_ID']]);
            $appointment_count = $check_appointments->fetch(PDO::FETCH_ASSOC)['appointment_count'];

            if ($appointment_count > 0) {
                throw new Exception("Cannot remove patient role: This patient has {$appointment_count} appointment(s) in the system. Please delete or reassign the appointments first.");
            }
        }

        // FIRST: Clear all role references in USER table (this removes foreign key constraints)
        $clear_roles = $pdo->prepare("UPDATE user SET PAT_ID = NULL, STAFF_ID = NULL, DOC_ID = NULL, USER_IS_SUPERADMIN = FALSE WHERE USER_ID = ?");
        $clear_roles->execute([$user_id]);

        // SECOND: Now safely delete role records (foreign key constraints are cleared)
        if ($user_data['PAT_ID']) {
            $delete_patient = $pdo->prepare("DELETE FROM patient WHERE PAT_ID = ?");
            $delete_patient->execute([$user_data['PAT_ID']]);
        }

        if ($user_data['STAFF_ID']) {
            $delete_staff = $pdo->prepare("DELETE FROM staff WHERE STAFF_ID = ?");
            $delete_staff->execute([$user_data['STAFF_ID']]);
        }

        if ($user_data['DOC_ID']) {
            $delete_doctor = $pdo->prepare("DELETE FROM doctor WHERE DOC_ID = ?");
            $delete_doctor->execute([$user_data['DOC_ID']]);
        }

        $pdo->commit();
        $_SESSION['success'] = "User role removed successfully! User is now a basic user.";
        header("Location: user_manage.php");
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Database error removing role: " . $e->getMessage();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = $e->getMessage();
    }
}

// Get all users with their roles
$users = $pdo->query("
    SELECT u.USER_ID, u.USER_NAME, u.USER_IS_SUPERADMIN, u.PAT_ID, u.STAFF_ID, u.DOC_ID,
           p.PAT_FIRST_NAME, p.PAT_LAST_NAME, p.PAT_EMAIL, p.PAT_CONTACT_NUM, p.PAT_GENDER, p.PAT_DOB,
           s.STAFF_FIRST_NAME, s.STAFF_LAST_NAME, s.STAFF_POSITION, s.STAFF_EMAIL,
           d.DOC_FIRST_NAME, d.DOC_LAST_NAME, d.DOC_EMAIL, d.DOC_CONTACT_NUM,
           CASE
               WHEN u.USER_IS_SUPERADMIN = 1 THEN 'Super Admin'
               WHEN u.STAFF_ID IS NOT NULL THEN 'Staff'
               WHEN u.DOC_ID IS NOT NULL THEN 'Doctor'
               WHEN u.PAT_ID IS NOT NULL THEN 'Patient'
               ELSE 'Basic User'
           END as user_role
    FROM user u
    LEFT JOIN patient p ON u.PAT_ID = p.PAT_ID
    LEFT JOIN staff s ON u.STAFF_ID = s.STAFF_ID
    LEFT JOIN doctor d ON u.DOC_ID = d.DOC_ID
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

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
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
                                                    echo '<br><small class="text-muted">' . htmlspecialchars($user['PAT_CONTACT_NUM']) . '</small>';

                                                    // Show appointment count for patients
                                                    $appt_count_stmt = $pdo->prepare("SELECT COUNT(*) as appt_count FROM appointment WHERE PAT_ID = ?");
                                                    $appt_count_stmt->execute([$user['PAT_ID']]);
                                                    $appt_count = $appt_count_stmt->fetch(PDO::FETCH_ASSOC)['appt_count'];
                                                    if ($appt_count > 0) {
                                                        echo '<br><small class="text-warning"><i class="fas fa-calendar-check me-1"></i>' . $appt_count . ' appointment(s)</small>';
                                                    }
                                                } elseif ($user['user_role'] === 'Staff' && $user['STAFF_FIRST_NAME']) {
                                                    echo htmlspecialchars($user['STAFF_FIRST_NAME'] . ' ' . $user['STAFF_LAST_NAME']);
                                                    echo '<br><small class="text-muted">' . htmlspecialchars($user['STAFF_EMAIL']) . '</small>';
                                                    if ($user['STAFF_POSITION']) {
                                                        echo '<br><small class="text-muted">' . htmlspecialchars($user['STAFF_POSITION']) . '</small>';
                                                    }
                                                } elseif ($user['user_role'] === 'Doctor' && $user['DOC_FIRST_NAME']) {
                                                    echo 'Dr. ' . htmlspecialchars($user['DOC_FIRST_NAME'] . ' ' . $user['DOC_LAST_NAME']);
                                                    echo '<br><small class="text-muted">' . htmlspecialchars($user['DOC_EMAIL']) . '</small>';
                                                    echo '<br><small class="text-muted">' . htmlspecialchars($user['DOC_CONTACT_NUM']) . '</small>';
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
                                                                        <button type="submit" name="remove_role" class="dropdown-item text-danger">
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
                                        <small class="text-muted">Creates patient record with unique contact/email</small>
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
                                        <small class="text-muted">Creates staff record with unique email</small>
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
                                        <small class="text-muted">Creates doctor record with unique contact/email</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="bg-danger text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
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
                            - <strong>Patient</strong>: Creates record in PATIENT table with unique contact number and email<br>
                            - <strong>Staff</strong>: Creates record in STAFF table with unique email<br>
                            - <strong>Doctor</strong>: Creates record in DOCTOR table with unique contact number and email<br>
                            - <strong>Remove Roles</strong>: User becomes basic user and all role-specific data is deleted<br>
                            - <strong>Important</strong>: Cannot remove patient role if the patient has existing appointments
                        </div>

                        <div class="alert alert-success">
                            <strong><i class="fas fa-check-circle me-2"></i>Unique Email Generation:</strong><br>
                            The system automatically generates unique emails for each role to avoid database conflicts. Each user gets a distinct email address based on their username and role type.
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
