<?php
$page_title = "Staff Dashboard";
include '../includes/head.php';
require_once '../data/dbconfig.php';

// Check if user is staff
if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

// Get statistics for staff
$today = date('Y-m-d');
$patients_count = $pdo->query("SELECT COUNT(*) FROM PATIENT")->fetchColumn();
$doctors_count = $pdo->query("SELECT COUNT(*) FROM DOCTOR")->fetchColumn();
$today_appointments = $pdo->prepare("SELECT COUNT(*) FROM APPOINTMENT WHERE APPT_DATE = ?");
$today_appointments->execute([$today]);
$today_appointments_count = $today_appointments->fetchColumn();

// Get today's appointments
$stmt = $pdo->prepare("
    SELECT a.APPT_ID, a.APPT_TIME, p.PAT_FIRST_NAME, p.PAT_LAST_NAME,
           d.DOC_FIRST_NAME, d.DOC_LAST_NAME, s.SERV_NAME, st.STAT_NAME
    FROM APPOINTMENT a
    JOIN PATIENT p ON a.PAT_ID = p.PAT_ID
    JOIN DOCTOR d ON a.DOC_ID = d.DOC_ID
    JOIN SERVICE s ON a.SERV_ID = s.SERV_ID
    JOIN STATUS st ON a.STAT_ID = st.STAT_ID
    WHERE a.APPT_DATE = ?
    ORDER BY a.APPT_TIME
");
$stmt->execute([$today]);
$today_appointments = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Staff Dashboard</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <span class="text-muted">Today: <?php echo date('F j, Y'); ?></span>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <h5 class="card-title">Total Patients</h5>
                            <h2 class="card-text"><?php echo $patients_count; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <h5 class="card-title">Doctors</h5>
                            <h2 class="card-text"><?php echo $doctors_count; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <h5 class="card-title">Today's Appointments</h5>
                            <h2 class="card-text"><?php echo $today_appointments_count; ?></h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Today's Appointments -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5>Today's Appointments</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($today_appointments)): ?>
                                <p class="text-muted">No appointments for today.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Time</th>
                                                <th>Patient</th>
                                                <th>Doctor</th>
                                                <th>Service</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($today_appointments as $appointment): ?>
                                                <tr>
                                                    <td><?php echo date('h:i A', strtotime($appointment['APPT_TIME'])); ?></td>
                                                    <td><?php echo htmlspecialchars($appointment['PAT_FIRST_NAME'] . ' ' . $appointment['PAT_LAST_NAME']); ?></td>
                                                    <td>Dr. <?php echo htmlspecialchars($appointment['DOC_FIRST_NAME'] . ' ' . $appointment['DOC_LAST_NAME']); ?></td>
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
                                                            <?php echo htmlspecialchars($appointment['STAT_NAME']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <a href="../modules/appointment_manage.php?action=view&id=<?php echo $appointment['APPT_ID']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5>Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <a href="../modules/patient_manage.php" class="btn btn-outline-primary w-100">
                                        Manage Patients
                                    </a>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <a href="../modules/doctor_manage.php" class="btn btn-outline-primary w-100">
                                        Manage Doctors
                                    </a>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <a href="../modules/appointment_manage.php" class="btn btn-outline-primary w-100">
                                        Appointments
                                    </a>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <a href="../modules/schedule_manage.php" class="btn btn-outline-primary w-100">
                                        Schedules
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../includes/tail.php'; ?>
