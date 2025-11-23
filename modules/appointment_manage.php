<?php
$page_title = "Appointment Management";
include '../includes/head.php';
require_once '../data/dbconfig.php';
require_once '../includes/auth.php';

// Check access - Super Admin and Staff
checkAccess(['superadmin', 'staff']);

// Function to generate appointment ID
function generateAppointmentId($pdo) {
    $year = date('Y');
    $month = date('m');

    // Get the last sequence number for this month
    $last_appt = $pdo->query("SELECT APPT_ID FROM appointment WHERE APPT_ID LIKE 'APT{$year}{$month}%' ORDER BY APPT_ID DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

    if ($last_appt) {
        $last_sequence = intval(substr($last_appt['APPT_ID'], -3));
        $sequence = str_pad($last_sequence + 1, 3, '0', STR_PAD_LEFT);
    } else {
        $sequence = '001';
    }

    return "APT{$year}{$month}{$sequence}";
}

// Handle create appointment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_appointment'])) {
    $pat_id = $_POST['pat_id'];
    $doc_id = $_POST['doc_id'];
    $serv_id = $_POST['serv_id'];
    $appt_date = $_POST['appt_date'];
    $appt_time = $_POST['appt_time'];

    try {
        // Check if doctor is available at that time
        $check_availability = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM appointment
            WHERE DOC_ID = ? AND APPT_DATE = ? AND APPT_TIME = ? AND STAT_ID != 3
        ");
        $check_availability->execute([$doc_id, $appt_date, $appt_time]);
        $conflict = $check_availability->fetch(PDO::FETCH_ASSOC)['count'];

        if ($conflict > 0) {
            $_SESSION['error'] = "Doctor is not available at the selected date and time. Please choose a different time.";
        } else {
            $appt_id = generateAppointmentId($pdo);
            $stmt = $pdo->prepare("INSERT INTO appointment (APPT_ID, APPT_DATE, APPT_TIME, PAT_ID, DOC_ID, SERV_ID, STAT_ID) VALUES (?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([$appt_id, $appt_date, $appt_time, $pat_id, $doc_id, $serv_id]);
            $_SESSION['success'] = "Appointment created successfully! Appointment ID: {$appt_id}";
            header("Location: appointment_manage.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error creating appointment: " . $e->getMessage();
    }
}

// Handle update appointment status
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $appt_id = $_POST['appt_id'];
    $status_id = $_POST['status_id'];

    try {
        $stmt = $pdo->prepare("UPDATE appointment SET STAT_ID = ? WHERE APPT_ID = ?");
        $stmt->execute([$status_id, $appt_id]);
        $_SESSION['success'] = "Appointment status updated successfully!";
        header("Location: appointment_manage.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error updating appointment: " . $e->getMessage();
    }
}

// Handle cancel appointment
if (isset($_GET['cancel_id'])) {
    $appt_id = $_GET['cancel_id'];

    try {
        $stmt = $pdo->prepare("UPDATE appointment SET STAT_ID = 3 WHERE APPT_ID = ?");
        $stmt->execute([$appt_id]);
        $_SESSION['success'] = "Appointment cancelled successfully!";
        header("Location: appointment_manage.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error cancelling appointment: " . $e->getMessage();
        header("Location: appointment_manage.php");
        exit();
    }
}

// Handle delete appointment
if (isset($_GET['delete_id'])) {
    $appt_id = $_GET['delete_id'];

    try {
        // Check if appointment has medical records
        $check_medical = $pdo->prepare("SELECT COUNT(*) as count FROM medical_record WHERE APPT_ID = ?");
        $check_medical->execute([$appt_id]);
        $medical_count = $check_medical->fetch(PDO::FETCH_ASSOC)['count'];

        if ($medical_count > 0) {
            $_SESSION['error'] = "Cannot delete appointment: Medical records exist for this appointment.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM appointment WHERE APPT_ID = ?");
            $stmt->execute([$appt_id]);
            $_SESSION['success'] = "Appointment deleted successfully!";
        }
        header("Location: appointment_manage.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error deleting appointment: " . $e->getMessage();
        header("Location: appointment_manage.php");
        exit();
    }
}

// Get all appointments with details
$appointments = $pdo->query("
    SELECT a.*, p.PAT_FIRST_NAME, p.PAT_LAST_NAME, d.DOC_FIRST_NAME, d.DOC_LAST_NAME,
           s.SERV_NAME, st.STAT_NAME, spec.SPEC_NAME,
           (SELECT COUNT(*) FROM medical_record WHERE APPT_ID = a.APPT_ID) as has_medical_record
    FROM appointment a
    JOIN patient p ON a.PAT_ID = p.PAT_ID
    JOIN doctor d ON a.DOC_ID = d.DOC_ID
    JOIN service s ON a.SERV_ID = s.SERV_ID
    JOIN status st ON a.STAT_ID = st.STAT_ID
    LEFT JOIN specialization spec ON d.SPEC_ID = spec.SPEC_ID
    ORDER BY a.APPT_DATE DESC, a.APPT_TIME DESC
")->fetchAll();

// Get data for dropdowns
$patients = $pdo->query("SELECT PAT_ID, PAT_FIRST_NAME, PAT_LAST_NAME FROM patient ORDER BY PAT_FIRST_NAME, PAT_LAST_NAME")->fetchAll();
$doctors = $pdo->query("SELECT DOC_ID, DOC_FIRST_NAME, DOC_LAST_NAME FROM doctor ORDER BY DOC_FIRST_NAME, DOC_LAST_NAME")->fetchAll();
$services = $pdo->query("SELECT SERV_ID, SERV_NAME FROM service ORDER BY SERV_NAME")->fetchAll();
$statuses = $pdo->query("SELECT STAT_ID, STAT_NAME FROM status ORDER BY STAT_ID")->fetchAll();
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
                        <i class="fas fa-calendar-check me-2"></i>Appointment Management
                    </h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAppointmentModal">
                        <i class="fas fa-plus me-1"></i>Create Appointment
                    </button>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <!-- Appointment Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <label class="form-label">Filter by Status</label>
                                <select class="form-control" id="statusFilter">
                                    <option value="">All Statuses</option>
                                    <?php foreach ($statuses as $status): ?>
                                        <option value="<?php echo $status['STAT_NAME']; ?>"><?php echo $status['STAT_NAME']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Filter by Date</label>
                                <input type="date" class="form-control" id="dateFilter">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Filter by Doctor</label>
                                <select class="form-control" id="doctorFilter">
                                    <option value="">All Doctors</option>
                                    <?php foreach ($doctors as $doctor): ?>
                                        <option value="<?php echo $doctor['DOC_ID']; ?>">Dr. <?php echo htmlspecialchars($doctor['DOC_FIRST_NAME'] . ' ' . $doctor['DOC_LAST_NAME']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="button" class="btn btn-secondary w-100" id="resetFilters">
                                    <i class="fas fa-refresh me-1"></i>Reset Filters
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h6 class="m-0 font-weight-bold">
                            <i class="fas fa-list me-2"></i>All Appointments
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="appointmentsTable">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Appointment ID</th>
                                        <th>Date & Time</th>
                                        <th>Patient</th>
                                        <th>Doctor</th>
                                        <th>Service</th>
                                        <th>Status</th>
                                        <th>Medical Record</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($appointments as $appointment): ?>
                                        <tr data-status="<?php echo $appointment['STAT_NAME']; ?>"
                                            data-date="<?php echo $appointment['APPT_DATE']; ?>"
                                            data-doctor="<?php echo $appointment['DOC_ID']; ?>">
                                            <td>
                                                <strong><?php echo $appointment['APPT_ID']; ?></strong>
                                            </td>
                                            <td>
                                                <?php echo date('M j, Y', strtotime($appointment['APPT_DATE'])); ?><br>
                                                <small class="text-muted"><?php echo date('g:i A', strtotime($appointment['APPT_TIME'])); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($appointment['PAT_FIRST_NAME'] . ' ' . $appointment['PAT_LAST_NAME']); ?></td>
                                            <td>
                                                Dr. <?php echo htmlspecialchars($appointment['DOC_FIRST_NAME'] . ' ' . $appointment['DOC_LAST_NAME']); ?>
                                                <?php if ($appointment['SPEC_NAME']): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($appointment['SPEC_NAME']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($appointment['SERV_NAME']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php
                                                    switch($appointment['STAT_NAME']) {
                                                        case 'Scheduled': echo 'primary'; break;
                                                        case 'Completed': echo 'success'; break;
                                                        case 'Cancelled': echo 'danger'; break;
                                                        default: echo 'secondary';
                                                    }
                                                ?>">
                                                    <?php echo $appointment['STAT_NAME']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($appointment['has_medical_record'] > 0): ?>
                                                    <span class="badge bg-success">Yes</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">No</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                                                        <i class="fas fa-cog me-1"></i>Actions
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <button type="button" class="dropdown-item update-status-btn"
                                                                    data-appt-id="<?php echo $appointment['APPT_ID']; ?>"
                                                                    data-current-status="<?php echo $appointment['STAT_ID']; ?>">
                                                                <i class="fas fa-sync me-2"></i>Update Status
                                                            </button>
                                                        </li>
                                                        <?php if ($appointment['STAT_NAME'] == 'Scheduled'): ?>
                                                            <li>
                                                                <a href="appointment_manage.php?cancel_id=<?php echo $appointment['APPT_ID']; ?>"
                                                                   class="dropdown-item text-warning"
                                                                   onclick="return confirm('Are you sure you want to cancel this appointment?')">
                                                                    <i class="fas fa-times me-2"></i>Cancel
                                                                </a>
                                                            </li>
                                                        <?php endif; ?>
                                                        <?php if (isSuperAdmin() && $appointment['has_medical_record'] == 0): ?>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <a href="appointment_manage.php?delete_id=<?php echo $appointment['APPT_ID']; ?>"
                                                                   class="dropdown-item text-danger"
                                                                   onclick="return confirm('Are you sure you want to delete this appointment? This action cannot be undone.')">
                                                                    <i class="fas fa-trash me-2"></i>Delete
                                                                </a>
                                                            </li>
                                                        <?php endif; ?>
                                                    </ul>
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

    <!-- Create Appointment Modal -->
    <div class="modal fade" id="createAppointmentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Create New Appointment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Patient *</label>
                            <select class="form-control" name="pat_id" required>
                                <option value="">Select Patient</option>
                                <?php foreach ($patients as $patient): ?>
                                    <option value="<?php echo $patient['PAT_ID']; ?>">
                                        <?php echo htmlspecialchars($patient['PAT_FIRST_NAME'] . ' ' . $patient['PAT_LAST_NAME']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Doctor *</label>
                            <select class="form-control" name="doc_id" required>
                                <option value="">Select Doctor</option>
                                <?php foreach ($doctors as $doctor): ?>
                                    <option value="<?php echo $doctor['DOC_ID']; ?>">
                                        Dr. <?php echo htmlspecialchars($doctor['DOC_FIRST_NAME'] . ' ' . $doctor['DOC_LAST_NAME']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Service *</label>
                            <select class="form-control" name="serv_id" required>
                                <option value="">Select Service</option>
                                <?php foreach ($services as $service): ?>
                                    <option value="<?php echo $service['SERV_ID']; ?>">
                                        <?php echo htmlspecialchars($service['SERV_NAME']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date *</label>
                                <input type="date" class="form-control" name="appt_date" min="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Time *</label>
                                <input type="time" class="form-control" name="appt_time" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="create_appointment" class="btn btn-primary">Create Appointment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Update Appointment Status</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="appt_id" id="status_appt_id">
                        <div class="mb-3">
                            <label class="form-label">Status *</label>
                            <select class="form-control" name="status_id" required>
                                <?php foreach ($statuses as $status): ?>
                                    <option value="<?php echo $status['STAT_ID']; ?>"><?php echo $status['STAT_NAME']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Filter functionality
            const statusFilter = document.getElementById('statusFilter');
            const dateFilter = document.getElementById('dateFilter');
            const doctorFilter = document.getElementById('doctorFilter');
            const resetFilters = document.getElementById('resetFilters');
            const appointmentsTable = document.getElementById('appointmentsTable');
            const rows = appointmentsTable.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            function filterAppointments() {
                const statusValue = statusFilter.value.toLowerCase();
                const dateValue = dateFilter.value;
                const doctorValue = doctorFilter.value;

                for (let row of rows) {
                    const status = row.getAttribute('data-status').toLowerCase();
                    const date = row.getAttribute('data-date');
                    const doctor = row.getAttribute('data-doctor');

                    const statusMatch = !statusValue || status === statusValue;
                    const dateMatch = !dateValue || date === dateValue;
                    const doctorMatch = !doctorValue || doctor === doctorValue;

                    row.style.display = (statusMatch && dateMatch && doctorMatch) ? '' : 'none';
                }
            }

            statusFilter.addEventListener('change', filterAppointments);
            dateFilter.addEventListener('change', filterAppointments);
            doctorFilter.addEventListener('change', filterAppointments);

            resetFilters.addEventListener('click', function() {
                statusFilter.value = '';
                dateFilter.value = '';
                doctorFilter.value = '';
                filterAppointments();
            });

            // Update status modal
            const updateStatusButtons = document.querySelectorAll('.update-status-btn');
            const updateStatusModal = new bootstrap.Modal(document.getElementById('updateStatusModal'));

            updateStatusButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const apptId = this.getAttribute('data-appt-id');
                    const currentStatus = this.getAttribute('data-current-status');

                    document.getElementById('status_appt_id').value = apptId;
                    document.querySelector('select[name="status_id"]').value = currentStatus;

                    updateStatusModal.show();
                });
            });
        });
    </script>
</body>
</html>
