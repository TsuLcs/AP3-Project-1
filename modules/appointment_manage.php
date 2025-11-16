<?php
$page_title = "Appointment Management";
include '../includes/head.php';
require_once '../data/dbconfig.php';

// Check access - Staff, Doctors, and Super Admin can access
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$action = $_GET['action'] ?? 'list';
$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($action == 'add') {
        try {
            // Generate unique appointment ID
            $appt_id = 'APT' . date('Ymd') . str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
            
            $stmt = $pdo->prepare("INSERT INTO APPOINTMENT (APPT_ID, APPT_DATE, APPT_TIME, PAT_ID, DOC_ID, SERV_ID, STAT_ID) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $appt_id,
                $_POST['appt_date'],
                $_POST['appt_time'],
                $_POST['pat_id'],
                $_POST['doc_id'],
                $_POST['serv_id'],
                $_POST['stat_id'] ?? 1 // Default to scheduled status
            ]);
            $message = '<div class="alert alert-success">Appointment booked successfully! Appointment ID: ' . $appt_id . '</div>';
            $action = 'list';
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Error booking appointment: ' . $e->getMessage() . '</div>';
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

// Handle status update
if (isset($_GET['update_status'])) {
    $stmt = $pdo->prepare("UPDATE APPOINTMENT SET STAT_ID = ? WHERE APPT_ID = ?");
    try {
        $stmt->execute([$_GET['status'], $_GET['update_status']]);
        $message = '<div class="alert alert-success">Appointment status updated successfully!</div>';
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Error updating status: ' . $e->getMessage() . '</div>';
    }
}

// Handle delete action
if (isset($_GET['delete']) && (isset($_SESSION['user_is_superadmin']) || isset($_SESSION['staff_id']))) {
    $stmt = $pdo->prepare("DELETE FROM APPOINTMENT WHERE APPT_ID = ?");
    try {
        $stmt->execute([$_GET['delete']]);
        $message = '<div class="alert alert-success">Appointment deleted successfully!</div>';
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Error deleting appointment: ' . $e->getMessage() . '</div>';
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-calendar-check me-2"></i>Appointment Management
                </h1>
                <?php if ($action == 'list' && (isset($_SESSION['user_is_superadmin']) || isset($_SESSION['staff_id']) || isset($_SESSION['doc_id']))): ?>
                    <a href="?action=add" class="btn btn-primary">
                        <i class="fas fa-calendar-plus me-2"></i>Book Appointment
                    </a>
                <?php endif; ?>
            </div>

            <?php echo $message; ?>

            <?php if ($action == 'list'): ?>
                <!-- Appointment List -->
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h6 class="m-0 font-weight-bold">
                            <i class="fas fa-list me-2"></i>All Appointments
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php
                        // Build query based on user role
                        $query = "
                            SELECT a.*, 
                                   p.PAT_FIRST_NAME, p.PAT_LAST_NAME, p.PAT_CONTACT_NUM,
                                   d.DOC_FIRST_NAME, d.DOC_LAST_NAME,
                                   s.SERV_NAME,
                                   st.STAT_NAME
                            FROM APPOINTMENT a
                            JOIN PATIENT p ON a.PAT_ID = p.PAT_ID
                            JOIN DOCTOR d ON a.DOC_ID = d.DOC_ID
                            JOIN SERVICE s ON a.SERV_ID = s.SERV_ID
                            JOIN STATUS st ON a.STAT_ID = st.STAT_ID
                        ";
                        
                        $params = [];
                        
                        // Filter by role
                        if (isset($_SESSION['doc_id'])) {
                            // Doctor sees only their appointments
                            $query .= " WHERE a.DOC_ID = ?";
                            $params[] = $_SESSION['doc_id'];
                        } elseif (isset($_SESSION['pat_id'])) {
                            // Patient sees only their appointments
                            $query .= " WHERE a.PAT_ID = ?";
                            $params[] = $_SESSION['pat_id'];
                        }
                        
                        $query .= " ORDER BY a.APPT_DATE DESC, a.APPT_TIME DESC";
                        
                        $stmt = $pdo->prepare($query);
                        $stmt->execute($params);
                        $appointments = $stmt->fetchAll();
                        ?>

                        <!-- Filter Form -->
                        <form method="GET" class="row g-3 mb-4">
                            <div class="col-md-3">
                                <input type="date" name="date_filter" class="form-control" 
                                       value="<?php echo $_GET['date_filter'] ?? ''; ?>" 
                                       placeholder="Filter by date">
                            </div>
                            <div class="col-md-3">
                                <select name="status_filter" class="form-control">
                                    <option value="">All Statuses</option>
                                    <?php
                                    $statuses = $pdo->query("SELECT * FROM STATUS")->fetchAll();
                                    foreach ($statuses as $status) {
                                        $selected = ($_GET['status_filter'] ?? '') == $status['STAT_ID'] ? 'selected' : '';
                                        echo "<option value='{$status['STAT_ID']}' $selected>{$status['STAT_NAME']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="search" class="form-control" 
                                       value="<?php echo $_GET['search'] ?? ''; ?>" 
                                       placeholder="Search by patient or doctor name...">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-outline-primary w-100">Filter</button>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Appt ID</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Patient</th>
                                        <th>Doctor</th>
                                        <th>Service</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($appointments)): ?>
                                        <tr>
                                            <td colspan="9" class="text-center text-muted py-4">
                                                <i class="fas fa-calendar-times fa-2x mb-2"></i><br>
                                                No appointments found.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($appointments as $appt): ?>
                                            <tr>
                                                <td><strong><?php echo $appt['APPT_ID']; ?></strong></td>
                                                <td><?php echo date('M j, Y', strtotime($appt['APPT_DATE'])); ?></td>
                                                <td><?php echo date('g:i A', strtotime($appt['APPT_TIME'])); ?></td>
                                                <td>
                                                    <?php echo htmlspecialchars($appt['PAT_FIRST_NAME'] . ' ' . $appt['PAT_LAST_NAME']); ?>
                                                    <br><small class="text-muted"><?php echo $appt['PAT_CONTACT_NUM']; ?></small>
                                                </td>
                                                <td>Dr. <?php echo htmlspecialchars($appt['DOC_FIRST_NAME'] . ' ' . $appt['DOC_LAST_NAME']); ?></td>
                                                <td><?php echo htmlspecialchars($appt['SERV_NAME']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php
                                                        switch($appt['STAT_NAME']) {
                                                            case 'Scheduled': echo 'primary'; break;
                                                            case 'Completed': echo 'success'; break;
                                                            case 'Cancelled': echo 'danger'; break;
                                                            case 'No Show': echo 'warning'; break;
                                                            default: echo 'secondary';
                                                        }
                                                    ?>">
                                                        <?php echo $appt['STAT_NAME']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M j, Y', strtotime($appt['APPT_CREATED_AT'])); ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="?action=view&id=<?php echo $appt['APPT_ID']; ?>" 
                                                           class="btn btn-outline-primary" title="View Details">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <?php if (isset($_SESSION['user_is_superadmin']) || isset($_SESSION['staff_id']) || isset($_SESSION['doc_id'])): ?>
                                                            <a href="?action=edit&id=<?php echo $appt['APPT_ID']; ?>" 
                                                               class="btn btn-outline-secondary" title="Edit">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        <?php if (isset($_SESSION['user_is_superadmin']) || isset($_SESSION['staff_id'])): ?>
                                                            <a href="?delete=<?php echo $appt['APPT_ID']; ?>" 
                                                               class="btn btn-outline-danger btn-delete" 
                                                               onclick="return confirm('Are you sure you want to delete this appointment?')"
                                                               title="Delete">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <!-- Quick Status Update for Staff/Doctors -->
                                                    <?php if (isset($_SESSION['user_is_superadmin']) || isset($_SESSION['staff_id']) || isset($_SESSION['doc_id'])): ?>
                                                        <div class="mt-1">
                                                            <small>
                                                                <a href="?update_status=<?php echo $appt['APPT_ID']; ?>&status=2" 
                                                                   class="text-success" title="Mark as Completed">✓</a> |
                                                                <a href="?update_status=<?php echo $appt['APPT_ID']; ?>&status=3" 
                                                                   class="text-danger" title="Mark as Cancelled">✗</a>
                                                            </small>
                                                        </div>
                                                    <?php endif; ?>
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
                $appt_data = [];
                if ($action == 'edit' && isset($_GET['id'])) {
                    $stmt = $pdo->prepare("
                        SELECT a.*, p.PAT_FIRST_NAME, p.PAT_LAST_NAME, d.DOC_FIRST_NAME, d.DOC_LAST_NAME
                        FROM APPOINTMENT a
                        JOIN PATIENT p ON a.PAT_ID = p.PAT_ID
                        JOIN DOCTOR d ON a.DOC_ID = d.DOC_ID
                        WHERE a.APPT_ID = ?
                    ");
                    $stmt->execute([$_GET['id']]);
                    $appt_data = $stmt->fetch();
                    if (!$appt_data) {
                        echo '<div class="alert alert-danger">Appointment not found.</div>';
                        include '../includes/tail.php';
                        exit();
                    }
                }

                // Get data for dropdowns
                $patients = $pdo->query("SELECT * FROM PATIENT ORDER BY PAT_FIRST_NAME, PAT_LAST_NAME")->fetchAll();
                $doctors = $pdo->query("SELECT * FROM DOCTOR ORDER BY DOC_FIRST_NAME, DOC_LAST_NAME")->fetchAll();
                $services = $pdo->query("SELECT * FROM SERVICE ORDER BY SERV_NAME")->fetchAll();
                $statuses = $pdo->query("SELECT * FROM STATUS ORDER BY STAT_ID")->fetchAll();
                ?>

                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h6 class="m-0 font-weight-bold">
                            <i class="fas fa-calendar-plus me-2"></i>
                            <?php echo $action == 'add' ? 'Book New Appointment' : 'Edit Appointment'; ?>
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <?php if ($action == 'edit'): ?>
                                <input type="hidden" name="id" value="<?php echo $appt_data['APPT_ID']; ?>">
                            <?php endif; ?>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="appt_date" class="form-label">Appointment Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="appt_date" name="appt_date"
                                           value="<?php echo $appt_data['APPT_DATE'] ?? ''; ?>" 
                                           min="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="appt_time" class="form-label">Appointment Time <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" id="appt_time" name="appt_time"
                                           value="<?php echo $appt_data['APPT_TIME'] ?? ''; ?>" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="pat_id" class="form-label">Patient <span class="text-danger">*</span></label>
                                    <select class="form-control" id="pat_id" name="pat_id" required>
                                        <option value="">Select Patient</option>
                                        <?php foreach ($patients as $patient): ?>
                                            <option value="<?php echo $patient['PAT_ID']; ?>"
                                                <?php echo (isset($appt_data['PAT_ID']) && $appt_data['PAT_ID'] == $patient['PAT_ID']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($patient['PAT_FIRST_NAME'] . ' ' . $patient['PAT_LAST_NAME']); ?>
                                                (<?php echo $patient['PAT_CONTACT_NUM']; ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="doc_id" class="form-label">Doctor <span class="text-danger">*</span></label>
                                    <select class="form-control" id="doc_id" name="doc_id" required>
                                        <option value="">Select Doctor</option>
                                        <?php foreach ($doctors as $doctor): ?>
                                            <option value="<?php echo $doctor['DOC_ID']; ?>"
                                                <?php echo (isset($appt_data['DOC_ID']) && $appt_data['DOC_ID'] == $doctor['DOC_ID']) ? 'selected' : ''; ?>>
                                                Dr. <?php echo htmlspecialchars($doctor['DOC_FIRST_NAME'] . ' ' . $doctor['DOC_LAST_NAME']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="serv_id" class="form-label">Service <span class="text-danger">*</span></label>
                                    <select class="form-control" id="serv_id" name="serv_id" required>
                                        <option value="">Select Service</option>
                                        <?php foreach ($services as $service): ?>
                                            <option value="<?php echo $service['SERV_ID']; ?>"
                                                <?php echo (isset($appt_data['SERV_ID']) && $appt_data['SERV_ID'] == $service['SERV_ID']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($service['SERV_NAME']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <?php if ($action == 'edit'): ?>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="stat_id" class="form-label">Status</label>
                                    <select class="form-control" id="stat_id" name="stat_id">
                                        <?php foreach ($statuses as $status): ?>
                                            <option value="<?php echo $status['STAT_ID']; ?>"
                                                <?php echo (isset($appt_data['STAT_ID']) && $appt_data['STAT_ID'] == $status['STAT_ID']) ? 'selected' : ''; ?>>
                                                <?php echo $status['STAT_NAME']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Note:</strong> New appointments are automatically set to "Scheduled" status.
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>
                                    <?php echo $action == 'add' ? 'Book Appointment' : 'Update Appointment'; ?>
                                </button>
                                <a href="?" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

            <?php elseif ($action == 'view' && isset($_GET['id'])): ?>
                <!-- View Appointment Details -->
                <?php
                $stmt = $pdo->prepare("
                    SELECT a.*, 
                           p.PAT_FIRST_NAME, p.PAT_LAST_NAME, p.PAT_EMAIL, p.PAT_CONTACT_NUM, p.PAT_DOB, p.PAT_GENDER,
                           d.DOC_FIRST_NAME, d.DOC_LAST_NAME, d.DOC_EMAIL, d.DOC_CONTACT_NUM,
                           s.SERV_NAME, s.SERV_DESCRIPTION,
                           st.STAT_NAME
                    FROM APPOINTMENT a
                    JOIN PATIENT p ON a.PAT_ID = p.PAT_ID
                    JOIN DOCTOR d ON a.DOC_ID = d.DOC_ID
                    JOIN SERVICE s ON a.SERV_ID = s.SERV_ID
                    JOIN STATUS st ON a.STAT_ID = st.STAT_ID
                    WHERE a.APPT_ID = ?
                ");
                $stmt->execute([$_GET['id']]);
                $appt_data = $stmt->fetch();

                if (!$appt_data) {
                    echo '<div class="alert alert-danger">Appointment not found.</div>';
                } else {
                ?>
                    <div class="card shadow">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold">
                                <i class="fas fa-calendar-alt me-2"></i>Appointment Details
                            </h6>
                            <div class="btn-group">
                                <?php if (isset($_SESSION['user_is_superadmin']) || isset($_SESSION['staff_id']) || isset($_SESSION['doc_id'])): ?>
                                    <a href="?action=edit&id=<?php echo $appt_data['APPT_ID']; ?>" class="btn btn-light btn-sm">
                                        <i class="fas fa-edit me-1"></i>Edit
                                    </a>
                                <?php endif; ?>
                                <a href="?" class="btn btn-light btn-sm">
                                    <i class="fas fa-arrow-left me-1"></i>Back to List
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Appointment Information</h6>
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="30%">Appointment ID</th>
                                            <td><strong><?php echo $appt_data['APPT_ID']; ?></strong></td>
                                        </tr>
                                        <tr>
                                            <th>Date</th>
                                            <td><?php echo date('l, F j, Y', strtotime($appt_data['APPT_DATE'])); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Time</th>
                                            <td><?php echo date('g:i A', strtotime($appt_data['APPT_TIME'])); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Service</th>
                                            <td>
                                                <strong><?php echo htmlspecialchars($appt_data['SERV_NAME']); ?></strong>
                                                <?php if ($appt_data['SERV_DESCRIPTION']): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($appt_data['SERV_DESCRIPTION']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Status</th>
                                            <td>
                                                <span class="badge bg-<?php
                                                    switch($appt_data['STAT_NAME']) {
                                                        case 'Scheduled': echo 'primary'; break;
                                                        case 'Completed': echo 'success'; break;
                                                        case 'Cancelled': echo 'danger'; break;
                                                        case 'No Show': echo 'warning'; break;
                                                        default: echo 'secondary';
                                                    }
                                                ?>">
                                                    <?php echo $appt_data['STAT_NAME']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Created</th>
                                            <td><?php echo date('F j, Y g:i A', strtotime($appt_data['APPT_CREATED_AT'])); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Last Updated</th>
                                            <td><?php echo date('F j, Y g:i A', strtotime($appt_data['APPT_UPDATED_AT'])); ?></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <div class="row">
                                        <div class="col-12 mb-4">
                                            <h6>Patient Information</h6>
                                            <table class="table table-bordered">
                                                <tr>
                                                    <th width="30%">Name</th>
                                                    <td><?php echo htmlspecialchars($appt_data['PAT_FIRST_NAME'] . ' ' . $appt_data['PAT_LAST_NAME']); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Contact</th>
                                                    <td><?php echo htmlspecialchars($appt_data['PAT_CONTACT_NUM']); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Email</th>
                                                    <td><?php echo htmlspecialchars($appt_data['PAT_EMAIL']); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Date of Birth</th>
                                                    <td><?php echo date('M j, Y', strtotime($appt_data['PAT_DOB'])); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Gender</th>
                                                    <td><?php echo htmlspecialchars($appt_data['PAT_GENDER']); ?></td>
                                                </tr>
                                            </table>
                                        </div>
                                        <div class="col-12">
                                            <h6>Doctor Information</h6>
                                            <table class="table table-bordered">
                                                <tr>
                                                    <th width="30%">Name</th>
                                                    <td>Dr. <?php echo htmlspecialchars($appt_data['DOC_FIRST_NAME'] . ' ' . $appt_data['DOC_LAST_NAME']); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Contact</th>
                                                    <td><?php echo htmlspecialchars($appt_data['DOC_CONTACT_NUM']); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Email</th>
                                                    <td><?php echo htmlspecialchars($appt_data['DOC_EMAIL']); ?></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
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
</body>
</html>