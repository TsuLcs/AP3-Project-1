<?php
$page_title = "Login - MediCare Clinic";
$hide_navbar = true;
include '../includes/head.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once '../data/dbconfig.php';
    
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM USER WHERE USER_NAME = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['USER_PASSWORD'])) {
        session_start();
        $_SESSION['user_id'] = $user['USER_ID'];
        $_SESSION['user_name'] = $user['USER_NAME'];
        $_SESSION['user_is_superadmin'] = $user['USER_IS_SUPERADMIN'];
        $_SESSION['pat_id'] = $user['PAT_ID'];
        $_SESSION['staff_id'] = $user['STAFF_ID'];
        $_SESSION['doc_id'] = $user['DOC_ID'];
        
        // Update last login
        $update_stmt = $pdo->prepare("UPDATE USER SET USER_LAST_LOGIN = NOW() WHERE USER_ID = ?");
        $update_stmt->execute([$user['USER_ID']]);
        
        header("Location: dashboard.php");
    } else {
        $error = "Invalid username or password";
    }
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Login to MediCare Clinic</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>
                    
                    <div class="mt-3 text-center">
                        <p>Don't have an account? <a href="../register/register_patient.php">Register as Patient</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/tail.php'; ?>