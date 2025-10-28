<?php
$page_title = "Service Management";
include '../includes/head.php';
require_once '../data/dbconfig.php';

// Check access - Super Admin and Staff can access
if (!isset($_SESSION['user_is_superadmin']) && !isset($_SESSION['staff_id'])) {
    header("Location: ../pages/login.php");
    exit();
}

$action = $_GET['action'] ?? 'list';
$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($action == 'add') {
        $stmt = $pdo->prepare("INSERT INTO SERVICE (SERV_NAME, SERV_DESCRIPTION, SERV_PRICE) VALUES (?, ?, ?)");
        try {
            $stmt->execute([
                $_POST['serv_name'],
                $_POST['serv_description'],
                $_POST['serv_price']
            ]);
            $message = '<div class="alert alert-success">Service added successfully!</div>';
            $action = 'list';
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Error adding service: ' . $e->getMessage() . '</div>';
        }
    } elseif ($action == 'edit' && isset($_POST['id'])) {
        $stmt = $pdo->prepare("UPDATE SERVICE SET SERV_NAME = ?, SERV_DESCRIPTION = ?, SERV_PRICE = ? WHERE SERV_ID = ?");
        try {
            $stmt->execute([
                $_POST['serv_name'],
                $_POST['serv_description'],
                $_POST['serv_price'],
                $_POST['id']
            ]);
            $message = '<div class="alert alert-success">Service updated successfully!</div>';
            $action = 'list';
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Error updating service: ' . $e->getMessage() . '</div>';
        }
    }
}

