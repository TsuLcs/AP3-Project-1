<?php
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

$pending_appointments = $pdo->prepare("
    SELECT COUNT(*) FROM APPOINTMENT a 
    JOIN STATUS s ON a.STAT_ID = s.STAT_ID 
    WHERE s.STAT_NAME = 'Scheduled' AND a.APPT_DATE >= ?
");
$pending_appointments->execute([$today]);
$pending_appointments_count = $pending_appointments->fetchColumn();

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
    LIMIT 10
");
$stmt->execute([$today]);
$today_appointments = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="row">
        

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-tachometer-alt me-2"></i>Staff Dashboard
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <span class="text-muted">Today: <?php echo date('F j, Y'); ?></span>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Patients</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $patients_count; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-user-injured fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Active Doctors</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $doctors_count; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-user-md fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Today's Appointments</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $today_appointments_count; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Pending Appointments</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pending_appointments_count; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-clock fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Today's Appointments -->
                <div class="col-lg-8 mb-4">
                    <div class="card shadow">
                        <div class="card-header bg-primary text-white">
                            <h6 class="m-0 font-weight-bold">
                                <i class="fas fa-calendar-day me-2"></i>Today's Appointments
                            </h6>
                        </div>
                        <div class="card-body">
                            <?php if (empty($today_appointments)): ?>
                                <p class="text-muted">No appointments for today.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead class="bg-light">
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
                                                    <td class="fw-bold"><?php echo date('h:i A', strtotime($appointment['APPT_TIME'])); ?></td>
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
                                                        <a href="../modules/appointment_manage.php?action=view&id=<?php echo $appointment['APPT_ID']; ?>" 
                                                           class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
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

                <!-- Quick Actions -->
                <div class="col-lg-4 mb-4">
                    <div class="card shadow">
                        <div class="card-header bg-success text-white">
                            <h6 class="m-0 font-weight-bold">
                                <i class="fas fa-bolt me-2"></i>Quick Actions
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="../modules/patient_manage.php" class="btn btn-outline-primary text-start">
                                    <i class="fas fa-user-injured me-2"></i>Manage Patients
                                </a>
                                <a href="../modules/doctor_manage.php" class="btn btn-outline-primary text-start">
                                    <i class="fas fa-user-md me-2"></i>Doctor Directory
                                </a>
                                <a href="../modules/appointment_manage.php" class="btn btn-outline-primary text-start">
                                    <i class="fas fa-calendar-check me-2"></i>Appointments
                                </a>
                                <a href="../modules/schedule_manage.php" class="btn btn-outline-primary text-start">
                                    <i class="fas fa-calendar-alt me-2"></i>Schedules
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>