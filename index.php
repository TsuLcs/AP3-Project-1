<?php
$page_title = "MediCare Clinic - Quality Healthcare Services";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 0;
        }
        .service-card {
            transition: transform 0.3s;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .service-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-hospital-user me-2"></i>MediCare Clinic
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="pages/login.php">
                    <i class="fas fa-sign-in-alt me-1"></i>Login
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section text-center">
        <div class="container">
            <h1 class="display-4 fw-bold mb-4">Quality Healthcare Services</h1>
            <p class="lead mb-4">Your health is our priority. Book appointments with expert doctors easily.</p>
            <a href="pages/login.php" class="btn btn-light btn-lg me-3">
                <i class="fas fa-calendar-check me-2"></i>Book Appointment
            </a>
            <a href="#services" class="btn btn-outline-light btn-lg">
                <i class="fas fa-stethoscope me-2"></i>Our Services
            </a>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Our Medical Services</h2>
                <p class="text-muted">Comprehensive healthcare services for you and your family</p>
            </div>
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="card service-card h-100 text-center">
                        <div class="card-body">
                            <i class="fas fa-user-md fa-3x text-primary mb-3"></i>
                            <h5 class="card-title">Medical Consultation</h5>
                            <p class="card-text">Expert medical advice and diagnosis from qualified doctors.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card service-card h-100 text-center">
                        <div class="card-body">
                            <i class="fas fa-vial fa-3x text-success mb-3"></i>
                            <h5 class="card-title">Laboratory Tests</h5>
                            <p class="card-text">Comprehensive laboratory testing and analysis.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card service-card h-100 text-center">
                        <div class="card-body">
                            <i class="fas fa-syringe fa-3x text-info mb-3"></i>
                            <h5 class="card-title">Vaccination</h5>
                            <p class="card-text">Vaccine administration and immunization services.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card service-card h-100 text-center">
                        <div class="card-body">
                            <i class="fas fa-heartbeat fa-3x text-danger mb-3"></i>
                            <h5 class="card-title">Health Check-up</h5>
                            <p class="card-text">Complete health examination and screening.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4">
        <div class="container text-center">
            <p>&copy; 2025 MediCare Clinic. All rights reserved.</p>
            <p>Contact: info@medicare.com | Phone: (032) 123-4567</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
