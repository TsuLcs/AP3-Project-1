<?php
$page_title = "Book Appointment";
include '../includes/head.php';
require_once '../data/dbconfig.php';
require_once '../includes/auth.php';

// Check access - Only Patient
checkAccess(['patient']);

$patient_id = $_SESSION['patient_id'];

// Function to generate appointment ID
function generateAppointmentId($pdo) {
    $year = date('Y');
    $month = date('m');

    $last_appt = $pdo->query("SELECT APPT_ID FROM appointment WHERE APPT_ID LIKE 'APT{$year}{$month}%' ORDER BY APPT_ID DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

    if ($last_appt) {
        $last_sequence = intval(substr($last_appt['APPT_ID'], -3));
        $sequence = str_pad($last_sequence + 1, 3, '0', STR_PAD_LEFT);
    } else {
        $sequence = '001';
    }

    return "APT{$year}{$month}{$sequence}";
}

// Handle book appointment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_appointment'])) {
    $doc_id = $_POST['doc_id'];
    $serv_id = $_POST['serv_id'];
    $appt_date = $_POST['appt_date'];
    $appt_time = $_POST['appt_time'];

    try {
        // Check if doctor is available at that time
        $check_availability = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM appointment
            WHERE DOC_ID = ? AND APPT_DATE = ? AND APPT_TIME = ? AND STAT_ID != 3
        ");
        $check_availability->execute([$doc_id, $appt_date, $appt_time]);
        $conflict = $check_availability->fetch(PDO::FETCH_ASSOC)['count'];

        if ($conflict > 0) {
            $_SESSION['error'] = "Doctor is not available at the selected date and time. Please choose a different time.";
        } else {
            $appt_id = generateAppointmentId($pdo);
            $stmt = $pdo->prepare("INSERT INTO appointment (APPT_ID, APPT_DATE, APPT_TIME, PAT_ID, DOC_ID, SERV_ID, STAT_ID) VALUES (?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([$appt_id, $appt_date, $appt_time, $patient_id, $doc_id, $serv_id]);
            $_SESSION['success'] = "Appointment booked successfully! Your Appointment ID: <strong>{$appt_id}</strong>. Please keep this for reference.";
            header("Location: patient_dashboard.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error booking appointment: " . $e->getMessage();
    }
}

// Get available doctors with their specializations
$doctors = $pdo->query("
    SELECT d.*, s.SPEC_NAME
    FROM doctor d
    LEFT JOIN specialization s ON d.SPEC_ID = s.SPEC_ID
    ORDER BY d.DOC_FIRST_NAME, d.DOC_LAST_NAME
")->fetchAll();

// Get services
$services = $pdo->query("SELECT * FROM service ORDER BY SERV_NAME")->fetchAll();

// Get doctor schedules for availability
$schedules = $pdo->query("SELECT * FROM schedule")->fetchAll();
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
        .doctor-card {
            transition: transform 0.3s, box-shadow 0.3s;
            border: 1px solid #e3f2fd;
        }
        .doctor-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .availability-badge {
            font-size: 0.75rem;
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
                        <i class="fas fa-calendar-plus me-2"></i>Book Appointment
                    </h1>
                    <a href="patient_dashboard.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                    </a>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <div class="row">
                    <!-- Doctor Selection -->
                    <div class="col-lg-4 mb-4">
                        <div class="card shadow">
                            <div class="card-header bg-primary text-white">
                                <h6 class="m-0 font-weight-bold">
                                    <i class="fas fa-user-md me-2"></i>Select Doctor
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="list-group" id="doctorList">
                                    <?php foreach ($doctors as $doctor): ?>
                                        <a href="#" class="list-group-item list-group-item-action doctor-item"
                                           data-doctor-id="<?php echo $doctor['DOC_ID']; ?>"
                                           data-doctor-name="Dr. <?php echo htmlspecialchars($doctor['DOC_FIRST_NAME'] . ' ' . $doctor['DOC_LAST_NAME']); ?>">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1">Dr. <?php echo htmlspecialchars($doctor['DOC_FIRST_NAME'] . ' ' . $doctor['DOC_LAST_NAME']); ?></h6>
                                            </div>
                                            <?php if ($doctor['SPEC_NAME']): ?>
                                                <span class="badge bg-info availability-badge"><?php echo htmlspecialchars($doctor['SPEC_NAME']); ?></span>
                                            <?php endif; ?>
                                            <small class="text-muted"><?php echo htmlspecialchars($doctor['DOC_EMAIL']); ?></small>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Selected Doctor Info -->
                        <div class="card shadow mt-4 d-none" id="selectedDoctorCard">
                            <div class="card-header bg-success text-white">
                                <h6 class="m-0 font-weight-bold">
                                    <i class="fas fa-check-circle me-2"></i>Selected Doctor
                                </h6>
                            </div>
                            <div class="card-body">
                                <h6 id="selectedDoctorName" class="text-success"></h6>
                                <p class="mb-1" id="selectedDoctorSpecialty"></p>
                                <p class="mb-0" id="selectedDoctorContact"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Appointment Form -->
                    <div class="col-lg-8">
                        <div class="card shadow">
                            <div class="card-header bg-primary text-white">
                                <h6 class="m-0 font-weight-bold">
                                    <i class="fas fa-calendar-alt me-2"></i>Appointment Details
                                </h6>
                            </div>
                            <div class="card-body">
                                <form method="POST" id="appointmentForm">
                                    <input type="hidden" name="doc_id" id="selectedDoctorId">

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Selected Doctor</label>
                                            <input type="text" class="form-control" id="displayDoctorName" readonly placeholder="Please select a doctor first">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Service Required *</label>
                                            <select class="form-control" name="serv_id" required id="serviceSelect">
                                                <option value="">Select Service</option>
                                                <?php foreach ($services as $service): ?>
                                                    <option value="<?php echo $service['SERV_ID']; ?>" data-price="<?php echo $service['SERV_PRICE']; ?>">
                                                        <?php echo htmlspecialchars($service['SERV_NAME']); ?> - ₱<?php echo number_format($service['SERV_PRICE'], 2); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Appointment Date *</label>
                                            <input type="date" class="form-control" name="appt_date" id="apptDate"
                                                   min="<?php echo date('Y-m-d'); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Preferred Time *</label>
                                            <select class="form-control" name="appt_time" required id="apptTime">
                                                <option value="">Select Time</option>
                                                <!-- Time slots will be populated by JavaScript -->
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Service Details -->
                                    <div class="card bg-light mb-3 d-none" id="serviceDetails">
                                        <div class="card-body">
                                            <h6 class="card-title">Service Information</h6>
                                            <p class="mb-1" id="serviceDescription"></p>
                                            <p class="mb-0"><strong>Price: </strong><span id="servicePrice"></span></p>
                                        </div>
                                    </div>

                                    <!-- Available Time Slots -->
                                    <div class="card mb-3 d-none" id="timeSlotsCard">
                                        <div class="card-header bg-info text-white">
                                            <h6 class="m-0 font-weight-bold">
                                                <i class="fas fa-clock me-2"></i>Available Time Slots
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div id="timeSlots" class="d-flex flex-wrap gap-2">
                                                <!-- Time slots will be populated here -->
                                            </div>
                                        </div>
                                    </div>

                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Important Notes:</strong>
                                        <ul class="mb-0 mt-2">
                                            <li>Please arrive 15 minutes before your scheduled appointment time</li>
                                            <li>Bring your valid ID and any previous medical records</li>
                                            <li>Cancellations should be made at least 24 hours in advance</li>
                                            <li>Late arrivals may result in rescheduling</li>
                                        </ul>
                                    </div>

                                    <div class="d-grid">
                                        <button type="submit" name="book_appointment" class="btn btn-primary btn-lg" id="bookButton" disabled>
                                            <i class="fas fa-calendar-check me-2"></i>Book Appointment
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const doctorItems = document.querySelectorAll('.doctor-item');
            const selectedDoctorCard = document.getElementById('selectedDoctorCard');
            const selectedDoctorName = document.getElementById('selectedDoctorName');
            const selectedDoctorSpecialty = document.getElementById('selectedDoctorSpecialty');
            const selectedDoctorContact = document.getElementById('selectedDoctorContact');
            const displayDoctorName = document.getElementById('displayDoctorName');
            const selectedDoctorId = document.getElementById('selectedDoctorId');
            const serviceSelect = document.getElementById('serviceSelect');
            const serviceDetails = document.getElementById('serviceDetails');
            const serviceDescription = document.getElementById('serviceDescription');
            const servicePrice = document.getElementById('servicePrice');
            const apptDate = document.getElementById('apptDate');
            const timeSlotsCard = document.getElementById('timeSlotsCard');
            const timeSlots = document.getElementById('timeSlots');
            const apptTime = document.getElementById('apptTime');
            const bookButton = document.getElementById('bookButton');

            let selectedDoctor = null;
            const services = <?php echo json_encode($services); ?>;

            // Doctor selection
            doctorItems.forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();

                    // Remove active class from all items
                    doctorItems.forEach(i => i.classList.remove('active'));
                    // Add active class to selected item
                    this.classList.add('active');

                    selectedDoctor = {
                        id: this.getAttribute('data-doctor-id'),
                        name: this.getAttribute('data-doctor-name'),
                        specialty: this.querySelector('.badge')?.textContent || 'General Practitioner',
                        contact: this.querySelector('.text-muted').textContent
                    };

                    // Update selected doctor display
                    selectedDoctorName.textContent = selectedDoctor.name;
                    selectedDoctorSpecialty.textContent = selectedDoctor.specialty;
                    selectedDoctorContact.textContent = selectedDoctor.contact;
                    displayDoctorName.value = selectedDoctor.name;
                    selectedDoctorId.value = selectedDoctor.id;

                    // Show selected doctor card
                    selectedDoctorCard.classList.remove('d-none');

                    // Enable form elements
                    serviceSelect.disabled = false;
                    apptDate.disabled = false;

                    checkFormCompletion();
                });
            });

            // Service selection
            serviceSelect.addEventListener('change', function() {
                const selectedServiceId = this.value;
                const selectedService = services.find(service => service.SERV_ID == selectedServiceId);

                if (selectedService) {
                    serviceDescription.textContent = selectedService.SERV_DESCRIPTION || 'No description available.';
                    servicePrice.textContent = '₱' + parseFloat(selectedService.SERV_PRICE).toFixed(2);
                    serviceDetails.classList.remove('d-none');
                } else {
                    serviceDetails.classList.add('d-none');
                }

                checkFormCompletion();
            });

            // Date selection - generate time slots
            apptDate.addEventListener('change', function() {
                if (!selectedDoctor || !this.value) return;

                generateTimeSlots(this.value);
            });

            function generateTimeSlots(selectedDate) {
                // Clear previous time slots
                timeSlots.innerHTML = '';
                apptTime.innerHTML = '<option value="">Select Time</option>';

                // Generate time slots from 8:00 AM to 5:00 PM
                const startHour = 8;
                const endHour = 17;
                const timeSlotsArray = [];

                for (let hour = startHour; hour < endHour; hour++) {
                    for (let minute = 0; minute < 60; minute += 30) {
                        const timeString = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}:00`;
                        const displayTime = `${hour > 12 ? hour - 12 : hour}:${minute.toString().padStart(2, '0')} ${hour >= 12 ? 'PM' : 'AM'}`;

                        timeSlotsArray.push({
                            value: timeString,
                            display: displayTime
                        });
                    }
                }

                // Populate time slots
                timeSlotsArray.forEach(slot => {
                    // Add to dropdown
                    const option = document.createElement('option');
                    option.value = slot.value;
                    option.textContent = slot.display;
                    apptTime.appendChild(option);

                    // Add to quick selection buttons
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'btn btn-outline-primary btn-sm time-slot-btn';
                    button.textContent = slot.display;
                    button.dataset.time = slot.value;
                    button.addEventListener('click', function() {
                        apptTime.value = this.dataset.time;
                        checkFormCompletion();
                    });
                    timeSlots.appendChild(button);
                });

                timeSlotsCard.classList.remove('d-none');
                checkFormCompletion();
            }

            // Time selection
            apptTime.addEventListener('change', checkFormCompletion);

            function checkFormCompletion() {
                const isFormComplete = selectedDoctorId.value && serviceSelect.value && apptDate.value && apptTime.value;
                bookButton.disabled = !isFormComplete;
            }

            // Set minimum date to today
            const today = new Date().toISOString().split('T')[0];
            apptDate.min = today;
        });
    </script>
</body>
</html>
