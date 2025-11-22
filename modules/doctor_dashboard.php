<?php
$page_title = "Doctor Dashboard";
include '../includes/head.php';
require_once '../data/dbconfig.php';
require_once '../includes/auth.php';

// Check access - Only Doctor
checkAccess(['doctor']);

$doctor_id = $_SESSION['doctor_id'];

// Get doctor's today appointments
$todays_appointments = $pdo->prepare("
    SELECT a.*, p.PAT_FIRST_NAME, p.PAT_LAST_NAME, p.PAT_GENDER, p.PAT_DOB,
           s.SERV_NAME, st.STAT_NAME
    FROM appointment a
    JOIN patient p ON a.PAT_ID = p.PAT_ID
    JOIN service s ON a.SERV_ID = s.SERV_ID
    JOIN status st ON a.STAT_ID = st.STAT_ID
    WHERE a.DOC_ID = ? AND a.APPT_DATE = CURDATE()
    ORDER BY a.APPT_TIME ASC
");
$todays_appointments->execute([$doctor_id]);
$todays_appointments = $todays_appointments->fetchAll();

// Get doctor's upcoming appointments
$upcoming_appointments = $pdo->prepare("
    SELECT a.*, p.PAT_FIRST_NAME, p.PAT_LAST_NAME, s.SERV_NAME, st.STAT_NAME
    FROM appointment a
    JOIN patient p ON a.PAT_ID = p.PAT_ID
    JOIN service s ON a.SERV_ID = s.SERV_ID
    JOIN status st ON a.STAT_ID = st.STAT_ID
    WHERE a.DOC_ID = ? AND a.APPT_DATE > CURDATE() AND a.STAT_ID = 1
    ORDER BY a.APPT_DATE ASC, a.APPT_TIME ASC
    LIMIT 5
");
$upcoming_appointments->execute([$doctor_id]);
$upcoming_appointments = $upcoming_appointments->fetchAll();

// Get doctor info
$doctor_info = $pdo->prepare("SELECT d.*, s.SPEC_NAME FROM doctor d LEFT JOIN specialization s ON d.SPEC_ID = s.SPEC_ID WHERE d.DOC_ID = ?");
$doctor_info->execute([$doctor_id]);
$doctor = $doctor_info->fetch(PDO::FETCH_ASSOC);
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
                        <i class="fas fa-tachometer-alt me-2"></i>Today's Appointments
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <span class="btn btn-sm btn-outline-secondary">
                            Welcome, Dr. <?php echo htmlspecialchars($doctor['DOC_FIRST_NAME'] . ' ' . $doctor['DOC_LAST_NAME']); ?>
                            <?php if ($doctor['SPEC_NAME']): ?>
                                <span class="badge bg-info ms-1"><?php echo htmlspecialchars($doctor['SPEC_NAME']); ?></span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>

                <!-- Doctor Info Card -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <h4 class="card-title">Dr. <?php echo htmlspecialchars($doctor['DOC_FIRST_NAME'] . ' ' . $doctor['DOC_LAST_NAME']); ?></h4>
                                        <p class="card-text mb-1">
                                            <i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($doctor['DOC_EMAIL']); ?>
                                        </p>
                                        <p class="card-text mb-1">
                                            <i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($doctor['DOC_CONTACT_NUM']); ?>
                                        </p>
                                        <?php if ($doctor['SPEC_NAME']): ?>
                                            <p class="card-text">
                                                <i class="fas fa-stethoscope me-2"></i><?php echo htmlspecialchars($doctor['SPEC_NAME']); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <div class="display-4">
                                            <?php echo count($todays_appointments); ?>
                                        </div>
                                        <p>Appointments Today</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Today's Appointments -->
                    <div class="col-lg-8">
                        <div class="card shadow">
                            <div class="card-header bg-light">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-calendar-day me-2"></i>Today's Schedule
                                </h6>
                            </div>
                            <div class="card-body">
                                <?php if (count($todays_appointments) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Time</th>
                                                    <th>Patient</th>
                                                    <th>Gender</th>
                                                    <th>Age</th>
                                                    <th>Service</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($todays_appointments as $appointment):
                                                    $age = date_diff(date_create($appointment['PAT_DOB']), date_create('today'))->y;
                                                ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?php echo date('g:i A', strtotime($appointment['APPT_TIME'])); ?></strong>
                                                        </td>
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($appointment['PAT_FIRST_NAME'] . ' ' . $appointment['PAT_LAST_NAME']); ?></strong>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-<?php echo $appointment['PAT_GENDER'] == 'Male' ? 'primary' : ($appointment['PAT_GENDER'] == 'Female' ? 'danger' : 'secondary'); ?>">
                                                                <?php echo $appointment['PAT_GENDER']; ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo $age; ?> years</td>
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
                                                                <a href="medical_records.php?appt_id=<?php echo $appointment['APPT_ID']; ?>"
                                                                   class="btn btn-sm btn-success">
                                                                    <i class="fas fa-file-medical me-1"></i>Start Consultation
                                                                </a>
                                                            <?php elseif ($appointment['STAT_NAME'] == 'Completed'): ?>
                                                                <a href="medical_records.php?appt_id=<?php echo $appointment['APPT_ID']; ?>"
                                                                   class="btn btn-sm btn-outline-primary">
                                                                    <i class="fas fa-eye me-1"></i>View Record
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
                                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                        <h5 class="text-muted">No appointments scheduled for today</h5>
                                        <p class="text-muted">Enjoy your day off or prepare for upcoming appointments.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Upcoming Appointments & Quick Stats -->
                    <div class="col-lg-4">
                        <!-- Upcoming Appointments -->
                        <div class="card shadow mb-4">
                            <div class="card-header bg-light">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-calendar-alt me-2"></i>Upcoming Appointments
                                </h6>
                            </div>
                            <div class="card-body">
                                <?php if (count($upcoming_appointments) > 0): ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($upcoming_appointments as $appointment): ?>
                                            <div class="list-group-item">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($appointment['PAT_FIRST_NAME'] . ' ' . $appointment['PAT_LAST_NAME']); ?></h6>
                                                    <small><?php echo date('M j', strtotime($appointment['APPT_DATE'])); ?></small>
                                                </div>
                                                <p class="mb-1">
                                                    <small><?php echo date('g:i A', strtotime($appointment['APPT_TIME'])); ?> - <?php echo htmlspecialchars($appointment['SERV_NAME']); ?></small>
                                                </p>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted text-center">No upcoming appointments</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="card shadow">
                            <div class="card-header bg-light">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-bolt me-2"></i>Quick Actions
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="doctor_schedule.php" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-calendar me-2"></i>My Schedule
                                    </a>
                                    <a href="medical_records.php" class="btn btn-outline-success btn-sm">
                                        <i class="fas fa-file-medical me-2"></i>Medical Records
                                    </a>
                                </div>
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
