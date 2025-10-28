<?php
$page_title = "Today's Appointments";
include '../includes/head.php';
require_once '../data/dbconfig.php';

// Check if user is doctor
if (!isset($_SESSION['doc_id'])) {
    header("Location: login.php");
    exit();
}

$doctor_id = $_SESSION['doc_id'];
$today = date('Y-m-d');

// Get doctor's today appointments
$stmt = $pdo->prepare("
    SELECT a.APPT_ID, a.APPT_TIME, p.PAT_FIRST_NAME, p.PAT_LAST_NAME,
           p.PAT_CONTACT_NUM, s.SERV_NAME, st.STAT_NAME
    FROM APPOINTMENT a
    JOIN PATIENT p ON a.PAT_ID = p.PAT_ID
    JOIN SERVICE s ON a.SERV_ID = s.SERV_ID
    JOIN STATUS st ON a.STAT_ID = st.STAT_ID
    WHERE a.DOC_ID = ? AND a.APPT_DATE = ?
    ORDER BY a.APPT_TIME
");
$stmt->execute([$doctor_id, $today]);
$appointments = $stmt->fetchAll();

// Get doctor info
$doctor_stmt = $pdo->prepare("
    SELECT d.*, s.SPEC_NAME
    FROM DOCTOR d
    LEFT JOIN SPECIALIZATION s ON d.SPEC_ID = s.SPEC_ID
    WHERE d.DOC_ID = ?
");
$doctor_stmt->execute([$doctor_id]);
$doctor = $doctor_stmt->fetch();
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Today's Appointments</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <span class="text-muted">Dr. <?php echo htmlspecialchars($doctor['DOC_FIRST_NAME'] . ' ' . $doctor['DOC_LAST_NAME']); ?> - <?php echo htmlspecialchars($doctor['SPEC_NAME']); ?></span>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Appointments for <?php echo date('F j, Y'); ?></h5>
                            <span class="badge bg-primary"><?php echo count($appointments); ?> appointments</span>
                        </div>
                        <div class="card-body">
                            <?php if (empty($appointments)): ?>
                                <div class="text-center py-4">
                                    <p class="text-muted">No appointments scheduled for today.</p>
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($appointments as $appointment): ?>
                                        <div class="col-md-6 col-lg-4 mb-4">
                                            <div class="card h-100 border-<?php
                                                switch($appointment['STAT_NAME']) {
                                                    case 'Scheduled': echo 'primary'; break;
                                                    case 'Completed': echo 'success'; break;
                                                    case 'Cancelled': echo 'danger'; break;
                                                    default: echo 'secondary';
                                                }
                                            ?>">
                                                <div class="card-header">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <strong><?php echo date('h:i A', strtotime($appointment['APPT_TIME'])); ?></strong>
                                                        <span class="badge bg-<?php
                                                            switch($appointment['STAT_NAME']) {
                                                                case 'Scheduled': echo 'primary'; break;
                                                                case 'Completed': echo 'success'; break;
                                                                case 'Cancelled': echo 'danger'; break;
                                                                default: echo 'secondary';
                                                            }
                                                        ?>">
                                                            <?php echo $appointment['STAT_NAME']; ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="card-body">
                                                    <h6 class="card-title"><?php echo htmlspecialchars($appointment['PAT_FIRST_NAME'] . ' ' . $appointment['PAT_LAST_NAME']); ?></h6>
                                                    <p class="card-text mb-1">
                                                        <small class="text-muted">Service: <?php echo htmlspecialchars($appointment['SERV_NAME']); ?></small>
                                                    </p>
                                                    <p class="card-text mb-1">
                                                        <small class="text-muted">Contact: <?php echo htmlspecialchars($appointment['PAT_CONTACT_NUM']); ?></small>
                                                    </p>
                                                </div>
                                                <div class="card-footer bg-transparent">
                                                    <div class="btn-group w-100">
                                                        <a href="../modules/medical_record_manage.php?action=create&appointment_id=<?php echo $appointment['APPT_ID']; ?>"
                                                           class="btn btn-sm btn-outline-primary">Medical Record</a>
                                                        <?php if ($appointment['STAT_NAME'] == 'Scheduled'): ?>
                                                            <a href="../modules/appointment_manage.php?action=complete&id=<?php echo $appointment['APPT_ID']; ?>"
                                                               class="btn btn-sm btn-outline-success">Complete</a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="row mt-4">
                <div class="col-md-4">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center">
                            <h5>Scheduled</h5>
                            <h3>
                                <?php
                                    $scheduled = array_filter($appointments, function($apt) {
                                        return $apt['STAT_NAME'] == 'Scheduled';
                                    });
                                    echo count($scheduled);
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <h5>Completed</h5>
                            <h3>
                                <?php
                                    $completed = array_filter($appointments, function($apt) {
                                        return $apt['STAT_NAME'] == 'Completed';
                                    });
                                    echo count($completed);
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-info text-white">
                        <div class="card-body text-center">
                            <h5>Total Today</h5>
                            <h3><?php echo count($appointments); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../includes/tail.php'; ?>
