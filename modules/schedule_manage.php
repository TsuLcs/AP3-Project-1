<?php
$page_title = "Schedule Management";
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
        $stmt = $pdo->prepare("INSERT INTO SCHEDULE (SCHED_DAYS, SCHED_START_TIME, SCHED_END_TIME, DOC_ID) VALUES (?, ?, ?, ?)");
        try {
            $stmt->execute([
                $_POST['sched_days'],
                $_POST['sched_start_time'],
                $_POST['sched_end_time'],
                $_POST['doc_id']
            ]);
            $message = '<div class="alert alert-success">Schedule added successfully!</div>';
            $action = 'list';
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Error adding schedule: ' . $e->getMessage() . '</div>';
        }
    } elseif ($action == 'edit' && isset($_POST['id'])) {
        $stmt = $pdo->prepare("UPDATE SCHEDULE SET SCHED_DAYS = ?, SCHED_START_TIME = ?, SCHED_END_TIME = ?, DOC_ID = ? WHERE SCHED_ID = ?");
        try {
            $stmt->execute([
                $_POST['sched_days'],
                $_POST['sched_start_time'],
                $_POST['sched_end_time'],
                $_POST['doc_id'],
                $_POST['id']
            ]);
            $message = '<div class="alert alert-success">Schedule updated successfully!</div>';
            $action = 'list';
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Error updating schedule: ' . $e->getMessage() . '</div>';
        }
    }
}

