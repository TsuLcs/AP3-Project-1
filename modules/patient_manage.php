<?php
$page_title = "Patient Management";
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
        $stmt = $pdo->prepare("INSERT INTO PATIENT (PAT_FIRST_NAME, PAT_MIDDLE_NAME, PAT_LAST_NAME, PAT_DOB, PAT_GENDER, PAT_CONTACT_NUM, PAT_EMAIL, PAT_ADDRESS) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        try {
            $stmt->execute([
                $_POST['first_name'],
                $_POST['middle_name'],
                $_POST['last_name'],
                $_POST['dob'],
                $_POST['gender'],
                $_POST['contact_num'],
                $_POST['email'],
                $_POST['address']
            ]);
            $message = '<div class="alert alert-success">Patient added successfully!</div>';
            $action = 'list';
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Error adding patient: ' . $e->getMessage() . '</div>';
        }
    } elseif ($action == 'edit' && isset($_POST['id'])) {
        $stmt = $pdo->prepare("UPDATE PATIENT SET PAT_FIRST_NAME = ?, PAT_MIDDLE_NAME = ?, PAT_LAST_NAME = ?, PAT_DOB = ?, PAT_GENDER = ?, PAT_CONTACT_NUM = ?, PAT_EMAIL = ?, PAT_ADDRESS = ? WHERE PAT_ID = ?");
        try {
            $stmt->execute([
                $_POST['first_name'],
                $_POST['middle_name'],
                $_POST['last_name'],
                $_POST['dob'],
                $_POST['gender'],
                $_POST['contact_num'],
                $_POST['email'],
                $_POST['address'],
                $_POST['id']
            ]);
            $message = '<div class="alert alert-success">Patient updated successfully!</div>';
            $action = 'list';
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Error updating patient: ' . $e->getMessage() . '</div>';
        }
    }
}

