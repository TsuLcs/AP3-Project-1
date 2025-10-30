<?php
$page_title = "Register as Patient";
$hide_navbar = true;
include '../includes/head.php';
require_once '../data/dbconfig.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Start transaction
        $pdo->beginTransaction();

        // Insert patient record
        $stmt = $pdo->prepare("INSERT INTO PATIENT (PAT_FIRST_NAME, PAT_MIDDLE_NAME, PAT_LAST_NAME, PAT_DOB, PAT_GENDER, PAT_CONTACT_NUM, PAT_EMAIL, PAT_ADDRESS) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['first_name'],
            $_POST['middle_name'],
            $_POST['last_name'],
            $_POST['dob'],
            $_POST['gender'],
            $_POST['contact_num'],
            $_POST['email'],
            $_POST['address']
        ]);

        $pat_id = $pdo->lastInsertId();

        // Create user account
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $user_stmt = $pdo->prepare("INSERT INTO USER (USER_NAME, USER_PASSWORD, USER_IS_SUPERADMIN, PAT_ID, STAFF_ID, DOC_ID) VALUES (?, ?, FALSE, ?, NULL, NULL)");
        $user_stmt->execute([$username, $password, $pat_id]);

        $pdo->commit();

        $message = '<div class="alert alert-success">Registration successful! You can now <a href="../pages/login.php">login</a> to your account.</div>';

        // Clear form
        $_POST = array();

    } catch (PDOException $e) {
        $pdo->rollBack();
        if ($e->getCode() == 23000) { // Duplicate entry
            if (strpos($e->getMessage(), 'PAT_EMAIL') !== false) {
                $message = '<div class="alert alert-danger">Email already exists. Please use a different email.</div>';
            } elseif (strpos($e->getMessage(), 'PAT_CONTACT_NUM') !== false) {
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
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Patient Registration</h4>
                </div>
                <div class="card-body">
                    <?php echo $message; ?>

                    <form method="POST">
                        <h5 class="mb-3">Personal Information</h5>
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

                        <div class="row">
                          <div class="col-md-4 mb-3">
                              <label for="dob" class="form-label">Date of Birth <span class="text-danger">*</span></label>
                              <input type="date" class="form-control" id="dob" name="dob"
                                     value="<?php echo htmlspecialchars($_POST['dob'] ?? ''); ?>"
                                     max="<?php echo date('Y-m-d'); ?>"
                                     required>
                          </div>
                            <div class="col-md-4 mb-3">
                                <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
                                <select class="form-control" id="gender" name="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="Male" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                    <option value="Other" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                        </div>

                        <hr class="my-4">
                        <h5 class="mb-3">Account Information</h5>

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

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                            <label class="form-check-label" for="terms">
                                I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms and Conditions</a>
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Register</button>

                        <div class="mt-3 text-center">
                            <p>Already have an account? <a href="../pages/login.php">Login here</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Terms and Conditions Modal -->
<div class="modal fade" id="termsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Terms and Conditions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6>1. Acceptance of Terms</h6>
                <p>By registering as a patient, you agree to these terms and conditions.</p>

                <h6>2. Medical Information</h6>
                <p>You consent to the storage and processing of your medical information for treatment purposes.</p>

                <h6>3. Appointments</h6>
                <p>Appointments must be cancelled at least 24 hours in advance.</p>

                <h6>4. Privacy</h6>
                <p>Your personal and medical information will be kept confidential and secure.</p>

                <h6>5. Communication</h6>
                <p>We may contact you via email or phone for appointment reminders and important updates.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
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

        // Check terms acceptance
        const terms = document.getElementById('terms');
        if (!terms.checked) {
            e.preventDefault();
            alert('You must accept the Terms and Conditions!');
            terms.focus();
            return;
        }
    });
});
</script>

<?php include '../includes/tail.php'; ?>
