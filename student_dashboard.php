<?php
session_start();
require 'db.php';

// Ensure user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$message = '';
$status = 'pending';
$remarks = '';

// Function to get current application session status
function getCurrentSessionStatus($conn) {
    $stmt = $conn->query("SELECT status FROM application_session ORDER BY action_date DESC LIMIT 1");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['status'] : 'closed';
}

// Get current application status
$applicationStatus = getCurrentSessionStatus($conn);

// Prevent re-submission on refresh by checking session variable
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_SESSION['application_submitted'])) {
    if ($applicationStatus === 'closed') {
        $message = "Sorry, scholarship applications are currently closed. Please check back later when applications open.";
    } else {
        // Retrieve form data
        $firstName = $_POST['first_name'];
        $lastName = $_POST['last_name'];
        $phone = $_POST['phone'];
        $email = $_POST['email'];
        $gender = $_POST['gender'];
        $dob = $_POST['dob'];
        $statusInput = $_POST['status'];
        $gpa = $_POST['gpa'];
        $creditHours = $_POST['credit_hours'];

        // Calculate age based on DOB
        $dobDate = new DateTime($dob);
        $today = new DateTime();
        $age = $today->diff($dobDate)->y;

        try {
            // Begin transaction
            $conn->beginTransaction();

            // Insert the application into the applications table
            $stmt = $conn->prepare("INSERT INTO applications (user_id, first_name, last_name, gpa, credit_hours, age, status, created_at) VALUES (:user_id, :first_name, :last_name, :gpa, :credit_hours, :age, 'pending', datetime('now'))");
            $stmt->execute([
                ':user_id' => $userId,
                ':first_name' => $firstName,
                ':last_name' => $lastName,
                ':gpa' => $gpa,
                ':credit_hours' => $creditHours,
                ':age' => $age
            ]);
            $applicationId = $conn->lastInsertId();

            // Set session flag to prevent duplicate submission
            $_SESSION['application_submitted'] = true;

            // Add notification for submission
            $conn->prepare("INSERT INTO notifications (user_id, notification_type, message) VALUES (:user_id, 'submission', 'Your application has been successfully submitted.')")
                ->execute([':user_id' => $userId]);

            // Eligibility check
            $isEligible = true;
            $eligibilityRemarks = '';

            if ($gpa < 3.2) {
                $isEligible = false;
                $eligibilityRemarks .= 'GPA below 3.2. ';
            }
            if ($creditHours < 12) {
                $isEligible = false;
                $eligibilityRemarks .= 'Less than 12 credit hours. ';
            }
            if ($age > 23) {
                $isEligible = false;
                $eligibilityRemarks .= 'Age over 23. ';
            }

            if ($isEligible) {
                // Verify with Registrar Records
                $registrarDb = new PDO('sqlite:registrar_db.sqlite');
                $registrarStmt = $registrarDb->prepare("SELECT * FROM registrar_records WHERE first_name = :first_name AND last_name = :last_name AND email = :email AND gpa = :gpa AND credit_hours = :credit_hours AND age = :age");
                $registrarStmt->execute([
                    ':first_name' => $firstName,
                    ':last_name' => $lastName,
                    ':email' => $email,
                    ':gpa' => $gpa,
                    ':credit_hours' => $creditHours,
                    ':age' => $age
                ]);

                if ($registrarStmt->fetch()) {
                    $status = 'verified';
                    $remarks = 'Application verified against registrar records.';
                } else {
                    $status = 'discrepant';
                    $remarks = 'Application data does not match registrar records.';
                }
            } else {
                $status = 'ineligible';
                $remarks = $eligibilityRemarks;
            }

            // Update application status and log
            $conn->prepare("UPDATE applications SET status = :status WHERE id = :id")
                ->execute([':status' => $status, ':id' => $applicationId]);

            $conn->prepare("INSERT INTO application_status_log (application_id, status, remarks) VALUES (:application_id, :status, :remarks)")
                ->execute([':application_id' => $applicationId, ':status' => $status, ':remarks' => $remarks]);

            // Add notification for status update
            $conn->prepare("INSERT INTO notifications (user_id, notification_type, message) VALUES (:user_id, 'status_update', :message)")
                ->execute([
                    ':user_id' => $userId,
                    ':message' => "Your application status is now: $status. $remarks"
                ]);

            // Commit transaction
            $conn->commit();
            $message = "Application submitted successfully. Status: $status";

        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollBack();
            $message = "Error submitting application: " . $e->getMessage();
        }
    }
}

