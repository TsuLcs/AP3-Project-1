<?php
include '../data/dbconfig.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("
        SELECT u.*, 
               p.PAT_FIRST_NAME, p.PAT_LAST_NAME,
               s.STAFF_FIRST_NAME, s.STAFF_LAST_NAME,
               d.DOC_FIRST_NAME, d.DOC_LAST_NAME
        FROM USER u 
        LEFT JOIN PATIENT p ON u.PAT_ID = p.PAT_ID
        LEFT JOIN STAFF s ON u.STAFF_ID = s.STAFF_ID
        LEFT JOIN DOCTOR d ON u.DOC_ID = d.DOC_ID
        WHERE u.USER_NAME = ? AND u.USER_IS_ACTIVE = 1
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if($user && password_verify($password, $user['USER_PASSWORD'])) {
        $_SESSION['user_id'] = $user['USER_ID'];
        $_SESSION['user_name'] = $user['USER_NAME'];
        $_SESSION['is_superadmin'] = $user['USER_IS_SUPERADMIN'];
        
        // Determine user role
        if($user['USER_IS_SUPERADMIN']) {
            $_SESSION['user_role'] = 'superadmin';
            header("Location: superadmin_dashboard.php");
        } elseif($user['PAT_ID']) {
            $_SESSION['user_role'] = 'patient';
            $_SESSION['user_display_name'] = $user['PAT_FIRST_NAME'] . ' ' . $user['PAT_LAST_NAME'];
            header("Location: patient_appointments.php");
        } elseif($user['STAFF_ID']) {
            $_SESSION['user_role'] = 'staff';
            $_SESSION['user_display_name'] = $user['STAFF_FIRST_NAME'] . ' ' . $user['STAFF_LAST_NAME'];
            header("Location: staff_dashboard.php");
        } elseif($user['DOC_ID']) {
            $_SESSION['user_role'] = 'doctor';
            $_SESSION['user_display_name'] = 'Dr. ' . $user['DOC_FIRST_NAME'] . ' ' . $user['DOC_LAST_NAME'];
            header("Location: doctor_today.php");
        }
        exit();
    } else {
        $error = "Invalid email or password!";
    }
}
?>

<?php include '../includes/head.php'; ?>
<?php include '../includes/navbar.php'; ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-sign-in-alt"></i> Login to Your Account</h4>
                </div>
                <div class="card-body">
                    <?php if(isset($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </button>
                    </form>
                    
                    <div class="mt-3 text-center">
                        <p>Don't have an account? 
                            <a href="../register/register_patient.php">Register as Patient</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/tail.php'; ?>