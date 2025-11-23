<?php
$page_title = "Doctor Schedule";
include '../includes/head.php';
require_once '../data/dbconfig.php';
require_once '../includes/auth.php';

// Check access - Doctors and Staff/Super Admin
checkAccess(['doctor', 'staff', 'superadmin']);

$doctor_id = $_SESSION['doctor_id'] ?? null;

// Handle add schedule
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_schedule'])) {
    $sched_days = $_POST['sched_days'];
    $sched_start_time = $_POST['sched_start_time'];
    $sched_end_time = $_POST['sched_end_time'];
    $doc_id = $_POST['doc_id'];

    try {
        // Check for overlapping schedules
        $check_stmt = $pdo->prepare("
            SELECT COUNT(*) as count FROM schedule
            WHERE DOC_ID = ? AND SCHED_DAYS = ?
            AND (
                (SCHED_START_TIME <= ? AND SCHED_END_TIME > ?) OR
                (SCHED_START_TIME < ? AND SCHED_END_TIME >= ?) OR
                (SCHED_START_TIME >= ? AND SCHED_END_TIME <= ?)
            )
        ");
        $check_stmt->execute([$doc_id, $sched_days, $sched_start_time, $sched_start_time, $sched_end_time, $sched_end_time, $sched_start_time, $sched_end_time]);
        $overlap = $check_stmt->fetch(PDO::FETCH_ASSOC)['count'];

        if ($overlap > 0) {
            $_SESSION['error'] = "Schedule conflict: Another schedule exists for the same day and overlapping time.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO schedule (SCHED_DAYS, SCHED_START_TIME, SCHED_END_TIME, DOC_ID) VALUES (?, ?, ?, ?)");
            $stmt->execute([$sched_days, $sched_start_time, $sched_end_time, $doc_id]);
            $_SESSION['success'] = "Schedule added successfully!";
            header("Location: doctor_schedule.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error adding schedule: " . $e->getMessage();
    }
}

// Handle update schedule
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_schedule'])) {
    $sched_id = $_POST['sched_id'];
    $sched_days = $_POST['sched_days'];
    $sched_start_time = $_POST['sched_start_time'];
    $sched_end_time = $_POST['sched_end_time'];

    try {
        $stmt = $pdo->prepare("UPDATE schedule SET SCHED_DAYS = ?, SCHED_START_TIME = ?, SCHED_END_TIME = ? WHERE SCHED_ID = ?");
        $stmt->execute([$sched_days, $sched_start_time, $sched_end_time, $sched_id]);
        $_SESSION['success'] = "Schedule updated successfully!";
        header("Location: doctor_schedule.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error updating schedule: " . $e->getMessage();
    }
}

// Handle delete schedule
if (isset($_GET['delete_id'])) {
    $sched_id = $_GET['delete_id'];

    try {
        $stmt = $pdo->prepare("DELETE FROM schedule WHERE SCHED_ID = ?");
        $stmt->execute([$sched_id]);
        $_SESSION['success'] = "Schedule deleted successfully!";
        header("Location: doctor_schedule.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error deleting schedule: " . $e->getMessage();
        header("Location: doctor_schedule.php");
        exit();
    }
}

// Get schedules based on user role
if ($doctor_id) {
    // Doctor can only see their own schedules
    $schedules = $pdo->prepare("
        SELECT s.*, d.DOC_FIRST_NAME, d.DOC_LAST_NAME
        FROM schedule s
        JOIN doctor d ON s.DOC_ID = d.DOC_ID
        WHERE s.DOC_ID = ?
        ORDER BY
            FIELD(s.SCHED_DAYS, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
            s.SCHED_START_TIME
    ");
    $schedules->execute([$doctor_id]);
    $doctor_list = $pdo->prepare("SELECT * FROM doctor WHERE DOC_ID = ? ORDER BY DOC_FIRST_NAME, DOC_LAST_NAME");
    $doctor_list->execute([$doctor_id]);
} else {
    // Staff/Super Admin can see all schedules
    $schedules = $pdo->query("
        SELECT s.*, d.DOC_FIRST_NAME, d.DOC_LAST_NAME
        FROM schedule s
        JOIN doctor d ON s.DOC_ID = d.DOC_ID
        ORDER BY
            FIELD(s.SCHED_DAYS, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
            s.SCHED_START_TIME
    ");
    $doctor_list = $pdo->query("SELECT * FROM doctor ORDER BY DOC_FIRST_NAME, DOC_LAST_NAME");
}
$schedules = $schedules->fetchAll();
$doctor_list = $doctor_list->fetchAll();

// Days of the week
$days_of_week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
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
        .schedule-card {
            border-left: 4px solid #007bff;
        }
        .today-schedule {
            background-color: #e8f5e8;
            border-left-color: #28a745;
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
                        <i class="fas fa-calendar-alt me-2"></i>Doctor Schedules
                    </h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addScheduleModal">
                        <i class="fas fa-plus me-1"></i>Add Schedule
                    </button>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <!-- Weekly Schedule View -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white">
                        <h6 class="m-0 font-weight-bold">
                            <i class="fas fa-calendar-week me-2"></i>Weekly Schedule
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($days_of_week as $day):
                                $day_schedules = array_filter($schedules, function($schedule) use ($day) {
                                    return $schedule['SCHED_DAYS'] == $day;
                                });
                                $is_today = $day == date('l');
                            ?>
                                <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                                    <div class="card schedule-card <?php echo $is_today ? 'today-schedule' : ''; ?>">
                                        <div class="card-header <?php echo $is_today ? 'bg-success text-white' : 'bg-light'; ?>">
                                            <h6 class="m-0 font-weight-bold">
                                                <?php echo $day; ?>
                                                <?php if ($is_today): ?>
                                                    <span class="badge bg-light text-success">Today</span>
                                                <?php endif; ?>
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <?php if (count($day_schedules) > 0): ?>
                                                <?php foreach ($day_schedules as $schedule): ?>
                                                    <div class="mb-2 p-2 border rounded">
                                                        <small class="text-primary">
                                                            <strong>Dr. <?php echo htmlspecialchars($schedule['DOC_FIRST_NAME'] . ' ' . $schedule['DOC_LAST_NAME']); ?></strong>
                                                        </small>
                                                        <br>
                                                        <small class="text-muted">
                                                            <?php echo date('g:i A', strtotime($schedule['SCHED_START_TIME'])); ?> -
                                                            <?php echo date('g:i A', strtotime($schedule['SCHED_END_TIME'])); ?>
                                                        </small>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <p class="text-muted text-center mb-0">No schedules</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Detailed Schedule List -->
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h6 class="m-0 font-weight-bold">
                            <i class="fas fa-list me-2"></i>All Schedules
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Doctor</th>
                                        <th>Days</th>
                                        <th>Start Time</th>
                                        <th>End Time</th>
                                        <th>Duration</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($schedules as $schedule):
                                        $start_time = strtotime($schedule['SCHED_START_TIME']);
                                        $end_time = strtotime($schedule['SCHED_END_TIME']);
                                        $duration = ($end_time - $start_time) / 3600; // hours
                                    ?>
                                        <tr>
                                            <td>
                                                <strong>Dr. <?php echo htmlspecialchars($schedule['DOC_FIRST_NAME'] . ' ' . $schedule['DOC_LAST_NAME']); ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary"><?php echo $schedule['SCHED_DAYS']; ?></span>
                                            </td>
                                            <td><?php echo date('g:i A', $start_time); ?></td>
                                            <td><?php echo date('g:i A', $end_time); ?></td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $duration; ?> hours</span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary edit-schedule-btn"
                                                            data-schedule-id="<?php echo $schedule['SCHED_ID']; ?>"
                                                            data-days="<?php echo $schedule['SCHED_DAYS']; ?>"
                                                            data-start-time="<?php echo $schedule['SCHED_START_TIME']; ?>"
                                                            data-end-time="<?php echo $schedule['SCHED_END_TIME']; ?>">
                                                        <i class="fas fa-edit me-1"></i>Edit
                                                    </button>
                                                    <a href="doctor_schedule.php?delete_id=<?php echo $schedule['SCHED_ID']; ?>"
                                                       class="btn btn-sm btn-outline-danger"
                                                       onclick="return confirm('Are you sure you want to delete this schedule?')">
                                                        <i class="fas fa-trash me-1"></i>Delete
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Schedule Modal -->
    <div class="modal fade" id="addScheduleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Schedule</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?php if (!$doctor_id): ?>
                            <div class="mb-3">
                                <label class="form-label">Doctor *</label>
                                <select class="form-control" name="doc_id" required>
                                    <option value="">Select Doctor</option>
                                    <?php foreach ($doctor_list as $doctor): ?>
                                        <option value="<?php echo $doctor['DOC_ID']; ?>">
                                            Dr. <?php echo htmlspecialchars($doctor['DOC_FIRST_NAME'] . ' ' . $doctor['DOC_LAST_NAME']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php else: ?>
                            <input type="hidden" name="doc_id" value="<?php echo $doctor_id; ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label">Days *</label>
                            <select class="form-control" name="sched_days" required>
                                <option value="">Select Day</option>
                                <?php foreach ($days_of_week as $day): ?>
                                    <option value="<?php echo $day; ?>"><?php echo $day; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Start Time *</label>
                                <input type="time" class="form-control" name="sched_start_time" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">End Time *</label>
                                <input type="time" class="form-control" name="sched_end_time" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_schedule" class="btn btn-primary">Add Schedule</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Schedule Modal -->
    <div class="modal fade" id="editScheduleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Schedule</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="sched_id" id="edit_sched_id">
                        <div class="mb-3">
                            <label class="form-label">Days *</label>
                            <select class="form-control" name="sched_days" id="edit_sched_days" required>
                                <?php foreach ($days_of_week as $day): ?>
                                    <option value="<?php echo $day; ?>"><?php echo $day; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Start Time *</label>
                                <input type="time" class="form-control" name="sched_start_time" id="edit_sched_start_time" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">End Time *</label>
                                <input type="time" class="form-control" name="sched_end_time" id="edit_sched_end_time" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_schedule" class="btn btn-primary">Update Schedule</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editButtons = document.querySelectorAll('.edit-schedule-btn');
            const editModal = new bootstrap.Modal(document.getElementById('editScheduleModal'));

            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const scheduleId = this.getAttribute('data-schedule-id');
                    const days = this.getAttribute('data-days');
                    const startTime = this.getAttribute('data-start-time');
                    const endTime = this.getAttribute('data-end-time');

                    document.getElementById('edit_sched_id').value = scheduleId;
                    document.getElementById('edit_sched_days').value = days;
                    document.getElementById('edit_sched_start_time').value = startTime;
                    document.getElementById('edit_sched_end_time').value = endTime;

                    editModal.show();
                });
            });
        });
    </script>
</body>
</html>
