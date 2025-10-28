<?php
$page_title = "Appointment Management";
include '../includes/head.php';
require_once '../data/dbconfig.php';

// Check access - All roles can access but with different permissions
if (!isset($_SESSION['user_id'])) {
    header("Location: ../pages/login.php");
    exit();
}

$action = $_GET['action'] ?? 'list';
$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($action == 'create') {
        try {
            // Generate appointment ID using stored procedure
            $stmt = $pdo->prepare("CALL GenerateAppointmentID(?, @new_id)");
            $stmt->execute([$_POST['appt_date']]);

            $stmt = $pdo->query("SELECT @new_id as new_appointment_id");
            $result = $stmt->fetch();
            $appointment_id = $result['new_appointment_id'];

            // Insert appointment
            $stmt = $pdo->prepare("INSERT INTO APPOINTMENT (APPT_ID, APPT_DATE, APPT_TIME, PAT_ID, DOC_ID, SERV_ID, STAT_ID) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $appointment_id,
                $_POST['appt_date'],
                $_POST['appt_time'],
                $_POST['pat_id'],
                $_POST['doc_id'],
                $_POST['serv_id'],
                1 // Default status: Scheduled
            ]);

            $message = '<div class="alert alert-success">Appointment created successfully! Appointment ID: <strong>' . $appointment_id . '</strong></div>';
            $action = 'list';
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Error creating appointment: ' . $e->getMessage() . '</div>';
        }
    } elseif ($action == 'edit' && isset($_POST['id'])) {
        $stmt = $pdo->prepare("UPDATE APPOINTMENT SET APPT_DATE = ?, APPT_TIME = ?, PAT_ID = ?, DOC_ID = ?, SERV_ID = ?, STAT_ID = ? WHERE APPT_ID = ?");
        try {
            $stmt->execute([
                $_POST['appt_date'],
                $_POST['appt_time'],
                $_POST['pat_id'],
                $_POST['doc_id'],
                $_POST['serv_id'],
                $_POST['stat_id'],
                $_POST['id']
            ]);
            $message = '<div class="alert alert-success">Appointment updated successfully!</div>';
            $action = 'list';
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Error updating appointment: ' . $e->getMessage() . '</div>';
        }
    }
}

// Handle cancel action
if (isset($_GET['cancel']) && (isset($_SESSION['user_is_superadmin']) || isset($_SESSION['staff_id']) || isset($_SESSION['pat_id']))) {
    $stmt = $pdo->prepare("UPDATE APPOINTMENT SET STAT_ID = 3 WHERE APPT_ID = ?"); // 3 = Cancelled
    try {
        $stmt->execute([$_GET['cancel']]);
        $message = '<div class="alert alert-success">Appointment cancelled successfully!</div>';
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Error cancelling appointment: ' . $e->getMessage() . '</div>';
    }
}