// Handle delete action
if (isset($_GET['delete']) && (isset($_SESSION['user_is_superadmin']) || isset($_SESSION['staff_id']))) {
    $stmt = $pdo->prepare("DELETE FROM SERVICE WHERE SERV_ID = ?");
    try {
        $stmt->execute([$_GET['delete']]);
        $message = '<div class="alert alert-success">Service deleted successfully!</div>';
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Error deleting service: ' . $e->getMessage() . '</div>';
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Service Management</h1>
                <?php if ($action == 'list' && (isset($_SESSION['user_is_superadmin']) || isset($_SESSION['staff_id']))): ?>
                    <a href="?action=add" class="btn btn-primary">Add New Service</a>
                <?php endif; ?>
            </div>

            <?php echo $message; ?>

            <?php if ($action == 'list'): ?>
                <!-- Service List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">All Services</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $stmt = $pdo->query("SELECT s.*, COUNT(a.APPT_ID) as appointment_count
                                            FROM SERVICE s
                                            LEFT JOIN APPOINTMENT a ON s.SERV_ID = a.SERV_ID
                                            GROUP BY s.SERV_ID
                                            ORDER BY s.SERV_NAME");
                        $services = $stmt->fetchAll();
                        ?>

                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Service Name</th>
                                        <th>Description</th>
                                        <th>Price</th>
                                        <th>Appointments</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($services)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">No services found.</td>
                                        </tr>
                                    <?php else: ?>
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
                                                <td>₱<?php echo number_format($service['SERV_PRICE'], 2); ?></td>
                                                <td>
                                                    <span class="badge bg-primary"><?php echo $service['appointment_count']; ?></span>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="?action=view&id=<?php echo $service['SERV_ID']; ?>" class="btn btn-outline-primary">View</a>
                                                        <a href="?action=edit&id=<?php echo $service['SERV_ID']; ?>" class="btn btn-outline-secondary">Edit</a>
                                                        <?php if (isset($_SESSION['user_is_superadmin']) || isset($_SESSION['staff_id'])): ?>
                                                            <a href="?delete=<?php echo $service['SERV_ID']; ?>"
                                                               class="btn btn-outline-danger btn-delete"
                                                               onclick="return confirm('Are you sure you want to delete this service?')">Delete</a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            <?php elseif ($action == 'add' || $action == 'edit'): ?>
                <!-- Add/Edit Form -->
                <?php
                $service_data = [];
                if ($action == 'edit' && isset($_GET['id'])) {
                    $stmt = $pdo->prepare("SELECT * FROM SERVICE WHERE SERV_ID = ?");
                    $stmt->execute([$_GET['id']]);
                    $service_data = $stmt->fetch();
                    if (!$service_data) {
                        echo '<div class="alert alert-danger">Service not found.</div>';
                        include '../includes/tail.php';
                        exit();
                    }
                }
                ?>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><?php echo $action == 'add' ? 'Add New Service' : 'Edit Service'; ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <?php if ($action == 'edit'): ?>
                                <input type="hidden" name="id" value="<?php echo $service_data['SERV_ID']; ?>">
                            <?php endif; ?>

                            <div class="mb-3">
                                <label for="serv_name" class="form-label">Service Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="serv_name" name="serv_name"
                                       value="<?php echo htmlspecialchars($service_data['SERV_NAME'] ?? ''); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="serv_description" class="form-label">Description</label>
                                <textarea class="form-control" id="serv_description" name="serv_description" rows="3"><?php echo htmlspecialchars($service_data['SERV_DESCRIPTION'] ?? ''); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="serv_price" class="form-label">Price (₱) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="serv_price" name="serv_price"
                                       value="<?php echo htmlspecialchars($service_data['SERV_PRICE'] ?? ''); ?>" step="0.01" min="0" required>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary"><?php echo $action == 'add' ? 'Add Service' : 'Update Service'; ?></button>
                                <a href="?" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>

            <?php elseif ($action == 'view' && isset($_GET['id'])): ?>
                <!-- View Service Details -->
                <?php
                $stmt = $pdo->prepare("SELECT * FROM SERVICE WHERE SERV_ID = ?");
                $stmt->execute([$_GET['id']]);
                $service_data = $stmt->fetch();

                if (!$service_data) {
                    echo '<div class="alert alert-danger">Service not found.</div>';
                } else {
                    // Get appointments for this service
                    $appointments_stmt = $pdo->prepare("
                        SELECT a.*, p.PAT_FIRST_NAME, p.PAT_LAST_NAME, d.DOC_FIRST_NAME, d.DOC_LAST_NAME, st.STAT_NAME
                        FROM APPOINTMENT a
                        JOIN PATIENT p ON a.PAT_ID = p.PAT_ID
                        JOIN DOCTOR d ON a.DOC_ID = d.DOC_ID
                        JOIN STATUS st ON a.STAT_ID = st.STAT_ID
                        WHERE a.SERV_ID = ?
                        ORDER BY a.APPT_DATE DESC
                        LIMIT 10
                    ");
                    $appointments_stmt->execute([$_GET['id']]);
                    $appointments = $appointments_stmt->fetchAll();
                ?>
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Service Details</h5>
                            <div class="btn-group">
                                <a href="?action=edit&id=<?php echo $service_data['SERV_ID']; ?>" class="btn btn-outline-secondary">Edit</a>
                                <a href="?" class="btn btn-outline-primary">Back to List</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="30%">Service ID</th>
                                            <td><?php echo $service_data['SERV_ID']; ?></td>
                                        </tr>
                                        <tr>
                                            <th>Name</th>
                                            <td><?php echo htmlspecialchars($service_data['SERV_NAME']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Description</th>
                                            <td>
                                                <?php if ($service_data['SERV_DESCRIPTION']): ?>
                                                    <?php echo nl2br(htmlspecialchars($service_data['SERV_DESCRIPTION'])); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">No description</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Price</th>
                                            <td><strong>₱<?php echo number_format($service_data['SERV_PRICE'], 2); ?></strong></td>
                                        </tr>
                                        <tr>
                                            <th>Created At</th>
                                            <td><?php echo date('F j, Y g:i A', strtotime($service_data['SERV_CREATED_AT'])); ?></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6>Recent Appointments</h6>
                                    <?php if (empty($appointments)): ?>
                                        <p class="text-muted">No appointments found for this service.</p>
                                    <?php else: ?>
                                        <div class="list-group">
                                            <?php foreach ($appointments as $appt): ?>
                                                <div class="list-group-item">
                                                    <div class="d-flex w-100 justify-content-between">
                                                        <h6 class="mb-1"><?php echo htmlspecialchars($appt['PAT_FIRST_NAME'] . ' ' . $appt['PAT_LAST_NAME']); ?></h6>
                                                        <small class="text-<?php
                                                            switch($appt['STAT_NAME']) {
                                                                case 'Scheduled': echo 'primary'; break;
                                                                case 'Completed': echo 'success'; break;
                                                                case 'Cancelled': echo 'danger'; break;
                                                                default: echo 'secondary';
                                                            }
                                                        ?>"><?php echo $appt['STAT_NAME']; ?></small>
                                                    </div>
                                                    <p class="mb-1">
                                                        Dr. <?php echo htmlspecialchars($appt['DOC_FIRST_NAME'] . ' ' . $appt['DOC_LAST_NAME']); ?>
                                                    </p>
                                                    <small class="text-muted">
                                                        <?php echo date('M j, Y', strtotime($appt['APPT_DATE'])); ?> at
                                                        <?php echo date('h:i A', strtotime($appt['APPT_TIME'])); ?>
                                                    </small>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="mt-2 text-center">
                                            <a href="../modules/appointment_manage.php?service_id=<?php echo $service_data['SERV_ID']; ?>"
                                               class="btn btn-sm btn-outline-primary">View All Appointments</a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include '../includes/tail.php'; ?>
