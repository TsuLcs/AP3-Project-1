<?php
$page_title = "Specialization Management";
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
        $stmt = $pdo->prepare("INSERT INTO SPECIALIZATION (SPEC_NAME) VALUES (?)");
        try {
            $stmt->execute([$_POST['spec_name']]);
            $message = '<div class="alert alert-success">Specialization added successfully!</div>';
            $action = 'list';
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Error adding specialization: ' . $e->getMessage() . '</div>';
        }
    } elseif ($action == 'edit' && isset($_POST['id'])) {
        $stmt = $pdo->prepare("UPDATE SPECIALIZATION SET SPEC_NAME = ? WHERE SPEC_ID = ?");
        try {
            $stmt->execute([$_POST['spec_name'], $_POST['id']]);
            $message = '<div class="alert alert-success">Specialization updated successfully!</div>';
            $action = 'list';
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Error updating specialization: ' . $e->getMessage() . '</div>';
        }
    }
}

// Handle delete action
if (isset($_GET['delete']) && isset($_SESSION['user_is_superadmin'])) {
    $stmt = $pdo->prepare("DELETE FROM SPECIALIZATION WHERE SPEC_ID = ?");
    try {
        $stmt->execute([$_GET['delete']]);
        $message = '<div class="alert alert-success">Specialization deleted successfully!</div>';
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Error deleting specialization: ' . $e->getMessage() . '</div>';
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Specialization Management</h1>
                <?php if ($action == 'list' && (isset($_SESSION['user_is_superadmin']) || isset($_SESSION['staff_id']))): ?>
                    <a href="?action=add" class="btn btn-primary">Add New Specialization</a>
                <?php endif; ?>
            </div>

            <?php echo $message; ?>

            <?php if ($action == 'list'): ?>
                <!-- Specialization List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">All Specializations</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $stmt = $pdo->query("SELECT s.*, COUNT(d.DOC_ID) as doctor_count
                                            FROM SPECIALIZATION s
                                            LEFT JOIN DOCTOR d ON s.SPEC_ID = d.SPEC_ID
                                            GROUP BY s.SPEC_ID
                                            ORDER BY s.SPEC_NAME");
                        $specializations = $stmt->fetchAll();
                        ?>

                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Specialization Name</th>
                                        <th>Number of Doctors</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($specializations)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">No specializations found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($specializations as $spec): ?>
                                            <tr>
                                                <td><?php echo $spec['SPEC_ID']; ?></td>
                                                <td><?php echo htmlspecialchars($spec['SPEC_NAME']); ?></td>
                                                <td>
                                                    <span class="badge bg-primary"><?php echo $spec['doctor_count']; ?> doctors</span>
                                                </td>
                                                <td><?php echo date('M j, Y', strtotime($spec['SPEC_CREATED_AT'])); ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="?action=view&id=<?php echo $spec['SPEC_ID']; ?>" class="btn btn-outline-primary">View</a>
                                                        <a href="?action=edit&id=<?php echo $spec['SPEC_ID']; ?>" class="btn btn-outline-secondary">Edit</a>
                                                        <?php if (isset($_SESSION['user_is_superadmin'])): ?>
                                                            <a href="?delete=<?php echo $spec['SPEC_ID']; ?>"
                                                               class="btn btn-outline-danger btn-delete"
                                                               onclick="return confirm('Are you sure you want to delete this specialization?')">Delete</a>
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
                $spec_data = [];
                if ($action == 'edit' && isset($_GET['id'])) {
                    $stmt = $pdo->prepare("SELECT * FROM SPECIALIZATION WHERE SPEC_ID = ?");
                    $stmt->execute([$_GET['id']]);
                    $spec_data = $stmt->fetch();
                    if (!$spec_data) {
                        echo '<div class="alert alert-danger">Specialization not found.</div>';
                        include '../includes/tail.php';
                        exit();
                    }
                }
                ?>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><?php echo $action == 'add' ? 'Add New Specialization' : 'Edit Specialization'; ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <?php if ($action == 'edit'): ?>
                                <input type="hidden" name="id" value="<?php echo $spec_data['SPEC_ID']; ?>">
                            <?php endif; ?>

                            <div class="mb-3">
                                <label for="spec_name" class="form-label">Specialization Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="spec_name" name="spec_name"
                                       value="<?php echo htmlspecialchars($spec_data['SPEC_NAME'] ?? ''); ?>" required>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary"><?php echo $action == 'add' ? 'Add Specialization' : 'Update Specialization'; ?></button>
                                <a href="?" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>

            <?php elseif ($action == 'view' && isset($_GET['id'])): ?>
                <!-- View Specialization Details -->
                <?php
                $stmt = $pdo->prepare("SELECT * FROM SPECIALIZATION WHERE SPEC_ID = ?");
                $stmt->execute([$_GET['id']]);
                $spec_data = $stmt->fetch();

                if (!$spec_data) {
                    echo '<div class="alert alert-danger">Specialization not found.</div>';
                } else {
                    // Get doctors in this specialization
                    $doctors_stmt = $pdo->prepare("
                        SELECT d.* FROM DOCTOR d
                        WHERE d.SPEC_ID = ?
                        ORDER BY d.DOC_FIRST_NAME, d.DOC_LAST_NAME
                    ");
                    $doctors_stmt->execute([$_GET['id']]);
                    $doctors = $doctors_stmt->fetchAll();
                ?>
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Specialization Details</h5>
                            <div class="btn-group">
                                <a href="?action=edit&id=<?php echo $spec_data['SPEC_ID']; ?>" class="btn btn-outline-secondary">Edit</a>
                                <a href="?" class="btn btn-outline-primary">Back to List</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="30%">Specialization ID</th>
                                            <td><?php echo $spec_data['SPEC_ID']; ?></td>
                                        </tr>
                                        <tr>
                                            <th>Name</th>
                                            <td><?php echo htmlspecialchars($spec_data['SPEC_NAME']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Number of Doctors</th>
                                            <td><?php echo count($doctors); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Created At</th>
                                            <td><?php echo date('F j, Y g:i A', strtotime($spec_data['SPEC_CREATED_AT'])); ?></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6>Doctors in this Specialization</h6>
                                    <?php if (empty($doctors)): ?>
                                        <p class="text-muted">No doctors found in this specialization.</p>
                                    <?php else: ?>
                                        <div class="list-group">
                                            <?php foreach ($doctors as $doctor): ?>
                                                <a href="../modules/doctor_manage.php?action=view&id=<?php echo $doctor['DOC_ID']; ?>"
                                                   class="list-group-item list-group-item-action">
                                                    Dr. <?php echo htmlspecialchars($doctor['DOC_FIRST_NAME'] . ' ' . $doctor['DOC_LAST_NAME']); ?>
                                                    <br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($doctor['DOC_EMAIL']); ?></small>
                                                </a>
                                            <?php endforeach; ?>
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
