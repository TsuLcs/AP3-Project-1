<?php
$page_title = "Staff Management";
include '../includes/head.php';
require_once '../data/dbconfig.php';
require_once '../includes/auth.php';

// Check access - Super Admin and Staff can access
if (!isset($_SESSION['user_is_superadmin']) && !isset($_SESSION['staff_id'])) {
    header("Location: ../pages/login.php");
    exit();
}

$action = $_GET['action'] ?? 'list';
$message = '';

// Handle search
$search_query = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_query = trim($_GET['search']);
}

// Handle staff details update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_staff'])) {
    $staff_id = $_POST['staff_id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $position = $_POST['position'];

    try {
        // Check for duplicate email (excluding current staff)
        $check_email = $pdo->prepare("SELECT COUNT(*) as count FROM staff WHERE STAFF_EMAIL = ? AND STAFF_ID != ?");
        $check_email->execute([$email, $staff_id]);
        $email_exists = $check_email->fetch(PDO::FETCH_ASSOC)['count'];

        if ($email_exists > 0) {
            $_SESSION['error'] = "Email address already exists for another staff member.";
        } else {
            $stmt = $pdo->prepare("UPDATE staff SET STAFF_FIRST_NAME = ?, STAFF_LAST_NAME = ?, STAFF_EMAIL = ?, STAFF_POSITION = ? WHERE STAFF_ID = ?");
            $stmt->execute([$first_name, $last_name, $email, $position, $staff_id]);
            $_SESSION['success'] = "Staff details updated successfully!";

            // Refresh the page to show updated data
            header("Location: staff_manage.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error updating staff: " . $e->getMessage();
    }
}

// Handle add new staff (Super Admin only)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_staff'])) {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $position = $_POST['position'];

    try {
        // Check for duplicate email
        $check_email = $pdo->prepare("SELECT COUNT(*) as count FROM staff WHERE STAFF_EMAIL = ?");
        $check_email->execute([$email]);
        $email_exists = $check_email->fetch(PDO::FETCH_ASSOC)['count'];

        if ($email_exists > 0) {
            $_SESSION['error'] = "Email address already exists for another staff member.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO staff (STAFF_FIRST_NAME, STAFF_LAST_NAME, STAFF_EMAIL, STAFF_POSITION) VALUES (?, ?, ?, ?)");
            $stmt->execute([$first_name, $last_name, $email, $position]);
            $_SESSION['success'] = "Staff member added successfully!";

            header("Location: staff_manage.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error adding staff: " . $e->getMessage();
    }
}

// Handle delete staff (Super Admin only)
if (isset($_GET['delete_id'])) {
    $staff_id = $_GET['delete_id'];

    try {
        // Check if staff has user account
        $check_user = $pdo->prepare("SELECT COUNT(*) as user_count FROM user WHERE STAFF_ID = ?");
        $check_user->execute([$staff_id]);
        $user_count = $check_user->fetch(PDO::FETCH_ASSOC)['user_count'];

        if ($user_count > 0) {
            $_SESSION['error'] = "Cannot delete staff member: This staff member has a user account. Please remove their staff role from User Management first.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM staff WHERE STAFF_ID = ?");
            $stmt->execute([$staff_id]);
            $_SESSION['success'] = "Staff member deleted successfully!";
        }

        header("Location: staff_manage.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error deleting staff: " . $e->getMessage();
        header("Location: staff_manage.php");
        exit();
    }
}

// Build query based on search
$query = "
    SELECT s.*, u.USER_ID, u.USER_NAME
    FROM staff s
    LEFT JOIN user u ON s.STAFF_ID = u.STAFF_ID
";

if (!empty($search_query)) {
    $query .= " WHERE s.STAFF_FIRST_NAME LIKE ? OR s.STAFF_LAST_NAME LIKE ? OR s.STAFF_EMAIL LIKE ?";
    $stmt = $pdo->prepare($query . " ORDER BY s.STAFF_FIRST_NAME, s.STAFF_LAST_NAME");
    $search_param = "%$search_query%";
    $stmt->execute([$search_param, $search_param, $search_param]);
} else {
    $stmt = $pdo->prepare($query . " ORDER BY s.STAFF_FIRST_NAME, s.STAFF_LAST_NAME");
    $stmt->execute();
}

$staff_members = $stmt->fetchAll();
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
                    <?php if (isset($_SESSION['user_is_superadmin']) && $_SESSION['user_is_superadmin']): ?>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStaffModal">
                            <i class="fas fa-plus me-1"></i>Add Staff
                        </button>
                    <?php endif; ?>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <!-- Search Bar -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-8">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="search" placeholder="Search staff by first name, last name, or email..." value="<?php echo htmlspecialchars($search_query); ?>">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fas fa-search me-1"></i>Search
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <?php if (!empty($search_query)): ?>
                                    <a href="staff_manage.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i>Clear Search
                                    </a>
                                    <span class="ms-2 text-muted">
                                        <?php echo count($staff_members); ?> result(s) for "<?php echo htmlspecialchars($search_query); ?>"
                                    </span>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    To assign staff roles to users, use <a href="user_manage.php" class="alert-link">User Management</a>
                </div>

                <div class="card shadow">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold">
                            <i class="fas fa-users me-2"></i>Staff Members
                            <?php if (!empty($search_query)): ?>
                                <small class="ms-2">Search Results</small>
                            <?php endif; ?>
                        </h6>
                        <span class="badge bg-light text-dark">
                            Total: <?php echo count($staff_members); ?> staff member(s)
                        </span>
                    </div>
                    <div class="card-body">
                        <?php if (count($staff_members) > 0): ?>
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
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($staff_members as $staff): ?>
                                            <tr>
                                                <td><?php echo $staff['STAFF_ID']; ?></td>
                                                <td>
                                                    <?php if ($staff['USER_NAME']): ?>
                                                        <?php echo htmlspecialchars($staff['USER_NAME']); ?>
                                                        <span class="badge bg-success ms-1">Has Account</span>
                                                    <?php else: ?>
                                                        <span class="text-muted">No User Account</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($staff['STAFF_FIRST_NAME']); ?></td>
                                                <td><?php echo htmlspecialchars($staff['STAFF_LAST_NAME']); ?></td>
                                                <td><?php echo htmlspecialchars($staff['STAFF_EMAIL']); ?></td>
                                                <td>
                                                    <?php if ($staff['STAFF_POSITION']): ?>
                                                        <span class="badge bg-info"><?php echo htmlspecialchars($staff['STAFF_POSITION']); ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Not Specified</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($staff['USER_NAME']): ?>
                                                        <span class="badge bg-success">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <button type="button" class="btn btn-sm btn-outline-primary edit-staff-btn"
                                                                data-staff-id="<?php echo $staff['STAFF_ID']; ?>"
                                                                data-first-name="<?php echo htmlspecialchars($staff['STAFF_FIRST_NAME']); ?>"
                                                                data-last-name="<?php echo htmlspecialchars($staff['STAFF_LAST_NAME']); ?>"
                                                                data-email="<?php echo htmlspecialchars($staff['STAFF_EMAIL']); ?>"
                                                                data-position="<?php echo htmlspecialchars($staff['STAFF_POSITION'] ?? ''); ?>">
                                                            <i class="fas fa-edit me-1"></i>Edit
                                                        </button>
                                                        <?php if (isset($_SESSION['user_is_superadmin']) && $_SESSION['user_is_superadmin'] && !$staff['USER_NAME']): ?>
                                                            <a href="staff_manage.php?delete_id=<?php echo $staff['STAFF_ID']; ?>"
                                                               class="btn btn-sm btn-outline-danger"
                                                               onclick="return confirm('Are you sure you want to delete this staff member?')">
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
                        <?php else: ?>
                            <div class="text-center py-4">
                                <?php if (!empty($search_query)): ?>
                                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No Staff Members Found</h5>
                                    <p class="text-muted">No staff members found matching "<?php echo htmlspecialchars($search_query); ?>"</p>
                                    <a href="staff_manage.php" class="btn btn-primary">
                                        <i class="fas fa-list me-1"></i>View All Staff
                                    </a>
                                <?php else: ?>
                                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No Staff Members</h5>
                                    <p class="text-muted">No staff members have been added yet.</p>
                                    <?php if (isset($_SESSION['user_is_superadmin']) && $_SESSION['user_is_superadmin']): ?>
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStaffModal">
                                            <i class="fas fa-plus me-1"></i>Add First Staff Member
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Edit Staff Modal -->
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
                            <input type="text" class="form-control" name="position" id="modal_position" placeholder="e.g., Receptionist, Nurse, Administrator">
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

    <!-- Add Staff Modal -->
    <?php if (isset($_SESSION['user_is_superadmin']) && $_SESSION['user_is_superadmin']): ?>
    <div class="modal fade" id="addStaffModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="addStaffForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Staff Member</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">First Name *</label>
                            <input type="text" class="form-control" name="first_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Last Name *</label>
                            <input type="text" class="form-control" name="last_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Position</label>
                            <input type="text" class="form-control" name="position" placeholder="e.g., Receptionist, Nurse, Administrator">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_staff" class="btn btn-primary">Add Staff</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

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
