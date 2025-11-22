<?php
$page_title = "Medical Records";
include '../includes/head.php';
require_once '../data/dbconfig.php';
require_once '../includes/auth.php';

// Check access - Super Admin, Staff, and Doctors
checkAccess(['superadmin', 'staff', 'doctor']);

$doctor_id = $_SESSION['doctor_id'] ?? null;

// Handle create/update medical record
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create_medical_record'])) {
        $appt_id = $_POST['appt_id'];
        $diagnosis = $_POST['diagnosis'];
        $prescription = $_POST['prescription'];
        $visit_date = $_POST['visit_date'];

        try {
            // Check if medical record already exists for this appointment
            $check_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM medical_record WHERE APPT_ID = ?");
            $check_stmt->execute([$appt_id]);
            $exists = $check_stmt->fetch(PDO::FETCH_ASSOC)['count'];

            if ($exists > 0) {
                $_SESSION['error'] = "Medical record already exists for this appointment.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO medical_record (MED_REC_DIAGNOSIS, MED_REC_PRESCRIPTION, MED_REC_VISIT_DATE, APPT_ID) VALUES (?, ?, ?, ?)");
                $stmt->execute([$diagnosis, $prescription, $visit_date, $appt_id]);

                // Update appointment status to completed
                $update_appt = $pdo->prepare("UPDATE appointment SET STAT_ID = 2 WHERE APPT_ID = ?");
                $update_appt->execute([$appt_id]);

                $_SESSION['success'] = "Medical record created successfully!";
                header("Location: medical_records.php");
                exit();
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error creating medical record: " . $e->getMessage();
        }
    }

    if (isset($_POST['update_medical_record'])) {
        $med_rec_id = $_POST['med_rec_id'];
        $diagnosis = $_POST['diagnosis'];
        $prescription = $_POST['prescription'];
        $visit_date = $_POST['visit_date'];

        try {
            $stmt = $pdo->prepare("UPDATE medical_record SET MED_REC_DIAGNOSIS = ?, MED_REC_PRESCRIPTION = ?, MED_REC_VISIT_DATE = ? WHERE MED_REC_ID = ?");
            $stmt->execute([$diagnosis, $prescription, $visit_date, $med_rec_id]);
            $_SESSION['success'] = "Medical record updated successfully!";
            header("Location: medical_records.php");
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error updating medical record: " . $e->getMessage();
        }
    }
}

