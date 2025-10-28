<?php
$page_title = "Payment Management";
include '../includes/head.php';
require_once '../data/dbconfig.php';

// Check access - Super Admin, Staff, and Doctors can access
if (!isset($_SESSION['user_is_superadmin']) && !isset($_SESSION['staff_id']) && !isset($_SESSION['doc_id'])) {
    header("Location: ../pages/login.php");
    exit();
}

$action = $_GET['action'] ?? 'list';
$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($action == 'add') {
        $stmt = $pdo->prepare("INSERT INTO PAYMENT (PAYMENT_AMOUNT, APPT_ID, PYMT_METH_ID, PYMT_STAT_ID) VALUES (?, ?, ?, ?)");
        try {
            $stmt->execute([
                $_POST['payment_amount'],
                $_POST['appt_id'],
                $_POST['pymt_meth_id'],
                $_POST['pymt_stat_id']
            ]);
            $message = '<div class="alert alert-success">Payment record added successfully!</div>';
            $action = 'list';
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Error adding payment record: ' . $e->getMessage() . '</div>';
        }
    } elseif ($action == 'edit' && isset($_POST['id'])) {
        $stmt = $pdo->prepare("UPDATE PAYMENT SET PAYMENT_AMOUNT = ?, APPT_ID = ?, PYMT_METH_ID = ?, PYMT_STAT_ID = ? WHERE PAYMENT_ID = ?");
        try {
            $stmt->execute([
                $_POST['payment_amount'],
                $_POST['appt_id'],
                $_POST['pymt_meth_id'],
                $_POST['pymt_stat_id'],
                $_POST['id']
            ]);
            $message = '<div class="alert alert-success">Payment record updated successfully!</div>';
            $action = 'list';
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Error updating payment record: ' . $e->getMessage() . '</div>';
        }
    }
}

