<?php
$page_title = "My Appointments";
include '../includes/head.php';
require_once '../data/dbconfig.php';

// Check if user is patient
if (!isset($_SESSION['pat_id'])) {
    header("Location: login.php");
    exit();
}

$patient_id = $_SESSION['pat_id'];

// Get patient's appointments
$stmt = $pdo->prepare("
    SELECT a.APPT_ID, a.APPT_DATE, a.APPT_TIME,
           d.DOC_FIRST_NAME, d.DOC_LAST_NAME, s.SPEC_NAME,
           sv.SERV_NAME, st.STAT_NAME,
           pm.PYMT_METH_NAME, ps.PYMT_STAT_NAME, p.PAYMENT_AMOUNT
    FROM APPOINTMENT a
    JOIN DOCTOR d ON a.DOC_ID = d.DOC_ID
    LEFT JOIN SPECIALIZATION s ON d.SPEC_ID = s.SPEC_ID
    JOIN SERVICE sv ON a.SERV_ID = sv.SERV_ID
    JOIN STATUS st ON a.STAT_ID = st.STAT_ID
    LEFT JOIN PAYMENT p ON a.APPT_ID = p.APPT_ID
    LEFT JOIN PAYMENT_METHOD pm ON p.PYMT_METH_ID = pm.PYMT_METH_ID
    LEFT JOIN PAYMENT_STATUS ps ON p.PYMT_STAT_ID = ps.PYMT_STAT_ID
    WHERE a.PAT_ID = ?
    ORDER BY a.APPT_DATE DESC, a.APPT_TIME DESC
");
$stmt->execute([$patient_id]);
$appointments = $stmt->fetchAll();

// Get patient info
$patient_stmt = $pdo->prepare("SELECT * FROM PATIENT WHERE PAT_ID = ?");
$patient_stmt->execute([$patient_id]);
$patient = $patient_stmt->fetch();
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">My Appointments</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="../modules/appointment_manage.php" class="btn btn-primary">Book New Appointment</a>
                </div>
            </div>

            <!-- Welcome Message -->
            <div class="alert alert-info">
                <h5>Welcome, <?php echo htmlspecialchars($patient['PAT_FIRST_NAME'] . ' ' . $patient['PAT_LAST_NAME']); ?>!</h5>
                <p class="mb-0">Here you can view your appointment history and book new appointments.</p>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Appointment History</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($appointments)): ?>
                                <div class="text-center py-4">
                                    <p class="text-muted">You don't have any appointments yet.</p>
                                    <a href="../modules/appointment_manage.php" class="btn btn-primary">Book Your First Appointment</a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Appointment ID</th>
                                                <th>Date & Time</th>
                                                <th>Doctor</th>
                                                <th>Specialization</th>
                                                <th>Service</th>
                                                <th>Status</th>
                                                <th>Payment</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($appointments as $appointment): ?>
                                                <tr>
                                                    <td><code><?php echo htmlspecialchars($appointment['APPT_ID']); ?></code></td>
                                                    <td>
                                                        <?php echo date('M j, Y', strtotime($appointment['APPT_DATE'])); ?><br>
                                                        <small class="text-muted"><?php echo date('h:i A', strtotime($appointment['APPT_TIME'])); ?></small>
                                                    </td>
                                                    <td>Dr. <?php echo htmlspecialchars($appointment['DOC_FIRST_NAME'] . ' ' . $appointment['DOC_LAST_NAME']); ?></td>
                                                    <td><?php echo htmlspecialchars($appointment['SPEC_NAME']); ?></td>
                                                    <td><?php echo htmlspecialchars($appointment['SERV_NAME']); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php
                                                            switch($appointment['STAT_NAME']) {
                                                                case 'Scheduled': echo 'primary'; break;
                                                                case 'Completed': echo 'success'; break;
                                                                case 'Cancelled': echo 'danger'; break;
                                                                default: echo 'secondary';
                                                            }
                                                        ?>">
                                                            <?php echo htmlspecialchars($appointment['STAT_NAME']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($appointment['PYMT_STAT_NAME']): ?>
                                                            <span class="badge bg-<?php
                                                                switch($appointment['PYMT_STAT_NAME']) {
                                                                    case 'Paid': echo 'success'; break;
                                                                    case 'Pending': echo 'warning'; break;
                                                                    case 'Refunded': echo 'info'; break;
                                                                    default: echo 'secondary';
                                                                }
                                                            ?>">
                                                                <?php echo htmlspecialchars($appointment['PYMT_STAT_NAME']); ?>
                                                            </span>
                                                            <br>
                                                            <small><?php echo htmlspecialchars($appointment['PYMT_METH_NAME']); ?> - ₱<?php echo number_format($appointment['PAYMENT_AMOUNT'], 2); ?></small>
                                                        <?php else: ?>
                                                            <span class="text-muted">Not billed</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <a href="../modules/appointment_manage.php?action=view&id=<?php echo $appointment['APPT_ID']; ?>"
                                                               class="btn btn-sm btn-outline-primary">View</a>
                                                            <?php if ($appointment['STAT_NAME'] == 'Scheduled'): ?>
                                                                <a href="../modules/appointment_manage.php?action=cancel&id=<?php echo $appointment['APPT_ID']; ?>"
                                                                   class="btn btn-sm btn-outline-danger"
                                                                   onclick="return confirm('Are you sure you want to cancel this appointment?')">Cancel</a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upcoming Appointments -->
            <?php
            $upcoming_stmt = $pdo->prepare("
                SELECT a.APPT_ID, a.APPT_DATE, a.APPT_TIME,
                       d.DOC_FIRST_NAME, d.DOC_LAST_NAME, s.SPEC_NAME,
                       sv.SERV_NAME
                FROM APPOINTMENT a
                JOIN DOCTOR d ON a.DOC_ID = d.DOC_ID
                LEFT JOIN SPECIALIZATION s ON d.SPEC_ID = s.SPEC_ID
                JOIN SERVICE sv ON a.SERV_ID = sv.SERV_ID
                JOIN STATUS st ON a.STAT_ID = st.STAT_ID
                WHERE a.PAT_ID = ? AND a.APPT_DATE >= ? AND st.STAT_NAME = 'Scheduled'
                ORDER BY a.APPT_DATE, a.APPT_TIME
                LIMIT 3
            ");
            $upcoming_stmt->execute([$patient_id, date('Y-m-d')]);
            $upcoming_appointments = $upcoming_stmt->fetchAll();

            if (!empty($upcoming_appointments)): ?>
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">Upcoming Appointments</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php foreach ($upcoming_appointments as $upcoming): ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="card border-success">
                                            <div class="card-body">
                                                <h6 class="card-title">Dr. <?php echo htmlspecialchars($upcoming['DOC_FIRST_NAME'] . ' ' . $upcoming['DOC_LAST_NAME']); ?></h6>
                                                <p class="card-text mb-1">
                                                    <strong><?php echo date('F j, Y', strtotime($upcoming['APPT_DATE'])); ?></strong><br>
                                                    <small class="text-muted"><?php echo date('h:i A', strtotime($upcoming['APPT_TIME'])); ?></small>
                                                </p>
                                                <p class="card-text mb-1">
                                                    <small>Service: <?php echo htmlspecialchars($upcoming['SERV_NAME']); ?></small>
                                                </p>
                                                <p class="card-text">
                                                    <small>Specialization: <?php echo htmlspecialchars($upcoming['SPEC_NAME']); ?></small>
                                                </p>
                                                <a href="../modules/appointment_manage.php?action=view&id=<?php echo $upcoming['APPT_ID']; ?>"
                                                   class="btn btn-sm btn-outline-success w-100">View Details</a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include '../includes/tail.php'; ?>
