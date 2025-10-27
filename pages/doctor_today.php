<?php
include '../data/dbconfig.php';

// Check if user is doctor
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'doctor') {
    header("Location: login.php");
    exit();
}

// Get doctor ID from user session
$stmt = $pdo->prepare("SELECT DOC_ID FROM USER WHERE USER_ID = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
$doctor_id = $user['DOC_ID'];

// Get today's appointments for this doctor
$stmt = $pdo->prepare("
    SELECT a.*, p.PAT_FIRST_NAME, p.PAT_LAST_NAME, p.PAT_DOB, p.PAT_GENDER,
           s.SERV_NAME, st.STAT_NAME
    FROM APPOINTMENT a
    JOIN PATIENT p ON a.PAT_ID = p.PAT_ID
    JOIN SERVICE s ON a.SERV_ID = s.SERV_ID
    JOIN STATUS st ON a.STAT_ID = st.STAT_ID
    WHERE a.DOC_ID = ? AND a.APPT_DATE = CURDATE()
    ORDER BY a.APPT_TIME
");
$stmt->execute([$doctor_id]);
$today_appointments = $stmt->fetchAll();
?>

<?php include '../includes/head.php'; ?>
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Today's Appointments</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <span class="me-2"><?= $_SESSION['user_display_name'] ?></span>
        <span class="badge bg-primary"><?= date('F j, Y') ?></span>
    </div>
</div>

<!-- Today's Schedule -->
<div class="row">
    <?php if(count($today_appointments) > 0): ?>
        <?php foreach($today_appointments as $appt): ?>
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><?= $appt['APPT_TIME'] ?></h6>
                        <span class="badge bg-<?= 
                            $appt['STAT_NAME'] == 'Scheduled' ? 'warning' : 
                            ($appt['STAT_NAME'] == 'Completed' ? 'success' : 'danger')
                        ?>">
                            <?= $appt['STAT_NAME'] ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title"><?= $appt['PAT_FIRST_NAME'] ?> <?= $appt['PAT_LAST_NAME'] ?></h5>
                        <p class="card-text">
                            <strong>Service:</strong> <?= $appt['SERV_NAME'] ?><br>
                            <strong>Gender:</strong> <?= $appt['PAT_GENDER'] ?><br>
                            <strong>DOB:</strong> <?= $appt['PAT_DOB'] ?>
                        </p>
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="../modules/medical_record_manage.php?action=create&appt_id=<?= $appt['APPT_ID'] ?>" 
                               class="btn btn-primary btn-sm">
                                <i class="fas fa-file-medical"></i> Add Record
                            </a>
                            <?php if($appt['STAT_NAME'] == 'Scheduled'): ?>
                                <a href="../modules/appointment_manage.php?action=complete&id=<?= $appt['APPT_ID'] ?>" 
                                   class="btn btn-success btn-sm">
                                    <i class="fas fa-check"></i> Mark Complete
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="alert alert-info text-center">
                <i class="fas fa-calendar-times fa-2x mb-3"></i>
                <h4>No appointments scheduled for today</h4>
                <p>You're all caught up! Enjoy your day.</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Appointment Statistics -->
<div class="row mt-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary">
            <div class="card-body text-center">
                <h4><?= count($today_appointments) ?></h4>
                <p>Total Today</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning">
            <div class="card-body text-center">
                <h4><?= count(array_filter($today_appointments, fn($a) => $a['STAT_NAME'] == 'Scheduled')) ?></h4>
                <p>Scheduled</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success">
            <div class="card-body text-center">
                <h4><?= count(array_filter($today_appointments, fn($a) => $a['STAT_NAME'] == 'Completed')) ?></h4>
                <p>Completed</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-info">
            <div class="card-body text-center">
                <h4><?= count(array_filter($today_appointments, fn($a) => $a['STAT_NAME'] == 'Cancelled')) ?></h4>
                <p>Cancelled</p>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/tail.php'; ?>