// Handle delete action
if (isset($_GET['delete']) && (isset($_SESSION['user_is_superadmin']) || isset($_SESSION['staff_id']) || isset($_SESSION['doc_id']))) {
    $stmt = $pdo->prepare("DELETE FROM SCHEDULE WHERE SCHED_ID = ?");
    try {
        $stmt->execute([$_GET['delete']]);
        $message = '<div class="alert alert-success">Schedule deleted successfully!</div>';
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Error deleting schedule: ' . $e->getMessage() . '</div>';
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Schedule Management</h1>
                <?php if ($action == 'list' && (isset($_SESSION['user_is_superadmin']) || isset($_SESSION['staff_id']) || isset($_SESSION['doc_id']))): ?>
                    <a href="?action=add" class="btn btn-primary">Add New Schedule</a>
                <?php endif; ?>
            </div>

            <?php echo $message; ?>

            <?php if ($action == 'list'): ?>
                <!-- Schedule List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">All Schedules</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        // Build query based on user role
                        if (isset($_SESSION['doc_id'])) {
                            // Doctor can only see their own schedule
                            $query = "SELECT s.*, d.DOC_FIRST_NAME, d.DOC_LAST_NAME, spec.SPEC_NAME
                                     FROM SCHEDULE s
                                     JOIN DOCTOR d ON s.DOC_ID = d.DOC_ID
                                     LEFT JOIN SPECIALIZATION spec ON d.SPEC_ID = spec.SPEC_ID
                                     WHERE s.DOC_ID = ?
                                     ORDER BY s.SCHED_DAYS, s.SCHED_START_TIME";
                            $stmt = $pdo->prepare($query);
                            $stmt->execute([$_SESSION['doc_id']]);
                        } else {
                            // Staff and Super Admin can see all schedules
                            $query = "SELECT s.*, d.DOC_FIRST_NAME, d.DOC_LAST_NAME, spec.SPEC_NAME
                                     FROM SCHEDULE s
                                     JOIN DOCTOR d ON s.DOC_ID = d.DOC_ID
                                     LEFT JOIN SPECIALIZATION spec ON d.SPEC_ID = spec.SPEC_ID
                                     ORDER BY d.DOC_FIRST_NAME, s.SCHED_DAYS, s.SCHED_START_TIME";
                            $stmt = $pdo->query($query);
                        }
                        $schedules = $stmt->fetchAll();
                        ?>

                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Doctor</th>
                                        <th>Specialization</th>
                                        <th>Days</th>
                                        <th>Start Time</th>
                                        <th>End Time</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($schedules)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">No schedules found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($schedules as $schedule): ?>
                                            <tr>
                                                <td><?php echo $schedule['SCHED_ID']; ?></td>
                                                <td>Dr. <?php echo htmlspecialchars($schedule['DOC_FIRST_NAME'] . ' ' . $schedule['DOC_LAST_NAME']); ?></td>
                                                <td><?php echo htmlspecialchars($schedule['SPEC_NAME']); ?></td>
                                                <td><?php echo htmlspecialchars($schedule['SCHED_DAYS']); ?></td>
                                                <td><?php echo date('h:i A', strtotime($schedule['SCHED_START_TIME'])); ?></td>
                                                <td><?php echo date('h:i A', strtotime($schedule['SCHED_END_TIME'])); ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="?action=edit&id=<?php echo $schedule['SCHED_ID']; ?>" class="btn btn-outline-secondary">Edit</a>
                                                        <?php if (isset($_SESSION['user_is_superadmin']) || isset($_SESSION['staff_id']) ||
                                                                 (isset($_SESSION['doc_id']) && $_SESSION['doc_id'] == $schedule['DOC_ID'])): ?>
                                                            <a href="?delete=<?php echo $schedule['SCHED_ID']; ?>"
                                                               class="btn btn-outline-danger btn-delete"
                                                               onclick="return confirm('Are you sure you want to delete this schedule?')">Delete</a>
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
                $schedule_data = [];
                if ($action == 'edit' && isset($_GET['id'])) {
                    $stmt = $pdo->prepare("SELECT s.*, d.DOC_FIRST_NAME, d.DOC_LAST_NAME FROM SCHEDULE s JOIN DOCTOR d ON s.DOC_ID = d.DOC_ID WHERE s.SCHED_ID = ?");
                    $stmt->execute([$_GET['id']]);
                    $schedule_data = $stmt->fetch();
                    if (!$schedule_data) {
                        echo '<div class="alert alert-danger">Schedule not found.</div>';
                        include '../includes/tail.php';
                        exit();
                    }
                }

                // Get doctors for dropdown
                $doctors_stmt = $pdo->query("SELECT * FROM DOCTOR ORDER BY DOC_FIRST_NAME, DOC_LAST_NAME");
                $doctors = $doctors_stmt->fetchAll();
                ?>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><?php echo $action == 'add' ? 'Add New Schedule' : 'Edit Schedule'; ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <?php if ($action == 'edit'): ?>
                                <input type="hidden" name="id" value="<?php echo $schedule_data['SCHED_ID']; ?>">
                            <?php endif; ?>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="doc_id" class="form-label">Doctor <span class="text-danger">*</span></label>
                                    <select class="form-control" id="doc_id" name="doc_id" required
                                        <?php echo (isset($_SESSION['doc_id']) && !isset($_SESSION['user_is_superadmin']) && !isset($_SESSION['staff_id'])) ? 'disabled' : ''; ?>>
                                        <option value="">Select Doctor</option>
                                        <?php foreach ($doctors as $doctor): ?>
                                            <option value="<?php echo $doctor['DOC_ID']; ?>"
                                                <?php
                                                    if (isset($_SESSION['doc_id']) && !isset($_SESSION['user_is_superadmin']) && !isset($_SESSION['staff_id'])) {
                                                        echo ($_SESSION['doc_id'] == $doctor['DOC_ID']) ? 'selected' : '';
                                                    } else {
                                                        echo (isset($schedule_data['DOC_ID']) && $schedule_data['DOC_ID'] == $doctor['DOC_ID']) ? 'selected' : '';
                                                    }
                                                ?>>
                                                Dr. <?php echo htmlspecialchars($doctor['DOC_FIRST_NAME'] . ' ' . $doctor['DOC_LAST_NAME']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (isset($_SESSION['doc_id']) && !isset($_SESSION['user_is_superadmin']) && !isset($_SESSION['staff_id'])): ?>
                                        <input type="hidden" name="doc_id" value="<?php echo $_SESSION['doc_id']; ?>">
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="sched_days" class="form-label">Days <span class="text-danger">*</span></label>
                                    <select class="form-control" id="sched_days" name="sched_days" required>
                                        <option value="">Select Days</option>
                                        <option value="Monday" <?php echo (isset($schedule_data['SCHED_DAYS']) && $schedule_data['SCHED_DAYS'] == 'Monday') ? 'selected' : ''; ?>>Monday</option>
                                        <option value="Tuesday" <?php echo (isset($schedule_data['SCHED_DAYS']) && $schedule_data['SCHED_DAYS'] == 'Tuesday') ? 'selected' : ''; ?>>Tuesday</option>
                                        <option value="Wednesday" <?php echo (isset($schedule_data['SCHED_DAYS']) && $schedule_data['SCHED_DAYS'] == 'Wednesday') ? 'selected' : ''; ?>>Wednesday</option>
                                        <option value="Thursday" <?php echo (isset($schedule_data['SCHED_DAYS']) && $schedule_data['SCHED_DAYS'] == 'Thursday') ? 'selected' : ''; ?>>Thursday</option>
                                        <option value="Friday" <?php echo (isset($schedule_data['SCHED_DAYS']) && $schedule_data['SCHED_DAYS'] == 'Friday') ? 'selected' : ''; ?>>Friday</option>
                                        <option value="Saturday" <?php echo (isset($schedule_data['SCHED_DAYS']) && $schedule_data['SCHED_DAYS'] == 'Saturday') ? 'selected' : ''; ?>>Saturday</option>
                                        <option value="Sunday" <?php echo (isset($schedule_data['SCHED_DAYS']) && $schedule_data['SCHED_DAYS'] == 'Sunday') ? 'selected' : ''; ?>>Sunday</option>
                                        <option value="Monday-Friday" <?php echo (isset($schedule_data['SCHED_DAYS']) && $schedule_data['SCHED_DAYS'] == 'Monday-Friday') ? 'selected' : ''; ?>>Monday-Friday</option>
                                        <option value="Weekends" <?php echo (isset($schedule_data['SCHED_DAYS']) && $schedule_data['SCHED_DAYS'] == 'Weekends') ? 'selected' : ''; ?>>Weekends</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="sched_start_time" class="form-label">Start Time <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" id="sched_start_time" name="sched_start_time"
                                           value="<?php echo isset($schedule_data['SCHED_START_TIME']) ? substr($schedule_data['SCHED_START_TIME'], 0, 5) : ''; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="sched_end_time" class="form-label">End Time <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" id="sched_end_time" name="sched_end_time"
                                           value="<?php echo isset($schedule_data['SCHED_END_TIME']) ? substr($schedule_data['SCHED_END_TIME'], 0, 5) : ''; ?>" required>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary"><?php echo $action == 'add' ? 'Add Schedule' : 'Update Schedule'; ?></button>
                                <a href="?" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include '../includes/tail.php'; ?>
