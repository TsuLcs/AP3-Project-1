<?php
$page_title = "Doctor Management";
include '../includes/head.php';
require_once '../data/dbconfig.php';
require_once '../includes/auth.php';

// Check access - Super Admin and Staff
checkAccess(['superadmin', 'staff']);

// Handle add doctor
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_doctor'])) {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $middle_name = $_POST['middle_name'] ?? '';
    $contact_num = $_POST['contact_num'];
    $email = $_POST['email'];
    $spec_id = $_POST['spec_id'] ?? null;

    try {
        // Check for duplicate contact number or email
        $check_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM doctor WHERE DOC_CONTACT_NUM = ? OR DOC_EMAIL = ?");
        $check_stmt->execute([$contact_num, $email]);
        $exists = $check_stmt->fetch(PDO::FETCH_ASSOC)['count'];

        if ($exists > 0) {
            $_SESSION['error'] = "Doctor with this contact number or email already exists.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO doctor (DOC_FIRST_NAME, DOC_LAST_NAME, DOC_MIDDLE_NAME, DOC_CONTACT_NUM, DOC_EMAIL, SPEC_ID) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$first_name, $last_name, $middle_name, $contact_num, $email, $spec_id]);
            $_SESSION['success'] = "Doctor added successfully!";
            header("Location: doctor_manage.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error adding doctor: " . $e->getMessage();
    }
}

// Handle update doctor
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_doctor'])) {
    $doc_id = $_POST['doc_id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $middle_name = $_POST['middle_name'] ?? '';
    $contact_num = $_POST['contact_num'];
    $email = $_POST['email'];
    $spec_id = $_POST['spec_id'] ?? null;

    try {
        // Check for duplicate contact number or email (excluding current doctor)
        $check_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM doctor WHERE (DOC_CONTACT_NUM = ? OR DOC_EMAIL = ?) AND DOC_ID != ?");
        $check_stmt->execute([$contact_num, $email, $doc_id]);
        $exists = $check_stmt->fetch(PDO::FETCH_ASSOC)['count'];

        if ($exists > 0) {
            $_SESSION['error'] = "Another doctor with this contact number or email already exists.";
        } else {
            $stmt = $pdo->prepare("UPDATE doctor SET DOC_FIRST_NAME = ?, DOC_LAST_NAME = ?, DOC_MIDDLE_NAME = ?, DOC_CONTACT_NUM = ?, DOC_EMAIL = ?, SPEC_ID = ? WHERE DOC_ID = ?");
            $stmt->execute([$first_name, $last_name, $middle_name, $contact_num, $email, $spec_id, $doc_id]);
            $_SESSION['success'] = "Doctor updated successfully!";
            header("Location: doctor_manage.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error updating doctor: " . $e->getMessage();
    }
}

// Handle delete doctor
if (isset($_GET['delete_id'])) {
    $doc_id = $_GET['delete_id'];

    try {
        // Check if doctor has appointments
        $check_appointments = $pdo->prepare("SELECT COUNT(*) as count FROM appointment WHERE DOC_ID = ?");
        $check_appointments->execute([$doc_id]);
        $appointment_count = $check_appointments->fetch(PDO::FETCH_ASSOC)['count'];

        if ($appointment_count > 0) {
            $_SESSION['error'] = "Cannot delete doctor: Doctor has {$appointment_count} appointment(s). Please reassign appointments first.";
        } else {
            // Check if doctor has user account
            $check_user = $pdo->prepare("SELECT COUNT(*) as count FROM user WHERE DOC_ID = ?");
            $check_user->execute([$doc_id]);
            $user_count = $check_user->fetch(PDO::FETCH_ASSOC)['count'];

            if ($user_count > 0) {
                $_SESSION['error'] = "Cannot delete doctor: Doctor has a user account. Please remove doctor role from User Management first.";
            } else {
                // Delete doctor schedules first
                $delete_schedules = $pdo->prepare("DELETE FROM schedule WHERE DOC_ID = ?");
                $delete_schedules->execute([$doc_id]);

                $stmt = $pdo->prepare("DELETE FROM doctor WHERE DOC_ID = ?");
                $stmt->execute([$doc_id]);
                $_SESSION['success'] = "Doctor deleted successfully!";
            }
        }
        header("Location: doctor_manage.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error deleting doctor: " . $e->getMessage();
        header("Location: doctor_manage.php");
        exit();
    }
}

// Get all doctors with their specializations
$doctors = $pdo->query("
    SELECT d.*, s.SPEC_NAME,
           (SELECT COUNT(*) FROM appointment WHERE DOC_ID = d.DOC_ID) as appointment_count,
           u.USER_ID as has_user_account
    FROM doctor d
    LEFT JOIN specialization s ON d.SPEC_ID = s.SPEC_ID
    LEFT JOIN user u ON d.DOC_ID = u.DOC_ID
    ORDER BY d.DOC_FIRST_NAME, d.DOC_LAST_NAME
")->fetchAll();

// Get specializations for dropdown
$specializations = $pdo->query("SELECT * FROM specialization ORDER BY SPEC_NAME")->fetchAll();
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
                        <i class="fas fa-user-md me-2"></i>Doctor Management
                    </h1>
                    <?php if (isSuperAdmin()): ?>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDoctorModal">
                            <i class="fas fa-plus me-1"></i>Add Doctor
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
                            <i class="fas fa-user-md me-2"></i>All Doctors
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Doctor ID</th>
                                        <th>Name</th>
                                        <th>Specialization</th>
                                        <th>Contact</th>
                                        <th>Email</th>
                                        <th>Appointments</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($doctors as $doctor): ?>
                                        <tr>
                                            <td><?php echo $doctor['DOC_ID']; ?></td>
                                            <td>
                                                <strong>Dr. <?php echo htmlspecialchars($doctor['DOC_FIRST_NAME'] . ' ' . $doctor['DOC_LAST_NAME']); ?></strong>
                                                <?php if ($doctor['DOC_MIDDLE_NAME']): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($doctor['DOC_MIDDLE_NAME']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($doctor['SPEC_NAME']): ?>
                                                    <span class="badge bg-info"><?php echo htmlspecialchars($doctor['SPEC_NAME']); ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Not Specified</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($doctor['DOC_CONTACT_NUM']); ?></td>
                                            <td><?php echo htmlspecialchars($doctor['DOC_EMAIL']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $doctor['appointment_count'] > 0 ? 'warning' : 'secondary'; ?>">
                                                    <?php echo $doctor['appointment_count']; ?> appointment(s)
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($doctor['has_user_account']): ?>
                                                    <span class="badge bg-success">Has Account</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">No Account</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary edit-doctor-btn"
                                                            data-doctor-id="<?php echo $doctor['DOC_ID']; ?>"
                                                            data-first-name="<?php echo htmlspecialchars($doctor['DOC_FIRST_NAME']); ?>"
                                                            data-last-name="<?php echo htmlspecialchars($doctor['DOC_LAST_NAME']); ?>"
                                                            data-middle-name="<?php echo htmlspecialchars($doctor['DOC_MIDDLE_NAME'] ?? ''); ?>"
                                                            data-contact="<?php echo htmlspecialchars($doctor['DOC_CONTACT_NUM']); ?>"
                                                            data-email="<?php echo htmlspecialchars($doctor['DOC_EMAIL']); ?>"
                                                            data-spec-id="<?php echo $doctor['SPEC_ID'] ?? ''; ?>">
                                                        <i class="fas fa-edit me-1"></i>Edit
                                                    </button>
                                                    <?php if (isSuperAdmin() && $doctor['appointment_count'] == 0 && !$doctor['has_user_account']): ?>
                                                        <a href="doctor_manage.php?delete_id=<?php echo $doctor['DOC_ID']; ?>"
                                                           class="btn btn-sm btn-outline-danger"
                                                           onclick="return confirm('Are you sure you want to delete this doctor?')">
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

    <!-- Add Doctor Modal -->
    <?php if (isSuperAdmin()): ?>
    <div class="modal fade" id="addDoctorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Doctor</h5>
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
                        <div class="mb-3">
                            <label class="form-label">Middle Name</label>
                            <input type="text" class="form-control" name="middle_name">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contact Number *</label>
                                <input type="text" class="form-control" name="contact_num" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email Address *</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Specialization</label>
                            <select class="form-control" name="spec_id">
                                <option value="">Select Specialization</option>
                                <?php foreach ($specializations as $spec): ?>
                                    <option value="<?php echo $spec['SPEC_ID']; ?>"><?php echo htmlspecialchars($spec['SPEC_NAME']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_doctor" class="btn btn-primary">Add Doctor</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Edit Doctor Modal -->
    <div class="modal fade" id="editDoctorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Doctor Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="doc_id" id="edit_doc_id">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name *</label>
                                <input type="text" class="form-control" name="first_name" id="edit_doc_first_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name *</label>
                                <input type="text" class="form-control" name="last_name" id="edit_doc_last_name" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Middle Name</label>
                            <input type="text" class="form-control" name="middle_name" id="edit_doc_middle_name">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contact Number *</label>
                                <input type="text" class="form-control" name="contact_num" id="edit_doc_contact_num" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email Address *</label>
                                <input type="email" class="form-control" name="email" id="edit_doc_email" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Specialization</label>
                            <select class="form-control" name="spec_id" id="edit_spec_id">
                                <option value="">Select Specialization</option>
                                <?php foreach ($specializations as $spec): ?>
                                    <option value="<?php echo $spec['SPEC_ID']; ?>"><?php echo htmlspecialchars($spec['SPEC_NAME']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_doctor" class="btn btn-primary">Update Doctor</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editButtons = document.querySelectorAll('.edit-doctor-btn');
            const editDoctorModal = new bootstrap.Modal(document.getElementById('editDoctorModal'));

            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const doctorId = this.getAttribute('data-doctor-id');
                    const firstName = this.getAttribute('data-first-name');
                    const lastName = this.getAttribute('data-last-name');
                    const middleName = this.getAttribute('data-middle-name');
                    const contactNum = this.getAttribute('data-contact');
                    const email = this.getAttribute('data-email');
                    const specId = this.getAttribute('data-spec-id');

                    // Populate modal fields
                    document.getElementById('edit_doc_id').value = doctorId;
                    document.getElementById('edit_doc_first_name').value = firstName;
                    document.getElementById('edit_doc_last_name').value = lastName;
                    document.getElementById('edit_doc_middle_name').value = middleName;
                    document.getElementById('edit_doc_contact_num').value = contactNum;
                    document.getElementById('edit_doc_email').value = email;
                    document.getElementById('edit_spec_id').value = specId;

                    // Show modal
                    editDoctorModal.show();
                });
            });
        });
    </script>
</body>
</html>
