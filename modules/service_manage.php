<?php
$page_title = "Service Management";
include '../includes/head.php';
require_once '../data/dbconfig.php';
require_once '../includes/auth.php';

// Check access - Super Admin and Staff
checkAccess(['superadmin', 'staff']);

// Handle add service
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_service'])) {
    $serv_name = $_POST['serv_name'];
    $serv_description = $_POST['serv_description'];
    $serv_price = $_POST['serv_price'];

    try {
        // Check if service already exists
        $check_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM service WHERE SERV_NAME = ?");
        $check_stmt->execute([$serv_name]);
        $exists = $check_stmt->fetch(PDO::FETCH_ASSOC)['count'];

        if ($exists > 0) {
            $_SESSION['error'] = "Service '$serv_name' already exists.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO service (SERV_NAME, SERV_DESCRIPTION, SERV_PRICE) VALUES (?, ?, ?)");
            $stmt->execute([$serv_name, $serv_description, $serv_price]);
            $_SESSION['success'] = "Service added successfully!";
            header("Location: service_manage.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error adding service: " . $e->getMessage();
    }
}

// Handle update service
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_service'])) {
    $serv_id = $_POST['serv_id'];
    $serv_name = $_POST['serv_name'];
    $serv_description = $_POST['serv_description'];
    $serv_price = $_POST['serv_price'];

    try {
        // Check if service already exists (excluding current one)
        $check_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM service WHERE SERV_NAME = ? AND SERV_ID != ?");
        $check_stmt->execute([$serv_name, $serv_id]);
        $exists = $check_stmt->fetch(PDO::FETCH_ASSOC)['count'];

        if ($exists > 0) {
            $_SESSION['error'] = "Service '$serv_name' already exists.";
        } else {
            $stmt = $pdo->prepare("UPDATE service SET SERV_NAME = ?, SERV_DESCRIPTION = ?, SERV_PRICE = ? WHERE SERV_ID = ?");
            $stmt->execute([$serv_name, $serv_description, $serv_price, $serv_id]);
            $_SESSION['success'] = "Service updated successfully!";
            header("Location: service_manage.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error updating service: " . $e->getMessage();
    }
}

// Handle delete service
if (isset($_GET['delete_id'])) {
    $serv_id = $_GET['delete_id'];

    try {
        // Check if service is being used by appointments
        $check_appointments = $pdo->prepare("SELECT COUNT(*) as count FROM appointment WHERE SERV_ID = ?");
        $check_appointments->execute([$serv_id]);
        $appointment_count = $check_appointments->fetch(PDO::FETCH_ASSOC)['count'];

        if ($appointment_count > 0) {
            $_SESSION['error'] = "Cannot delete service: $appointment_count appointment(s) are associated with this service.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM service WHERE SERV_ID = ?");
            $stmt->execute([$serv_id]);
            $_SESSION['success'] = "Service deleted successfully!";
        }
        header("Location: service_manage.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error deleting service: " . $e->getMessage();
        header("Location: service_manage.php");
        exit();
    }
}

// Get all services with appointment counts
$services = $pdo->query("
    SELECT s.*, COUNT(a.APPT_ID) as appointment_count
    FROM service s
    LEFT JOIN appointment a ON s.SERV_ID = a.SERV_ID
    GROUP BY s.SERV_ID
    ORDER BY s.SERV_NAME
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
                        <i class="fas fa-concierge-bell me-2"></i>Service Management
                    </h1>
                    <?php if (isSuperAdmin()): ?>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                            <i class="fas fa-plus me-1"></i>Add Service
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
                            <i class="fas fa-list me-2"></i>Medical Services
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="bg-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Service Name</th>
                                        <th>Description</th>
                                        <th>Price</th>
                                        <th>Appointments</th>
                                        <th>Created Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($services as $service): ?>
                                        <tr>
                                            <td><?php echo $service['SERV_ID']; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($service['SERV_NAME']); ?></strong>
                                            </td>
                                            <td>
                                                <?php if ($service['SERV_DESCRIPTION']): ?>
                                                    <small class="text-muted"><?php echo htmlspecialchars($service['SERV_DESCRIPTION']); ?></small>
                                                <?php else: ?>
                                                    <span class="text-muted">No description</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong class="text-success">₱<?php echo number_format($service['SERV_PRICE'], 2); ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $service['appointment_count'] > 0 ? 'warning' : 'secondary'; ?>">
                                                    <?php echo $service['appointment_count']; ?> appointment(s)
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($service['SERV_CREATED_AT'])); ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary edit-service-btn"
                                                            data-service-id="<?php echo $service['SERV_ID']; ?>"
                                                            data-service-name="<?php echo htmlspecialchars($service['SERV_NAME']); ?>"
                                                            data-service-description="<?php echo htmlspecialchars($service['SERV_DESCRIPTION'] ?? ''); ?>"
                                                            data-service-price="<?php echo $service['SERV_PRICE']; ?>">
                                                        <i class="fas fa-edit me-1"></i>Edit
                                                    </button>
                                                    <?php if (isSuperAdmin() && $service['appointment_count'] == 0): ?>
                                                        <a href="service_manage.php?delete_id=<?php echo $service['SERV_ID']; ?>"
                                                           class="btn btn-sm btn-outline-danger"
                                                           onclick="return confirm('Are you sure you want to delete this service?')">
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

    <!-- Add Service Modal -->
    <?php if (isSuperAdmin()): ?>
    <div class="modal fade" id="addServiceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Service</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Service Name *</label>
                            <input type="text" class="form-control" name="serv_name" required
                                   placeholder="e.g., Consultation, Laboratory Test, Vaccination">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="serv_description" rows="3"
                                      placeholder="Describe the service..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price (₱) *</label>
                            <input type="number" class="form-control" name="serv_price" step="0.01" min="0" required
                                   placeholder="0.00">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_service" class="btn btn-primary">Add Service</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Edit Service Modal -->
    <div class="modal fade" id="editServiceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Service</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="serv_id" id="edit_serv_id">
                        <div class="mb-3">
                            <label class="form-label">Service Name *</label>
                            <input type="text" class="form-control" name="serv_name" id="edit_serv_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="serv_description" id="edit_serv_description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price (₱) *</label>
                            <input type="number" class="form-control" name="serv_price" id="edit_serv_price" step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_service" class="btn btn-primary">Update Service</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editButtons = document.querySelectorAll('.edit-service-btn');
            const editModal = new bootstrap.Modal(document.getElementById('editServiceModal'));

            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const serviceId = this.getAttribute('data-service-id');
                    const serviceName = this.getAttribute('data-service-name');
                    const serviceDescription = this.getAttribute('data-service-description');
                    const servicePrice = this.getAttribute('data-service-price');

                    document.getElementById('edit_serv_id').value = serviceId;
                    document.getElementById('edit_serv_name').value = serviceName;
                    document.getElementById('edit_serv_description').value = serviceDescription;
                    document.getElementById('edit_serv_price').value = servicePrice;

                    editModal.show();
                });
            });
        });
    </script>
</body>
</html>
