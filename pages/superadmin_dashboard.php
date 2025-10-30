<?php
$page_title = "Super Admin Dashboard";
include '../includes/head.php';
require_once '../data/dbconfig.php';

// Check if user is super admin
if (!isset($_SESSION['user_is_superadmin']) || !$_SESSION['user_is_superadmin']) {
    header("Location: login.php");
    exit();
}

// Get statistics
$users_count = $pdo->query("SELECT COUNT(*) FROM USER")->fetchColumn();
$doctors_count = $pdo->query("SELECT COUNT(*) FROM DOCTOR")->fetchColumn();
$patients_count = $pdo->query("SELECT COUNT(*) FROM PATIENT")->fetchColumn();
$appointments_count = $pdo->query("SELECT COUNT(*) FROM APPOINTMENT")->fetchColumn();
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Super Admin Dashboard</h1>
            </div>

            <!-- Statistics Cards -->
            <div class="row">
                <div class="col-md-3 mb-4">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <h5 class="card-title">Total Users</h5>
                            <h2 class="card-text"><?php echo $users_count; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <h5 class="card-title">Doctors</h5>
                            <h2 class="card-text"><?php echo $doctors_count; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <h5 class="card-title">Patients</h5>
                            <h2 class="card-text"><?php echo $patients_count; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card text-white bg-info">
                        <div class="card-body">
                            <h5 class="card-title">Appointments</h5>
                            <h2 class="card-text"><?php echo $appointments_count; ?></h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5>Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <a href="../modules/staff_manage.php" class="btn btn-outline-primary w-100">
                                        Manage Staff
                                    </a>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <a href="../modules/user_manage.php" class="btn btn-outline-primary w-100">
                                        Manage Users
                                    </a>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <a href="../modules/specialization_manage.php" class="btn btn-outline-primary w-100">
                                        Specializations
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