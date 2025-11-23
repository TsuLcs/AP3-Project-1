<?php
$page_title = "Medical History";
include '../includes/head.php';
require_once '../data/dbconfig.php';
require_once '../includes/auth.php';

// Check access - Patients and Doctors
checkAccess(['patient', 'doctor']);

$patient_id = $_SESSION['patient_id'] ?? null;
$doctor_id = $_SESSION['doctor_id'] ?? null;

// Build query based on user role
if ($patient_id) {
    // Patients can only see their own medical history
    $medical_history = $pdo->prepare("
        SELECT mr.*, a.APPT_DATE, a.APPT_TIME,
               d.DOC_FIRST_NAME, d.DOC_LAST_NAME, d.SPEC_ID,
               s.SERV_NAME, spec.SPEC_NAME
        FROM medical_record mr
        JOIN appointment a ON mr.APPT_ID = a.APPT_ID
        JOIN doctor d ON a.DOC_ID = d.DOC_ID
        JOIN service s ON a.SERV_ID = s.SERV_ID
        LEFT JOIN specialization spec ON d.SPEC_ID = spec.SPEC_ID
        WHERE a.PAT_ID = ?
        ORDER BY mr.MED_REC_VISIT_DATE DESC
    ");
    $medical_history->execute([$patient_id]);

    // Get patient info
    $patient_info = $pdo->prepare("SELECT * FROM patient WHERE PAT_ID = ?");
    $patient_info->execute([$patient_id]);
    $patient = $patient_info->fetch(PDO::FETCH_ASSOC);
} elseif ($doctor_id) {
    // Doctors can see medical history of their patients
    $medical_history = $pdo->prepare("
        SELECT mr.*, a.APPT_DATE, a.APPT_TIME,
               p.PAT_FIRST_NAME, p.PAT_LAST_NAME, p.PAT_GENDER, p.PAT_DOB,
               s.SERV_NAME, spec.SPEC_NAME
        FROM medical_record mr
        JOIN appointment a ON mr.APPT_ID = a.APPT_ID
        JOIN patient p ON a.PAT_ID = p.PAT_ID
        JOIN service s ON a.SERV_ID = s.SERV_ID
        LEFT JOIN specialization spec ON a.DOC_ID = spec.SPEC_ID
        WHERE a.DOC_ID = ?
        ORDER BY mr.MED_REC_VISIT_DATE DESC
    ");
    $medical_history->execute([$doctor_id]);
} else {
    // This shouldn't happen due to access control
    header("Location: unauthorized.php");
    exit();
}

$medical_history = $medical_history->fetchAll();
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
    <style>
        .medical-record-card {
            border-left: 4px solid #007bff;
            transition: transform 0.2s;
        }
        .medical-record-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .diagnosis-text {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 10px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-history me-2"></i>Medical History
                    </h1>
                    <?php if ($patient_id): ?>
                        <a href="patient_dashboard.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Patient Info Card (for doctors) -->
                <?php if ($doctor_id && isset($_GET['patient_id'])):
                    $view_patient_id = $_GET['patient_id'];
                    $view_patient = $pdo->prepare("SELECT * FROM patient WHERE PAT_ID = ?");
                    $view_patient->execute([$view_patient_id]);
                    $patient = $view_patient->fetch(PDO::FETCH_ASSOC);
                ?>
                    <div class="card bg-primary text-white mb-4">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <h4 class="card-title"><?php echo htmlspecialchars($patient['PAT_FIRST_NAME'] . ' ' . $patient['PAT_LAST_NAME']); ?></h4>
                                    <p class="card-text mb-1">
                                        <i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($patient['PAT_EMAIL']); ?>
                                    </p>
                                    <p class="card-text mb-1">
                                        <i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($patient['PAT_CONTACT_NUM']); ?>
                                    </p>
                                    <p class="card-text">
                                        <i class="fas fa-birthday-cake me-2"></i>
                                        <?php echo date('F j, Y', strtotime($patient['PAT_DOB'])); ?>
                                        (<?php echo date_diff(date_create($patient['PAT_DOB']), date_create('today'))->y; ?> years old)
                                    </p>
                                </div>
                                <div class="col-md-4 text-end">
                                    <div class="display-4">
                                        <?php echo count($medical_history); ?>
                                    </div>
                                    <p>Medical Records</p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php elseif ($patient_id): ?>
                    <!-- Patient's own info -->
                    <div class="card bg-primary text-white mb-4">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <h4 class="card-title"><?php echo htmlspecialchars($patient['PAT_FIRST_NAME'] . ' ' . $patient['PAT_LAST_NAME']); ?></h4>
                                    <p class="card-text">
                                        <i class="fas fa-birthday-cake me-2"></i>
                                        <?php echo date('F j, Y', strtotime($patient['PAT_DOB'])); ?>
                                        (<?php echo date_diff(date_create($patient['PAT_DOB']), date_create('today'))->y; ?> years old)
                                    </p>
                                </div>
                                <div class="col-md-4 text-end">
                                    <div class="display-4">
                                        <?php echo count($medical_history); ?>
                                    </div>
                                    <p>Medical Records</p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Medical History Timeline -->
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h6 class="m-0 font-weight-bold">
                            <i class="fas fa-file-medical-alt me-2"></i>Medical Records History
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (count($medical_history) > 0): ?>
                            <div class="timeline">
                                <?php foreach ($medical_history as $record):
                                    $age_at_visit = date_diff(date_create($patient['PAT_DOB']), date_create($record['MED_REC_VISIT_DATE']))->y;
                                ?>
                                    <div class="card medical-record-card mb-4">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-8">
                                                    <h5 class="card-title text-primary">
                                                        <?php echo date('F j, Y', strtotime($record['MED_REC_VISIT_DATE'])); ?>
                                                    </h5>
                                                    <div class="mb-2">
                                                        <?php if ($patient_id): ?>
                                                            <span class="badge bg-info me-2">
                                                                Dr. <?php echo htmlspecialchars($record['DOC_FIRST_NAME'] . ' ' . $record['DOC_LAST_NAME']); ?>
                                                            </span>
                                                            <?php if ($record['SPEC_NAME']): ?>
                                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($record['SPEC_NAME']); ?></span>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <span class="badge bg-info me-2">
                                                                <?php echo htmlspecialchars($record['PAT_FIRST_NAME'] . ' ' . $record['PAT_LAST_NAME']); ?>
                                                            </span>
                                                            <span class="badge bg-secondary">Age: <?php echo $age_at_visit; ?></span>
                                                        <?php endif; ?>
                                                        <span class="badge bg-success"><?php echo htmlspecialchars($record['SERV_NAME']); ?></span>
                                                    </div>

                                                    <?php if ($record['MED_REC_DIAGNOSIS']): ?>
                                                        <div class="mb-3">
                                                            <h6 class="text-muted">Diagnosis:</h6>
                                                            <div class="diagnosis-text">
                                                                <?php echo nl2br(htmlspecialchars($record['MED_REC_DIAGNOSIS'])); ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>

                                                    <?php if ($record['MED_REC_PRESCRIPTION']): ?>
                                                        <div class="mb-3">
                                                            <h6 class="text-muted">Prescription:</h6>
                                                            <div class="diagnosis-text">
                                                                <?php echo nl2br(htmlspecialchars($record['MED_REC_PRESCRIPTION'])); ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-md-4 text-end">
                                                    <small class="text-muted">
                                                        Appointment: <?php echo date('M j, Y', strtotime($record['APPT_DATE'])); ?><br>
                                                        Time: <?php echo date('g:i A', strtotime($record['APPT_TIME'])); ?>
                                                    </small>
                                                    <br><br>
                                                    <button type="button" class="btn btn-sm btn-outline-primary view-record-details"
                                                            data-diagnosis="<?php echo htmlspecialchars($record['MED_REC_DIAGNOSIS'] ?? 'No diagnosis recorded.'); ?>"
                                                            data-prescription="<?php echo htmlspecialchars($record['MED_REC_PRESCRIPTION'] ?? 'No prescription recorded.'); ?>">
                                                        <i class="fas fa-eye me-1"></i>View Details
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-file-medical-alt fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No Medical History Found</h5>
                                <p class="text-muted">
                                    <?php if ($patient_id): ?>
                                        You don't have any medical records yet. Your medical history will appear here after your appointments.
                                    <?php else: ?>
                                        No medical records found for this patient.
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Record Details Modal -->
    <div class="modal fade" id="recordDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Medical Record Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-4">
                        <h6 class="text-primary">Diagnosis</h6>
                        <div class="border p-3 bg-light rounded" id="modalDiagnosis">
                            No diagnosis recorded.
                        </div>
                    </div>
                    <div class="mb-4">
                        <h6 class="text-primary">Prescription</h6>
                        <div class="border p-3 bg-light rounded" id="modalPrescription">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const viewButtons = document.querySelectorAll('.view-record-details');
            const detailsModal = new bootstrap.Modal(document.getElementById('recordDetailsModal'));

            viewButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const diagnosis = this.getAttribute('data-diagnosis');
                    const prescription = this.getAttribute('data-prescription');

                    document.getElementById('modalDiagnosis').textContent = diagnosis;
                    document.getElementById('modalPrescription').textContent = prescription;

                    detailsModal.show();
                });
            });
        });
    </script>
</body>
</html>
