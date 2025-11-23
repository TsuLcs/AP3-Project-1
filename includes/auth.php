<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if a user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if the logged-in user is a superadmin
 */
function isSuperAdmin() {
    return isset($_SESSION['user_is_superadmin']) && $_SESSION['user_is_superadmin'];
}

/**
 * Check if the logged-in user is staff
 */
function isStaff() {
    return isset($_SESSION['staff_id']);
}

/**
 * Check if the logged-in user is a doctor
 */
function isDoctor() {
    return isset($_SESSION['doctor_id']);
}

/**
 * Check if the logged-in user is a patient
 */
function isPatient() {
    return isset($_SESSION['patient_id']);
}

/**
 * Redirect user based on role
 */
function redirectBasedOnRole() {
    if (!isLoggedIn()) {
        header("Location: ../pages/login.php");
        exit();
    }

    if (isSuperAdmin()) {
        header("Location: ../modules/dashboard.php");
    } elseif (isStaff()) {
        header("Location: ../modules/staff_dashboard.php");
    } elseif (isDoctor()) {
        header("Location: ../modules/doctor_dashboard.php");
    } elseif (isPatient()) {
        header("Location: ../modules/patient_dashboard.php");
    } else {
        showUnauthorized();
    }
    exit();
}

/**
 * Check access for allowed roles
 * $allowed_roles = ['superadmin','staff','doctor','patient'];
 */
function checkAccess($allowed_roles = []) {
    if (!isLoggedIn()) {
        header("Location: ../pages/login.php");
        exit();
    }

    $user_role = '';
    if (isSuperAdmin()) $user_role = 'superadmin';
    elseif (isStaff()) $user_role = 'staff';
    elseif (isDoctor()) $user_role = 'doctor';
    elseif (isPatient()) $user_role = 'patient';

    if (!in_array($user_role, $allowed_roles)) {
        showUnauthorized();
    }
}

/**
 * Display a simple unauthorized / access denied page
 */
function showUnauthorized() {
    http_response_code(403);
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>403 Forbidden</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body { display:flex; align-items:center; justify-content:center; height:100vh; background:#f8f9fa; }
            .box { text-align:center; }
        </style>
    </head>
    <body>
        <div class="box">
            <h1 class="display-1 text-danger">403</h1>
            <h3>Access Denied</h3>
            <p>You do not have permission to access this page.</p>
            <a href="../pages/login.php" class="btn btn-primary mt-3">Go to Login</a>
        </div>
    </body>
    </html>';
    exit();
}
?>