// Handle complete action (for doctors)
if (isset($_GET['complete']) && isset($_SESSION['doc_id'])) {
    $stmt = $pdo->prepare("UPDATE APPOINTMENT SET STAT_ID = 2 WHERE APPT_ID = ? AND DOC_ID = ?"); // 2 = Completed
    try {
        $stmt->execute([$_GET['complete'], $_SESSION['doc_id']]);
        $message = '<div class="alert alert-success">Appointment marked as completed!</div>';
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Error completing appointment: ' . $e->getMessage() . '</div>';
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Appointment Management</h1>
                <?php if ($action == 'list' && (isset($_SESSION['user_is_superadmin']) || isset($_SESSION['staff_id']) || isset($_SESSION['pat_id']))): ?>
                    <a href="?action=create" class="btn btn-primary">Create New Appointment</a>
                <?php endif; ?>
            </div>

            <?php echo $message; ?>

            <?php if ($action == 'list'): ?>
                <!-- Appointment List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">All Appointments</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        // Build query based on user role
                        if (isset($_SESSION['pat_id'])) {
                            // Patient can only see their own appointments
                            $query = "SELECT a.*, p.PAT_FIRST_NAME, p.PAT_LAST_NAME, d.DOC_FIRST_NAME, d.DOC_LAST_NAME,
                                             s.SERV_NAME, st.STAT_NAME, spec.SPEC_NAME
                                     FROM APPOINTMENT a
                                     JOIN PATIENT p ON a.PAT_ID = p.PAT_ID
                                     JOIN DOCTOR d ON a.DOC_ID = d.DOC_ID
                                     LEFT JOIN SPECIALIZATION spec ON d.SPEC_ID = spec.SPEC_ID
                                     JOIN SERVICE s ON a.SERV_ID = s.SERV_ID
                                     JOIN STATUS st ON a.STAT_ID = st.STAT_ID
                                     WHERE a.PAT_ID = ?
                                     ORDER BY a.APPT_DATE DESC, a.APPT_TIME DESC";
                            $stmt = $pdo->prepare($query);
                            $stmt->execute([$_SESSION['pat_id']]);
                        } elseif (isset($_SESSION['doc_id'])) {
                            // Doctor can only see their own appointments
                            $query = "SELECT a.*, p.PAT_FIRST_NAME, p.PAT_LAST_NAME, d.DOC_FIRST_NAME, d.DOC_LAST_NAME,
                                             s.SERV_NAME, st.STAT_NAME, spec.SPEC_NAME
                                     FROM APPOINTMENT a
                                     JOIN PATIENT p ON a.PAT_ID = p.PAT_ID
                                     JOIN DOCTOR d ON a.DOC_ID = d.DOC_ID
                                     LEFT JOIN SPECIALIZATION spec ON d.SPEC_ID = spec.SPEC_ID
                                     JOIN SERVICE s ON a.SERV_ID = s.SERV_ID
                                     JOIN STATUS st ON a.STAT_ID = st.STAT_ID
                                     WHERE a.DOC_ID = ?
                                     ORDER BY a.APPT_DATE DESC, a.APPT_TIME DESC";
                            $stmt = $pdo->prepare($query);
                            $stmt->execute([$_SESSION['doc_id']]);
                        } else {
                            // Staff and Super Admin can see all appointments
                            $query = "SELECT a.*, p.PAT_FIRST_NAME, p.PAT_LAST_NAME, d.DOC_FIRST_NAME, d.DOC_LAST_NAME,
                                             s.SERV_NAME, st.STAT_NAME, spec.SPEC_NAME
                                     FROM APPOINTMENT a
                                     JOIN PATIENT p ON a.PAT_ID = p.PAT_ID
                                     JOIN DOCTOR d ON a.DOC_ID = d.DOC_ID
                                     LEFT JOIN SPECIALIZATION spec ON d.SPEC_ID = spec.SPEC_ID
                                     JOIN SERVICE s ON a.SERV_ID = s.SERV_ID
                                     JOIN STATUS st ON a.STAT_ID = st.STAT_ID
                                     ORDER BY a.APPT_DATE DESC, a.APPT_TIME DESC";
                            $stmt = $pdo->query($query);
                        }
                        $appointments = $stmt->fetchAll();
                        ?>

                        <!-- Search Form -->
                        <form method="GET" class="mb-3">
                            <div class="input-group">
                                <input type="text" name="search" class="form-control" placeholder="Search by appointment ID..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                                <button type="submit" class="btn btn-outline-primary">Search</button>
                                <?php if (!empty($_GET['search'])): ?>
                                    <a href="?" class="btn btn-outline-secondary">Clear</a>
                                <?php endif; ?>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Appointment ID</th>
                                        <th>Date & Time</th>
                                        <th>Patient</th>
                                        <th>Doctor</th>
                                        <th>Specialization</th>
                                        <th>Service</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($appointments)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center text-muted">No appointments found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($appointments as $appt): ?>
                                            <tr>
                                                <td><code><?php echo htmlspecialchars($appt['APPT_ID']); ?></code></td>
                                                <td>
                                                    <?php echo date('M j, Y', strtotime($appt['APPT_DATE'])); ?><br>
                                                    <small class="text-muted"><?php echo date('h:i A', strtotime($appt['APPT_TIME'])); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($appt['PAT_FIRST_NAME'] . ' ' . $appt['PAT_LAST_NAME']); ?></td>
                                                <td>Dr. <?php echo htmlspecialchars($appt['DOC_FIRST_NAME'] . ' ' . $appt['DOC_LAST_NAME']); ?></td>
                                                <td><?php echo htmlspecialchars($appt['SPEC_NAME']); ?></td>
                                                <td><?php echo htmlspecialchars($appt['SERV_NAME']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php
                                                        switch($appt['STAT_NAME']) {
                                                            case 'Scheduled': echo 'primary'; break;
                                                            case 'Completed': echo 'success'; break;
                                                            case 'Cancelled': echo 'danger'; break;
                                                            default: echo 'secondary';
                                                        }
                                                    ?>">
                                                        <?php echo htmlspecialchars($appt['STAT_NAME']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="?action=view&id=<?php echo $appt['APPT_ID']; ?>" class="btn btn-outline-primary">View</a>
                                                        <?php if (isset($_SESSION['user_is_superadmin']) || isset($_SESSION['staff_id'])): ?>
                                                            <a href="?action=edit&id=<?php echo $appt['APPT_ID']; ?>" class="btn btn-outline-secondary">Edit</a>
                                                        <?php endif; ?>
                                                        <?php if ($appt['STAT_NAME'] == 'Scheduled'): ?>
                                                            <?php if (isset($_SESSION['doc_id']) && $_SESSION['doc_id'] == $appt['DOC_ID']): ?>
                                                                <a href="?complete=<?php echo $appt['APPT_ID']; ?>"
                                                                   class="btn btn-outline-success"
                                                                   onclick="return confirm('Mark this appointment as completed?')">Complete</a>
                                                            <?php endif; ?>
                                                            <?php if (isset($_SESSION['user_is_superadmin']) || isset($_SESSION['staff_id']) ||
                                                                     (isset($_SESSION['pat_id']) && $_SESSION['pat_id'] == $appt['PAT_ID'])): ?>
                                                                <a href="?cancel=<?php echo $appt['APPT_ID']; ?>"
                                                                   class="btn btn-outline-danger"
                                                                   onclick="return confirm('Are you sure you want to cancel this appointment?')">Cancel</a>
                                                            <?php endif; ?>
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
                $appointment_data = [];
                if ($action == 'edit' && isset($_GET['id'])) {
                    $stmt = $pdo->prepare("SELECT a.*, p.PAT_FIRST_NAME, p.PAT_LAST_NAME, d.DOC_FIRST_NAME, d.DOC_LAST_NAME
                                          FROM APPOINTMENT a
                                          JOIN PATIENT p ON a.PAT_ID = p.PAT_ID
                                          JOIN DOCTOR d ON a.DOC_ID = d.DOC_ID
                                          WHERE a.APPT_ID = ?");
                    $stmt->execute([$_GET['id']]);
                    $appointment_data = $stmt->fetch();
                    if (!$appointment_data) {
                        echo '<div class="alert alert-danger">Appointment not found.</div>';
                        include '../includes/tail.php';
                        exit();
                    }
                }

                // Get data for dropdowns
                $patients_stmt = $pdo->query("SELECT * FROM PATIENT ORDER BY PAT_FIRST_NAME, PAT_LAST_NAME");
                $patients = $patients_stmt->fetchAll();

                $doctors_stmt = $pdo->query("SELECT d.*, s.SPEC_NAME FROM DOCTOR d LEFT JOIN SPECIALIZATION s ON d.SPEC_ID = s.SPEC_ID ORDER BY d.DOC_FIRST_NAME, d.DOC_LAST_NAME");
                $doctors = $doctors_stmt->fetchAll();

                $services_stmt = $pdo->query("SELECT * FROM SERVICE ORDER BY SERV_NAME");
                $services = $services_stmt->fetchAll();

                $status_stmt = $pdo->query("SELECT * FROM STATUS ORDER BY STAT_NAME");
                $statuses = $status_stmt->fetchAll();
                ?>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><?php echo $action == 'create' ? 'Create New Appointment' : 'Edit Appointment'; ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <?php if ($action == 'edit'): ?>
                                <input type="hidden" name="id" value="<?php echo $appointment_data['APPT_ID']; ?>">
                            <?php endif; ?>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="pat_id" class="form-label">Patient <span class="text-danger">*</span></label>
                                    <select class="form-control" id="pat_id" name="pat_id" required
                                        <?php echo (isset($_SESSION['pat_id']) && !isset($_SESSION['user_is_superadmin']) && !isset($_SESSION['staff_id'])) ? 'disabled' : ''; ?>>
                                        <option value="">Select Patient</option>
                                        <?php foreach ($patients as $patient): ?>
                                            <option value="<?php echo $patient['PAT_ID']; ?>"
                                                <?php
                                                    if (isset($_SESSION['pat_id']) && !isset($_SESSION['user_is_superadmin']) && !isset($_SESSION['staff_id'])) {
                                                        echo ($_SESSION['pat_id'] == $patient['PAT_ID']) ? 'selected' : '';
                                                    } else {
                                                        echo (isset($appointment_data['PAT_ID']) && $appointment_data['PAT_ID'] == $patient['PAT_ID']) ? 'selected' : '';
                                                    }
                                                ?>>
                                                <?php echo htmlspecialchars($patient['PAT_FIRST_NAME'] . ' ' . $patient['PAT_LAST_NAME']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (isset($_SESSION['pat_id']) && !isset($_SESSION['user_is_superadmin']) && !isset($_SESSION['staff_id'])): ?>
                                        <input type="hidden" name="pat_id" value="<?php echo $_SESSION['pat_id']; ?>">
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="doc_id" class="form-label">Doctor <span class="text-danger">*</span></label>
                                    <select class="form-control" id="doc_id" name="doc_id" required>
                                        <option value="">Select Doctor</option>
                                        <?php foreach ($doctors as $doctor): ?>
                                            <option value="<?php echo $doctor['DOC_ID']; ?>"
                                                <?php echo (isset($appointment_data['DOC_ID']) && $appointment_data['DOC_ID'] == $doctor['DOC_ID']) ? 'selected' : ''; ?>>
                                                Dr. <?php echo htmlspecialchars($doctor['DOC_FIRST_NAME'] . ' ' . $doctor['DOC_LAST_NAME']); ?>
                                                (<?php echo htmlspecialchars($doctor['SPEC_NAME']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="serv_id" class="form-label">Service <span class="text-danger">*</span></label>
                                    <select class="form-control" id="serv_id" name="serv_id" required>
                                        <option value="">Select Service</option>
                                        <?php foreach ($services as $service): ?>
                                            <option value="<?php echo $service['SERV_ID']; ?>"
                                                <?php echo (isset($appointment_data['SERV_ID']) && $appointment_data['SERV_ID'] == $service['SERV_ID']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($service['SERV_NAME']); ?> - ₱<?php echo number_format($service['SERV_PRICE'], 2); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php if ($action == 'edit' && (isset($_SESSION['user_is_superadmin']) || isset($_SESSION['staff_id']))): ?>
                                <div class="col-md-6 mb-3">
                                    <label for="stat_id" class="form-label">Status <span class="text-danger">*</span></label>
                                    <select class="form-control" id="stat_id" name="stat_id" required>
                                        <option value="">Select Status</option>
                                        <?php foreach ($statuses as $status): ?>
                                            <option value="<?php echo $status['STAT_ID']; ?>"
                                                <?php echo (isset($appointment_data['STAT_ID']) && $appointment_data['STAT_ID'] == $status['STAT_ID']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($status['STAT_NAME']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="appt_date" class="form-label">Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="appt_date" name="appt_date"
                                           value="<?php echo isset($appointment_data['APPT_DATE']) ? $appointment_data['APPT_DATE'] : ''; ?>"
                                           min="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="appt_time" class="form-label">Time <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" id="appt_time" name="appt_time"
                                           value="<?php echo isset($appointment_data['APPT_TIME']) ? substr($appointment_data['APPT_TIME'], 0, 5) : ''; ?>" required>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary"><?php echo $action == 'create' ? 'Create Appointment' : 'Update Appointment'; ?></button>
                                <a href="?" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>

            <?php elseif ($action == 'view' && isset($_GET['id'])): ?>
                <!-- View Appointment Details -->
                <?php
                $stmt = $pdo->prepare("
                    SELECT a.*, p.*, d.*, s.SERV_NAME, s.SERV_PRICE, st.STAT_NAME, spec.SPEC_NAME,
                           pm.PYMT_METH_NAME, ps.PYMT_STAT_NAME, pay.PAYMENT_AMOUNT
                    FROM APPOINTMENT a
                    JOIN PATIENT p ON a.PAT_ID = p.PAT_ID
                    JOIN DOCTOR d ON a.DOC_ID = d.DOC_ID
                    LEFT JOIN SPECIALIZATION spec ON d.SPEC_ID = spec.SPEC_ID
                    JOIN SERVICE s ON a.SERV_ID = s.SERV_ID
                    JOIN STATUS st ON a.STAT_ID = st.STAT_ID
                    LEFT JOIN PAYMENT pay ON a.APPT_ID = pay.APPT_ID
                    LEFT JOIN PAYMENT_METHOD pm ON pay.PYMT_METH_ID = pm.PYMT_METH_ID
                    LEFT JOIN PAYMENT_STATUS ps ON pay.PYMT_STAT_ID = ps.PYMT_STAT_ID
                    WHERE a.APPT_ID = ?
                ");
                $stmt->execute([$_GET['id']]);
                $appointment_data = $stmt->fetch();

                if (!$appointment_data) {
                    echo '<div class="alert alert-danger">Appointment not found.</div>';
                } else {
                ?>
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Appointment Details</h5>
                            <div class="btn-group">
                                <?php if (isset($_SESSION['user_is_superadmin']) || isset($_SESSION['staff_id'])): ?>
                                    <a href="?action=edit&id=<?php echo $appointment_data['APPT_ID']; ?>" class="btn btn-outline-secondary">Edit</a>
                                <?php endif; ?>
                                <a href="?" class="btn btn-outline-primary">Back to List</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Appointment Information</h6>
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="30%">Appointment ID</th>
                                            <td><code><?php echo $appointment_data['APPT_ID']; ?></code></td>
                                        </tr>
                                        <tr>
                                            <th>Date</th>
                                            <td><?php echo date('F j, Y', strtotime($appointment_data['APPT_DATE'])); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Time</th>
                                            <td><?php echo date('h:i A', strtotime($appointment_data['APPT_TIME'])); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Service</th>
                                            <td>
                                                <?php echo htmlspecialchars($appointment_data['SERV_NAME']); ?><br>
                                                <small class="text-muted">₱<?php echo number_format($appointment_data['SERV_PRICE'], 2); ?></small>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Status</th>
                                            <td>
                                                <span class="badge bg-<?php
                                                    switch($appointment_data['STAT_NAME']) {
                                                        case 'Scheduled': echo 'primary'; break;
                                                        case 'Completed': echo 'success'; break;
                                                        case 'Cancelled': echo 'danger'; break;
                                                        default: echo 'secondary';
                                                    }
                                                ?>">
                                                    <?php echo htmlspecialchars($appointment_data['STAT_NAME']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Created At</th>
                                            <td><?php echo date('F j, Y g:i A', strtotime($appointment_data['APPT_CREATED_AT'])); ?></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6>Patient Information</h6>
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="30%">Name</th>
                                            <td><?php echo htmlspecialchars($appointment_data['PAT_FIRST_NAME'] . ' ' . $appointment_data['PAT_LAST_NAME']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Email</th>
                                            <td><?php echo htmlspecialchars($appointment_data['PAT_EMAIL']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Contact</th>
                                            <td><?php echo htmlspecialchars($appointment_data['PAT_CONTACT_NUM']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Date of Birth</th>
                                            <td><?php echo date('F j, Y', strtotime($appointment_data['PAT_DOB'])); ?></td>
                                        </tr>
                                    </table>

                                    <h6 class="mt-4">Doctor Information</h6>
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="30%">Name</th>
                                            <td>Dr. <?php echo htmlspecialchars($appointment_data['DOC_FIRST_NAME'] . ' ' . $appointment_data['DOC_LAST_NAME']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Specialization</th>
                                            <td><?php echo htmlspecialchars($appointment_data['SPEC_NAME']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Contact</th>
                                            <td><?php echo htmlspecialchars($appointment_data['DOC_CONTACT_NUM']); ?></td>
                                        </tr>
                                    </table>

                                    <?php if ($appointment_data['PYMT_METH_NAME']): ?>
                                    <h6 class="mt-4">Payment Information</h6>
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="30%">Amount</th>
                                            <td>₱<?php echo number_format($appointment_data['PAYMENT_AMOUNT'], 2); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Method</th>
                                            <td><?php echo htmlspecialchars($appointment_data['PYMT_METH_NAME']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Status</th>
                                            <td>
                                                <span class="badge bg-<?php
                                                    switch($appointment_data['PYMT_STAT_NAME']) {
                                                        case 'Paid': echo 'success'; break;
                                                        case 'Pending': echo 'warning'; break;
                                                        case 'Refunded': echo 'info'; break;
                                                        default: echo 'secondary';
                                                    }
                                                ?>">
                                                    <?php echo htmlspecialchars($appointment_data['PYMT_STAT_NAME']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
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