// Handle delete action
if (isset($_GET['delete']) && (isset($_SESSION['user_is_superadmin']) || isset($_SESSION['staff_id']))) {
    $stmt = $pdo->prepare("DELETE FROM PAYMENT WHERE PAYMENT_ID = ?");
    try {
        $stmt->execute([$_GET['delete']]);
        $message = '<div class="alert alert-success">Payment record deleted successfully!</div>';
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Error deleting payment record: ' . $e->getMessage() . '</div>';
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Payment Management</h1>
                <?php if ($action == 'list' && (isset($_SESSION['user_is_superadmin']) || isset($_SESSION['staff_id']))): ?>
                    <a href="?action=add" class="btn btn-primary">Add New Payment</a>
                <?php endif; ?>
            </div>

            <?php echo $message; ?>

            <?php if ($action == 'list'): ?>
                <!-- Payment List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">All Payment Records</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $query = "SELECT p.*, a.APPT_ID, pat.PAT_FIRST_NAME, pat.PAT_LAST_NAME,
                                         d.DOC_FIRST_NAME, d.DOC_LAST_NAME, pm.PYMT_METH_NAME, ps.PYMT_STAT_NAME,
                                         s.SERV_NAME, s.SERV_PRICE
                                 FROM PAYMENT p
                                 JOIN APPOINTMENT a ON p.APPT_ID = a.APPT_ID
                                 JOIN PATIENT pat ON a.PAT_ID = pat.PAT_ID
                                 JOIN DOCTOR d ON a.DOC_ID = d.DOC_ID
                                 JOIN SERVICE s ON a.SERV_ID = s.SERV_ID
                                 JOIN PAYMENT_METHOD pm ON p.PYMT_METH_ID = pm.PYMT_METH_ID
                                 JOIN PAYMENT_STATUS ps ON p.PYMT_STAT_ID = ps.PYMT_STAT_ID
                                 ORDER BY p.PAYMENT_DATE DESC";
                        $stmt = $pdo->query($query);
                        $payments = $stmt->fetchAll();
                        ?>

                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Payment ID</th>
                                        <th>Appointment ID</th>
                                        <th>Patient</th>
                                        <th>Doctor</th>
                                        <th>Service</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($payments)): ?>
                                        <tr>
                                            <td colspan="10" class="text-center text-muted">No payment records found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($payments as $payment): ?>
                                            <tr>
                                                <td><?php echo $payment['PAYMENT_ID']; ?></td>
                                                <td><code><?php echo htmlspecialchars($payment['APPT_ID']); ?></code></td>
                                                <td><?php echo htmlspecialchars($payment['PAT_FIRST_NAME'] . ' ' . $payment['PAT_LAST_NAME']); ?></td>
                                                <td>Dr. <?php echo htmlspecialchars($payment['DOC_FIRST_NAME'] . ' ' . $payment['DOC_LAST_NAME']); ?></td>
                                                <td><?php echo htmlspecialchars($payment['SERV_NAME']); ?></td>
                                                <td><strong>₱<?php echo number_format($payment['PAYMENT_AMOUNT'], 2); ?></strong></td>
                                                <td><?php echo htmlspecialchars($payment['PYMT_METH_NAME']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php
                                                        switch($payment['PYMT_STAT_NAME']) {
                                                            case 'Paid': echo 'success'; break;
                                                            case 'Pending': echo 'warning'; break;
                                                            case 'Refunded': echo 'info'; break;
                                                            default: echo 'secondary';
                                                        }
                                                    ?>">
                                                        <?php echo htmlspecialchars($payment['PYMT_STAT_NAME']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M j, Y', strtotime($payment['PAYMENT_DATE'])); ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="?action=view&id=<?php echo $payment['PAYMENT_ID']; ?>" class="btn btn-outline-primary">View</a>
                                                        <?php if (isset($_SESSION['user_is_superadmin']) || isset($_SESSION['staff_id'])): ?>
                                                            <a href="?action=edit&id=<?php echo $payment['PAYMENT_ID']; ?>" class="btn btn-outline-secondary">Edit</a>
                                                            <a href="?delete=<?php echo $payment['PAYMENT_ID']; ?>"
                                                               class="btn btn-outline-danger btn-delete"
                                                               onclick="return confirm('Are you sure you want to delete this payment record?')">Delete</a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            <?php elseif ($action == 'add' || $action == 'edit'): ?>
                <!-- Add/Edit Form -->
                <?php
                $payment_data = [];
                if ($action == 'edit' && isset($_GET['id'])) {
                    $stmt = $pdo->prepare("SELECT p.*, a.APPT_ID, pat.PAT_FIRST_NAME, pat.PAT_LAST_NAME
                                          FROM PAYMENT p
                                          JOIN APPOINTMENT a ON p.APPT_ID = a.APPT_ID
                                          JOIN PATIENT pat ON a.PAT_ID = pat.PAT_ID
                                          WHERE p.PAYMENT_ID = ?");
                    $stmt->execute([$_GET['id']]);
                    $payment_data = $stmt->fetch();
                    if (!$payment_data) {
                        echo '<div class="alert alert-danger">Payment record not found.</div>';
                        include '../includes/tail.php';
                        exit();
                    }
                }

                // Get appointments for dropdown
                $appointments_stmt = $pdo->query("
                    SELECT a.APPT_ID, a.APPT_DATE, p.PAT_FIRST_NAME, p.PAT_LAST_NAME, s.SERV_NAME, s.SERV_PRICE
                    FROM APPOINTMENT a
                    JOIN PATIENT p ON a.PAT_ID = p.PAT_ID
                    JOIN SERVICE s ON a.SERV_ID = s.SERV_ID
                    WHERE a.STAT_ID = 2 -- Completed appointments
                    ORDER BY a.APPT_DATE DESC
                ");
                $appointments = $appointments_stmt->fetchAll();

                // Get payment methods
                $methods_stmt = $pdo->query("SELECT * FROM PAYMENT_METHOD ORDER BY PYMT_METH_NAME");
                $methods = $methods_stmt->fetchAll();

                // Get payment statuses
                $statuses_stmt = $pdo->query("SELECT * FROM PAYMENT_STATUS ORDER BY PYMT_STAT_NAME");
                $statuses = $statuses_stmt->fetchAll();
                ?>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><?php echo $action == 'add' ? 'Add New Payment' : 'Edit Payment'; ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <?php if ($action == 'edit'): ?>
                                <input type="hidden" name="id" value="<?php echo $payment_data['PAYMENT_ID']; ?>">
                            <?php endif; ?>

                            <div class="mb-3">
                                <label for="appt_id" class="form-label">Appointment <span class="text-danger">*</span></label>
                                <select class="form-control" id="appt_id" name="appt_id" required>
                                    <option value="">Select Appointment</option>
                                    <?php foreach ($appointments as $appt): ?>
                                        <option value="<?php echo $appt['APPT_ID']; ?>"
                                            <?php echo (isset($payment_data['APPT_ID']) && $payment_data['APPT_ID'] == $appt['APPT_ID']) ? 'selected' : ''; ?>
                                            data-price="<?php echo $appt['SERV_PRICE']; ?>">
                                            <?php echo htmlspecialchars($appt['APPT_ID']); ?> -
                                            <?php echo htmlspecialchars($appt['PAT_FIRST_NAME'] . ' ' . $appt['PAT_LAST_NAME']); ?>
                                            (<?php echo date('M j, Y', strtotime($appt['APPT_DATE'])); ?>)
                                            - ₱<?php echo number_format($appt['SERV_PRICE'], 2); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="payment_amount" class="form-label">Payment Amount (₱) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="payment_amount" name="payment_amount"
                                       value="<?php echo htmlspecialchars($payment_data['PAYMENT_AMOUNT'] ?? ''); ?>" step="0.01" min="0" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="pymt_meth_id" class="form-label">Payment Method <span class="text-danger">*</span></label>
                                    <select class="form-control" id="pymt_meth_id" name="pymt_meth_id" required>
                                        <option value="">Select Method</option>
                                        <?php foreach ($methods as $method): ?>
                                            <option value="<?php echo $method['PYMT_METH_ID']; ?>"
                                                <?php echo (isset($payment_data['PYMT_METH_ID']) && $payment_data['PYMT_METH_ID'] == $method['PYMT_METH_ID']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($method['PYMT_METH_NAME']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="pymt_stat_id" class="form-label">Payment Status <span class="text-danger">*</span></label>
                                    <select class="form-control" id="pymt_stat_id" name="pymt_stat_id" required>
                                        <option value="">Select Status</option>
                                        <?php foreach ($statuses as $status): ?>
                                            <option value="<?php echo $status['PYMT_STAT_ID']; ?>"
                                                <?php echo (isset($payment_data['PYMT_STAT_ID']) && $payment_data['PYMT_STAT_ID'] == $status['PYMT_STAT_ID']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($status['PYMT_STAT_NAME']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary"><?php echo $action == 'add' ? 'Add Payment' : 'Update Payment'; ?></button>
                                <a href="?" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>

                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const apptSelect = document.getElementById('appt_id');
                    const amountInput = document.getElementById('payment_amount');

                    apptSelect.addEventListener('change', function() {
                        const selectedOption = this.options[this.selectedIndex];
                        const servicePrice = selectedOption.getAttribute('data-price');
                        if (servicePrice && !amountInput.value) {
                            amountInput.value = servicePrice;
                        }
                    });
                });
                </script>

            <?php elseif ($action == 'view' && isset($_GET['id'])): ?>
                <!-- View Payment Details -->
                <?php
                $stmt = $pdo->prepare("
                    SELECT p.*, a.APPT_ID, a.APPT_DATE, a.APPT_TIME,
                           pat.PAT_FIRST_NAME, pat.PAT_LAST_NAME, pat.PAT_EMAIL, pat.PAT_CONTACT_NUM,
                           d.DOC_FIRST_NAME, d.DOC_LAST_NAME, s.SPEC_NAME,
                           srv.SERV_NAME, srv.SERV_PRICE,
                           pm.PYMT_METH_NAME, ps.PYMT_STAT_NAME
                    FROM PAYMENT p
                    JOIN APPOINTMENT a ON p.APPT_ID = a.APPT_ID
                    JOIN PATIENT pat ON a.PAT_ID = pat.PAT_ID
                    JOIN DOCTOR d ON a.DOC_ID = d.DOC_ID
                    LEFT JOIN SPECIALIZATION s ON d.SPEC_ID = s.SPEC_ID
                    JOIN SERVICE srv ON a.SERV_ID = srv.SERV_ID
                    JOIN PAYMENT_METHOD pm ON p.PYMT_METH_ID = pm.PYMT_METH_ID
                    JOIN PAYMENT_STATUS ps ON p.PYMT_STAT_ID = ps.PYMT_STAT_ID
                    WHERE p.PAYMENT_ID = ?
                ");
                $stmt->execute([$_GET['id']]);
                $payment_data = $stmt->fetch();

                if (!$payment_data) {
                    echo '<div class="alert alert-danger">Payment record not found.</div>';
                } else {
                ?>
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Payment Details</h5>
                            <div class="btn-group">
                                <?php if (isset($_SESSION['user_is_superadmin']) || isset($_SESSION['staff_id'])): ?>
                                    <a href="?action=edit&id=<?php echo $payment_data['PAYMENT_ID']; ?>" class="btn btn-outline-secondary">Edit</a>
                                <?php endif; ?>
                                <a href="?" class="btn btn-outline-primary">Back to List</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Payment Information</h6>
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="30%">Payment ID</th>
                                            <td><?php echo $payment_data['PAYMENT_ID']; ?></td>
                                        </tr>
                                        <tr>
                                            <th>Appointment ID</th>
                                            <td><code><?php echo htmlspecialchars($payment_data['APPT_ID']); ?></code></td>
                                        </tr>
                                        <tr>
                                            <th>Amount</th>
                                            <td><strong>₱<?php echo number_format($payment_data['PAYMENT_AMOUNT'], 2); ?></strong></td>
                                        </tr>
                                        <tr>
                                            <th>Payment Method</th>
                                            <td><?php echo htmlspecialchars($payment_data['PYMT_METH_NAME']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Payment Status</th>
                                            <td>
                                                <span class="badge bg-<?php
                                                    switch($payment_data['PYMT_STAT_NAME']) {
                                                        case 'Paid': echo 'success'; break;
                                                        case 'Pending': echo 'warning'; break;
                                                        case 'Refunded': echo 'info'; break;
                                                        default: echo 'secondary';
                                                    }
                                                ?>">
                                                    <?php echo htmlspecialchars($payment_data['PYMT_STAT_NAME']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Payment Date</th>
                                            <td><?php echo date('F j, Y g:i A', strtotime($payment_data['PAYMENT_DATE'])); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Created At</th>
                                            <td><?php echo date('F j, Y g:i A', strtotime($payment_data['PAYMENT_CREATED_AT'])); ?></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6>Appointment Information</h6>
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="30%">Date</th>
                                            <td><?php echo date('F j, Y', strtotime($payment_data['APPT_DATE'])); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Time</th>
                                            <td><?php echo date('h:i A', strtotime($payment_data['APPT_TIME'])); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Service</th>
                                            <td>
                                                <?php echo htmlspecialchars($payment_data['SERV_NAME']); ?><br>
                                                <small class="text-muted">Service Price: ₱<?php echo number_format($payment_data['SERV_PRICE'], 2); ?></small>
                                            </td>
                                        </tr>
                                    </table>

                                    <h6 class="mt-4">Patient Information</h6>
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="30%">Name</th>
                                            <td><?php echo htmlspecialchars($payment_data['PAT_FIRST_NAME'] . ' ' . $payment_data['PAT_LAST_NAME']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Email</th>
                                            <td><?php echo htmlspecialchars($payment_data['PAT_EMAIL']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Contact</th>
                                            <td><?php echo htmlspecialchars($payment_data['PAT_CONTACT_NUM']); ?></td>
                                        </tr>
                                    </table>

                                    <h6 class="mt-4">Doctor Information</h6>
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="30%">Name</th>
                                            <td>Dr. <?php echo htmlspecialchars($payment_data['DOC_FIRST_NAME'] . ' ' . $payment_data['DOC_LAST_NAME']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Specialization</th>
                                            <td><?php echo htmlspecialchars($payment_data['SPEC_NAME']); ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include '../includes/tail.php'; ?>
