<?php
$page_title = "Medical Record Management";
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
    if ($action == 'create') {
        $stmt = $pdo->prepare("INSERT INTO MEDICAL_RECORD (MED_REC_DIAGNOSIS, MED_REC_PRESCRIPTION, MED_REC_VISIT_DATE, APPT_ID) VALUES (?, ?, ?, ?)");
        try {
            $stmt->execute([
                $_POST['diagnosis'],
                $_POST['prescription'],
                $_POST['visit_date'],
                $_POST['appt_id']
            ]);
            $message = '<div class="alert alert-success">Medical record created successfully!</div>';
            $action = 'list';
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Error creating medical record: ' . $e->getMessage() . '</div>';
        }
    } elseif ($action == 'edit' && isset($_POST['id'])) {
        $stmt = $pdo->prepare("UPDATE MEDICAL_RECORD SET MED_REC_DIAGNOSIS = ?, MED_REC_PRESCRIPTION = ?, MED_REC_VISIT_DATE = ? WHERE MED_REC_ID = ?");
        try {
            $stmt->execute([
                $_POST['diagnosis'],
                $_POST['prescription'],
                $_POST['visit_date'],
                $_POST['id']
            ]);
            $message = '<div class="alert alert-success">Medical record updated successfully!</div>';
            $action = 'list';
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Error updating medical record: ' . $e->getMessage() . '</div>';
        }
    }
}

