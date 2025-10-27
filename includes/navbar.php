<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="../index.php">
            <i class="fas fa-hospital-alt"></i> MediCare Clinic
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'index.php' ? 'active' : '' ?>" href="../index.php">Home</a>
                </li>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <?php if($_SESSION['is_superadmin']): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page == 'superadmin_dashboard.php' ? 'active' : '' ?>" href="../pages/superadmin_dashboard.php">Dashboard</a>
                        </li>
                    <?php elseif($_SESSION['user_role'] == 'staff'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page == 'staff_dashboard.php' ? 'active' : '' ?>" href="../pages/staff_dashboard.php">Dashboard</a>
                        </li>
                    <?php elseif($_SESSION['user_role'] == 'doctor'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page == 'doctor_today.php' ? 'active' : '' ?>" href="../pages/doctor_today.php">My Schedule</a>
                        </li>
                    <?php elseif($_SESSION['user_role'] == 'patient'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page == 'patient_appointments.php' ? 'active' : '' ?>" href="../pages/patient_appointments.php">My Appointments</a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?= $_SESSION['user_name'] ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#"><i class="fas fa-cog"></i> Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../pages/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page == 'login.php' ? 'active' : '' ?>" href="../pages/login.php">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>