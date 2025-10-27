<?php
if(!isset($_SESSION['user_id'])) {
    header("Location: ../pages/login.php");
    exit();
}
?>
<div class="row">
    <div class="col-md-3 col-lg-2 bg-light sidebar">
        <div class="position-sticky pt-3">
            <?php if($_SESSION['is_superadmin']): ?>
                <!-- Super Admin Sidebar -->
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="../pages/superadmin_dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../modules/staff_manage.php">
                            <i class="fas fa-users"></i> Staff Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../modules/patient_manage.php">
                            <i class="fas fa-user-injured"></i> Patient Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../modules/doctor_manage.php">
                            <i class="fas fa-user-md"></i> Doctor Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../modules/specialization_manage.php">
                            <i class="fas fa-stethoscope"></i> Specializations
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../modules/service_manage.php">
                            <i class="fas fa-procedures"></i> Services
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../modules/user_manage.php">
                            <i class="fas fa-user-cog"></i> User Accounts
                        </a>
                    </li>
                </ul>
            <?php elseif($_SESSION['user_role'] == 'staff'): ?>
                <!-- Staff Sidebar -->
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="../pages/staff_dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../modules/patient_manage.php">
                            <i class="fas fa-user-injured"></i> Patients
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../modules/appointment_manage.php">
                            <i class="fas fa-calendar-check"></i> Appointments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../modules/medical_record_manage.php">
                            <i class="fas fa-file-medical"></i> Medical Records
                        </a>
                    </li>
                </ul>
            <?php elseif($_SESSION['user_role'] == 'doctor'): ?>
                <!-- Doctor Sidebar -->
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="../pages/doctor_today.php">
                            <i class="fas fa-calendar-day"></i> Today's Appointments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../modules/schedule_manage.php">
                            <i class="fas fa-clock"></i> My Schedule
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../modules/medical_record_manage.php">
                            <i class="fas fa-file-medical"></i> Medical Records
                        </a>
                    </li>
                </ul>
            <?php elseif($_SESSION['user_role'] == 'patient'): ?>
                <!-- Patient Sidebar -->
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="../pages/patient_appointments.php">
                            <i class="fas fa-calendar-check"></i> My Appointments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../modules/appointment_manage.php?action=create">
                            <i class="fas fa-plus-circle"></i> Book Appointment
                        </a>
                    </li>
                </ul>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-md-9 col-lg-10 main-content">