<?php
// Sidebar for different user roles
$user_role = '';
if (isset($_SESSION['user_is_superadmin']) && $_SESSION['user_is_superadmin']) {
    $user_role = 'superadmin';
} elseif (isset($_SESSION['staff_id'])) {
    $user_role = 'staff';
} elseif (isset($_SESSION['doc_id'])) {
    $user_role = 'doctor';
} elseif (isset($_SESSION['pat_id'])) {
    $user_role = 'patient';
}
?>

<div class="sidebar bg-light border-end" style="width: 250px; min-height: calc(100vh - 76px);">
    <div class="p-3">
        <h6 class="text-uppercase text-muted mb-3">Navigation</h6>
        <ul class="nav nav-pills flex-column">
            <?php if ($user_role === 'superadmin'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="../pages/superadmin_dashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../modules/staff_manage.php">Staff Management</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../modules/user_manage.php">User Management</a>
                </li>
                
            <?php elseif ($user_role === 'staff'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="../pages/staff_dashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../modules/patient_manage.php">Patient Management</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../modules/doctor_manage.php">Doctor Management</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../modules/appointment_manage.php">Appointments</a>
                </li>
                
            <?php elseif ($user_role === 'doctor'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="../pages/doctor_today.php">Today's Appointments</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../modules/schedule_manage.php">My Schedule</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../modules/medical_record_manage.php">Medical Records</a>
                </li>
                
            <?php elseif ($user_role === 'patient'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="../pages/patient_appointments.php">My Appointments</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../modules/appointment_manage.php">Book Appointment</a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</div>