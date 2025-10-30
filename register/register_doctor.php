<?php
$page_title = "Register Doctor";
include '../includes/head.php';
require_once '../data/dbconfig.php';

// Check access - Super Admin and Staff can register doctors
if (!isset($_SESSION['user_is_superadmin']) && !isset($_SESSION['staff_id'])) {
    header("Location: ../pages/login.php");
    exit();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Start transaction
        $pdo->beginTransaction();

        // Insert doctor record
        $stmt = $pdo->prepare("INSERT INTO DOCTOR (DOC_FIRST_NAME, DOC_LAST_NAME, DOC_MIDDLE_NAME, DOC_CONTACT_NUM, DOC_EMAIL, SPEC_ID) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['middle_name'],
            $_POST['contact_num'],
            $_POST['email'],
            $_POST['spec_id']
        ]);

        $doc_id = $pdo->lastInsertId();

        // Create user account
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $user_stmt = $pdo->prepare("INSERT INTO USER (USER_NAME, USER_PASSWORD, USER_IS_SUPERADMIN, PAT_ID, STAFF_ID, DOC_ID) VALUES (?, ?, FALSE, NULL, NULL, ?)");
        $user_stmt->execute([$username, $password, $doc_id]);

        $pdo->commit();

        $message = '<div class="alert alert-success">Doctor registration successful! <a href="../modules/doctor_manage.php">View all doctors</a></div>';

        // Clear form
        $_POST = array();

    } catch (PDOException $e) {
        $pdo->rollBack();
        if ($e->getCode() == 23000) { // Duplicate entry
            if (strpos($e->getMessage(), 'DOC_EMAIL') !== false) {
                $message = '<div class="alert alert-danger">Email already exists. Please use a different email.</div>';
            } elseif (strpos($e->getMessage(), 'DOC_CONTACT_NUM') !== false) {
                $message = '<div class="alert alert-danger">Contact number already exists. Please use a different contact number.</div>';
            } elseif (strpos($e->getMessage(), 'USER_NAME') !== false) {
                $message = '<div class="alert alert-danger">Username already exists. Please choose a different username.</div>';
            } else {
                $message = '<div class="alert alert-danger">Registration failed: ' . $e->getMessage() . '</div>';
            }
        } else {
            $message = '<div class="alert alert-danger">Registration failed: ' . $e->getMessage() . '</div>';
        }
    }
}

// Get specializations for dropdown
$specs_stmt = $pdo->query("SELECT * FROM SPECIALIZATION ORDER BY SPEC_NAME");
$specializations = $specs_stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Register New Doctor</h1>
                <a href="../modules/doctor_manage.php" class="btn btn-outline-primary">Back to Doctor Management</a>
            </div>

            <?php echo $message; ?>

            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Doctor Registration Form</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <h6 class="mb-3">Doctor Information</h6>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="first_name" name="first_name"
                                               value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="middle_name" class="form-label">Middle Name</label>
                                        <input type="text" class="form-control" id="middle_name" name="middle_name"
                                               value="<?php echo htmlspecialchars($_POST['middle_name'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="last_name" name="last_name"
                                               value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email"
                                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="contact_num" class="form-label">Contact Number <span class="text-danger">*</span></label>
                                        <input type="tel" class="form-control" id="contact_num" name="contact_num"
                                               value="<?php echo htmlspecialchars($_POST['contact_num'] ?? ''); ?>" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="spec_id" class="form-label">Specialization <span class="text-danger">*</span></label>
                                    <select class="form-control" id="spec_id" name="spec_id" required>
                                        <option value="">Select Specialization</option>
                                        <?php foreach ($specializations as $spec): ?>
                                            <option value="<?php echo $spec['SPEC_ID']; ?>"
                                                <?php echo (isset($_POST['spec_id']) && $_POST['spec_id'] == $spec['SPEC_ID']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($spec['SPEC_NAME']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <hr class="my-4">
                                <h6 class="mb-3">Account Information</h6>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="username" name="username"
                                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                                        <div class="form-text">Choose a unique username for login.</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                        <div class="form-text">Password must be at least 8 characters long.</div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>

                                <div class="alert alert-info">
                                    <strong>Note:</strong> Doctors will have access to their appointment schedules,
                                    patient medical records, and the ability to manage their own availability.
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">Register Doctor</button>
                                    <a href="../modules/doctor_manage.php" class="btn btn-secondary">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');

    form.addEventListener('submit', function(e) {
        // Check password match
        if (password.value !== confirmPassword.value) {
            e.preventDefault();
            alert('Passwords do not match!');
            confirmPassword.focus();
            return;
        }

        // Check password length
        if (password.value.length < 8) {
            e.preventDefault();
            alert('Password must be at least 8 characters long!');
            password.focus();
            return;
        }
    });
});
</script>

<?php include '../includes/tail.php'; ?>
