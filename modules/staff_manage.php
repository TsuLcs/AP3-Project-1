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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($action == 'add') {
        $stmt = $pdo->prepare("INSERT INTO STAFF (STAFF_FIRST_NAME, STAFF_LAST_NAME, STAFF_EMAIL, STAFF_POSITION) VALUES (?, ?, ?, ?)");
        try {
            $stmt->execute([
                $_POST['first_name'],
                $_POST['last_name'],
                $_POST['email'],
                $_POST['position']
            ]);
            $message = '<div class="alert alert-success">Staff added successfully!</div>';
            $action = 'list';
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Error adding staff: ' . $e->getMessage() . '</div>';
        }
    } elseif ($action == 'edit' && isset($_POST['id'])) {
        $stmt = $pdo->prepare("UPDATE STAFF SET STAFF_FIRST_NAME = ?, STAFF_LAST_NAME = ?, STAFF_EMAIL = ?, STAFF_POSITION = ? WHERE STAFF_ID = ?");
        try {
            $stmt->execute([
                $_POST['first_name'],
                $_POST['last_name'],
                $_POST['email'],
                $_POST['position'],
                $_POST['id']
            ]);
            $message = '<div class="alert alert-success">Staff updated successfully!</div>';
            $action = 'list';
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Error updating staff: ' . $e->getMessage() . '</div>';
        }
    }
}

// Handle delete action
if (isset($_GET['delete']) && isset($_SESSION['user_is_superadmin'])) {
    $stmt = $pdo->prepare("DELETE FROM STAFF WHERE STAFF_ID = ?");
    try {
        $stmt->execute([$_GET['delete']]);
        $message = '<div class="alert alert-success">Staff deleted successfully!</div>';
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Error deleting staff: ' . $e->getMessage() . '</div>';
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Staff Management</h1>
                <?php if ($action == 'list' && (isset($_SESSION['user_is_superadmin']) || isset($_SESSION['staff_id']))): ?>
                    <a href="?action=add" class="btn btn-primary">Add New Staff</a>
                <?php endif; ?>
            </div>

            <?php echo $message; ?>

            <?php if ($action == 'list'): ?>
                <!-- Staff List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">All Staff Members</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $search = $_GET['search'] ?? '';
                        $query = "SELECT * FROM STAFF WHERE 1=1";
                        $params = [];

                        if (!empty($search)) {
                            $query .= " AND (STAFF_FIRST_NAME LIKE ? OR STAFF_LAST_NAME LIKE ? OR STAFF_EMAIL LIKE ?)";
                            $search_term = "%$search%";
                            $params = [$search_term, $search_term, $search_term];
                        }

                        $query .= " ORDER BY STAFF_FIRST_NAME, STAFF_LAST_NAME";
                        $stmt = $pdo->prepare($query);
                        $stmt->execute($params);
                        $staff = $stmt->fetchAll();
                        ?>

                        <!-- Search Form -->
                        <form method="GET" class="mb-3">
                            <div class="input-group">
                                <input type="text" name="search" class="form-control" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($search); ?>">
                                <button type="submit" class="btn btn-outline-primary">Search</button>
                                <?php if (!empty($search)): ?>
                                    <a href="?" class="btn btn-outline-secondary">Clear</a>
                                <?php endif; ?>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Position</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($staff)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">No staff members found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($staff as $member): ?>
                                            <tr>
                                                <td><?php echo $member['STAFF_ID']; ?></td>
                                                <td><?php echo htmlspecialchars($member['STAFF_FIRST_NAME'] . ' ' . $member['STAFF_LAST_NAME']); ?></td>
                                                <td><?php echo htmlspecialchars($member['STAFF_EMAIL']); ?></td>
                                                <td><?php echo htmlspecialchars($member['STAFF_POSITION']); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($member['STAFF_CREATED_AT'])); ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="?action=view&id=<?php echo $member['STAFF_ID']; ?>" class="btn btn-outline-primary">View</a>
                                                        <a href="?action=edit&id=<?php echo $member['STAFF_ID']; ?>" class="btn btn-outline-secondary">Edit</a>
                                                        <?php if (isset($_SESSION['user_is_superadmin'])): ?>
                                                            <a href="?delete=<?php echo $member['STAFF_ID']; ?>"
                                                               class="btn btn-outline-danger btn-delete"
                                                               onclick="return confirm('Are you sure you want to delete this staff member?')">Delete</a>
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
                $staff_data = [];
                if ($action == 'edit' && isset($_GET['id'])) {
                    $stmt = $pdo->prepare("SELECT * FROM STAFF WHERE STAFF_ID = ?");
                    $stmt->execute([$_GET['id']]);
                    $staff_data = $stmt->fetch();
                    if (!$staff_data) {
                        echo '<div class="alert alert-danger">Staff member not found.</div>';
                        include '../includes/tail.php';
                        exit();
                    }
                }
                ?>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><?php echo $action == 'add' ? 'Add New Staff' : 'Edit Staff'; ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <?php if ($action == 'edit'): ?>
                                <input type="hidden" name="id" value="<?php echo $staff_data['STAFF_ID']; ?>">
                            <?php endif; ?>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="first_name" name="first_name"
                                           value="<?php echo htmlspecialchars($staff_data['STAFF_FIRST_NAME'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="last_name" name="last_name"
                                           value="<?php echo htmlspecialchars($staff_data['STAFF_LAST_NAME'] ?? ''); ?>" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email"
                                           value="<?php echo htmlspecialchars($staff_data['STAFF_EMAIL'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="position" class="form-label">Position</label>
                                    <input type="text" class="form-control" id="position" name="position"
                                           value="<?php echo htmlspecialchars($staff_data['STAFF_POSITION'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary"><?php echo $action == 'add' ? 'Add Staff' : 'Update Staff'; ?></button>
                                <a href="?" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>

            <?php elseif ($action == 'view' && isset($_GET['id'])): ?>
                <!-- View Staff Details -->
                <?php
                $stmt = $pdo->prepare("SELECT * FROM STAFF WHERE STAFF_ID = ?");
                $stmt->execute([$_GET['id']]);
                $staff_data = $stmt->fetch();

                if (!$staff_data) {
                    echo '<div class="alert alert-danger">Staff member not found.</div>';
                } else {
                ?>
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Staff Details</h5>
                            <div class="btn-group">
                                <a href="?action=edit&id=<?php echo $staff_data['STAFF_ID']; ?>" class="btn btn-outline-secondary">Edit</a>
                                <a href="?" class="btn btn-outline-primary">Back to List</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="30%">Staff ID</th>
                                            <td><?php echo $staff_data['STAFF_ID']; ?></td>
                                        </tr>
                                        <tr>
                                            <th>First Name</th>
                                            <td><?php echo htmlspecialchars($staff_data['STAFF_FIRST_NAME']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Last Name</th>
                                            <td><?php echo htmlspecialchars($staff_data['STAFF_LAST_NAME']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Email</th>
                                            <td><?php echo htmlspecialchars($staff_data['STAFF_EMAIL']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Position</th>
                                            <td><?php echo htmlspecialchars($staff_data['STAFF_POSITION']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Created At</th>
                                            <td><?php echo date('F j, Y g:i A', strtotime($staff_data['STAFF_CREATED_AT'])); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Last Updated</th>
                                            <td><?php echo date('F j, Y g:i A', strtotime($staff_data['STAFF_UPDATED_AT'])); ?></td>
                                        </tr>
                                    </table>
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
