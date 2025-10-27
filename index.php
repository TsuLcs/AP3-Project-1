<?php include 'includes/head.php'; ?>
<?php include 'includes/navbar.php'; ?>

<!-- Hero Section -->
<section class="hero-section bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold">Welcome to MediCare Clinic</h1>
                <p class="lead">Your health is our priority. Book appointments with expert doctors easily and conveniently.</p>
                <div class="mt-4">
                    <?php if(!isset($_SESSION['user_id'])): ?>
                        <a href="pages/login.php" class="btn btn-light btn-lg me-3">
                            <i class="fas fa-sign-in-alt"></i> Patient Login
                        </a>
                        <a href="pages/login.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-user-md"></i> Doctor Login
                        </a>
                    <?php else: ?>
                        <a href="<?= 
                            $_SESSION['is_superadmin'] ? 'pages/superadmin_dashboard.php' : 
                            ($_SESSION['user_role'] == 'staff' ? 'pages/staff_dashboard.php' : 
                            ($_SESSION['user_role'] == 'doctor' ? 'pages/doctor_today.php' : 
                            'pages/patient_appointments.php')) 
                        ?>" class="btn btn-light btn-lg">
                            Go to Dashboard
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-6">
                <img src="assets/images/logo.jpg" alt="MediCare Clinic" class="img-fluid rounded-circle">
            </div>
        </div>
    </div>
</section>

<!-- Services Section -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-5">Our Services</h2>
        <div class="row">
            <div class="col-md-3 mb-4">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-heartbeat fa-3x text-primary mb-3"></i>
                        <h5 class="card-title">Cardiology</h5>
                        <p class="card-text">Expert heart care and cardiovascular treatments.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-baby fa-3x text-primary mb-3"></i>
                        <h5 class="card-title">Pediatrics</h5>
                        <p class="card-text">Comprehensive care for children and infants.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-brain fa-3x text-primary mb-3"></i>
                        <h5 class="card-title">Neurology</h5>
                        <p class="card-text">Advanced neurological diagnosis and treatment.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-x-ray fa-3x text-primary mb-3"></i>
                        <h5 class="card-title">Radiology</h5>
                        <p class="card-text">State-of-the-art imaging and diagnostic services.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Contact Section -->
<section class="bg-light py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center">
                <h2>Contact Us</h2>
                <p class="lead">Have questions? We're here to help.</p>
                <div class="row mt-4">
                    <div class="col-md-4">
                        <i class="fas fa-map-marker-alt fa-2x text-primary"></i>
                        <p>123 Health Street, Medical City</p>
                    </div>
                    <div class="col-md-4">
                        <i class="fas fa-phone fa-2x text-primary"></i>
                        <p>(032) 123-4567</p>
                    </div>
                    <div class="col-md-4">
                        <i class="fas fa-envelope fa-2x text-primary"></i>
                        <p>info@medicareclinic.com</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/tail.php'; ?>