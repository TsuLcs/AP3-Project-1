<?php
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isSuperAdmin() {
    return isset($_SESSION['user_is_superadmin']) && $_SESSION['user_is_superadmin'];
}

function isStaff() {
    return isset($_SESSION['staff_id']);
}

function isDoctor() {
    return isset($_SESSION['doctor_id']);
}

function isPatient() {
    return isset($_SESSION['patient_id']);
}

function redirectBasedOnRole() {
    if (isSuperAdmin()) {
        header("Location: modules/dashboard.php");
    } elseif (isStaff()) {
        header("Location: modules/staff_dashboard.php");
    } elseif (isDoctor()) {
        header("Location: modules/doctor_dashboard.php");
    } elseif (isPatient()) {
        header("Location: modules/patient_dashboard.php");
    } else {
        header("Location: login.php");
    }
    exit();
}

function checkAccess($allowed_roles) {
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
        header("Location: ../pages/unauthorized.php");
        exit();
    }
}
?>
