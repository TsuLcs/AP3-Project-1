<?php
$page_title = "Staff Management";
include '../includes/head.php';
require_once '../data/dbconfig.php';

// Check access - Super Admin and Staff can access
if (!isset($_SESSION['user_is_superadmin']) && !isset($_SESSION['staff_id'])) {
    header("Location: ../pages/login.php");
    exit();
}

$action = $_GET['action'] ?? 'list';
$message = '';

// Handle staff details update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_staff'])) {
    $staff_id = $_POST['staff_id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $position = $_POST['position'];
    
    try {
        $stmt = $pdo->prepare("UPDATE STAFF SET STAFF_FIRST_NAME = ?, STAFF_LAST_NAME = ?, STAFF_EMAIL = ?, STAFF_POSITION = ? WHERE STAFF_ID = ?");
        $stmt->execute([$first_name, $last_name, $email, $position, $staff_id]);
        $success_message = "Staff details updated successfully!";
        
        // Refresh the page to show updated data
        header("Location: staff_manage.php");
        exit();
    } catch (PDOException $e) {
        $error_message = "Error updating staff: " . $e->getMessage();
    }
}

// Get all staff members
$staff_members = $pdo->query("
    SELECT s.*, u.USER_NAME 
    FROM STAFF s 
    JOIN USER u ON s.STAFF_ID = u.USER_ID 
    ORDER BY s.STAFF_FIRST_NAME, s.STAFF_LAST_NAME
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
                        <i class="fas fa-users-cog me-2"></i>Staff Management
                    </h1>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        To assign staff roles, use <a href="user_manage.php" class="alert-link">User Management</a>
                    </div>
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
                            <i class="fas fa-users me-2"></i>Staff Members
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="staffTable">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Staff ID</th>
                                        <th>Username</th>
                                        <th>First Name</th>
                                        <th>Last Name</th>
                                        <th>Email</th>
                                        <th>Position</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($staff_members as $staff): ?>
                                        <tr>
                                            <td><?php echo $staff['STAFF_ID']; ?></td>
                                            <td><?php echo htmlspecialchars($staff['USER_NAME']); ?></td>
                                            <td><?php echo htmlspecialchars($staff['STAFF_FIRST_NAME']); ?></td>
                                            <td><?php echo htmlspecialchars($staff['STAFF_LAST_NAME']); ?></td>
                                            <td><?php echo htmlspecialchars($staff['STAFF_EMAIL']); ?></td>
                                            <td><?php echo htmlspecialchars($staff['STAFF_POSITION']); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-primary edit-staff-btn" 
                                                        data-staff-id="<?php echo $staff['STAFF_ID']; ?>"
                                                        data-first-name="<?php echo htmlspecialchars($staff['STAFF_FIRST_NAME']); ?>"
                                                        data-last-name="<?php echo htmlspecialchars($staff['STAFF_LAST_NAME']); ?>"
                                                        data-email="<?php echo htmlspecialchars($staff['STAFF_EMAIL']); ?>"
                                                        data-position="<?php echo htmlspecialchars($staff['STAFF_POSITION']); ?>">
                                                    <i class="fas fa-edit me-1"></i>Edit
                                                </button>
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

    <!-- Single Edit Staff Modal -->
    <div class="modal fade" id="editStaffModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="editStaffForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Staff Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="staff_id" id="modal_staff_id">
                        <div class="mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="first_name" id="modal_first_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="last_name" id="modal_last_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="modal_email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Position</label>
                            <input type="text" class="form-control" name="position" id="modal_position">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_staff" class="btn btn-primary">Update Staff</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editButtons = document.querySelectorAll('.edit-staff-btn');
            const editStaffModal = new bootstrap.Modal(document.getElementById('editStaffModal'));
            
            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const staffId = this.getAttribute('data-staff-id');
                    const firstName = this.getAttribute('data-first-name');
                    const lastName = this.getAttribute('data-last-name');
                    const email = this.getAttribute('data-email');
                    const position = this.getAttribute('data-position');
                    
                    // Populate modal fields
                    document.getElementById('modal_staff_id').value = staffId;
                    document.getElementById('modal_first_name').value = firstName;
                    document.getElementById('modal_last_name').value = lastName;
                    document.getElementById('modal_email').value = email;
                    document.getElementById('modal_position').value = position;
                    
                    // Show modal
                    editStaffModal.show();
                });
            });
            
            // Clear modal data when hidden to prevent stale data
            document.getElementById('editStaffModal').addEventListener('hidden.bs.modal', function () {
                document.getElementById('editStaffForm').reset();
            });
        });
    </script>
</body>
</html>