// Handle delete medical record
if (isset($_GET['delete_id'])) {
    $med_rec_id = $_GET['delete_id'];

    try {
        $stmt = $pdo->prepare("DELETE FROM medical_record WHERE MED_REC_ID = ?");
        $stmt->execute([$med_rec_id]);
        $_SESSION['success'] = "Medical record deleted successfully!";
        header("Location: medical_records.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error deleting medical record: " . $e->getMessage();
        header("Location: medical_records.php");
        exit();
    }
}

// Build query based on user role
$query = "
    SELECT mr.*, a.APPT_DATE, a.APPT_TIME,
           p.PAT_FIRST_NAME, p.PAT_LAST_NAME, p.PAT_GENDER, p.PAT_DOB,
           d.DOC_FIRST_NAME, d.DOC_LAST_NAME, s.SERV_NAME
    FROM medical_record mr
    JOIN appointment a ON mr.APPT_ID = a.APPT_ID
    JOIN patient p ON a.PAT_ID = p.PAT_ID
    JOIN doctor d ON a.DOC_ID = d.DOC_ID
    JOIN service s ON a.SERV_ID = s.SERV_ID
";

if ($doctor_id) {
    $query .= " WHERE a.DOC_ID = ?";
    $medical_records = $pdo->prepare($query . " ORDER BY mr.MED_REC_VISIT_DATE DESC");
    $medical_records->execute([$doctor_id]);
} else {
    $medical_records = $pdo->query($query . " ORDER BY mr.MED_REC_VISIT_DATE DESC");
}
$medical_records = $medical_records->fetchAll();

// Get completed appointments without medical records for doctors
if ($doctor_id) {
    $available_appointments = $pdo->prepare("
        SELECT a.*, p.PAT_FIRST_NAME, p.PAT_LAST_NAME, s.SERV_NAME
        FROM appointment a
        JOIN patient p ON a.PAT_ID = p.PAT_ID
        JOIN service s ON a.SERV_ID = s.SERV_ID
        WHERE a.DOC_ID = ? AND a.STAT_ID = 2
        AND a.APPT_ID NOT IN (SELECT APPT_ID FROM medical_record)
        ORDER BY a.APPT_DATE DESC
    ");
    $available_appointments->execute([$doctor_id]);
} else {
    $available_appointments = $pdo->query("
        SELECT a.*, p.PAT_FIRST_NAME, p.PAT_LAST_NAME, s.SERV_NAME
        FROM appointment a
        JOIN patient p ON a.PAT_ID = p.PAT_ID
        JOIN service s ON a.SERV_ID = s.SERV_ID
        WHERE a.STAT_ID = 2
        AND a.APPT_ID NOT IN (SELECT APPT_ID FROM medical_record)
        ORDER BY a.APPT_DATE DESC
    ");
}
$available_appointments = $available_appointments->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - MediCare Clinic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-file-medical me-2"></i>Medical Records
                    </h1>
                    <?php if ($doctor_id): ?>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createMedicalRecordModal">
                            <i class="fas fa-plus me-1"></i>Create Medical Record
                        </button>
                    <?php endif; ?>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <!-- Medical Records List -->
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h6 class="m-0 font-weight-bold">
                            <i class="fas fa-list me-2"></i>Medical Records
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (count($medical_records) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Record ID</th>
                                            <th>Patient</th>
                                            <th>Doctor</th>
                                            <th>Visit Date</th>
                                            <th>Service</th>
                                            <th>Diagnosis</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($medical_records as $record):
                                            $age = date_diff(date_create($record['PAT_DOB']), date_create('today'))->y;
                                        ?>
                                            <tr>
                                                <td>#MR<?php echo str_pad($record['MED_REC_ID'], 4, '0', STR_PAD_LEFT); ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($record['PAT_FIRST_NAME'] . ' ' . $record['PAT_LAST_NAME']); ?></strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?php echo $record['PAT_GENDER']; ?>, <?php echo $age; ?> years
                                                    </small>
                                                </td>
                                                <td>Dr. <?php echo htmlspecialchars($record['DOC_FIRST_NAME'] . ' ' . $record['DOC_LAST_NAME']); ?></td>
                                                <td>
                                                    <?php echo date('M j, Y', strtotime($record['MED_REC_VISIT_DATE'])); ?><br>
                                                    <small class="text-muted">Appt: <?php echo date('M j, Y', strtotime($record['APPT_DATE'])); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($record['SERV_NAME']); ?></td>
                                                <td>
                                                    <?php if ($record['MED_REC_DIAGNOSIS']): ?>
                                                        <span class="badge bg-info" data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($record['MED_REC_DIAGNOSIS']); ?>">
                                                            View Diagnosis
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">No Diagnosis</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <button type="button" class="btn btn-sm btn-outline-primary view-record-btn"
                                                                data-record-id="<?php echo $record['MED_REC_ID']; ?>"
                                                                data-patient-name="<?php echo htmlspecialchars($record['PAT_FIRST_NAME'] . ' ' . $record['PAT_LAST_NAME']); ?>"
                                                                data-doctor-name="Dr. <?php echo htmlspecialchars($record['DOC_FIRST_NAME'] . ' ' . $record['DOC_LAST_NAME']); ?>"
                                                                data-visit-date="<?php echo $record['MED_REC_VISIT_DATE']; ?>"
                                                                data-service="<?php echo htmlspecialchars($record['SERV_NAME']); ?>"
                                                                data-diagnosis="<?php echo htmlspecialchars($record['MED_REC_DIAGNOSIS'] ?? ''); ?>"
                                                                data-prescription="<?php echo htmlspecialchars($record['MED_REC_PRESCRIPTION'] ?? ''); ?>">
                                                            <i class="fas fa-eye me-1"></i>View
                                                        </button>
                                                        <?php if ($doctor_id == $record['DOC_ID'] || isSuperAdmin()): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-warning edit-record-btn"
                                                                    data-record-id="<?php echo $record['MED_REC_ID']; ?>"
                                                                    data-diagnosis="<?php echo htmlspecialchars($record['MED_REC_DIAGNOSIS'] ?? ''); ?>"
                                                                    data-prescription="<?php echo htmlspecialchars($record['MED_REC_PRESCRIPTION'] ?? ''); ?>"
                                                                    data-visit-date="<?php echo $record['MED_REC_VISIT_DATE']; ?>">
                                                                <i class="fas fa-edit me-1"></i>Edit
                                                            </button>
                                                        <?php endif; ?>
                                                        <?php if (isSuperAdmin()): ?>
                                                            <a href="medical_records.php?delete_id=<?php echo $record['MED_REC_ID']; ?>"
                                                               class="btn btn-sm btn-outline-danger"
                                                               onclick="return confirm('Are you sure you want to delete this medical record? This action cannot be undone.')">
                                                                <i class="fas fa-trash me-1"></i>Delete
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-file-medical-alt fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No Medical Records Found</h5>
                                <p class="text-muted">No medical records have been created yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Create Medical Record Modal -->
    <?php if ($doctor_id): ?>
    <div class="modal fade" id="createMedicalRecordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Create Medical Record</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Select Appointment *</label>
                            <select class="form-control" name="appt_id" required>
                                <option value="">Select Completed Appointment</option>
                                <?php foreach ($available_appointments as $appointment): ?>
                                    <option value="<?php echo $appointment['APPT_ID']; ?>">
                                        <?php echo date('M j, Y', strtotime($appointment['APPT_DATE'])); ?> -
                                        <?php echo htmlspecialchars($appointment['PAT_FIRST_NAME'] . ' ' . $appointment['PAT_LAST_NAME']); ?> -
                                        <?php echo htmlspecialchars($appointment['SERV_NAME']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (count($available_appointments) == 0): ?>
                                <small class="text-muted">No completed appointments available for medical record creation.</small>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Visit Date *</label>
                            <input type="date" class="form-control" name="visit_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Diagnosis</label>
                            <textarea class="form-control" name="diagnosis" rows="4" placeholder="Enter patient diagnosis..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Prescription</label>
                            <textarea class="form-control" name="prescription" rows="4" placeholder="Enter prescribed medications and instructions..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="create_medical_record" class="btn btn-primary">Create Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- View Medical Record Modal -->
    <div class="modal fade" id="viewMedicalRecordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Medical Record Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Patient:</strong> <span id="viewPatientName"></span>
                        </div>
                        <div class="col-md-6">
                            <strong>Doctor:</strong> <span id="viewDoctorName"></span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Visit Date:</strong> <span id="viewVisitDate"></span>
                        </div>
                        <div class="col-md-6">
                            <strong>Service:</strong> <span id="viewService"></span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <strong>Diagnosis:</strong>
                        <div class="border p-3 bg-light rounded" id="viewDiagnosis">
                            No diagnosis recorded.
                        </div>
                    </div>
                    <div class="mb-3">
                        <strong>Prescription:</strong>
                        <div class="border p-3 bg-light rounded" id="viewPrescription">
                            No prescription recorded.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Medical Record Modal -->
    <div class="modal fade" id="editMedicalRecordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Medical Record</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="med_rec_id" id="editRecordId">
                        <div class="mb-3">
                            <label class="form-label">Visit Date *</label>
                            <input type="date" class="form-control" name="visit_date" id="editVisitDate" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Diagnosis</label>
                            <textarea class="form-control" name="diagnosis" id="editDiagnosis" rows="4" placeholder="Enter patient diagnosis..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Prescription</label>
                            <textarea class="form-control" name="prescription" id="editPrescription" rows="4" placeholder="Enter prescribed medications and instructions..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_medical_record" class="btn btn-primary">Update Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // View record modal
            const viewButtons = document.querySelectorAll('.view-record-btn');
            const viewModal = new bootstrap.Modal(document.getElementById('viewMedicalRecordModal'));

            viewButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const diagnosis = this.getAttribute('data-diagnosis') || 'No diagnosis recorded.';
                    const prescription = this.getAttribute('data-prescription') || 'No prescription recorded.';

                    document.getElementById('viewPatientName').textContent = this.getAttribute('data-patient-name');
                    document.getElementById('viewDoctorName').textContent = this.getAttribute('data-doctor-name');
                    document.getElementById('viewVisitDate').textContent = new Date(this.getAttribute('data-visit-date')).toLocaleDateString();
                    document.getElementById('viewService').textContent = this.getAttribute('data-service');
                    document.getElementById('viewDiagnosis').textContent = diagnosis;
                    document.getElementById('viewPrescription').textContent = prescription;

                    viewModal.show();
                });
            });

            // Edit record modal
            const editButtons = document.querySelectorAll('.edit-record-btn');
            const editModal = new bootstrap.Modal(document.getElementById('editMedicalRecordModal'));

            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    document.getElementById('editRecordId').value = this.getAttribute('data-record-id');
                    document.getElementById('editVisitDate').value = this.getAttribute('data-visit-date');
                    document.getElementById('editDiagnosis').value = this.getAttribute('data-diagnosis') || '';
                    document.getElementById('editPrescription').value = this.getAttribute('data-prescription') || '';

                    editModal.show();
                });
            });

            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>
