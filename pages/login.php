<?php
// Only start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../data/dbconfig.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM user WHERE USER_NAME = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['USER_PASSWORD'])) {
            $_SESSION['user_id'] = $user['USER_ID'];
            $_SESSION['user_name'] = $user['USER_NAME'];
            $_SESSION['user_is_superadmin'] = (bool)$user['USER_IS_SUPERADMIN'];

            // Set role-specific sessions
            if ($user['PAT_ID']) {
                $_SESSION['patient_id'] = $user['PAT_ID'];
                header("Location: ../modules/patient_dashboard.php");
            } elseif ($user['STAFF_ID']) {
                $_SESSION['staff_id'] = $user['STAFF_ID'];
                header("Location: ../modules/staff_dashboard.php");
            } elseif ($user['DOC_ID']) {
                $_SESSION['doctor_id'] = $user['DOC_ID'];
                header("Location: ../modules/doctor_dashboard.php");
            } elseif ($user['USER_IS_SUPERADMIN']) {
                header("Location: ../modules/dashboard.php");
            } else {
                $error = "User has no assigned role.";
            }
            exit();
        } else {
            $error = "Invalid username or password.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MediCare Clinic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .login-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="login-container d-flex align-items-center">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-4">
                    <div class="login-card p-4">
                        <div class="text-center mb-4">
                            <i class="fas fa-hospital-user fa-3x text-primary mb-3"></i>
                            <h2 class="fw-bold">MediCare Clinic</h2>
                            <p class="text-muted">Please sign in to your account</p>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" name="username" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" name="password" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 py-2">
                                <i class="fas fa-sign-in-alt me-2"></i>Sign In
                            </button>
                        </form>

                        <div class="text-center mt-3">
                            <small class="text-muted">
                                Demo Accounts:<br>
                                Super Admin: superadmin / admin123<br>
                                Patient: test / 123456789<br>
                                Staff: test1 / 123456789<br>
                                Doctor: test2 / 123456789
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
