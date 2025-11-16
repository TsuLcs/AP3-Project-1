<?php
// Sidebar for different user roles
$user_role = '';
$user_name = $_SESSION['user_name'] ?? 'User';

if (isset($_SESSION['user_is_superadmin']) && $_SESSION['user_is_superadmin']) {
    $user_role = 'superadmin';
    $role_name = 'Super Administrator';
} elseif (isset($_SESSION['staff_id'])) {
    $user_role = 'staff';
    $role_name = 'Staff Member';
} elseif (isset($_SESSION['doc_id'])) {
    $user_role = 'doctor';
    $role_name = 'Doctor';
} elseif (isset($_SESSION['pat_id'])) {
    $user_role = 'patient';
    $role_name = 'Patient';
}
?>

<!-- Font Awesome for icons -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

<div class="sidebar bg-dark text-white" style="width: 280px; height: 100vh; position: fixed; left: 0; top: 0; overflow-y: auto;">
    <!-- User Profile Section -->
    <div class="p-4 border-bottom border-secondary">
        <div class="d-flex align-items-center">
            <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" 
                 style="width: 45px; height: 45px;">
                <i class="fas fa-user text-white"></i>
            </div>
            <div>
                <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($user_name); ?></h6>
                <small class="text-muted"><?php echo $role_name; ?></small>
            </div>
        </div>
    </div>

    <!-- Navigation Menu -->
    <div class="p-3">
        <ul class="nav nav-pills flex-column">
            <?php if ($user_role === 'superadmin'): ?>
                <!-- Super Admin Menu -->
                <li class="nav-item mb-2">
                    <a class="nav-link text-white d-flex align-items-center active" href="../pages/dashboard.php">
                        <i class="fas fa-tachometer-alt me-3"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                
                <li class="nav-header mt-4 mb-2 text-uppercase small text-white fw-bol">System Management</li>
                
                <li class="nav-item mb-2">
                    <a class="nav-link text-white d-flex align-items-center" href="../modules/staff_manage.php">
                        <i class="fas fa-users-cog me-3"></i>
                        <span>Staff Management</span>
                    </a>
                </li>
                
                <li class="nav-item mb-2">
                    <a class="nav-link text-white d-flex align-items-center" href="../modules/user_manage.php">
                        <i class="fas fa-user-shield me-3"></i>
                        <span>User Management</span>
                    </a>
                </li>
                
                <li class="nav-item mb-2">
                    <a class="nav-link text-white d-flex align-items-center" href="../modules/doctor_manage.php">
                        <i class="fas fa-user-md me-3"></i>
                        <span>Doctor Management</span>
                    </a>
                </li>

                <li class="nav-item mb-2">
                    <a class="nav-link text-white d-flex align-items-center" href="../modules/specialization_manage.php">
                        <i class="fas fa-stethoscope me-3"></i>
                        <span>Specializations</span>
                    </a>
                </li>

            <?php elseif ($user_role === 'staff'): ?>
                <!-- Staff Menu -->
                <li class="nav-item mb-2">
                    <a class="nav-link text-white d-flex align-items-center active" href="../pages/dashboard.php">
                        <i class="fas fa-tachometer-alt me-3"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                
                <li class="nav-header mt-4 mb-2 text-uppercase small text-muted fw-bold">Patient Management</li>
                
                <li class="nav-item mb-2">
                    <a class="nav-link text-white d-flex align-items-center" href="../modules/patient_manage.php">
                        <i class="fas fa-user-injured me-3"></i>
                        <span>Patient Management</span>
                    </a>
                </li>
                
                <li class="nav-header mt-4 mb-2 text-uppercase small text-muted fw-bold">Appointments</li>
                
                <li class="nav-item mb-2">
                    <a class="nav-link text-white d-flex align-items-center" href="../modules/appointment_manage.php">
                        <i class="fas fa-calendar-check me-3"></i>
                        <span>Appointment Management</span>
                    </a>
                </li>
                
                <li class="nav-item mb-2">
                    <a class="nav-link text-white d-flex align-items-center" href="../modules/schedule_manage.php">
                        <i class="fas fa-calendar-alt me-3"></i>
                        <span>Schedule Management</span>
                    </a>
                </li>
                
                <li class="nav-header mt-4 mb-2 text-uppercase small text-muted fw-bold">Doctors</li>
                
                <li class="nav-item mb-2">
                    <a class="nav-link text-white d-flex align-items-center" href="../modules/doctor_manage.php">
                        <i class="fas fa-user-md me-3"></i>
                        <span>Doctor Directory</span>
                    </a>
                </li>

            <?php elseif ($user_role === 'doctor'): ?>
                <!-- Doctor Menu -->
                <li class="nav-item mb-2">
                    <a class="nav-link text-white d-flex align-items-center active" href="../pages/dashboard.php">
                        <i class="fas fa-tachometer-alt me-3"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                
                <li class="nav-item mb-2">
                    <a class="nav-link text-white d-flex align-items-center" href="../pages/doctor_today.php">
                        <i class="fas fa-calendar-day me-3"></i>
                        <span>Today's Appointments</span>
                    </a>
                </li>
                
                <li class="nav-item mb-2">
                    <a class="nav-link text-white d-flex align-items-center" href="../modules/schedule_manage.php">
                        <i class="fas fa-calendar-alt me-3"></i>
                        <span>My Schedule</span>
                    </a>
                </li>
                
                <li class="nav-item mb-2">
                    <a class="nav-link text-white d-flex align-items-center" href="../modules/medical_record_manage.php">
                        <i class="fas fa-file-medical me-3"></i>
                        <span>Medical Records</span>
                    </a>
                </li>
                
                <li class="nav-item mb-2">
                    <a class="nav-link text-white d-flex align-items-center" href="../modules/appointment_manage.php">
                        <i class="fas fa-list-alt me-3"></i>
                        <span>All Appointments</span>
                    </a>
                </li>

            <?php elseif ($user_role === 'patient'): ?>
                <!-- Patient Menu -->
                <li class="nav-item mb-2">
                    <a class="nav-link text-white d-flex align-items-center active" href="../pages/dashboard.php">
                        <i class="fas fa-tachometer-alt me-3"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                
                <li class="nav-item mb-2">
                    <a class="nav-link text-white d-flex align-items-center" href="../pages/patient_appointments.php">
                        <i class="fas fa-list-alt me-3"></i>
                        <span>My Appointments</span>
                    </a>
                </li>
                
                <li class="nav-item mb-2">
                    <a class="nav-link text-white d-flex align-items-center" href="../modules/appointment_manage.php">
                        <i class="fas fa-calendar-plus me-3"></i>
                        <span>Book Appointment</span>
                    </a>
                </li>
                
                <!-- NEW: Patient Profile Link -->
                <li class="nav-header mt-4 mb-2 text-uppercase small text-white fw-bold">My Account</li>
                
                <li class="nav-item mb-2">
                    <a class="nav-link text-white d-flex align-items-center" href="../pages/patient_profile.php">
                        <i class="fas fa-user-edit me-3"></i>
                        <span>My Profile</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
        
        <!-- Logout Button -->
        <div class="mt-5 pt-4 border-top border-secondary">
            <a href="../pages/logout.php" class="btn btn-outline-light w-100 d-flex align-items-center justify-content-center">
                <i class="fas fa-sign-out-alt me-2"></i>
                Logout
            </a>
        </div>
    </div>
</div>

<style>
.sidebar {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    position: fixed;
    left: 0;
    top: 0;
    height: 100vh;
    width: 280px;
    z-index: 100;
    overflow-y: auto;
}

/* Ensure main content doesn't get hidden behind sidebar */
.container-fluid {
    padding-left: 280px !important;
}

.nav-link {
    border-radius: 8px;
    padding: 12px 15px;
    transition: all 0.3s ease;
    border: none;
}

.nav-link:hover, .nav-link.active {
    background-color: rgba(52, 152, 219, 0.2);
    transform: translateX(5px);
}

.nav-link.active {
    background-color: #3498db !important;
    color: white !important;
    border-left: 4px solid #fff;
}

.nav-header {
    font-size: 0.75rem;
    letter-spacing: 1px;
    padding-left: 15px;
}

.bg-primary {
    background-color: #3498db !important;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
    .sidebar {
        position: relative;
        width: 100%;
        height: auto;
    }
    
    .container-fluid {
        padding-left: 0 !important;
    }
}
</style>