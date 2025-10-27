<?php
include '../data/dbconfig.php';

// Check if user is superadmin
if(!isset($_SESSION['user_id']) || !$_SESSION['is_superadmin']) {
    header("Location: login.php");
    exit();
}

// Get statistics
$stats = [
    'patients' => $pdo->query("SELECT COUNT(*) FROM PATIENT")->fetchColumn(),
    'doctors' => $pdo->query("SELECT COUNT(*) FROM DOCTOR")->fetchColumn(),
    'staff' => $pdo->query("SELECT COUNT(*) FROM STAFF")->fetchColumn(),
    'appointments' => $pdo->query("SELECT COUNT(*) FROM APPOINTMENT WHERE APPT_DATE = CURDATE()")->fetchColumn()
];
?>

<?php include '../includes/head.php'; ?>
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Super Admin Dashboard</h1>
</div>

<!-- Statistics Cards -->
<div class="row">
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?= $stats['patients'] ?></h4>
                        <p>Total Patients</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-user-injured fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-success">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?= $stats['doctors'] ?></h4>
                        <p>Total Doctors</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-user-md fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?= $stats['staff'] ?></h4>
                        <p>Total Staff</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-info">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?= $stats['appointments'] ?></h4>
                        <p>Today's Appointments</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-calendar-day fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Recent Appointments</h5>
            </div>
            <div class="card-body">
                <?php
                $stmt = $pdo->query("
                    SELECT a.APPT_ID, a.APPT_DATE, a.APPT_TIME, 
                           p.PAT_FIRST_NAME, p.PAT_LAST_NAME,
                           d.DOC_FIRST_NAME, d.DOC_LAST_NAME
                    FROM APPOINTMENT a
                    JOIN PATIENT p ON a.PAT_ID = p.PAT_ID
                    JOIN DOCTOR d ON a.DOC_ID = d.DOC_ID
                    ORDER BY a.APPT_CREATED_AT DESC LIMIT 5
                ");
                $appointments = $stmt->fetchAll();
                ?>
                <div class="list-group">
                    <?php foreach($appointments as $appt): ?>
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><?= $appt['PAT_FIRST_NAME'] ?> <?= $appt['PAT_LAST_NAME'] ?></h6>
                                <small><?= $appt['APPT_DATE'] ?> <?= $appt['APPT_TIME'] ?></small>
                            </div>
                            <p class="mb-1">Dr. <?= $appt['DOC_FIRST_NAME'] ?> <?= $appt['DOC_LAST_NAME'] ?></p>
                            <small class="text-muted">ID: <?= $appt['APPT_ID'] ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="../modules/staff_manage.php" class="btn btn-outline-primary">
                        <i class="fas fa-users"></i> Manage Staff
                    </a>
                    <a href="../modules/doctor_manage.php" class="btn btn-outline-success">
                        <i class="fas fa-user-md"></i> Manage Doctors
                    </a>
                    <a href="../modules/patient_manage.php" class="btn btn-outline-info">
                        <i class="fas fa-user-injured"></i> Manage Patients
                    </a>
                    <a href="../modules/user_manage.php" class="btn btn-outline-warning">
                        <i class="fas fa-user-cog"></i> Manage Users
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/tail.php'; ?>