// Handle delete action
if (isset($_GET['delete']) && (isset($_SESSION['user_is_superadmin']) || isset($_SESSION['staff_id']) || isset($_SESSION['doc_id']))) {
    $stmt = $pdo->prepare("DELETE FROM MEDICAL_RECORD WHERE MED_REC_ID = ?");
    try {
        $stmt->execute([$_GET['delete']]);
        $message = '<div class="alert alert-success">Medical record deleted successfully!</div>';
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Error deleting medical record: ' . $e->getMessage() . '</div>';
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Medical Record Management</h1>
                <?php if ($action == 'list' && (isset($_SESSION['user_is_superadmin']) || isset($_SESSION['staff_id']) || isset($_SESSION['doc_id']))): ?>
                    <a href="?action=create" class="btn btn-primary">Create New Medical Record</a>
                <?php endif; ?>
            </div>

            <?php echo $message; ?>

            <?php if ($action == 'list'): ?>
                <!-- Medical Record List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">All Medical Records</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        // Build query based on user role
                        if (isset($_SESSION['doc_id'])) {
                            // Doctor can only see records for their appointments
                            $query = "SELECT mr.*, a.APPT_ID, p.PAT_FIRST_NAME, p.PAT_LAST_NAME, d.DOC_FIRST_NAME, d.DOC_LAST_NAME
                                     FROM MEDICAL_RECORD mr
                                     JOIN APPOINTMENT a ON mr.APPT_ID = a.APPT_ID
                                     JOIN PATIENT p ON a.PAT_ID = p.PAT_ID
                                     JOIN DOCTOR d ON a.DOC_ID = d.DOC_ID
                                     WHERE a.DOC_ID = ?
                                     ORDER BY mr.MED_REC_VISIT_DATE DESC";
                            $stmt = $pdo->prepare($query);
                            $stmt->execute([$_SESSION['doc_id']]);
                        } else {
                            // Staff and Super Admin can see all records
                            $query = "SELECT mr.*, a.APPT_ID, p.PAT_FIRST_NAME, p.PAT_LAST_NAME, d.DOC_FIRST_NAME, d.DOC_LAST_NAME
                                     FROM MEDICAL_RECORD mr
                                     JOIN APPOINTMENT a ON mr.APPT_ID = a.APPT_ID
                                     JOIN PATIENT p ON a.PAT_ID = p.PAT_ID
                                     JOIN DOCTOR d ON a.DOC_ID = d.DOC_ID
                                     ORDER BY mr.MED_REC_VISIT_DATE DESC";
                            $stmt = $pdo->query($query);
                        }
                        $records = $stmt->fetchAll();
                        ?>

                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Record ID</th>
                                        <th>Appointment ID</th>
                                        <th>Patient</th>
                                        <th>Doctor</th>
                                        <th>Visit Date</th>
                                        <th>Diagnosis</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($records)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">No medical records found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($records as $record): ?>
                                            <tr>
                                                <td><?php echo $record['MED_REC_ID']; ?></td>
                                                <td><code><?php echo htmlspecialchars($record['APPT_ID']); ?></code></td>
                                                <td><?php echo htmlspecialchars($record['PAT_FIRST_NAME'] . ' ' . $record['PAT_LAST_NAME']); ?></td>
                                                <td>Dr. <?php echo htmlspecialchars($record['DOC_FIRST_NAME'] . ' ' . $record['DOC_LAST_NAME']); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($record['MED_REC_VISIT_DATE'])); ?></td>
                                                <td>
                                                    <?php 
                                                    $diagnosis = $record['MED_REC_DIAGNOSIS'];
                                                    if (strlen($diagnosis) > 50) {
                                                        echo htmlspecialchars(substr($diagnosis, 0, 50)) . '...';
                                                    } else {
                                                        echo htmlspecialchars($diagnosis);
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="?action=view&id=<?php echo $record['MED_REC_ID']; ?>" class="btn btn-outline-primary">View</a>
                                                        <a href="?action=edit&id=<?php echo $record['MED_REC_ID']; ?>" class="btn btn-outline-secondary">Edit</a>
                                                        <?php if (isset($_SESSION['user_is_superadmin']) || isset($_SESSION['staff_id']) || 
                                                                 (isset($_SESSION['doc_id']) && $_SESSION['doc_id'] == $record['DOC_ID'])): ?>
                                                            <a href="?delete=<?php echo $record['MED_REC_ID']; ?>" 
                                                               class="btn btn-outline-danger btn-delete" 
                                                               onclick="return confirm('Are you sure you want to delete this medical record?')">Delete</a>
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

            <?php elseif ($action == 'create' || $action == 'edit'): ?>
                <!-- Create/Edit Form -->
                <?php
                $record_data = [];
                if ($action == 'edit' && isset($_GET['id'])) {
                    $stmt = $pdo->prepare("SELECT mr.*, a.APPT_ID, p.PAT_FIRST_NAME, p.PAT_LAST_NAME, d.DOC_FIRST_NAME, d.DOC_LAST_NAME
                                          FROM MEDICAL_RECORD mr
                                          JOIN APPOINTMENT a ON mr.APPT_ID = a.APPT_ID
                                          JOIN PATIENT p ON a.PAT_ID = p.PAT_ID
                                          JOIN DOCTOR d ON a.DOC_ID = d.DOC_ID
                                          WHERE mr.MED_REC_ID = ?");
                    $stmt->execute([$_GET['id']]);
                    $record_data = $stmt->fetch();
                    if (!$record_data) {
                        echo '<div class="alert alert-danger">Medical record not found.</div>';
                        include '../includes/tail.php';
                        exit();
                    }
                }

                // Get appointments for dropdown
                if (isset($_SESSION['doc_id'])) {
                    // Doctor can only see their appointments
                    $appointments_stmt = $pdo->prepare("
                        SELECT a.APPT_ID, a.APPT_DATE, p.PAT_FIRST_NAME, p.PAT_LAST_NAME
                        FROM APPOINTMENT a
                        JOIN PATIENT p ON a.PAT_ID = p.PAT_ID
                        WHERE a.DOC_ID = ? AND a.STAT_ID = 2 -- Completed appointments
                        ORDER BY a.APPT_DATE DESC
                    ");
                    $appointments_stmt->execute([$_SESSION['doc_id']]);
                } else {
                    // Staff and Super Admin can see all appointments
                    $appointments_stmt = $pdo->query("
                        SELECT a.APPT_ID, a.APPT_DATE, p.PAT_FIRST_NAME, p.PAT_LAST_NAME, d.DOC_FIRST_NAME, d.DOC_LAST_NAME
                        FROM APPOINTMENT a
                        JOIN PATIENT p ON a.PAT_ID = p.PAT_ID
                        JOIN DOCTOR d ON a.DOC_ID = d.DOC_ID
                        WHERE a.STAT_ID = 2 -- Completed appointments
                        ORDER BY a.APPT_DATE DESC
                    ");
                }
                $appointments = $appointments_stmt->fetchAll();
                ?>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><?php echo $action == 'create' ? 'Create New Medical Record' : 'Edit Medical Record'; ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <?php if ($action == 'edit'): ?>
                                <input type="hidden" name="id" value="<?php echo $record_data['MED_REC_ID']; ?>">
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <label for="appt_id" class="form-label">Appointment <span class="text-danger">*</span></label>
                                <select class="form-control" id="appt_id" name="appt_id" required <?php echo $action == 'edit' ? 'disabled' : ''; ?>>
                                    <option value="">Select Appointment</option>
                                    <?php foreach ($appointments as $appt): ?>
                                        <option value="<?php echo $appt['APPT_ID']; ?>" 
                                            <?php echo (isset($record_data['APPT_ID']) && $record_data['APPT_ID'] == $appt['APPT_ID']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($appt['APPT_ID']); ?> - 
                                            <?php echo htmlspecialchars($appt['PAT_FIRST_NAME'] . ' ' . $appt['PAT_LAST_NAME']); ?>
                                            (<?php echo date('M j, Y', strtotime($appt['APPT_DATE'])); ?>)
                                            <?php if (isset($appt['DOC_FIRST_NAME'])): ?>
                                                - Dr. <?php echo htmlspecialchars($appt['DOC_FIRST_NAME'] . ' ' . $appt['DOC_LAST_NAME']); ?>
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($action == 'edit'): ?>
                                    <input type="hidden" name="appt_id" value="<?php echo $record_data['APPT_ID']; ?>">
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label for="visit_date" class="form-label">Visit Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="visit_date" name="visit_date" 
                                       value="<?php echo isset($record_data['MED_REC_VISIT_DATE']) ? $record_data['MED_REC_VISIT_DATE'] : date('Y-m-d'); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="diagnosis" class="form-label">Diagnosis <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="diagnosis" name="diagnosis" rows="4" required><?php echo htmlspecialchars($record_data['MED_REC_DIAGNOSIS'] ?? ''); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="prescription" class="form-label">Prescription</label>
                                <textarea class="form-control" id="prescription" name="prescription" rows="4"><?php echo htmlspecialchars($record_data['MED_REC_PRESCRIPTION'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary"><?php echo $action == 'create' ? 'Create Record' : 'Update Record'; ?></button>
                                <a href="?" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>

            <?php elseif ($action == 'view' && isset($_GET['id'])): ?>
                <!-- View Medical Record Details -->
                <?php
                $stmt = $pdo->prepare("
                    SELECT mr.*, a.APPT_ID, a.APPT_DATE, a.APPT_TIME,
                           p.PAT_FIRST_NAME, p.PAT_LAST_NAME, p.PAT_DOB, p.PAT_GENDER,
                           d.DOC_FIRST_NAME, d.DOC_LAST_NAME, s.SPEC_NAME
                    FROM MEDICAL_RECORD mr
                    JOIN APPOINTMENT a ON mr.APPT_ID = a.APPT_ID
                    JOIN PATIENT p ON a.PAT_ID = p.PAT_ID
                    JOIN DOCTOR d ON a.DOC_ID = d.DOC_ID
                    LEFT JOIN SPECIALIZATION s ON d.SPEC_ID = s.SPEC_ID
                    WHERE mr.MED_REC_ID = ?
                ");
                $stmt->execute([$_GET['id']]);
                $record_data = $stmt->fetch();
                
                if (!$record_data) {
                    echo '<div class="alert alert-danger">Medical record not found.</div>';
                } else {
                ?>
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Medical Record Details</h5>
                            <div class="btn-group">
                                <a href="?action=edit&id=<?php echo $record_data['MED_REC_ID']; ?>" class="btn btn-outline-secondary">Edit</a>
                                <a href="?" class="btn btn-outline-primary">Back to List</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Medical Information</h6>
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="30%">Record ID</th>
                                            <td><?php echo $record_data['MED_REC_ID']; ?></td>
                                        </tr>
                                        <tr>
                                            <th>Appointment ID</th>
                                            <td><code><?php echo htmlspecialchars($record_data['APPT_ID']); ?></code></td>
                                        </tr>
                                        <tr>
                                            <th>Visit Date</th>
                                            <td><?php echo date('F j, Y', strtotime($record_data['MED_REC_VISIT_DATE'])); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Original Appointment</th>
                                            <td>
                                                <?php echo date('F j, Y', strtotime($record_data['APPT_DATE'])); ?><br>
                                                <small class="text-muted"><?php echo date('h:i A', strtotime($record_data['APPT_TIME'])); ?></small>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Created At</th>
                                            <td><?php echo date('F j, Y g:i A', strtotime($record_data['MED_REC_CREATED_AT'])); ?></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6>Patient Information</h6>
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="30%">Name</th>
                                            <td><?php echo htmlspecialchars($record_data['PAT_FIRST_NAME'] . ' ' . $record_data['PAT_LAST_NAME']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Date of Birth</th>
                                            <td><?php echo date('F j, Y', strtotime($record_data['PAT_DOB'])); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Gender</th>
                                            <td><?php echo htmlspecialchars($record_data['PAT_GENDER']); ?></td>
                                        </tr>
                                    </table>

                                    <h6 class="mt-4">Doctor Information</h6>
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="30%">Name</th>
                                            <td>Dr. <?php echo htmlspecialchars($record_data['DOC_FIRST_NAME'] . ' ' . $record_data['DOC_LAST_NAME']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Specialization</th>
                                            <td><?php echo htmlspecialchars($record_data['SPEC_NAME']); ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <h6>Diagnosis</h6>
                                    <div class="card">
                                        <div class="card-body">
                                            <?php echo nl2br(htmlspecialchars($record_data['MED_REC_DIAGNOSIS'])); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6>Prescription</h6>
                                    <div class="card">
                                        <div class="card-body">
                                            <?php if ($record_data['MED_REC_PRESCRIPTION']): ?>
                                                <?php echo nl2br(htmlspecialchars($record_data['MED_REC_PRESCRIPTION'])); ?>
                                            <?php else: ?>
                                                <span class="text-muted">No prescription provided</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
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