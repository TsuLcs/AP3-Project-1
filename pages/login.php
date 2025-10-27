<?php
session_start();
require_once '../data/database/dbconfig.php';  // database connection
include '../includes/head.php';
?>

<div class="container py-5">
    <div class="text-center mb-5">
        <h1 class="fw-bold text-primary">Clinic Booking System</h1>
        <p class="text-muted">Sign in to manage your appointments, records, and more.</p>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow border-0">
                <div class="card-body p-4">
                    <h4 class="text-center mb-4 fw-semibold">Login</h4>

                    <?php
                    // Handle login submission
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $username = trim($_POST['username']);
                        $password = trim($_POST['password']);

                        if (!empty($username) && !empty($password)) {
                            $stmt = $pdo->prepare("SELECT * FROM USER WHERE USER_NAME = ?");
                            $stmt->execute([$username]);
                            $user = $stmt->fetch(PDO::FETCH_ASSOC);

                            if ($user && password_verify($password, $user['USER_PASSWORD'])) {
                                // Store session info
                                $_SESSION['user_id'] = $user['USER_ID'];
                                $_SESSION['username'] = $user['USER_NAME'];

                                // Redirect based on role
                                if ($user['USER_IS_SUPERADMIN']) {
                                    header("Location: superadmin_dashboard.php");
                                    exit;
                                } elseif (!is_null($user['STAFF_ID'])) {
                                    header("Location: staff_dashboard.php");
                                    exit;
                                } elseif (!is_null($user['DOC_ID'])) {
                                    header("Location: doctor_today.php");
                                    exit;
                                } elseif (!is_null($user['PAT_ID'])) {
                                    header("Location: patient_appointments.php");
                                    exit;
                                } else {
                                    echo '<div class="alert alert-warning">User role not found.</div>';
                                }
                            } else {
                                echo '<div class="alert alert-danger">Invalid username or password.</div>';
                            }
                        } else {
                            echo '<div class="alert alert-warning">Please fill in both fields.</div>';
                        }
                    }
                    ?>

                    <form action="" method="POST">
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
                        <p class="text-muted small mb-2">Don’t have an account?</p>
                        <div class="d-grid gap-2">
                            <a href="../register/register_patient.php" class="btn btn-outline-success btn-sm">Register as Patient</a>
                            <a href="../register/register_staff.php" class="btn btn-outline-secondary btn-sm">Register as Staff</a>
                            <a href="../register/register_doctor.php" class="btn btn-outline-info btn-sm">Register as Doctor</a>
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

<?php include '../includes/tail.php'; ?>
