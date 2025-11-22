<?php
$page_title = "Patient Dashboard";
include '../includes/head.php';
require_once '../data/dbconfig.php';
require_once '../includes/auth.php';

// Check access - Only Patient
checkAccess(['patient']);

$patient_id = $_SESSION['patient_id'];

// Get patient info
$patient_info = $pdo->prepare("SELECT * FROM patient WHERE PAT_ID = ?");
$patient_info->execute([$patient_id]);
$patient = $patient_info->fetch(PDO::FETCH_ASSOC);

// Get patient's upcoming appointments
$upcoming_appointments = $pdo->prepare("
    SELECT a.*, d.DOC_FIRST_NAME, d.DOC_LAST_NAME, s.SERV_NAME, st.STAT_NAME, spec.SPEC_NAME
    FROM appointment a
    JOIN doctor d ON a.DOC_ID = d.DOC_ID
    JOIN service s ON a.SERV_ID = s.SERV_ID
    JOIN status st ON a.STAT_ID = st.STAT_ID
    LEFT JOIN specialization spec ON d.SPEC_ID = spec.SPEC_ID
    WHERE a.PAT_ID = ? AND a.APPT_DATE >= CURDATE()
    ORDER BY a.APPT_DATE ASC, a.APPT_TIME ASC
");
$upcoming_appointments->execute([$patient_id]);
$upcoming_appointments = $upcoming_appointments->fetchAll();

// Get patient's appointment history
$appointment_history = $pdo->prepare("
    SELECT a.*, d.DOC_FIRST_NAME, d.DOC_LAST_NAME, s.SERV_NAME, st.STAT_NAME, spec.SPEC_NAME
    FROM appointment a
    JOIN doctor d ON a.DOC_ID = d.DOC_ID
    JOIN service s ON a.SERV_ID = s.SERV_ID
    JOIN status st ON a.STAT_ID = st.STAT_ID
    LEFT JOIN specialization spec ON d.SPEC_ID = spec.SPEC_ID
    WHERE a.PAT_ID = ? AND a.APPT_DATE < CURDATE()
    ORDER BY a.APPT_DATE DESC, a.APPT_TIME DESC
    LIMIT 5
");
$appointment_history->execute([$patient_id]);
$appointment_history = $appointment_history->fetchAll();
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
                        <i class="fas fa-tachometer-alt me-2"></i>My Appointments
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <span class="btn btn-sm btn-outline-secondary">
                            Welcome, <?php echo htmlspecialchars($patient['PAT_FIRST_NAME'] . ' ' . $patient['PAT_LAST_NAME']); ?>
                        </span>
                    </div>
                </div>

                <!-- Patient Info Card -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <h4 class="card-title"><?php echo htmlspecialchars($patient['PAT_FIRST_NAME'] . ' ' . $patient['PAT_LAST_NAME']); ?></h4>
                                        <p class="card-text mb-1">
                                            <i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($patient['PAT_EMAIL']); ?>
                                        </p>
                                        <p class="card-text mb-1">
                                            <i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($patient['PAT_CONTACT_NUM']); ?>
                                        </p>
                                        <p class="card-text">
                                            <i class="fas fa-birthday-cake me-2"></i>
                                            <?php echo date('F j, Y', strtotime($patient['PAT_DOB'])); ?>
                                            (<?php echo date_diff(date_create($patient['PAT_DOB']), date_create('today'))->y; ?> years old)
                                        </p>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <div class="display-4">
                                            <?php echo count($upcoming_appointments); ?>
                                        </div>
                                        <p>Upcoming Appointments</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Upcoming Appointments -->
                    <div class="col-lg-8">
                        <div class="card shadow">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-calendar-check me-2"></i>Upcoming Appointments
                                </h6>
                                <a href="book_appointment.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus me-1"></i>Book New Appointment
                                </a>
                            </div>
                            <div class="card-body">
                                <?php if (count($upcoming_appointments) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Date & Time</th>
                                                    <th>Doctor</th>
                                                    <th>Specialization</th>
                                                    <th>Service</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($upcoming_appointments as $appointment): ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?php echo date('M j, Y', strtotime($appointment['APPT_DATE'])); ?></strong><br>
                                                            <small class="text-muted"><?php echo date('g:i A', strtotime($appointment['APPT_TIME'])); ?></small>
                                                        </td>
                                                        <td>
                                                            Dr. <?php echo htmlspecialchars($appointment['DOC_FIRST_NAME'] . ' ' . $appointment['DOC_LAST_NAME']); ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($appointment['SPEC_NAME']): ?>
                                                                <span class="badge bg-info"><?php echo htmlspecialchars($appointment['SPEC_NAME']); ?></span>
                                                            <?php else: ?>
                                                                <span class="badge bg-secondary">General</span>
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
                                                            <?php if ($appointment['STAT_NAME'] == 'Scheduled'): ?>
                                                                <a href="patient_dashboard.php?cancel_id=<?php echo $appointment['APPT_ID']; ?>"
                                                                   class="btn btn-sm btn-outline-danger"
                                                                   onclick="return confirm('Are you sure you want to cancel this appointment?')">
                                                                    <i class="fas fa-times me-1"></i>Cancel
                                                                </a>
                                                            <?php elseif ($appointment['STAT_NAME'] == 'Completed'): ?>
                                                                <a href="medical_history.php?appt_id=<?php echo $appointment['APPT_ID']; ?>"
                                                                   class="btn btn-sm btn-outline-primary">
                                                                    <i class="fas fa-eye me-1"></i>View Details
                                                                </a>
                                                            <?php else: ?>
                                                                <span class="text-muted">Cancelled</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-calendar-plus fa-3x text-muted mb-3"></i>
                                        <h5 class="text-muted">No upcoming appointments</h5>
                                        <p class="text-muted">You don't have any scheduled appointments. Book your first appointment now!</p>
                                        <a href="book_appointment.php" class="btn btn-primary">
                                            <i class="fas fa-plus me-1"></i>Book Appointment
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Stats & History -->
                    <div class="col-lg-4">
                        <!-- Quick Stats -->
                        <div class="card shadow mb-4">
                            <div class="card-header bg-light">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-chart-bar me-2"></i>Appointment Summary
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush">
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        Total Appointments
                                        <span class="badge bg-primary rounded-pill">
                                            <?php echo count($upcoming_appointments) + count($appointment_history); ?>
                                        </span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        Upcoming
                                        <span class="badge bg-success rounded-pill">
                                            <?php echo count($upcoming_appointments); ?>
                                        </span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        Completed
                                        <span class="badge bg-info rounded-pill">
                                            <?php
                                                $completed = array_filter($appointment_history, function($appt) {
                                                    return $appt['STAT_NAME'] == 'Completed';
                                                });
                                                echo count($completed);
                                            ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recent History -->
                        <div class="card shadow">
                            <div class="card-header bg-light">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-history me-2"></i>Recent Appointments
                                </h6>
                            </div>
                            <div class="card-body">
                                <?php if (count($appointment_history) > 0): ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($appointment_history as $appointment): ?>
                                            <div class="list-group-item">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h6 class="mb-1">Dr. <?php echo htmlspecialchars($appointment['DOC_FIRST_NAME'] . ' ' . $appointment['DOC_LAST_NAME']); ?></h6>
                                                    <small><?php echo date('M j', strtotime($appointment['APPT_DATE'])); ?></small>
                                                </div>
                                                <p class="mb-1">
                                                    <small><?php echo htmlspecialchars($appointment['SERV_NAME']); ?></small>
                                                </p>
                                                <small class="text-<?php
                                                    switch($appointment['STAT_NAME']) {
                                                        case 'Completed': echo 'success'; break;
                                                        case 'Cancelled': echo 'danger'; break;
                                                        default: echo 'muted';
                                                    }
                                                ?>">
                                                    <?php echo $appointment['STAT_NAME']; ?>
                                                </small>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted text-center">No appointment history</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
