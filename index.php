<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediCare Clinic - Home</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="assets/images/favicon.jpg" alt="MediCare Clinic" height="25" class="d-inline-block align-text-top">
                MediCare Clinic
            </a>
            <div class="navbar-nav ms-auto">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- Logged In User -->
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="badge bg-success me-2">
                                <i class="fas fa-circle"></i> Online
                            </span>
                            Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <span class="dropdown-item-text small text-muted">
                                    <i class="fas fa-user-circle me-1"></i>
                                    <?php
                                    if ($_SESSION['user_is_superadmin']) {
                                        echo 'Super Admin';
                                    } elseif ($_SESSION['staff_id']) {
                                        echo 'Staff';
                                    } elseif ($_SESSION['doc_id']) {
                                        echo 'Doctor';
                                    } elseif ($_SESSION['pat_id']) {
                                        echo 'Patient';
                                    }
                                    ?>
                                </span>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="pages/dashboard.php">
                                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                </a>
                            </li>
                            <li><a class="dropdown-item" href="pages/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <!-- Not Logged In -->
                    <div class="d-flex align-items-center">
                        <span class="badge bg-secondary me-3">
                            <i class="fas fa-circle"></i> Offline
                        </span>
                        <a class="nav-link" href="pages/login.php">
                            <i class="fas fa-sign-in-alt me-1"></i>Login
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section bg-light py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold text-primary">Welcome to MediCare Clinic</h1>
                    <p class="lead mb-4">Your health is our priority. Book appointments with expert doctors and get the best medical care.</p>
                    <div class="d-flex gap-3">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <!-- Logged In User Actions -->
                            <a href="pages/dashboard.php" class="btn btn-primary btn-lg">Go to Dashboard</a>
                            <a href="pages/logout.php" class="btn btn-outline-secondary btn-lg">Logout</a>
                        <?php else: ?>
                            <!-- Not Logged In Actions -->
                            <a href="pages/login.php" class="btn btn-primary btn-lg">Book Appointment</a>
                            <a href="#services" class="btn btn-outline-primary btn-lg">Our Services</a>
                        <?php endif; ?>
                    </div>
                    
                    <!-- User Status Indicator -->
                    <div class="mt-4">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <div class="alert alert-success d-inline-flex align-items-center" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                You are logged in as 
                                <strong class="mx-1">
                                    <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                                </strong>
                                (<?php
                                if ($_SESSION['user_is_superadmin']) {
                                    echo 'Super Administrator';
                                } elseif ($_SESSION['staff_id']) {
                                    echo 'Staff Member';
                                } elseif ($_SESSION['doc_id']) {
                                    echo 'Doctor';
                                } elseif ($_SESSION['pat_id']) {
                                    echo 'Patient';
                                }
                                ?>)
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning d-inline-flex align-items-center" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                You are not logged in. <a href="pages/login.php" class="alert-link mx-1">Login</a> to access your account.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-6">
                    <img src="https://plus.unsplash.com/premium_photo-1681842883882-b5c1c9f37869?fm=jpg&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MXx8bWVkaWNhbCUyMGNsaW5pY3xlbnwwfHwwfHx8MA%3D%3D&ixlib=rb-4.1.0&q=60&w=3000"
                         alt="Medical Care" class="img-fluid rounded shadow">
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Our Medical Services</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <h5 class="card-title text-primary">Consultation</h5>
                            <p class="card-text">Professional medical consultation with our experienced doctors.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <h5 class="card-title text-primary">Laboratory Tests</h5>
                            <p class="card-text">Comprehensive laboratory testing and analysis.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <h5 class="card-title text-primary">Health Check-up</h5>
                            <p class="card-text">Complete health examination and preventive care.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="bg-light py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4 text-center">
                    <div class="feature-icon bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                         style="width: 80px; height: 80px;">
                        <i class="fs-4">⏰</i>
                    </div>
                    <h5>Easy Booking</h5>
                    <p>Book appointments online 24/7</p>
                </div>
                <div class="col-md-4 text-center">
                    <div class="feature-icon bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                         style="width: 80px; height: 80px;">
                        <i class="fs-4">👨‍⚕️</i>
                    </div>
                    <h5>Expert Doctors</h5>
                    <p>Qualified and experienced medical professionals</p>
                </div>
                <div class="col-md-4 text-center">
                    <div class="feature-icon bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                         style="width: 80px; height: 80px;">
                        <i class="fs-4">📱</i>
                    </div>
                    <h5>Digital Records</h5>
                    <p>Access your medical records anytime</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>MediCare Clinic</h5>
                    <p>Providing quality healthcare services since 2010.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p>Contact: +1 234 567 8900<br>Email: info@medicareclinic.com</p>
                </div>
            </div>
            <hr>
            <p class="text-center mb-0">&copy; 2024 MediCare Clinic. All rights reserved.</p>
        </div>
    </footer>

    <!-- Font Awesome for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>