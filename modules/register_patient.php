<?php
// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../data/dbconfig.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $last_name = trim($_POST['last_name']);
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $contact = trim($_POST['contact']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    try {
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT * FROM user WHERE USER_NAME = ? OR PAT_ID IN (SELECT PAT_ID FROM patient WHERE PAT_EMAIL = ?)");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = "Username or email already exists.";
        } else {
            // Insert into patient table
            $stmt = $pdo->prepare("INSERT INTO patient 
                (PAT_FIRST_NAME, PAT_MIDDLE_NAME, PAT_LAST_NAME, PAT_DOB, PAT_GENDER, PAT_CONTACT_NUM, PAT_EMAIL, PAT_ADDRESS)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$first_name, $middle_name ?: null, $last_name, $dob ?: null, $gender ?: null, $contact, $email, $address ?: null]);

            $pat_id = $pdo->lastInsertId();

            // Insert into user table
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO user (USER_NAME, USER_PASSWORD, PAT_ID) VALUES (?, ?, ?)");
            $stmt->execute([$username, $hashed_password, $pat_id]);

            $success = "Patient registered successfully! You can now <a href='../pages/login.php'>login</a>.";
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
<title>Register Patient - MediCare Clinic</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { min-height:100vh; display:flex; align-items:center; justify-content:center; background:#f0f2f5; }
    .register-card { background:white; padding:2rem; border-radius:15px; box-shadow:0 10px 30px rgba(0,0,0,0.1); width:100%; max-width:500px; }
</style>
</head>
<body>
<div class="register-card">
    <h2 class="text-center mb-4">Register as Patient</h2>

    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label>First Name</label>
            <input type="text" class="form-control" name="first_name" required>
        </div>
        <div class="mb-3">
            <label>Middle Name</label>
            <input type="text" class="form-control" name="middle_name">
        </div>
        <div class="mb-3">
            <label>Last Name</label>
            <input type="text" class="form-control" name="last_name" required>
        </div>
        <div class="mb-3">
            <label>Date of Birth</label>
            <input type="date" class="form-control" name="dob">
        </div>
        <div class="mb-3">
            <label>Gender</label>
            <select class="form-control" name="gender">
                <option value="">Select</option>
                <option>Male</option>
                <option>Female</option>
                <option>Other</option>
            </select>
        </div>
        <div class="mb-3">
            <label>Contact Number</label>
            <input type="text" class="form-control" name="contact" required>
        </div>
        <div class="mb-3">
            <label>Email</label>
            <input type="email" class="form-control" name="email" required>
        </div>
        <div class="mb-3">
            <label>Address</label>
            <textarea class="form-control" name="address"></textarea>
        </div>
        <hr>
        <div class="mb-3">
            <label>Username</label>
            <input type="text" class="form-control" name="username" required>
        </div>
        <div class="mb-3">
            <label>Password</label>
            <input type="password" class="form-control" name="password" required>
        </div>

        <button type="submit" class="btn btn-primary w-100">Register</button>
    </form>
</div>
</body>
</html>
