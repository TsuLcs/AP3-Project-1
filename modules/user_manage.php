<?php
$page_title = "User Management";
include '../includes/head.php';
require_once '../data/dbconfig.php';

// Check access - Only Super Admin can access
if (!isset($_SESSION['user_is_superadmin']) || !$_SESSION['user_is_superadmin']) {
    header("Location: ../pages/login.php");
    exit();
}

$action = $_GET['action'] ?? 'list';
$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($action == 'create') {
        $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO USER (USER_NAME, USER_PASSWORD, USER_IS_SUPERADMIN, PAT_ID, STAFF_ID, DOC_ID) VALUES (?, ?, ?, ?, ?, ?)");
        try {
            $stmt->execute([
                $_POST['username'],
                $hashed_password,
                $_POST['is_superadmin'] ?? 0,
                $_POST['pat_id'] ?: null,
                $_POST['staff_id'] ?: null,
                $_POST['doc_id'] ?: null
            ]);
            $message = '<div class="alert alert-success">User created successfully!</div>';
            $action = 'list';
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Error creating user: ' . $e->getMessage() . '</div>';
        }
    } elseif ($action == 'edit' && isset($_POST['id'])) {
        // Handle password update
        if (!empty($_POST['password'])) {
            $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE USER SET USER_NAME = ?, USER_PASSWORD = ?, USER_IS_SUPERADMIN = ?, PAT_ID = ?, STAFF_ID = ?, DOC_ID = ? WHERE USER_ID = ?");
            $stmt->execute([
                $_POST['username'],
                $hashed_password,
                $_POST['is_superadmin'] ?? 0,
                $_POST['pat_id'] ?: null,
                $_POST['staff_id'] ?: null,
                $_POST['doc_id'] ?: null,
                $_POST['id']
            ]);
        } else {
            $stmt = $pdo->prepare("UPDATE USER SET USER_NAME = ?, USER_IS_SUPERADMIN = ?, PAT_ID = ?, STAFF_ID = ?, DOC_ID = ? WHERE USER_ID = ?");
            $stmt->execute([
                $_POST['username'],
                $_POST['is_superadmin'] ?? 0,
                $_POST['pat_id'] ?: null,
                $_POST['staff_id'] ?: null,
                $_POST['doc_id'] ?: null,
                $_POST['id']
            ]);
        }
        $message = '<div class="alert alert-success">User updated successfully!</div>';
        $action = 'list';
    }
}

