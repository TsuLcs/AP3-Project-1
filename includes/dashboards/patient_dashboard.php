<?php
// Check if user is patient
if (!isset($_SESSION['pat_id'])) {
    header("Location: login.php");
    exit();
}

$patient_id = $_SESSION['pat_id'];
$today = date('Y-m-d');

// Get patient's upcoming appointments - FIXED QUERY
$upcoming_appointments = $pdo->prepare("
    SELECT COUNT(*) FROM APPOINTMENT a
    JOIN STATUS s ON a.STAT_ID = s.STAT_ID
    WHERE a.PAT_ID = ? AND a.APPT_DATE >= ? AND s.STAT_NAME = 'Scheduled'
");
$upcoming_appointments->execute([$patient_id, $today]);
$upcoming_appointments_count = $upcoming_appointments->fetchColumn();

// Get total appointments - FIXED QUERY
$total_appointments = $pdo->prepare("SELECT COUNT(*) FROM APPOINTMENT WHERE PAT_ID = ?");
$total_appointments->execute([$patient_id]);
$total_appointments_count = $total_appointments->fetchColumn();

// Get medical records count - Check if MEDICAL_RECORD table exists
$medical_records_count = 0;
try {
    $medical_records = $pdo->prepare("SELECT COUNT(*) FROM MEDICAL_RECORD WHERE PAT_ID = ?");
    $medical_records->execute([$patient_id]);
    $medical_records_count = $medical_records->fetchColumn();
} catch (PDOException $e) {
    // Table might not exist, set to 0
    $medical_records_count = 0;
}

// Get next appointment - FIXED QUERY
$next_appointment = $pdo->prepare("
    SELECT a.APPT_ID, a.APPT_DATE, a.APPT_TIME, d.DOC_FIRST_NAME, d.DOC_LAST_NAME, s.SERV_NAME
    FROM APPOINTMENT a
    JOIN DOCTOR d ON a.DOC_ID = d.DOC_ID
    JOIN SERVICE s ON a.SERV_ID = s.SERV_ID
    JOIN STATUS st ON a.STAT_ID = st.STAT_ID
    WHERE a.PAT_ID = ? AND a.APPT_DATE >= ? AND st.STAT_NAME = 'Scheduled'
    ORDER BY a.APPT_DATE, a.APPT_TIME
    LIMIT 1
");
$next_appointment->execute([$patient_id, $today]);
$next_appt = $next_appointment->fetch();

// Get recent appointments - FIXED QUERY
$recent_appointments = $pdo->prepare("
    SELECT a.APPT_ID, a.APPT_DATE, a.APPT_TIME, d.DOC_FIRST_NAME, d.DOC_LAST_NAME, 
           s.SERV_NAME, st.STAT_NAME
    FROM APPOINTMENT a
    JOIN DOCTOR d ON a.DOC_ID = d.DOC_ID
    JOIN SERVICE s ON a.SERV_ID = s.SERV_ID
    JOIN STATUS st ON a.STAT_ID = st.STAT_ID
    WHERE a.PAT_ID = ?
    ORDER BY a.APPT_DATE DESC, a.APPT_TIME DESC
    LIMIT 5
");
$recent_appointments->execute([$patient_id]);
$recent_appts = $recent_appointments->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-user-injured me-2"></i>Patient Dashboard
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <span class="text-muted"><?php echo date('F j, Y'); ?></span>
    </div>
</div>

<!-- Welcome Alert -->
<div class="alert alert-info mb-4">
    <i class="fas fa-info-circle me-2"></i>
    <strong>Welcome, <?php echo $_SESSION['user_name']; ?>!</strong> 
    <?php if ($next_appt): ?>
        Your next appointment is on <?php echo date('F j', strtotime($next_appt['APPT_DATE'])); ?> at <?php echo date('g:i A', strtotime($next_appt['APPT_TIME'])); ?>.
    <?php else: ?>
        You have no upcoming appointments. <a href="../modules/appointment_manage.php" class="alert-link">Book one now!</a>
    <?php endif; ?>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Upcoming Appointments</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $upcoming_appointments_count; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
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
                            Total Visits</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_appointments_count; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-history fa-2x text-gray-300"></i>
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
                            Medical Records</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $medical_records_count; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-file-medical fa-2x text-gray-300"></i>
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
                            Next Appointment</div>
                        <div class="h6 mb-0 font-weight-bold text-gray-800">
                            <?php if ($next_appt): ?>
                                <?php echo date('M j', strtotime($next_appt['APPT_DATE'])); ?><br>
                                <small><?php echo date('g:i A', strtotime($next_appt['APPT_TIME'])); ?></small>
                            <?php else: ?>
                                Not scheduled
                            <?php endif; ?>
                        </div>
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
    <!-- Recent Appointments -->
    <div class="col-lg-8 mb-4">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-history me-2"></i>Recent Appointments
                </h6>
            </div>
            <div class="card-body">
                <?php if (empty($recent_appts)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-calendar-plus fa-3x text-muted mb-3"></i>
                        <p class="text-muted">You haven't booked any appointments yet.</p>
                        <a href="../modules/appointment_manage.php" class="btn btn-primary">
                            <i class="fas fa-calendar-plus me-2"></i>Book Your First Appointment
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="bg-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Doctor</th>
                                    <th>Service</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_appts as $appointment): ?>
                                    <tr>
                                        <td class="fw-bold"><?php echo date('M j, Y', strtotime($appointment['APPT_DATE'])); ?></td>
                                        <td><?php echo date('g:i A', strtotime($appointment['APPT_TIME'])); ?></td>
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
                                                <i class="fas fa-eye"></i> View
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

    <!-- Quick Actions & Next Appointment -->
    <div class="col-lg-4 mb-4">
        <!-- Quick Actions -->
        <div class="card shadow">
            <div class="card-header bg-success text-white">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-bolt me-2"></i>Quick Actions
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="../modules/appointment_manage.php" class="btn btn-outline-primary text-start">
                        <i class="fas fa-calendar-plus me-2"></i>Book Appointment
                    </a>
                    <a href="../pages/patient_appointments.php" class="btn btn-outline-primary text-start">
                        <i class="fas fa-list-alt me-2"></i>My Appointments
                    </a>
                    <a href="../modules/medical_record_manage.php" class="btn btn-outline-primary text-start">
                        <i class="fas fa-file-medical me-2"></i>Medical Records
                    </a>
                </div>
            </div>
        </div>

        <!-- Next Appointment Details -->
        <?php if ($next_appt): ?>
        <div class="card shadow mt-4">
            <div class="card-header bg-info text-white">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-calendar-day me-2"></i>Next Appointment
                </h6>
            </div>
            <div class="card-body">
                <div class="text-center">
                    <h5 class="text-primary"><?php echo date('l, F j', strtotime($next_appt['APPT_DATE'])); ?></h5>
                    <h4 class="text-success mb-3"><?php echo date('g:i A', strtotime($next_appt['APPT_TIME'])); ?></h4>
                    
                    <div class="mb-3">
                        <strong>Doctor:</strong><br>
                        Dr. <?php echo htmlspecialchars($next_appt['DOC_FIRST_NAME'] . ' ' . $next_appt['DOC_LAST_NAME']); ?>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Service:</strong><br>
                        <?php echo htmlspecialchars($next_appt['SERV_NAME']); ?>
                    </div>
                    
                    <div class="alert alert-warning small">
                        <i class="fas fa-exclamation-circle me-1"></i>
                        Please arrive 15 minutes before your appointment time.
                    </div>
                    
                    <a href="../modules/appointment_manage.php?action=view&id=<?php echo $next_appt['APPT_ID']; ?>" 
                       class="btn btn-primary btn-sm">
                        <i class="fas fa-info-circle me-1"></i>View Details
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>