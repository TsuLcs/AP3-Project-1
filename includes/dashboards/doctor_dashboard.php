<?php
$page_title = "Doctor Dashboard";
require_once '../data/dbconfig.php';

// Check if user is doctor
if (!isset($_SESSION['doc_id'])) {
    header("Location: login.php");
    exit();
}

$doctor_id = $_SESSION['doc_id'];
$today = date('Y-m-d');

// Get doctor's today appointments count
$today_appointments = $pdo->prepare("
    SELECT COUNT(*) FROM APPOINTMENT 
    WHERE DOC_ID = ? AND APPT_DATE = ?
");
$today_appointments->execute([$doctor_id, $today]);
$today_appointments_count = $today_appointments->fetchColumn();

// Get total appointments this month (all statuses)
$current_month = date('Y-m');
$monthly_appointments = $pdo->prepare("
    SELECT COUNT(*) FROM APPOINTMENT 
    WHERE DOC_ID = ? AND DATE_FORMAT(APPT_DATE, '%Y-%m') = ?
");
$monthly_appointments->execute([$doctor_id, $current_month]);
$monthly_appointments_count = $monthly_appointments->fetchColumn();

// Get pending appointments (scheduled for today or future)
$pending_appointments = $pdo->prepare("
    SELECT COUNT(*) FROM APPOINTMENT a
    JOIN STATUS s ON a.STAT_ID = s.STAT_ID
    WHERE a.DOC_ID = ? AND a.APPT_DATE >= ? AND s.STAT_NAME = 'Scheduled'
");
$pending_appointments->execute([$doctor_id, $today]);
$pending_appointments_count = $pending_appointments->fetchColumn();

// Get today's appointments with details
$stmt = $pdo->prepare("
    SELECT a.APPT_ID, a.APPT_TIME, p.PAT_FIRST_NAME, p.PAT_LAST_NAME, p.PAT_ID,
           s.SERV_NAME, st.STAT_NAME, p.PAT_DOB, p.PAT_GENDER
    FROM APPOINTMENT a
    JOIN PATIENT p ON a.PAT_ID = p.PAT_ID
    JOIN SERVICE s ON a.SERV_ID = s.SERV_ID
    JOIN STATUS st ON a.STAT_ID = st.STAT_ID
    WHERE a.DOC_ID = ? AND a.APPT_DATE = ?
    ORDER BY a.APPT_TIME
");
$stmt->execute([$doctor_id, $today]);
$today_appointments = $stmt->fetchAll();

// Get doctor's next available slot (first scheduled appointment)
$next_slot = $pdo->prepare("
    SELECT APPT_DATE, APPT_TIME FROM APPOINTMENT 
    WHERE DOC_ID = ? AND APPT_DATE >= ? AND STAT_ID IN (SELECT STAT_ID FROM STATUS WHERE STAT_NAME = 'Scheduled')
    ORDER BY APPT_DATE, APPT_TIME LIMIT 1
");
$next_slot->execute([$doctor_id, $today]);
$next_available = $next_slot->fetch();

// Get upcoming appointments for the sidebar
$upcoming = $pdo->prepare("
    SELECT a.APPT_DATE, a.APPT_TIME, p.PAT_FIRST_NAME, p.PAT_LAST_NAME, s.SERV_NAME
    FROM APPOINTMENT a
    JOIN PATIENT p ON a.PAT_ID = p.PAT_ID
    JOIN SERVICE s ON a.SERV_ID = s.SERV_ID
    WHERE a.DOC_ID = ? AND a.APPT_DATE >= ?
    AND a.STAT_ID IN (SELECT STAT_ID FROM STATUS WHERE STAT_NAME = 'Scheduled')
    ORDER BY a.APPT_DATE, a.APPT_TIME
    LIMIT 3
");
$upcoming->execute([$doctor_id, $today]);
$upcoming_appointments = $upcoming->fetchAll();
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
    <style>
        .sidebar {
            width: 220px !important;
        }
        .main-content {
            margin-left: 220px !important;
            width: calc(100% - 220px) !important;
        }
        .stat-card {
            transition: transform 0.2s ease-in-out;
            border-left: 4px solid !important;
            height: 100%;
        }
        .stat-card:hover {
            transform: translateY(-1px);
        }
        .border-left-primary { border-left-color: #4e73df !important; }
        .border-left-success { border-left-color: #1cc88a !important; }
        .border-left-warning { border-left-color: #f6c23e !important; }
        .border-left-info { border-left-color: #36b9cc !important; }
        .appointment-time {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            white-space: nowrap;
        }
        .compact-table th,
        .compact-table td {
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
        }
        .compact-table thead th {
            border-bottom: 1px solid #dee2e6;
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .quick-action-item {
            padding: 0.75rem 1rem !important;
            border: none !important;
            border-bottom: 1px solid #f8f9fa !important;
        }
        .quick-action-item:last-child {
            border-bottom: none !important;
        }
        .upcoming-item {
            padding: 0.75rem 0 !important;
            border: none !important;
            border-bottom: 1px solid #f8f9fa !important;
        }
        .upcoming-item:last-child {
            border-bottom: none !important;
        }
        /* Compact spacing */
        .compact-section {
            margin-bottom: 1rem !important;
        }
        .compact-card .card-body {
            padding: 1rem;
        }
        .compact-card .card-header {
            padding: 0.75rem 1rem;
        }
    </style>
</head>
<body>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <!-- Main Content -->
            <main class="main-content col-md-10 px-3" style="min-height: 100vh;">
                <!-- Header -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-2 border-bottom compact-section">
                    <h1 class="h3 mb-0">
                        <i class="fas fa-user-md me-2"></i>Doctor Dashboard
                    </h1>
                    <div class="btn-toolbar mb-0">
                        <span class="text-muted small"><?php echo date('M j, Y'); ?></span>
                    </div>
                </div>

                <!-- Welcome Alert - More Compact -->
                <div class="alert alert-info py-2 mb-3 compact-section">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-info-circle me-2"></i>
                        <div class="small">
                            <strong class="me-1">Welcome, Dr. <?php echo $_SESSION['user_name']; ?>!</strong>
                            You have <strong><?php echo $today_appointments_count; ?> appointment(s)</strong> today.
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards - More Compact -->
                <div class="row mb-3 g-2 compact-section">
                    <div class="col-xl-3 col-md-6">
                        <div class="card stat-card border-left-primary shadow-sm h-100">
                            <div class="card-body py-2">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1 small">
                                            Today's Appointments</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $today_appointments_count; ?>
                                        </div>
                                        <div class="text-muted small">For <?php echo date('M j'); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-calendar-day text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card stat-card border-left-success shadow-sm h-100">
                            <div class="card-body py-2">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1 small">
                                            This Month</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $monthly_appointments_count; ?>
                                        </div>
                                        <div class="text-muted small">Total for <?php echo date('F'); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-calendar-alt text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card stat-card border-left-warning shadow-sm h-100">
                            <div class="card-body py-2">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1 small">
                                            Pending</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $pending_appointments_count; ?>
                                        </div>
                                        <div class="text-muted small">Scheduled</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clock text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card stat-card border-left-info shadow-sm h-100">
                            <div class="card-body py-2">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1 small">
                                            Next Available</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php if ($next_available): ?>
                                                <?php echo date('M j', strtotime($next_available['APPT_DATE'])); ?>
                                            <?php else: ?>
                                                No slots
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-muted small">
                                            <?php if ($next_available): ?>
                                                <?php echo date('g:i A', strtotime($next_available['APPT_TIME'])); ?>
                                            <?php else: ?>
                                                No upcoming
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-calendar-check text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <!-- Today's Appointments - More Compact -->
                    <div class="col-lg-8">
                        <div class="card shadow-sm h-100 compact-card">
                            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-2">
                                <h6 class="m-0 font-weight-bold">
                                    <i class="fas fa-list me-1"></i>Today's Appointments
                                </h6>
                                <span class="badge bg-light text-dark"><?php echo $today_appointments_count; ?></span>
                            </div>
                            <div class="card-body p-0">
                                <?php if (empty($today_appointments)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-calendar-times fa-2x text-muted mb-2"></i>
                                        <p class="text-muted small mb-2">No appointments today</p>
                                        <a href="../modules/schedule_manage.php" class="btn btn-sm btn-primary">
                                            <i class="fas fa-calendar-alt me-1"></i>Manage Schedule
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0 compact-table">
                                            <thead>
                                                <tr>
                                                    <th class="ps-2">Time</th>
                                                    <th>Patient</th>
                                                    <th>Age/Gender</th>
                                                    <th>Service</th>
                                                    <th>Status</th>
                                                    <th class="text-center pe-2">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($today_appointments as $appointment): 
                                                    $age = $appointment['PAT_DOB'] ? date_diff(date_create($appointment['PAT_DOB']), date_create('today'))->y : 'N/A';
                                                ?>
                                                    <tr>
                                                        <td class="appointment-time ps-2">
                                                            <?php echo date('h:i A', strtotime($appointment['APPT_TIME'])); ?>
                                                        </td>
                                                        <td>
                                                            <div class="fw-bold small"><?php echo htmlspecialchars($appointment['PAT_FIRST_NAME'] . ' ' . $appointment['PAT_LAST_NAME']); ?></div>
                                                            <div class="text-muted x-small">ID: <?php echo $appointment['PAT_ID']; ?></div>
                                                        </td>
                                                        <td>
                                                            <div class="fw-bold small"><?php echo $age; ?></div>
                                                            <div class="text-muted x-small"><?php echo $appointment['PAT_GENDER'] ?: 'Not specified'; ?></div>
                                                        </td>
                                                        <td class="small"><?php echo htmlspecialchars($appointment['SERV_NAME']); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php
                                                                switch($appointment['STAT_NAME']) {
                                                                    case 'Scheduled': echo 'primary'; break;
                                                                    case 'Completed': echo 'success'; break;
                                                                    case 'Cancelled': echo 'danger'; break;
                                                                    case 'No Show': echo 'warning'; break;
                                                                    default: echo 'secondary';
                                                                }
                                                            ?> small">
                                                                <?php echo htmlspecialchars($appointment['STAT_NAME']); ?>
                                                            </span>
                                                        </td>
                                                        <td class="text-center pe-2">
                                                            <div class="btn-group btn-group-sm">
                                                                <a href="../modules/medical_record_manage.php?pat_id=<?php echo $appointment['PAT_ID']; ?>" 
                                                                   class="btn btn-outline-primary btn-sm" title="Medical Records" data-bs-toggle="tooltip">
                                                                    <i class="fas fa-file-medical"></i>
                                                                </a>
                                                                <a href="../modules/appointment_manage.php?action=view&id=<?php echo $appointment['APPT_ID']; ?>" 
                                                                   class="btn btn-outline-info btn-sm" title="View Details" data-bs-toggle="tooltip">
                                                                    <i class="fas fa-eye"></i>
                                                                </a>
                                                            </div>
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

                    <!-- Quick Actions & Upcoming - More Compact -->
                    <div class="col-lg-4">
                        <!-- Quick Actions -->
                        <div class="card shadow-sm mb-3 compact-card">
                            <div class="card-header bg-success text-white py-2">
                                <h6 class="m-0 font-weight-bold">
                                    <i class="fas fa-bolt me-1"></i>Quick Actions
                                </h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush">
                                    <a href="../pages/doctor_today.php" class="list-group-item list-group-item-action quick-action-item">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-calendar-day text-primary me-2"></i>
                                            <div>
                                                <div class="small fw-bold">Today's Schedule</div>
                                                <div class="x-small text-muted">View detailed schedule</div>
                                            </div>
                                        </div>
                                    </a>
                                    <a href="../modules/schedule_manage.php" class="list-group-item list-group-item-action quick-action-item">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-calendar-alt text-primary me-2"></i>
                                            <div>
                                                <div class="small fw-bold">My Schedule</div>
                                                <div class="x-small text-muted">Manage availability</div>
                                            </div>
                                        </div>
                                    </a>
                                    <a href="../modules/medical_record_manage.php" class="list-group-item list-group-item-action quick-action-item">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-file-medical text-primary me-2"></i>
                                            <div>
                                                <div class="small fw-bold">Medical Records</div>
                                                <div class="x-small text-muted">Patient records</div>
                                            </div>
                                        </div>
                                    </a>
                                    <a href="../modules/appointment_manage.php" class="list-group-item list-group-item-action quick-action-item">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-list-alt text-primary me-2"></i>
                                            <div>
                                                <div class="small fw-bold">All Appointments</div>
                                                <div class="x-small text-muted">View all appointments</div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Upcoming Appointments -->
                        <div class="card shadow-sm compact-card">
                            <div class="card-header bg-info text-white py-2">
                                <h6 class="m-0 font-weight-bold">
                                    <i class="fas fa-clock me-1"></i>Upcoming Appointments
                                </h6>
                            </div>
                            <div class="card-body p-0">
                                <?php if (empty($upcoming_appointments)): ?>
                                    <div class="text-center py-3">
                                        <i class="fas fa-calendar-plus fa-lg text-muted mb-2"></i>
                                        <p class="text-muted small mb-0">No upcoming appointments</p>
                                    </div>
                                <?php else: ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($upcoming_appointments as $appt): ?>
                                            <div class="list-group-item px-3 py-2 upcoming-item">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-light rounded p-1 text-center" style="min-width: 40px;">
                                                            <div class="fw-bold text-primary small"><?php echo date('d', strtotime($appt['APPT_DATE'])); ?></div>
                                                            <div class="x-small text-muted"><?php echo date('M', strtotime($appt['APPT_DATE'])); ?></div>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-2">
                                                        <div class="small fw-bold"><?php echo htmlspecialchars($appt['PAT_FIRST_NAME'] . ' ' . $appt['PAT_LAST_NAME']); ?></div>
                                                        <div class="x-small text-muted">
                                                            <i class="fas fa-clock me-1"></i>
                                                            <?php echo date('g:i A', strtotime($appt['APPT_TIME'])); ?>
                                                        </div>
                                                        <span class="badge bg-light text-dark x-small"><?php echo htmlspecialchars($appt['SERV_NAME']); ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="text-center mt-2 p-2 border-top">
                                        <a href="../modules/appointment_manage.php" class="btn btn-sm btn-outline-info">
                                            View All
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    </script>
</body>
</html>