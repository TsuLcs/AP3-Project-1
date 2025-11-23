<?php
$page_title = "Staff Dashboard";
include '../includes/head.php';
require_once '../data/dbconfig.php';
require_once '../includes/auth.php';

// Check access - Only Staff
checkAccess(['staff']);

// Get staff-specific statistics
$staff_id = $_SESSION['staff_id'];
$today_appointments = $pdo->query("SELECT COUNT(*) as count FROM appointment WHERE APPT_DATE = CURDATE()")->fetch(PDO::FETCH_ASSOC)['count'];
$total_patients = $pdo->query("SELECT COUNT(*) as count FROM patient")->fetch(PDO::FETCH_ASSOC)['count'];
$pending_appointments = $pdo->query("SELECT COUNT(*) as count FROM appointment WHERE STAT_ID = 1 AND APPT_DATE >= CURDATE()")->fetch(PDO::FETCH_ASSOC)['count'];

// Today's appointments for staff view
$todays_appointments = $pdo->query("
    SELECT a.*, p.PAT_FIRST_NAME, p.PAT_LAST_NAME, d.DOC_FIRST_NAME, d.DOC_LAST_NAME,
           s.SERV_NAME, st.STAT_NAME
    FROM appointment a
    JOIN patient p ON a.PAT_ID = p.PAT_ID
    JOIN doctor d ON a.DOC_ID = d.DOC_ID
    JOIN service s ON a.SERV_ID = s.SERV_ID
    JOIN status st ON a.STAT_ID = st.STAT_ID
    WHERE a.APPT_DATE = CURDATE()
    ORDER BY a.APPT_TIME ASC
")->fetchAll();
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
                        <i class="fas fa-tachometer-alt me-2"></i>Staff Dashboard
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <span class="btn btn-sm btn-outline-secondary">Welcome, Staff Member</span>
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
                                            Today's Appointments</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $today_appointments; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
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
                                            Total Patients</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_patients; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user-injured fa-2x text-gray-300"></i>
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
                                            Pending Appointments</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pending_appointments; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clock fa-2x text-gray-300"></i>
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
                                            Quick Actions</div>
                                        <div class="h6 mb-0 font-weight-bold text-gray-800">Manage System</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-bolt fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header bg-light">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-bolt me-2"></i>Quick Actions
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-md-4 mb-3">
                                        <a href="appointment_manage.php" class="btn btn-primary btn-lg w-100 py-3">
                                            <i class="fas fa-calendar-plus fa-2x mb-2"></i><br>
                                            Manage Appointments
                                        </a>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <a href="patient_manage.php" class="btn btn-success btn-lg w-100 py-3">
                                            <i class="fas fa-user-injured fa-2x mb-2"></i><br>
                                            Patient Management
                                        </a>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <a href="doctor_manage.php" class="btn btn-info btn-lg w-100 py-3">
                                            <i class="fas fa-user-md fa-2x mb-2"></i><br>
                                            Doctor Management
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Today's Appointments -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header bg-light">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-calendar-day me-2"></i>Today's Appointments
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
                                                    <th>Doctor</th>
                                                    <th>Service</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($todays_appointments as $appointment): ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?php echo date('g:i A', strtotime($appointment['APPT_TIME'])); ?></strong>
                                                        </td>
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
                                                                <?php echo $appointment['STAT_NAME']; ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <a href="appointment_manage.php" class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-edit me-1"></i>Manage
                                                            </a>
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
                                        <p class="text-muted">All clear! You can relax or prepare for upcoming appointments.</p>
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
</body>
</html>
