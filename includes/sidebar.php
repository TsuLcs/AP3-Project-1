<nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <?php if (isSuperAdmin()): ?>
                <li class="nav-item">
                    <a class="nav-link active" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../modules/user_manage.php">
                        <i class="fas fa-user-shield me-2"></i>User Management
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../modules/staff_manage.php">
                        <i class="fas fa-users-cog me-2"></i>Staff Management
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="doctor_manage.php">
                        <i class="fas fa-user-md me-2"></i>Doctor Management
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="patient_manage.php">
                        <i class="fas fa-user-injured me-2"></i>Patient Management
                    </a>
                </li>
            <?php elseif (isStaff()): ?>
                <li class="nav-item">
                    <a class="nav-link active" href="staff_dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="appointment_manage.php">
                        <i class="fas fa-calendar-check me-2"></i>Appointments
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="patient_manage.php">
                        <i class="fas fa-user-injured me-2"></i>Patients
                    </a>
                </li>
            <?php elseif (isDoctor()): ?>
                <li class="nav-item">
                    <a class="nav-link active" href="doctor_dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i>Today's Appointments
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="doctor_schedule.php">
                        <i class="fas fa-calendar me-2"></i>My Schedule
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="medical_records.php">
                        <i class="fas fa-file-medical me-2"></i>Medical Records
                    </a>
                </li>
            <?php elseif (isPatient()): ?>
                <li class="nav-item">
                    <a class="nav-link active" href="patient_dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i>My Appointments
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="book_appointment.php">
                        <i class="fas fa-calendar-plus me-2"></i>Book Appointment
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="medical_history.php">
                        <i class="fas fa-history me-2"></i>Medical History
                    </a>
                </li>
            <?php endif; ?>
        </ul>

        <hr>

        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link text-danger" href="../pages/logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
            </li>
        </ul>
    </div>
</nav>
