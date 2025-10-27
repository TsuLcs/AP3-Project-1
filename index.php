<?php include 'data/dbconfig.php'; ?>
<?php include 'includes/head.php'; ?>

<div class="container py-5">
    <div class="text-center mb-5">
        <h1 class="fw-bold text-primary">🏥 Welcome to Our Medical Clinic</h1>
        <p class="lead text-muted">Book your appointments, view records, and connect with our doctors online.</p>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-lg border-0">
                <div class="card-body p-4">
                    <h4 class="card-title text-center mb-4 fw-semibold">Login to Your Account</h4>
                    <form action="login.php" method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" name="username" id="username" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" name="password" id="password" class="form-control" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>

                    <hr class="my-4">

                    <div class="text-center">
                        <p class="small text-muted mb-1">Don’t have an account?</p>
                        <div class="d-grid gap-2">
                            <a href="register_patient.php" class="btn btn-outline-success btn-sm">Register as Patient</a>
                            <a href="register_staff.php" class="btn btn-outline-secondary btn-sm">Register as Staff</a>
                            <a href="register_doctor.php" class="btn btn-outline-info btn-sm">Register as Doctor</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="text-center mt-5 text-muted">
        <small>© <?php echo date('Y'); ?> Medical Clinic Booking System | All Rights Reserved</small>
    </footer>
</div>

<?php include 'includes/tail.php'; ?>
