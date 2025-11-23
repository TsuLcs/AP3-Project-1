<?php
$page_title = "Patient Management";
include '../includes/head.php';
require_once '../data/dbconfig.php';
require_once '../includes/auth.php';

// Check access - Super Admin and Staff
checkAccess(['superadmin', 'staff']);

$action = $_GET['action'] ?? 'list';

// Handle add patient
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_patient'])) {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $middle_name = $_POST['middle_name'] ?? '';
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $contact_num = $_POST['contact_num'];
    $email = $_POST['email'];
    $address = $_POST['address'];

    try {
        // Check for duplicate contact number or email
        $check_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM patient WHERE PAT_CONTACT_NUM = ? OR PAT_EMAIL = ?");
        $check_stmt->execute([$contact_num, $email]);
        $exists = $check_stmt->fetch(PDO::FETCH_ASSOC)['count'];

        if ($exists > 0) {
            $_SESSION['error'] = "Patient with this contact number or email already exists.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO patient (PAT_FIRST_NAME, PAT_LAST_NAME, PAT_MIDDLE_NAME, PAT_DOB, PAT_GENDER, PAT_CONTACT_NUM, PAT_EMAIL, PAT_ADDRESS) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$first_name, $last_name, $middle_name, $dob, $gender, $contact_num, $email, $address]);
            $_SESSION['success'] = "Patient added successfully!";
            header("Location: patient_manage.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error adding patient: " . $e->getMessage();
    }
}

// Handle update patient
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_patient'])) {
    $pat_id = $_POST['pat_id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $middle_name = $_POST['middle_name'] ?? '';
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $contact_num = $_POST['contact_num'];
    $email = $_POST['email'];
    $address = $_POST['address'];

    try {
        // Check for duplicate contact number or email (excluding current patient)
        $check_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM patient WHERE (PAT_CONTACT_NUM = ? OR PAT_EMAIL = ?) AND PAT_ID != ?");
        $check_stmt->execute([$contact_num, $email, $pat_id]);
        $exists = $check_stmt->fetch(PDO::FETCH_ASSOC)['count'];

        if ($exists > 0) {
            $_SESSION['error'] = "Another patient with this contact number or email already exists.";
        } else {
            $stmt = $pdo->prepare("UPDATE patient SET PAT_FIRST_NAME = ?, PAT_LAST_NAME = ?, PAT_MIDDLE_NAME = ?, PAT_DOB = ?, PAT_GENDER = ?, PAT_CONTACT_NUM = ?, PAT_EMAIL = ?, PAT_ADDRESS = ? WHERE PAT_ID = ?");
            $stmt->execute([$first_name, $last_name, $middle_name, $dob, $gender, $contact_num, $email, $address, $pat_id]);
            $_SESSION['success'] = "Patient updated successfully!";
            header("Location: patient_manage.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error updating patient: " . $e->getMessage();
    }
}

// Handle delete patient
if (isset($_GET['delete_id'])) {
    $pat_id = $_GET['delete_id'];

    try {
        // Check if patient has appointments
        $check_appointments = $pdo->prepare("SELECT COUNT(*) as count FROM appointment WHERE PAT_ID = ?");
        $check_appointments->execute([$pat_id]);
        $appointment_count = $check_appointments->fetch(PDO::FETCH_ASSOC)['count'];

        if ($appointment_count > 0) {
            $_SESSION['error'] = "Cannot delete patient: Patient has {$appointment_count} appointment(s). Please delete appointments first.";
        } else {
            // Check if patient has user account
            $check_user = $pdo->prepare("SELECT COUNT(*) as count FROM user WHERE PAT_ID = ?");
            $check_user->execute([$pat_id]);
            $user_count = $check_user->fetch(PDO::FETCH_ASSOC)['count'];

            if ($user_count > 0) {
                $_SESSION['error'] = "Cannot delete patient: Patient has a user account. Please remove patient role from User Management first.";
            } else {
                $stmt = $pdo->prepare("DELETE FROM patient WHERE PAT_ID = ?");
                $stmt->execute([$pat_id]);
                $_SESSION['success'] = "Patient deleted successfully!";
            }
        }
        header("Location: patient_manage.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error deleting patient: " . $e->getMessage();
        header("Location: patient_manage.php");
        exit();
    }
}

// Get all patients
$patients = $pdo->query("
    SELECT p.*,
           (SELECT COUNT(*) FROM appointment WHERE PAT_ID = p.PAT_ID) as appointment_count,
           u.USER_ID as has_user_account
    FROM patient p
    LEFT JOIN user u ON p.PAT_ID = u.PAT_ID
    ORDER BY p.PAT_FIRST_NAME, p.PAT_LAST_NAME
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
                        <i class="fas fa-user-injured me-2"></i>Patient Management
                    </h1>
                    <?php if (isSuperAdmin()): ?>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPatientModal">
                            <i class="fas fa-plus me-1"></i>Add Patient
                        </button>
                    <?php endif; ?>
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
                            <i class="fas fa-users me-2"></i>All Patients
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Patient ID</th>
                                        <th>Name</th>
                                        <th>Contact</th>
                                        <th>Email</th>
                                        <th>Gender</th>
                                        <th>Date of Birth</th>
                                        <th>Appointments</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($patients as $patient): ?>
                                        <tr>
                                            <td><?php echo $patient['PAT_ID']; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($patient['PAT_FIRST_NAME'] . ' ' . $patient['PAT_LAST_NAME']); ?></strong>
                                                <?php if ($patient['PAT_MIDDLE_NAME']): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($patient['PAT_MIDDLE_NAME']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($patient['PAT_CONTACT_NUM']); ?></td>
                                            <td><?php echo htmlspecialchars($patient['PAT_EMAIL']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $patient['PAT_GENDER'] == 'Male' ? 'primary' : ($patient['PAT_GENDER'] == 'Female' ? 'danger' : 'secondary'); ?>">
                                                    <?php echo $patient['PAT_GENDER']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($patient['PAT_DOB'])); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $patient['appointment_count'] > 0 ? 'warning' : 'secondary'; ?>">
                                                    <?php echo $patient['appointment_count']; ?> appointment(s)
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($patient['has_user_account']): ?>
                                                    <span class="badge bg-success">Has Account</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">No Account</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary edit-patient-btn"
                                                            data-patient-id="<?php echo $patient['PAT_ID']; ?>"
                                                            data-first-name="<?php echo htmlspecialchars($patient['PAT_FIRST_NAME']); ?>"
                                                            data-last-name="<?php echo htmlspecialchars($patient['PAT_LAST_NAME']); ?>"
                                                            data-middle-name="<?php echo htmlspecialchars($patient['PAT_MIDDLE_NAME'] ?? ''); ?>"
                                                            data-dob="<?php echo $patient['PAT_DOB']; ?>"
                                                            data-gender="<?php echo $patient['PAT_GENDER']; ?>"
                                                            data-contact="<?php echo htmlspecialchars($patient['PAT_CONTACT_NUM']); ?>"
                                                            data-email="<?php echo htmlspecialchars($patient['PAT_EMAIL']); ?>"
                                                            data-address="<?php echo htmlspecialchars($patient['PAT_ADDRESS'] ?? ''); ?>">
                                                        <i class="fas fa-edit me-1"></i>Edit
                                                    </button>
                                                    <?php if (isSuperAdmin() && $patient['appointment_count'] == 0 && !$patient['has_user_account']): ?>
                                                        <a href="patient_manage.php?delete_id=<?php echo $patient['PAT_ID']; ?>"
                                                           class="btn btn-sm btn-outline-danger"
                                                           onclick="return confirm('Are you sure you want to delete this patient?')">
                                                            <i class="fas fa-trash me-1"></i>Delete
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Patient Modal -->
    <?php if (isSuperAdmin()): ?>
    <div class="modal fade" id="addPatientModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Patient</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name *</label>
                                <input type="text" class="form-control" name="first_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name *</label>
                                <input type="text" class="form-control" name="last_name" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Middle Name</label>
                                <input type="text" class="form-control" name="middle_name">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date of Birth *</label>
                                <input type="date" class="form-control" name="dob" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Gender *</label>
                                <select class="form-control" name="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contact Number *</label>
                                <input type="text" class="form-control" name="contact_num" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email Address *</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_patient" class="btn btn-primary">Add Patient</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Edit Patient Modal -->
    <div class="modal fade" id="editPatientModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Patient Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="pat_id" id="edit_pat_id">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name *</label>
                                <input type="text" class="form-control" name="first_name" id="edit_first_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name *</label>
                                <input type="text" class="form-control" name="last_name" id="edit_last_name" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Middle Name</label>
                                <input type="text" class="form-control" name="middle_name" id="edit_middle_name">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date of Birth *</label>
                                <input type="date" class="form-control" name="dob" id="edit_dob" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Gender *</label>
                                <select class="form-control" name="gender" id="edit_gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contact Number *</label>
                                <input type="text" class="form-control" name="contact_num" id="edit_contact_num" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email Address *</label>
                            <input type="email" class="form-control" name="email" id="edit_email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" id="edit_address" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_patient" class="btn btn-primary">Update Patient</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editButtons = document.querySelectorAll('.edit-patient-btn');
            const editPatientModal = new bootstrap.Modal(document.getElementById('editPatientModal'));

            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const patientId = this.getAttribute('data-patient-id');
                    const firstName = this.getAttribute('data-first-name');
                    const lastName = this.getAttribute('data-last-name');
                    const middleName = this.getAttribute('data-middle-name');
                    const dob = this.getAttribute('data-dob');
                    const gender = this.getAttribute('data-gender');
                    const contactNum = this.getAttribute('data-contact');
                    const email = this.getAttribute('data-email');
                    const address = this.getAttribute('data-address');

                    // Populate modal fields
                    document.getElementById('edit_pat_id').value = patientId;
                    document.getElementById('edit_first_name').value = firstName;
                    document.getElementById('edit_last_name').value = lastName;
                    document.getElementById('edit_middle_name').value = middleName;
                    document.getElementById('edit_dob').value = dob;
                    document.getElementById('edit_gender').value = gender;
                    document.getElementById('edit_contact_num').value = contactNum;
                    document.getElementById('edit_email').value = email;
                    document.getElementById('edit_address').value = address;

                    // Show modal
                    editPatientModal.show();
                });
            });
        });
    </script>
</body>
</html>
