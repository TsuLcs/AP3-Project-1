<?php
$page_title = "Doctor Management";
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
        $stmt = $pdo->prepare("INSERT INTO DOCTOR (DOC_FIRST_NAME, DOC_LAST_NAME, DOC_MIDDLE_NAME, DOC_CONTACT_NUM, DOC_EMAIL, SPEC_ID) VALUES (?, ?, ?, ?, ?, ?)");
        try {
            $stmt->execute([
                $_POST['first_name'],
                $_POST['last_name'],
                $_POST['middle_name'],
                $_POST['contact_num'],
                $_POST['email'],
                $_POST['spec_id']
            ]);
            $message = '<div class="alert alert-success">Doctor added successfully!</div>';
            $action = 'list';
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Error adding doctor: ' . $e->getMessage() . '</div>';
        }
    } elseif ($action == 'edit' && isset($_POST['id'])) {
        $stmt = $pdo->prepare("UPDATE DOCTOR SET DOC_FIRST_NAME = ?, DOC_LAST_NAME = ?, DOC_MIDDLE_NAME = ?, DOC_CONTACT_NUM = ?, DOC_EMAIL = ?, SPEC_ID = ? WHERE DOC_ID = ?");
        try {
            $stmt->execute([
                $_POST['first_name'],
                $_POST['last_name'],
                $_POST['middle_name'],
                $_POST['contact_num'],
                $_POST['email'],
                $_POST['spec_id'],
                $_POST['id']
            ]);
            $message = '<div class="alert alert-success">Doctor updated successfully!</div>';
            $action = 'list';
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Error updating doctor: ' . $e->getMessage() . '</div>';
        }
    }
}