// Retrieve the latest application for the user
$latestApplicationStmt = $conn->prepare("
    SELECT a.*, asl.remarks AS latest_remarks 
    FROM applications a
    LEFT JOIN (
        SELECT application_id, remarks 
        FROM application_status_log 
        WHERE (application_id, changed_at) IN (
            SELECT application_id, MAX(changed_at)
            FROM application_status_log
            GROUP BY application_id
        )
    ) asl ON a.id = asl.application_id
    WHERE a.user_id = :user_id
    ORDER BY a.id DESC
    LIMIT 1
");
$latestApplicationStmt->execute([':user_id' => $userId]);
$latestApplication = $latestApplicationStmt->fetch(PDO::FETCH_ASSOC);

// Retrieve all applications and their latest remarks for the user to display in history
$applicationsStmt = $conn->prepare("
    SELECT a.*, asl.remarks AS latest_remarks 
    FROM applications a
    LEFT JOIN (
        SELECT application_id, remarks 
        FROM application_status_log 
        WHERE (application_id, changed_at) IN (
            SELECT application_id, MAX(changed_at)
            FROM application_status_log
            GROUP BY application_id
        )
    ) asl ON a.id = asl.application_id
    WHERE a.user_id = :user_id
    ORDER BY a.id DESC
");
$applicationsStmt->execute([':user_id' => $userId]);
$applications = $applicationsStmt->fetchAll(PDO::FETCH_ASSOC);

// Retrieve all unread notifications for the user
$notificationsStmt = $conn->prepare("
    SELECT * FROM notifications 
    WHERE user_id = :user_id AND read = 0 
    ORDER BY sent_at DESC
");
$notificationsStmt->execute([':user_id' => $userId]);
$notifications = $notificationsStmt->fetchAll(PDO::FETCH_ASSOC);

// Clear application submitted flag if viewing the page (not submitting)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    unset($_SESSION['application_submitted']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #e9ecef; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .dashboard-container { width: 90%; max-width: 800px; background-color: #f9f9f9; border-radius: 10px; padding: 20px; box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1); }
        h1, h3 { text-align: center; color: #333; }
        .section { margin-bottom: 20px; padding: 15px; border-radius: 5px; background-color: #ffffff; }
        .form-group { margin-bottom: 15px; }
        label { font-weight: bold; }
        input[type="text"], input[type="number"], select { width: 100%; padding: 10px; margin: 5px 0; box-sizing: border-box; border-radius: 5px; border: 1px solid #ccc; }
        .button-group { display: flex; justify-content: center; margin-top: 20px; }
        button { padding: 10px 20px; background-color: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        .message { color: green; font-weight: bold; text-align: center; margin-top: 15px; }
    </style>
</head>
<body>

<div class="dashboard-container">
    <h1>Welcome to the Scholarship Dashboard, <?= htmlspecialchars($latestApplication['first_name'] ?? 'Student') ?></h1>
    <p style="text-align: center;">Manage your scholarship application and view your application status here.</p>

    <?php if ($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <!-- Spacer -->
    <p> </p>
    <div class="section">
        <h3>Latest Application Status</h3>
        <div class="status-info">
            <p><strong>Status:</strong> <?= htmlspecialchars($latestApplication['status'] ?? 'No application yet.') ?></p>
            <p><strong>Remarks:</strong> <?= htmlspecialchars($latestApplication['latest_remarks'] ?? 'No remarks.') ?></p>
        </div>
    </div>

    <div class="section">
        <h3>Submit a New Application</h3>
        <form method="POST">
            <div class="form-group">
                <label for="student_number">Student Number:</label>
                <input type="text" name="student_number" required>
            </div>
            <div class="form-group">
                <label for="first_name">First Name:</label>
                <input type="text" name="first_name" required>
            </div>
            <div class="form-group">
                <label for="last_name">Last Name:</label>
                <input type="text" name="last_name" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone Number:</label>
                <input type="text" name="phone" required>
            </div>
            <div class="form-group">
                <label for="email">Email Address:</label>
                <input type="text" name="email" required>
            </div>
            <div class="form-group">
                <label for="gender">Gender:</label>
                <select name="gender" required>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                </select>
            </div>
            <div class="form-group">
                <label for="dob">Date of Birth:</label>
                <input type="text" name="dob" placeholder="YYYY-MM-DD" required>
            </div>
            <div class="form-group">
                <label for="status">Status:</label>
                <select name="status" required>
                    <option value="freshman">Freshman</option>
                    <option value="sophomore">Sophomore</option>
                    <option value="junior">Junior</option>
                    <option value="senior">Senior</option>
                </select>
            </div>
            <div class="form-group">
                <label for="gpa">Cumulative GPA:</label>
                <input type="number" step="0.01" name="gpa" required>
            </div>
            <div class="form-group">
                <label for="credit_hours">Credit Hours (This Semester):</label>
                <input type="number" name="credit_hours" required>
            </div>
            <div class="button-group">
                <button type="submit">Submit Application</button>
            </div>
        </form>
    </div>

    <!-- Application History Log -->
    <div class="section">
        <h3>Application History</h3>
        <div class="application-log">
            <?php if (empty($applications)): ?>
                <p>No prior applications submitted.</p>
            <?php else: ?>
                <?php foreach ($applications as $application): ?>
                    <p><strong>Application Date:</strong> <?= htmlspecialchars($application['created_at']) ?></p>
                    <p><strong>Status:</strong> <?= htmlspecialchars($application['status']) ?></p>
                    <p><strong>Remarks:</strong> <?= htmlspecialchars($application['latest_remarks'] ?? 'No remarks.') ?></p>
                    <hr>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

     <!-- Notifications -->
     <div class="section">
        <h3>Notifications</h3>
        <div class="notifications">
            <?php if (empty($notifications)): ?>
                <p>No new notifications.</p>
            <?php else: ?>
                <?php foreach ($notifications as $notification): ?>
                    <p><strong><?= htmlspecialchars($notification['sent_at']) ?>:</strong> <?= htmlspecialchars($notification['message']) ?></p>
                    <hr>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Logout Button -->
    <div class="button-group">
        <a href="logout.php"><button type="button">Logout</button></a>
    </div>
</div>

</body>
</html>
