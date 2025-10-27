<?php
include '../data/dbconfig.php';

// Check if user is staff
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'staff') {
    header("Location: login.php");
    exit();
}

// Get today's appointments for staff
$stmt = $pdo->prepare("
    SELECT a.*, p.PAT_FIRST_NAME, p.PAT_LAST_NAME, p.PAT_CONTACT_NUM,
           d.DOC_FIRST_NAME, d.DOC_LAST_NAME, s.SERV_NAME, st.STAT_NAME
    FROM APPOINTMENT a
    JOIN PATIENT p ON a.PAT_ID = p.PAT_ID
    JOIN DOCTOR d ON a.DOC_ID = d.DOC_ID
    JOIN SERVICE s ON a.SERV_ID = s.SERV_ID
    JOIN STATUS st ON a.STAT_ID = st.STAT_ID
    WHERE a.APPT_DATE = CURDATE()
    ORDER BY a.APPT_TIME
");
$stmt->execute();
$today_appointments = $stmt->fetchAll();
?>

<?php include '../includes/head.php'; ?>
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Staff Dashboard</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <span class="me-2">Welcome, <?= $_SESSION['user_display_name'] ?></span>
    </div>
</div>

<!-- Today's Appointments -->
<div class="card">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-calendar-day"></i> Today's Appointments</h5>
    </div>
    <div class="card-body">
        <?php if(count($today_appointments) > 0): ?>
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
                        <?php foreach($today_appointments as $appt): ?>
                            <tr>
                                <td><?= $appt['APPT_TIME'] ?></td>
                                <td><?= $appt['PAT_FIRST_NAME'] ?> <?= $appt['PAT_LAST_NAME'] ?></td>
                                <td>Dr. <?= $appt['DOC_FIRST_NAME'] ?> <?= $appt['DOC_LAST_NAME'] ?></td>
                                <td><?= $appt['SERV_NAME'] ?></td>
                                <td>
                                    <span class="badge bg-<?= 
                                        $appt['STAT_NAME'] == 'Scheduled' ? 'warning' : 
                                        ($appt['STAT_NAME'] == 'Completed' ? 'success' : 'danger')
                                    ?>">
                                        <?= $appt['STAT_NAME'] ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="../modules/appointment_manage.php?action=view&id=<?= $appt['APPT_ID'] ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="../modules/appointment_manage.php?action=edit&id=<?= $appt['APPT_ID'] ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">No appointments scheduled for today.</div>
        <?php endif; ?>
    </div>
</div>

<!-- Quick Stats -->
<div class="row mt-4">
    <div class="col-md-4">
        <div class="card text-white bg-success">
            <div class="card-body text-center">
                <h3><?= count(array_filter($today_appointments, fn($a) => $a['STAT_NAME'] == 'Scheduled')) ?></h3>
                <p>Scheduled Today</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-primary">
            <div class="card-body text-center">
                <h3><?= count(array_filter($today_appointments, fn($a) => $a['STAT_NAME'] == 'Completed')) ?></h3>
                <p>Completed Today</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-warning">
            <div class="card-body text-center">
                <h3><?= count($today_appointments) ?></h3>
                <p>Total Today</p>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/tail.php'; ?>