// Handle delete action
if (isset($_GET['delete']) && (isset($_SESSION['user_is_superadmin']) || isset($_SESSION['staff_id']))) {
    $stmt = $pdo->prepare("DELETE FROM DOCTOR WHERE DOC_ID = ?");
    try {
        $stmt->execute([$_GET['delete']]);
        $message = '<div class="alert alert-success">Doctor deleted successfully!</div>';
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Error deleting doctor: ' . $e->getMessage() . '</div>';
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Doctor Management</h1>
                <?php if ($action == 'list' && (isset($_SESSION['user_is_superadmin']) || isset($_SESSION['staff_id']))): ?>
                    <a href="?action=add" class="btn btn-primary">Add New Doctor</a>
                <?php endif; ?>
            </div>

            <?php echo $message; ?>

            <?php if ($action == 'list'): ?>
                <!-- Doctor List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">All Doctors</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $search = $_GET['search'] ?? '';
                        $query = "SELECT d.*, s.SPEC_NAME FROM DOCTOR d LEFT JOIN SPECIALIZATION s ON d.SPEC_ID = s.SPEC_ID WHERE 1=1";
                        $params = [];

                        if (!empty($search)) {
                            $query .= " AND (d.DOC_FIRST_NAME LIKE ? OR d.DOC_LAST_NAME LIKE ? OR d.DOC_EMAIL LIKE ?)";
                            $search_term = "%$search%";
                            $params = [$search_term, $search_term, $search_term];
                        }

                        $query .= " ORDER BY d.DOC_FIRST_NAME, d.DOC_LAST_NAME";
                        $stmt = $pdo->prepare($query);
                        $stmt->execute($params);
                        $doctors = $stmt->fetchAll();
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
                                        <th>Specialization</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($doctors)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">No doctors found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($doctors as $doctor): ?>
                                            <tr>
                                                <td><?php echo $doctor['DOC_ID']; ?></td>
                                                <td>
                                                    Dr. <?php echo htmlspecialchars($doctor['DOC_FIRST_NAME'] . ' ' .
                                                          ($doctor['DOC_MIDDLE_NAME'] ? $doctor['DOC_MIDDLE_NAME'] . ' ' : '') .
                                                          $doctor['DOC_LAST_NAME']); ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($doctor['DOC_EMAIL']); ?></td>
                                                <td><?php echo htmlspecialchars($doctor['DOC_CONTACT_NUM']); ?></td>
                                                <td>
                                                    <?php if ($doctor['SPEC_NAME']): ?>
                                                        <span class="badge bg-primary"><?php echo htmlspecialchars($doctor['SPEC_NAME']); ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">Not specified</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="?action=view&id=<?php echo $doctor['DOC_ID']; ?>" class="btn btn-outline-primary">View</a>
                                                        <a href="?action=edit&id=<?php echo $doctor['DOC_ID']; ?>" class="btn btn-outline-secondary">Edit</a>
                                                        <?php if (isset($_SESSION['user_is_superadmin']) || isset($_SESSION['staff_id'])): ?>
                                                            <a href="?delete=<?php echo $doctor['DOC_ID']; ?>"
                                                               class="btn btn-outline-danger btn-delete"
                                                               onclick="return confirm('Are you sure you want to delete this doctor?')">Delete</a>
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
                $doctor_data = [];
                if ($action == 'edit' && isset($_GET['id'])) {
                    $stmt = $pdo->prepare("SELECT * FROM DOCTOR WHERE DOC_ID = ?");
                    $stmt->execute([$_GET['id']]);
                    $doctor_data = $stmt->fetch();
                    if (!$doctor_data) {
                        echo '<div class="alert alert-danger">Doctor not found.</div>';
                        include '../includes/tail.php';
                        exit();
                    }
                }

                // Get specializations for dropdown
                $specs_stmt = $pdo->query("SELECT * FROM SPECIALIZATION ORDER BY SPEC_NAME");
                $specializations = $specs_stmt->fetchAll();
                ?>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><?php echo $action == 'add' ? 'Add New Doctor' : 'Edit Doctor'; ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <?php if ($action == 'edit'): ?>
                                <input type="hidden" name="id" value="<?php echo $doctor_data['DOC_ID']; ?>">
                            <?php endif; ?>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="first_name" name="first_name"
                                           value="<?php echo htmlspecialchars($doctor_data['DOC_FIRST_NAME'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="middle_name" class="form-label">Middle Name</label>
                                    <input type="text" class="form-control" id="middle_name" name="middle_name"
                                           value="<?php echo htmlspecialchars($doctor_data['DOC_MIDDLE_NAME'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="last_name" name="last_name"
                                           value="<?php echo htmlspecialchars($doctor_data['DOC_LAST_NAME'] ?? ''); ?>" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email"
                                           value="<?php echo htmlspecialchars($doctor_data['DOC_EMAIL'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="contact_num" class="form-label">Contact Number <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" id="contact_num" name="contact_num"
                                           value="<?php echo htmlspecialchars($doctor_data['DOC_CONTACT_NUM'] ?? ''); ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="spec_id" class="form-label">Specialization</label>
                                <select class="form-control" id="spec_id" name="spec_id">
                                    <option value="">Select Specialization</option>
                                    <?php foreach ($specializations as $spec): ?>
                                        <option value="<?php echo $spec['SPEC_ID']; ?>"
                                            <?php echo (isset($doctor_data['SPEC_ID']) && $doctor_data['SPEC_ID'] == $spec['SPEC_ID']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($spec['SPEC_NAME']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary"><?php echo $action == 'add' ? 'Add Doctor' : 'Update Doctor'; ?></button>
                                <a href="?" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>

            <?php elseif ($action == 'view' && isset($_GET['id'])): ?>
                <!-- View Doctor Details -->
                <?php
                $stmt = $pdo->prepare("SELECT d.*, s.SPEC_NAME FROM DOCTOR d LEFT JOIN SPECIALIZATION s ON d.SPEC_ID = s.SPEC_ID WHERE d.DOC_ID = ?");
                $stmt->execute([$_GET['id']]);
                $doctor_data = $stmt->fetch();

                if (!$doctor_data) {
                    echo '<div class="alert alert-danger">Doctor not found.</div>';
                } else {
                    // Get doctor's schedule
                    $schedule_stmt = $pdo->prepare("SELECT * FROM SCHEDULE WHERE DOC_ID = ? ORDER BY SCHED_DAYS, SCHED_START_TIME");
                    $schedule_stmt->execute([$_GET['id']]);
                    $schedules = $schedule_stmt->fetchAll();

                    // Get today's appointments
                    $today = date('Y-m-d');
                    $appointments_stmt = $pdo->prepare("
                        SELECT a.*, p.PAT_FIRST_NAME, p.PAT_LAST_NAME, s.SERV_NAME, st.STAT_NAME
                        FROM APPOINTMENT a
                        JOIN PATIENT p ON a.PAT_ID = p.PAT_ID
                        JOIN SERVICE s ON a.SERV_ID = s.SERV_ID
                        JOIN STATUS st ON a.STAT_ID = st.STAT_ID
                        WHERE a.DOC_ID = ? AND a.APPT_DATE = ?
                        ORDER BY a.APPT_TIME
                    ");
                    $appointments_stmt->execute([$_GET['id'], $today]);
                    $appointments = $appointments_stmt->fetchAll();
                ?>
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Doctor Details</h5>
                            <div class="btn-group">
                                <a href="?action=edit&id=<?php echo $doctor_data['DOC_ID']; ?>" class="btn btn-outline-secondary">Edit</a>
                                <a href="?" class="btn btn-outline-primary">Back to List</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="30%">Doctor ID</th>
                                            <td><?php echo $doctor_data['DOC_ID']; ?></td>
                                        </tr>
                                        <tr>
                                            <th>First Name</th>
                                            <td><?php echo htmlspecialchars($doctor_data['DOC_FIRST_NAME']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Middle Name</th>
                                            <td><?php echo htmlspecialchars($doctor_data['DOC_MIDDLE_NAME']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Last Name</th>
                                            <td><?php echo htmlspecialchars($doctor_data['DOC_LAST_NAME']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Email</th>
                                            <td><?php echo htmlspecialchars($doctor_data['DOC_EMAIL']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Contact Number</th>
                                            <td><?php echo htmlspecialchars($doctor_data['DOC_CONTACT_NUM']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Specialization</th>
                                            <td><?php echo htmlspecialchars($doctor_data['SPEC_NAME']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Created At</th>
                                            <td><?php echo date('F j, Y g:i A', strtotime($doctor_data['DOC_CREATED_AT'])); ?></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6>Today's Appointments (<?php echo date('M j, Y'); ?>)</h6>
                                    <?php if (empty($appointments)): ?>
                                        <p class="text-muted">No appointments for today.</p>
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
                                                    <p class="mb-1"><?php echo htmlspecialchars($appt['SERV_NAME']); ?></p>
                                                    <small class="text-muted">
                                                        <?php echo date('h:i A', strtotime($appt['APPT_TIME'])); ?>
                                                    </small>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                    <h6 class="mt-4">Schedule</h6>
                                    <?php if (empty($schedules)): ?>
                                        <p class="text-muted">No schedule set.</p>
                                    <?php else: ?>
                                        <div class="list-group">
                                            <?php foreach ($schedules as $schedule): ?>
                                                <div class="list-group-item">
                                                    <strong><?php echo htmlspecialchars($schedule['SCHED_DAYS']); ?></strong><br>
                                                    <small class="text-muted">
                                                        <?php echo date('h:i A', strtotime($schedule['SCHED_START_TIME'])); ?> -
                                                        <?php echo date('h:i A', strtotime($schedule['SCHED_END_TIME'])); ?>
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
