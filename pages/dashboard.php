<?php
$page_title = "Dashboard";
include '../includes/head.php';
require_once '../data/dbconfig.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Determine user role and include appropriate dashboard content
$user_role = '';
if (isset($_SESSION['user_is_superadmin']) && $_SESSION['user_is_superadmin']) {
    $user_role = 'superadmin';
} elseif (isset($_SESSION['staff_id'])) {
    $user_role = 'staff';
} elseif (isset($_SESSION['doc_id'])) {
    $user_role = 'doctor';
} elseif (isset($_SESSION['pat_id'])) {
    $user_role = 'patient';
}
?>

<div class="container-fluid">
    <div class="row">
        <!-- Include sidebar here -->
        <?php include '../includes/sidebar.php'; ?>
        
        <!-- Main content - remove conflicting styles -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <?php
            // Include the appropriate dashboard content
            $dashboard_file = "../includes/dashboards/{$user_role}_dashboard.php";
            if (file_exists($dashboard_file)) {
                include $dashboard_file;
            } else {
                echo "<div class='alert alert-danger'>Dashboard not available for your role.</div>";
            }
            ?>
        </main>
    </div>
</div>

<?php include '../includes/tail.php'; ?>