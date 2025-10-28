<?php
$page_title = "Status Management";
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
        $stmt = $pdo->prepare("INSERT INTO STATUS (STAT_NAME) VALUES (?)");
        try {
            $stmt->execute([$_POST['stat_name']]);
            $message = '<div class="alert alert-success">Status added successfully!</div>';
            $action = 'list';
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Error adding status: ' . $e->getMessage() . '</div>';
        }
    } elseif ($action == 'edit' && isset($_POST['id'])) {
        $stmt = $pdo->prepare("UPDATE STATUS SET STAT_NAME = ? WHERE STAT_ID = ?");
        try {
            $stmt->execute([$_POST['stat_name'], $_POST['id']]);
            $message = '<div class="alert alert-success">Status updated successfully!</div>';
            $action = 'list';
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Error updating status: ' . $e->getMessage() . '</div>';
        }
    }
}

// Handle delete action
if (isset($_GET['delete']) && isset($_SESSION['user_is_superadmin'])) {
    $stmt = $pdo->prepare("DELETE FROM STATUS WHERE STAT_ID = ?");
    try {
        $stmt->execute([$_GET['delete']]);
        $message = '<div class="alert alert-success">Status deleted successfully!</div>';
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Error deleting status: ' . $e->getMessage() . '</div>';
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Status Management</h1>
                <?php if ($action == 'list' && (isset($_SESSION['user_is_superadmin']) || isset($_SESSION['staff_id']))): ?>
                    <a href="?action=add" class="btn btn-primary">Add New Status</a>
                <?php endif; ?>
            </div>

            <?php echo $message; ?>

            <?php if ($action == 'list'): ?>
                <!-- Status List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Appointment Status</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $stmt = $pdo->query("SELECT s.*, COUNT(a.APPT_ID) as appointment_count
                                            FROM STATUS s
                                            LEFT JOIN APPOINTMENT a ON s.STAT_ID = a.STAT_ID
                                            GROUP BY s.STAT_ID
                                            ORDER BY s.STAT_NAME");
                        $statuses = $stmt->fetchAll();
                        ?>

                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Status Name</th>
                                        <th>Appointments</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($statuses)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">No statuses found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($statuses as $status): ?>
                                            <tr>
                                                <td><?php echo $status['STAT_ID']; ?></td>
                                                <td>
                                                    <span class="badge bg-<?php
                                                        switch($status['STAT_NAME']) {
                                                            case 'Scheduled': echo 'primary'; break;
                                                            case 'Completed': echo 'success'; break;
                                                            case 'Cancelled': echo 'danger'; break;
                                                            default: echo 'secondary';
                                                        }
                                                    ?>">
                                                        <?php echo htmlspecialchars($status['STAT_NAME']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo $status['appointment_count']; ?></span>
                                                </td>
                                                <td><?php echo date('M j, Y', strtotime($status['STAT_CREATED_AT'])); ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="?action=edit&id=<?php echo $status['STAT_ID']; ?>" class="btn btn-outline-secondary">Edit</a>
                                                        <?php if (isset($_SESSION['user_is_superadmin'])): ?>
                                                            <a href="?delete=<?php echo $status['STAT_ID']; ?>"
                                                               class="btn btn-outline-danger btn-delete"
                                                               onclick="return confirm('Are you sure you want to delete this status?')">Delete</a>
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
                $status_data = [];
                if ($action == 'edit' && isset($_GET['id'])) {
                    $stmt = $pdo->prepare("SELECT * FROM STATUS WHERE STAT_ID = ?");
                    $stmt->execute([$_GET['id']]);
                    $status_data = $stmt->fetch();
                    if (!$status_data) {
                        echo '<div class="alert alert-danger">Status not found.</div>';
                        include '../includes/tail.php';
                        exit();
                    }
                }
                ?>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><?php echo $action == 'add' ? 'Add New Status' : 'Edit Status'; ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <?php if ($action == 'edit'): ?>
                                <input type="hidden" name="id" value="<?php echo $status_data['STAT_ID']; ?>">
                            <?php endif; ?>

                            <div class="mb-3">
                                <label for="stat_name" class="form-label">Status Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="stat_name" name="stat_name"
                                       value="<?php echo htmlspecialchars($status_data['STAT_NAME'] ?? ''); ?>" required>
                                <div class="form-text">Common statuses: Scheduled, Completed, Cancelled</div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary"><?php echo $action == 'add' ? 'Add Status' : 'Update Status'; ?></button>
                                <a href="?" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include '../includes/tail.php'; ?>
