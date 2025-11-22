<?php
$page_title = "Specialization Management";
include '../includes/head.php';
require_once '../data/dbconfig.php';
require_once '../includes/auth.php';

// Check access - Super Admin and Staff
checkAccess(['superadmin', 'staff']);

// Handle add specialization
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_specialization'])) {
    $spec_name = $_POST['spec_name'];

    try {
        // Check if specialization already exists
        $check_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM specialization WHERE SPEC_NAME = ?");
        $check_stmt->execute([$spec_name]);
        $exists = $check_stmt->fetch(PDO::FETCH_ASSOC)['count'];

        if ($exists > 0) {
            $_SESSION['error'] = "Specialization '$spec_name' already exists.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO specialization (SPEC_NAME) VALUES (?)");
            $stmt->execute([$spec_name]);
            $_SESSION['success'] = "Specialization added successfully!";
            header("Location: specialization_manage.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error adding specialization: " . $e->getMessage();
    }
}

// Handle update specialization
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_specialization'])) {
    $spec_id = $_POST['spec_id'];
    $spec_name = $_POST['spec_name'];

    try {
        // Check if specialization already exists (excluding current one)
        $check_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM specialization WHERE SPEC_NAME = ? AND SPEC_ID != ?");
        $check_stmt->execute([$spec_name, $spec_id]);
        $exists = $check_stmt->fetch(PDO::FETCH_ASSOC)['count'];

        if ($exists > 0) {
            $_SESSION['error'] = "Specialization '$spec_name' already exists.";
        } else {
            $stmt = $pdo->prepare("UPDATE specialization SET SPEC_NAME = ? WHERE SPEC_ID = ?");
            $stmt->execute([$spec_name, $spec_id]);
            $_SESSION['success'] = "Specialization updated successfully!";
            header("Location: specialization_manage.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error updating specialization: " . $e->getMessage();
    }
}

// Handle delete specialization
if (isset($_GET['delete_id'])) {
    $spec_id = $_GET['delete_id'];

    try {
        // Check if specialization is being used by doctors
        $check_doctors = $pdo->prepare("SELECT COUNT(*) as count FROM doctor WHERE SPEC_ID = ?");
        $check_doctors->execute([$spec_id]);
        $doctor_count = $check_doctors->fetch(PDO::FETCH_ASSOC)['count'];

        if ($doctor_count > 0) {
            $_SESSION['error'] = "Cannot delete specialization: $doctor_count doctor(s) are associated with this specialization.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM specialization WHERE SPEC_ID = ?");
            $stmt->execute([$spec_id]);
            $_SESSION['success'] = "Specialization deleted successfully!";
        }
        header("Location: specialization_manage.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error deleting specialization: " . $e->getMessage();
        header("Location: specialization_manage.php");
        exit();
    }
}

// Get all specializations with doctor counts
$specializations = $pdo->query("
    SELECT s.*, COUNT(d.DOC_ID) as doctor_count
    FROM specialization s
    LEFT JOIN doctor d ON s.SPEC_ID = d.SPEC_ID
    GROUP BY s.SPEC_ID
    ORDER BY s.SPEC_NAME
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
                        <i class="fas fa-stethoscope me-2"></i>Specialization Management
                    </h1>
                    <?php if (isSuperAdmin()): ?>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSpecializationModal">
                            <i class="fas fa-plus me-1"></i>Add Specialization
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
                            <i class="fas fa-list me-2"></i>Medical Specializations
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="bg-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Specialization Name</th>
                                        <th>Number of Doctors</th>
                                        <th>Created Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($specializations as $spec): ?>
                                        <tr>
                                            <td><?php echo $spec['SPEC_ID']; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($spec['SPEC_NAME']); ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $spec['doctor_count'] > 0 ? 'success' : 'secondary'; ?>">
                                                    <?php echo $spec['doctor_count']; ?> doctor(s)
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($spec['SPEC_CREATED_AT'])); ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary edit-spec-btn"
                                                            data-spec-id="<?php echo $spec['SPEC_ID']; ?>"
                                                            data-spec-name="<?php echo htmlspecialchars($spec['SPEC_NAME']); ?>">
                                                        <i class="fas fa-edit me-1"></i>Edit
                                                    </button>
                                                    <?php if (isSuperAdmin() && $spec['doctor_count'] == 0): ?>
                                                        <a href="specialization_manage.php?delete_id=<?php echo $spec['SPEC_ID']; ?>"
                                                           class="btn btn-sm btn-outline-danger"
                                                           onclick="return confirm('Are you sure you want to delete this specialization?')">
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

    <!-- Add Specialization Modal -->
    <?php if (isSuperAdmin()): ?>
    <div class="modal fade" id="addSpecializationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Specialization</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Specialization Name *</label>
                            <input type="text" class="form-control" name="spec_name" required
                                   placeholder="e.g., Cardiology, Dermatology, Pediatrics">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_specialization" class="btn btn-primary">Add Specialization</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Edit Specialization Modal -->
    <div class="modal fade" id="editSpecializationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Specialization</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="spec_id" id="edit_spec_id">
                        <div class="mb-3">
                            <label class="form-label">Specialization Name *</label>
                            <input type="text" class="form-control" name="spec_name" id="edit_spec_name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_specialization" class="btn btn-primary">Update Specialization</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editButtons = document.querySelectorAll('.edit-spec-btn');
            const editModal = new bootstrap.Modal(document.getElementById('editSpecializationModal'));

            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const specId = this.getAttribute('data-spec-id');
                    const specName = this.getAttribute('data-spec-name');

                    document.getElementById('edit_spec_id').value = specId;
                    document.getElementById('edit_spec_name').value = specName;

                    editModal.show();
                });
            });
        });
    </script>
</body>
</html>