// Handle delete action
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM USER WHERE USER_ID = ?");
    try {
        $stmt->execute([$_GET['delete']]);
        $message = '<div class="alert alert-success">User deleted successfully!</div>';
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Error deleting user: ' . $e->getMessage() . '</div>';
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">User Management</h1>
                <?php if ($action == 'list'): ?>
                    <a href="?action=create" class="btn btn-primary">Create New User</a>
                <?php endif; ?>
            </div>

            <?php echo $message; ?>

            <?php if ($action == 'list'): ?>
                <!-- User List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">All Users</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $query = "SELECT u.*,
                                         p.PAT_FIRST_NAME, p.PAT_LAST_NAME,
                                         s.STAFF_FIRST_NAME, s.STAFF_LAST_NAME,
                                         d.DOC_FIRST_NAME, d.DOC_LAST_NAME
                                 FROM USER u
                                 LEFT JOIN PATIENT p ON u.PAT_ID = p.PAT_ID
                                 LEFT JOIN STAFF s ON u.STAFF_ID = s.STAFF_ID
                                 LEFT JOIN DOCTOR d ON u.DOC_ID = d.DOC_ID
                                 ORDER BY u.USER_CREATED_AT DESC";
                        $stmt = $pdo->query($query);
                        $users = $stmt->fetchAll();
                        ?>

                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>User ID</th>
                                        <th>Username</th>
                                        <th>Role</th>
                                        <th>Linked To</th>
                                        <th>Super Admin</th>
                                        <th>Last Login</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($users)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center text-muted">No users found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td><?php echo $user['USER_ID']; ?></td>
                                                <td><?php echo htmlspecialchars($user['USER_NAME']); ?></td>
                                                <td>
                                                    <?php
                                                    if ($user['USER_IS_SUPERADMIN']) {
                                                        echo '<span class="badge bg-danger">Super Admin</span>';
                                                    } elseif ($user['PAT_ID']) {
                                                        echo '<span class="badge bg-primary">Patient</span>';
                                                    } elseif ($user['STAFF_ID']) {
                                                        echo '<span class="badge bg-warning">Staff</span>';
                                                    } elseif ($user['DOC_ID']) {
                                                        echo '<span class="badge bg-success">Doctor</span>';
                                                    } else {
                                                        echo '<span class="badge bg-secondary">No Role</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    if ($user['PAT_ID']) {
                                                        echo htmlspecialchars($user['PAT_FIRST_NAME'] . ' ' . $user['PAT_LAST_NAME']);
                                                    } elseif ($user['STAFF_ID']) {
                                                        echo htmlspecialchars($user['STAFF_FIRST_NAME'] . ' ' . $user['STAFF_LAST_NAME']);
                                                    } elseif ($user['DOC_ID']) {
                                                        echo 'Dr. ' . htmlspecialchars($user['DOC_FIRST_NAME'] . ' ' . $user['DOC_LAST_NAME']);
                                                    } else {
                                                        echo '-';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php if ($user['USER_IS_SUPERADMIN']): ?>
                                                        <span class="badge bg-success">Yes</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">No</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($user['USER_LAST_LOGIN']): ?>
                                                        <?php echo date('M j, Y g:i A', strtotime($user['USER_LAST_LOGIN'])); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">Never</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo date('M j, Y', strtotime($user['USER_CREATED_AT'])); ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="?action=view&id=<?php echo $user['USER_ID']; ?>" class="btn btn-outline-primary">View</a>
                                                        <a href="?action=edit&id=<?php echo $user['USER_ID']; ?>" class="btn btn-outline-secondary">Edit</a>
                                                        <?php if ($user['USER_ID'] != $_SESSION['user_id']): // Prevent self-deletion ?>
                                                            <a href="?delete=<?php echo $user['USER_ID']; ?>"
                                                               class="btn btn-outline-danger btn-delete"
                                                               onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
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

            <?php elseif ($action == 'create' || $action == 'edit'): ?>
                <!-- Create/Edit Form -->
                <?php
                $user_data = [];
                if ($action == 'edit' && isset($_GET['id'])) {
                    $stmt = $pdo->prepare("SELECT u.*,
                                                  p.PAT_FIRST_NAME, p.PAT_LAST_NAME,
                                                  s.STAFF_FIRST_NAME, s.STAFF_LAST_NAME,
                                                  d.DOC_FIRST_NAME, d.DOC_LAST_NAME
                                          FROM USER u
                                          LEFT JOIN PATIENT p ON u.PAT_ID = p.PAT_ID
                                          LEFT JOIN STAFF s ON u.STAFF_ID = s.STAFF_ID
                                          LEFT JOIN DOCTOR d ON u.DOC_ID = d.DOC_ID
                                          WHERE u.USER_ID = ?");
                    $stmt->execute([$_GET['id']]);
                    $user_data = $stmt->fetch();
                    if (!$user_data) {
                        echo '<div class="alert alert-danger">User not found.</div>';
                        include '../includes/tail.php';
                        exit();
                    }
                }

                // Get data for dropdowns
                $patients_stmt = $pdo->query("SELECT * FROM PATIENT ORDER BY PAT_FIRST_NAME, PAT_LAST_NAME");
                $patients = $patients_stmt->fetchAll();

                $staff_stmt = $pdo->query("SELECT * FROM STAFF ORDER BY STAFF_FIRST_NAME, STAFF_LAST_NAME");
                $staff = $staff_stmt->fetchAll();

                $doctors_stmt = $pdo->query("SELECT * FROM DOCTOR ORDER BY DOC_FIRST_NAME, DOC_LAST_NAME");
                $doctors = $doctors_stmt->fetchAll();
                ?>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><?php echo $action == 'create' ? 'Create New User' : 'Edit User'; ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <?php if ($action == 'edit'): ?>
                                <input type="hidden" name="id" value="<?php echo $user_data['USER_ID']; ?>">
                            <?php endif; ?>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="username" name="username"
                                           value="<?php echo htmlspecialchars($user_data['USER_NAME'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Password <?php if ($action == 'create'): ?><span class="text-danger">*</span><?php else: ?><small class="text-muted">(Leave blank to keep current)</small><?php endif; ?></label>
                                    <input type="password" class="form-control" id="password" name="password"
                                           <?php if ($action == 'create'): ?>required<?php endif; ?>>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_superadmin" name="is_superadmin" value="1"
                                        <?php echo (isset($user_data['USER_IS_SUPERADMIN']) && $user_data['USER_IS_SUPERADMIN']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_superadmin">
                                        Super Admin User
                                    </label>
                                </div>
                                <div class="form-text">Super admin users have full access to all system features.</div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="pat_id" class="form-label">Link to Patient</label>
                                    <select class="form-control" id="pat_id" name="pat_id">
                                        <option value="">No Patient</option>
                                        <?php foreach ($patients as $patient): ?>
                                            <option value="<?php echo $patient['PAT_ID']; ?>"
                                                <?php echo (isset($user_data['PAT_ID']) && $user_data['PAT_ID'] == $patient['PAT_ID']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($patient['PAT_FIRST_NAME'] . ' ' . $patient['PAT_LAST_NAME']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="staff_id" class="form-label">Link to Staff</label>
                                    <select class="form-control" id="staff_id" name="staff_id">
                                        <option value="">No Staff</option>
                                        <?php foreach ($staff as $staff_member): ?>
                                            <option value="<?php echo $staff_member['STAFF_ID']; ?>"
                                                <?php echo (isset($user_data['STAFF_ID']) && $user_data['STAFF_ID'] == $staff_member['STAFF_ID']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($staff_member['STAFF_FIRST_NAME'] . ' ' . $staff_member['STAFF_LAST_NAME']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="doc_id" class="form-label">Link to Doctor</label>
                                    <select class="form-control" id="doc_id" name="doc_id">
                                        <option value="">No Doctor</option>
                                        <?php foreach ($doctors as $doctor): ?>
                                            <option value="<?php echo $doctor['DOC_ID']; ?>"
                                                <?php echo (isset($user_data['DOC_ID']) && $user_data['DOC_ID'] == $doctor['DOC_ID']) ? 'selected' : ''; ?>>
                                                Dr. <?php echo htmlspecialchars($doctor['DOC_FIRST_NAME'] . ' ' . $doctor['DOC_LAST_NAME']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="alert alert-info">
                                <strong>Note:</strong> A user can only be linked to one role (Patient, Staff, or Doctor).
                                Super Admin users typically don't have role links.
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary"><?php echo $action == 'create' ? 'Create User' : 'Update User'; ?></button>
                                <a href="?" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>

            <?php elseif ($action == 'view' && isset($_GET['id'])): ?>
                <!-- View User Details -->
                <?php
                $stmt = $pdo->prepare("SELECT u.*,
                                              p.PAT_FIRST_NAME, p.PAT_LAST_NAME, p.PAT_EMAIL, p.PAT_CONTACT_NUM,
                                              s.STAFF_FIRST_NAME, s.STAFF_LAST_NAME, s.STAFF_EMAIL, s.STAFF_POSITION,
                                              d.DOC_FIRST_NAME, d.DOC_LAST_NAME, d.DOC_EMAIL, d.DOC_CONTACT_NUM, spec.SPEC_NAME
                                      FROM USER u
                                      LEFT JOIN PATIENT p ON u.PAT_ID = p.PAT_ID
                                      LEFT JOIN STAFF s ON u.STAFF_ID = s.STAFF_ID
                                      LEFT JOIN DOCTOR d ON u.DOC_ID = d.DOC_ID
                                      LEFT JOIN SPECIALIZATION spec ON d.SPEC_ID = spec.SPEC_ID
                                      WHERE u.USER_ID = ?");
                $stmt->execute([$_GET['id']]);
                $user_data = $stmt->fetch();

                if (!$user_data) {
                    echo '<div class="alert alert-danger">User not found.</div>';
                } else {
                ?>
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">User Details</h5>
                            <div class="btn-group">
                                <a href="?action=edit&id=<?php echo $user_data['USER_ID']; ?>" class="btn btn-outline-secondary">Edit</a>
                                <a href="?" class="btn btn-outline-primary">Back to List</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>User Information</h6>
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="30%">User ID</th>
                                            <td><?php echo $user_data['USER_ID']; ?></td>
                                        </tr>
                                        <tr>
                                            <th>Username</th>
                                            <td><?php echo htmlspecialchars($user_data['USER_NAME']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Role</th>
                                            <td>
                                                <?php
                                                if ($user_data['USER_IS_SUPERADMIN']) {
                                                    echo '<span class="badge bg-danger">Super Admin</span>';
                                                } elseif ($user_data['PAT_ID']) {
                                                    echo '<span class="badge bg-primary">Patient</span>';
                                                } elseif ($user_data['STAFF_ID']) {
                                                    echo '<span class="badge bg-warning">Staff</span>';
                                                } elseif ($user_data['DOC_ID']) {
                                                    echo '<span class="badge bg-success">Doctor</span>';
                                                } else {
                                                    echo '<span class="badge bg-secondary">No Role</span>';
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Super Admin</th>
                                            <td>
                                                <?php if ($user_data['USER_IS_SUPERADMIN']): ?>
                                                    <span class="badge bg-success">Yes</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">No</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Last Login</th>
                                            <td>
                                                <?php if ($user_data['USER_LAST_LOGIN']): ?>
                                                    <?php echo date('F j, Y g:i A', strtotime($user_data['USER_LAST_LOGIN'])); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Never logged in</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Created At</th>
                                            <td><?php echo date('F j, Y g:i A', strtotime($user_data['USER_CREATED_AT'])); ?></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <?php if ($user_data['PAT_ID']): ?>
                                        <h6>Patient Information</h6>
                                        <table class="table table-bordered">
                                            <tr>
                                                <th width="30%">Name</th>
                                                <td><?php echo htmlspecialchars($user_data['PAT_FIRST_NAME'] . ' ' . $user_data['PAT_LAST_NAME']); ?></td>
                                            </tr>
                                            <tr>
                                                <th>Email</th>
                                                <td><?php echo htmlspecialchars($user_data['PAT_EMAIL']); ?></td>
                                            </tr>
                                            <tr>
                                                <th>Contact</th>
                                                <td><?php echo htmlspecialchars($user_data['PAT_CONTACT_NUM']); ?></td>
                                            </tr>
                                        </table>
                                    <?php elseif ($user_data['STAFF_ID']): ?>
                                        <h6>Staff Information</h6>
                                        <table class="table table-bordered">
                                            <tr>
                                                <th width="30%">Name</th>
                                                <td><?php echo htmlspecialchars($user_data['STAFF_FIRST_NAME'] . ' ' . $user_data['STAFF_LAST_NAME']); ?></td>
                                            </tr>
                                            <tr>
                                                <th>Email</th>
                                                <td><?php echo htmlspecialchars($user_data['STAFF_EMAIL']); ?></td>
                                            </tr>
                                            <tr>
                                                <th>Position</th>
                                                <td><?php echo htmlspecialchars($user_data['STAFF_POSITION']); ?></td>
                                            </tr>
                                        </table>
                                    <?php elseif ($user_data['DOC_ID']): ?>
                                        <h6>Doctor Information</h6>
                                        <table class="table table-bordered">
                                            <tr>
                                                <th width="30%">Name</th>
                                                <td>Dr. <?php echo htmlspecialchars($user_data['DOC_FIRST_NAME'] . ' ' . $user_data['DOC_LAST_NAME']); ?></td>
                                            </tr>
                                            <tr>
                                                <th>Email</th>
                                                <td><?php echo htmlspecialchars($user_data['DOC_EMAIL']); ?></td>
                                            </tr>
                                            <tr>
                                                <th>Contact</th>
                                                <td><?php echo htmlspecialchars($user_data['DOC_CONTACT_NUM']); ?></td>
                                            </tr>
                                            <tr>
                                                <th>Specialization</th>
                                                <td><?php echo htmlspecialchars($user_data['SPEC_NAME']); ?></td>
                                            </tr>
                                        </table>
                                    <?php else: ?>
                                        <div class="alert alert-info">
                                            This user is not linked to any specific role (Patient, Staff, or Doctor).
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
