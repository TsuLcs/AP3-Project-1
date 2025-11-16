<?php
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
$staff_count = $pdo->query("SELECT COUNT(*) FROM STAFF")->fetchColumn();    

// Recent activity - last 5 logins
$recent_logins = $pdo->query("
    SELECT u.USER_NAME, u.USER_LAST_LOGIN, 
           COALESCE(p.PAT_FIRST_NAME, s.STAFF_FIRST_NAME, d.DOC_FIRST_NAME) as FIRST_NAME,
           COALESCE(p.PAT_LAST_NAME, s.STAFF_LAST_NAME, d.DOC_LAST_NAME) as LAST_NAME
    FROM USER u
    LEFT JOIN PATIENT p ON u.PAT_ID = p.PAT_ID
    LEFT JOIN STAFF s ON u.STAFF_ID = s.STAFF_ID
    LEFT JOIN DOCTOR d ON u.DOC_ID = d.DOC_ID
    WHERE u.USER_LAST_LOGIN IS NOT NULL
    ORDER BY u.USER_LAST_LOGIN DESC
    LIMIT 5
")->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-tachometer-alt me-2"></i>Super Admin Dashboard
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <span class="text-muted"><?php echo date('F j, Y'); ?></span>
    </div>
</div>

<!-- Statistics Cards - FIXED LAYOUT -->
<div class="row mb-4">
    <div class="col-xl-2 col-md-3 col-sm-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1" style="font-size: 0.7rem; line-height: 1.2;">
                            Total Users
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $users_count; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-3 col-sm-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1" style="font-size: 0.7rem; line-height: 1.2;">
                            Doctors
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $doctors_count; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-user-md fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-3 col-sm-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1" style="font-size: 0.7rem; line-height: 1.2;">
                            Patients
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $patients_count; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-user-injured fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-3 col-sm-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1" style="font-size: 0.7rem; line-height: 1.2;">
                            Staff
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $staff_count; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users-cog fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-3 col-sm-6 mb-4">
        <div class="card border-left-secondary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1" style="font-size: 0.65rem; line-height: 1.1;">
                            Appointments
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $appointments_count; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Quick Actions -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-bolt me-2"></i>Quick Actions
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <a href="../modules/staff_manage.php" class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center py-2" style="min-height: 60px;">
                            <i class="fas fa-users-cog me-2"></i>
                            <span>Manage Staff</span>
                        </a>
                    </div>
                    <div class="col-md-6 mb-3">
                        <a href="../modules/user_manage.php" class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center py-2" style="min-height: 60px;">
                            <i class="fas fa-user-shield me-2"></i>
                            <span>Manage Users</span>
                        </a>
                    </div>
                    <div class="col-md-6 mb-3">
                        <a href="../modules/doctor_manage.php" class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center py-2" style="min-height: 60px;">
                            <i class="fas fa-user-md me-2"></i>
                            <span>Manage Doctors</span>
                        </a>
                    </div>
                    <div class="col-md-6 mb-3">
                        <a href="../modules/specialization_manage.php" class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center py-2" style="min-height: 60px;">
                            <i class="fas fa-stethoscope me-2"></i>
                            <span>Specializations</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow">
            <div class="card-header bg-success text-white">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-history me-2"></i>Recent Logins
                </h6>
            </div>
            <div class="card-body">
                <?php if (empty($recent_logins)): ?>
                    <p class="text-muted">No recent login activity.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recent_logins as $login): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                        <i class="fas fa-user text-white"></i>
                                    </div>
                                    <div>
                                        <strong class="d-block"><?php echo htmlspecialchars($login['USER_NAME']); ?></strong>
                                        <small class="text-muted">
                                            <?php 
                                            $fullName = trim($login['FIRST_NAME'] . ' ' . $login['LAST_NAME']);
                                            echo htmlspecialchars($fullName ?: 'No name');
                                            ?>
                                        </small>
                                    </div>
                                </div>
                                <small class="text-muted text-nowrap">
                                    <?php echo date('M j, g:i A', strtotime($login['USER_LAST_LOGIN'])); ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    border: 1px solid #e3e6f0;
    border-radius: 0.35rem;
}

.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}

.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}

.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}

.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}

.border-left-secondary {
    border-left: 0.25rem solid #858796 !important;
}

.shadow {
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15) !important;
}

/* Better responsive behavior */
@media (max-width: 768px) {
    .col-xl-2 {
        flex: 0 0 50%;
        max-width: 50%;
    }
}

@media (max-width: 576px) {
    .col-xl-2 {
        flex: 0 0 100%;
        max-width: 100%;
    }
}
</style>