// Handle delete action
if (isset($_GET['delete']) && (isset($_SESSION['user_is_superadmin']) || isset($_SESSION['staff_id']))) {
    $stmt = $pdo->prepare("DELETE FROM PATIENT WHERE PAT_ID = ?");
    try {
        $stmt->execute([$_GET['delete']]);
        $message = '<div class="alert alert-success">Patient deleted successfully!</div>';
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Error deleting patient: ' . $e->getMessage() . '</div>';
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Patient Management</h1>
                <?php if ($action == 'list' && (isset($_SESSION['user_is_superadmin']) || isset($_SESSION['staff_id']))): ?>
                    <a href="?action=add" class="btn btn-primary">Add New Patient</a>
                <?php endif; ?>
            </div>

            <?php echo $message; ?>

            <?php if ($action == 'list'): ?>
                <!-- Patient List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">All Patients</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $search = $_GET['search'] ?? '';
                        $query = "SELECT * FROM PATIENT WHERE 1=1";
                        $params = [];

                        if (!empty($search)) {
                            $query .= " AND (PAT_FIRST_NAME LIKE ? OR PAT_LAST_NAME LIKE ? OR PAT_EMAIL LIKE ?)";
                            $search_term = "%$search%";
                            $params = [$search_term, $search_term, $search_term];
                        }

                        $query .= " ORDER BY PAT_FIRST_NAME, PAT_LAST_NAME";
                        $stmt = $pdo->prepare($query);
                        $stmt->execute($params);
                        $patients = $stmt->fetchAll();
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
                                        <th>Contact</th>
                                        <th>Gender</th>
                                        <th>Date of Birth</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($patients)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">No patients found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($patients as $patient): ?>
                                            <tr>
                                                <td><?php echo $patient['PAT_ID']; ?></td>
                                                <td>
                                                    <?php echo htmlspecialchars($patient['PAT_FIRST_NAME'] . ' ' .
                                                          ($patient['PAT_MIDDLE_NAME'] ? $patient['PAT_MIDDLE_NAME'] . ' ' : '') .
                                                          $patient['PAT_LAST_NAME']); ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($patient['PAT_EMAIL']); ?></td>
                                                <td><?php echo htmlspecialchars($patient['PAT_CONTACT_NUM']); ?></td>
                                                <td><?php echo htmlspecialchars($patient['PAT_GENDER']); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($patient['PAT_DOB'])); ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="?action=view&id=<?php echo $patient['PAT_ID']; ?>" class="btn btn-outline-primary">View</a>
                                                        <a href="?action=edit&id=<?php echo $patient['PAT_ID']; ?>" class="btn btn-outline-secondary">Edit</a>
                                                        <?php if (isset($_SESSION['user_is_superadmin']) || isset($_SESSION['staff_id'])): ?>
                                                            <a href="?delete=<?php echo $patient['PAT_ID']; ?>"
                                                               class="btn btn-outline-danger btn-delete"
                                                               onclick="return confirm('Are you sure you want to delete this patient?')">Delete</a>
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
                $patient_data = [];
                if ($action == 'edit' && isset($_GET['id'])) {
                    $stmt = $pdo->prepare("SELECT * FROM PATIENT WHERE PAT_ID = ?");
                    $stmt->execute([$_GET['id']]);
                    $patient_data = $stmt->fetch();
                    if (!$patient_data) {
                        echo '<div class="alert alert-danger">Patient not found.</div>';
                        include '../includes/tail.php';
                        exit();
                    }
                }
                ?>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><?php echo $action == 'add' ? 'Add New Patient' : 'Edit Patient'; ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <?php if ($action == 'edit'): ?>
                                <input type="hidden" name="id" value="<?php echo $patient_data['PAT_ID']; ?>">
                            <?php endif; ?>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="first_name" name="first_name"
                                           value="<?php echo htmlspecialchars($patient_data['PAT_FIRST_NAME'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="middle_name" class="form-label">Middle Name</label>
                                    <input type="text" class="form-control" id="middle_name" name="middle_name"
                                           value="<?php echo htmlspecialchars($patient_data['PAT_MIDDLE_NAME'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="last_name" name="last_name"
                                           value="<?php echo htmlspecialchars($patient_data['PAT_LAST_NAME'] ?? ''); ?>" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email"
                                           value="<?php echo htmlspecialchars($patient_data['PAT_EMAIL'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="contact_num" class="form-label">Contact Number <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" id="contact_num" name="contact_num"
                                           value="<?php echo htmlspecialchars($patient_data['PAT_CONTACT_NUM'] ?? ''); ?>" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="dob" class="form-label">Date of Birth</label>
                                    <input type="date" class="form-control" id="dob" name="dob"
                                           value="<?php echo htmlspecialchars($patient_data['PAT_DOB'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="gender" class="form-label">Gender</label>
                                    <select class="form-control" id="gender" name="gender">
                                        <option value="">Select Gender</option>
                                        <option value="Male" <?php echo (isset($patient_data['PAT_GENDER']) && $patient_data['PAT_GENDER'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo (isset($patient_data['PAT_GENDER']) && $patient_data['PAT_GENDER'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                        <option value="Other" <?php echo (isset($patient_data['PAT_GENDER']) && $patient_data['PAT_GENDER'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($patient_data['PAT_ADDRESS'] ?? ''); ?></textarea>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary"><?php echo $action == 'add' ? 'Add Patient' : 'Update Patient'; ?></button>
                                <a href="?" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>

            <?php elseif ($action == 'view' && isset($_GET['id'])): ?>
                <!-- View Patient Details -->
                <?php
                $stmt = $pdo->prepare("SELECT * FROM PATIENT WHERE PAT_ID = ?");
                $stmt->execute([$_GET['id']]);
                $patient_data = $stmt->fetch();

                if (!$patient_data) {
                    echo '<div class="alert alert-danger">Patient not found.</div>';
                } else {
                    // Get patient's appointments
                    $appointments_stmt = $pdo->prepare("
                        SELECT a.*, d.DOC_FIRST_NAME, d.DOC_LAST_NAME, s.SERV_NAME, st.STAT_NAME
                        FROM APPOINTMENT a
                        JOIN DOCTOR d ON a.DOC_ID = d.DOC_ID
                        JOIN SERVICE s ON a.SERV_ID = s.SERV_ID
                        JOIN STATUS st ON a.STAT_ID = st.STAT_ID
                        WHERE a.PAT_ID = ?
                        ORDER BY a.APPT_DATE DESC
                        LIMIT 5
                    ");
                    $appointments_stmt->execute([$_GET['id']]);
                    $appointments = $appointments_stmt->fetchAll();
                ?>
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Patient Details</h5>
                            <div class="btn-group">
                                <a href="?action=edit&id=<?php echo $patient_data['PAT_ID']; ?>" class="btn btn-outline-secondary">Edit</a>
                                <a href="?" class="btn btn-outline-primary">Back to List</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="30%">Patient ID</th>
                                            <td><?php echo $patient_data['PAT_ID']; ?></td>
                                        </tr>
                                        <tr>
                                            <th>First Name</th>
                                            <td><?php echo htmlspecialchars($patient_data['PAT_FIRST_NAME']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Middle Name</th>
                                            <td><?php echo htmlspecialchars($patient_data['PAT_MIDDLE_NAME']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Last Name</th>
                                            <td><?php echo htmlspecialchars($patient_data['PAT_LAST_NAME']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Email</th>
                                            <td><?php echo htmlspecialchars($patient_data['PAT_EMAIL']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Contact Number</th>
                                            <td><?php echo htmlspecialchars($patient_data['PAT_CONTACT_NUM']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Date of Birth</th>
                                            <td><?php echo date('F j, Y', strtotime($patient_data['PAT_DOB'])); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Gender</th>
                                            <td><?php echo htmlspecialchars($patient_data['PAT_GENDER']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Address</th>
                                            <td><?php echo nl2br(htmlspecialchars($patient_data['PAT_ADDRESS'])); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Created At</th>
                                            <td><?php echo date('F j, Y g:i A', strtotime($patient_data['PAT_CREATED_AT'])); ?></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6>Recent Appointments</h6>
                                    <?php if (empty($appointments)): ?>
                                        <p class="text-muted">No appointments found.</p>
                                    <?php else: ?>
                                        <div class="list-group">
                                            <?php foreach ($appointments as $appt): ?>
                                                <div class="list-group-item">
                                                    <div class="d-flex w-100 justify-content-between">
                                                        <h6 class="mb-1">Dr. <?php echo htmlspecialchars($appt['DOC_FIRST_NAME'] . ' ' . $appt['DOC_LAST_NAME']); ?></h6>
                                                        <small class="text-<?php
                                                            switch($appt['STAT_NAME']) {
                                                                case 'Scheduled': echo 'primary'; break;
                                                                case 'Completed': echo 'success'; break;
                                                                case 'Cancelled': echo 'danger'; break;
                                                                default: echo 'secondary';
                                                            }
                                                        ?>"><?php echo $appt['STAT_NAME']; ?></small>
                                                    </div>
                                                    <p class="mb-1"><?php echo htmlspecialchars($appt['SERV_NAME']); ?></p>
                                                    <small class="text-muted">
                                                        <?php echo date('M j, Y', strtotime($appt['APPT_DATE'])); ?> at
                                                        <?php echo date('h:i A', strtotime($appt['APPT_TIME'])); ?>
                                                    </small>
                                                </div>
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
