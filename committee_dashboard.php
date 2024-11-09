<?php
session_start();
require 'db.php';

// Ensure user is logged in and is a committee member
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'committee') {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Retrieve committee member's name
$userStmt = $conn->prepare("SELECT username FROM users WHERE id = :user_id");
$userStmt->execute([':user_id' => $userId]);
$userData = $userStmt->fetch(PDO::FETCH_ASSOC);
$userName = $userData['username'] ?? 'Committee Member';

$message = '';

// Function to get the current session status
function getCurrentSessionStatus($conn) {
    $stmt = $conn->query("SELECT status FROM application_session ORDER BY action_date DESC LIMIT 1");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        // If no record exists, insert the default 'open' status
        $conn->prepare("INSERT INTO application_session (status) VALUES ('open')")->execute();
        return 'open';
    }
    
    return $result['status'];
}

// Function to determine winner based on criteria
function determineWinnerBasedOnCriteria($candidates, $conn) {
    // Sort by credit hours (descending)
    usort($candidates, function($a, $b) {
        if ($a['credit_hours'] === $b['credit_hours']) {
            // If credit hours are equal, sort by age (ascending)
            return $a['age'] - $b['age'];
        }
        return $b['credit_hours'] - $a['credit_hours'];
    });
    
    return $candidates[0];
}

// Get the current session status
$sessionStatus = getCurrentSessionStatus($conn);

// Handle application session toggle (open/close) only on form submission with valid CSRF token
if ($_SERVER['REQUEST_METHOD'] == 'POST' && 
    isset($_POST['toggle_applications']) && 
    isset($_POST['csrf_token']) && 
    isset($_SESSION['csrf_token']) && 
    hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    
    // Check if the status has actually changed to prevent duplicate submissions
    $newStatus = $sessionStatus === 'open' ? 'closed' : 'open';
    
    // Begin transaction
    $conn->beginTransaction();
    
    try {
        // Insert new status
        $stmt = $conn->prepare("INSERT INTO application_session (status) VALUES (:status)");
        $stmt->execute([':status' => $newStatus]);
        
        // Update the session status
        $sessionStatus = $newStatus;
        $message = "Applications have been " . ($newStatus === 'open' ? "opened." : "closed.");
        
        // Send notification about the application status change
        $notifyStmt = $conn->prepare("INSERT INTO notifications (user_id, notification_type, message) VALUES (:user_id, 'system', :message)");
        $notifyStmt->execute([
            ':user_id' => $userId,
            ':message' => $message
        ]);
        
        // If applications are closed, determine and notify winner
        if ($newStatus === 'closed') {
            // Reset all users' winner status
            $conn->prepare("UPDATE users SET winner = 0")->execute();
            
            // Retrieve eligible applicants
            $applicantsStmt = $conn->prepare("
                SELECT * FROM applications 
                WHERE status = 'verified' 
                AND gpa >= 3.2 
                AND credit_hours >= 12 
                AND age <= 23 
                ORDER BY gpa DESC, credit_hours DESC, age ASC
            ");
            $applicantsStmt->execute();
            $eligibleApplicants = $applicantsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $winner = null;
            if (count($eligibleApplicants) === 1) {
                $winner = $eligibleApplicants[0]; // Only one eligible applicant
            } elseif (count($eligibleApplicants) > 1) {
                $topGpaApplicants = array_filter($eligibleApplicants, function ($app) use ($eligibleApplicants) {
                    return $app['gpa'] === $eligibleApplicants[0]['gpa'];
                });

                // Further refine based on criteria if multiple top GPA candidates
                if (count($topGpaApplicants) === 1) {
                    $winner = reset($topGpaApplicants);
                } else {
                    $winner = determineWinnerBasedOnCriteria($topGpaApplicants, $conn);
                }
            }

            // Notify the winner and update awarded data if a winner is found
            if ($winner) {
                // Update the winner's record in users table
                $conn->prepare("UPDATE users SET winner = 1 WHERE id = :user_id")
                    ->execute([':user_id' => $winner['user_id']]);

                // Notify the winner
                $conn->prepare("INSERT INTO notifications (user_id, notification_type, message) 
                    VALUES (:user_id, 'award', 'Congratulations! You have been awarded the scholarship.')")
                    ->execute([':user_id' => $winner['user_id']]);

                // Update application status to awarded
                $conn->prepare("UPDATE applications SET status = 'awarded' WHERE id = :app_id")
                    ->execute([':app_id' => $winner['id']]);

                // Notify other eligible applicants
                $otherApplicants = array_filter($eligibleApplicants, fn($app) => $app['id'] !== $winner['id']);
                foreach ($otherApplicants as $app) {
                    $conn->prepare("INSERT INTO notifications (user_id, notification_type, message) 
                        VALUES (:user_id, 'award', 'Thank you for your application. The scholarship has been awarded to another student.')")
                        ->execute([':user_id' => $app['user_id']]);
                    
                    // Update other applications to declined
                    $conn->prepare("UPDATE applications SET status = 'declined' WHERE id = :app_id")
                        ->execute([':app_id' => $app['id']]);
                }

                // Notify committee of winner selection
                $winnerName = $winner['first_name'] . ' ' . $winner['last_name'];
                $conn->prepare("INSERT INTO notifications (user_id, notification_type, message) 
                    VALUES (:user_id, 'system', :message)")
                    ->execute([
                        ':user_id' => $userId,
                        ':message' => "Winner has been selected: $winnerName."
                    ]);
                $message .= " Winner has been selected and notified.";
            } else {
                $message .= " No eligible applications found for awarding.";
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        // Regenerate CSRF token to prevent duplicate submissions
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        $message = "Error: Could not update application status. " . $e->getMessage();
    }
}

// Handle scheduled application status changes
if ($_SERVER['REQUEST_METHOD'] == 'POST' && 
    isset($_POST['set_schedule']) && 
    isset($_POST['schedule_time']) && 
    isset($_POST['csrf_token']) && 
    isset($_SESSION['csrf_token']) && 
    hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    
    try {
        $scheduledTime = new DateTime($_POST['schedule_time']);
        $currentTime = new DateTime();
        
        if ($scheduledTime > $currentTime) {
            $stmt = $conn->prepare("INSERT INTO application_session (status, closing_time) VALUES (:status, :closing_time)");
            $stmt->execute([
                ':status' => $sessionStatus === 'open' ? 'closed' : 'open',
                ':closing_time' => $scheduledTime->format('Y-m-d H:i:s')
            ]);
            $message = "Status change has been scheduled for " . $scheduledTime->format('Y-m-d H:i:s');
        } else {
            $message = "Error: Scheduled time must be in the future.";
        }
    } catch (Exception $e) {
        $message = "Error: Could not schedule status change. " . $e->getMessage();
    }
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Retrieve all applications
$applications = $conn->query("
    SELECT a.*, u.username 
    FROM applications a 
    JOIN users u ON a.user_id = u.id 
    ORDER BY a.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Retrieve unread notifications
$notificationsStmt = $conn->prepare("
    SELECT * FROM notifications 
    WHERE user_id = :user_id AND read = 0 
    ORDER BY sent_at DESC
");
$notificationsStmt->execute([':user_id' => $userId]);
$notifications = $notificationsStmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Committee Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #e9ecef; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .dashboard-container { width: 90%; max-width: 1000px; background-color: #f9f9f9; border-radius: 10px; padding: 20px; box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1); }
        h1, h3 { text-align: center; color: #333; }
        .centered { text-align: center; margin-top: 0; margin-bottom: 15px; }
        .section { margin-bottom: 20px; padding: 15px; border-radius: 5px; background-color: #ffffff; }
        .form-group { display: flex; align-items: center; gap: 10px; margin-bottom: 15px; }
        input[type="datetime-local"] { padding: 8px; border: 1px solid #ccc; border-radius: 5px; width: auto; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table, th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #007bff; color: white; }
        button { padding: 10px 15px; background-color: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; }
        button:hover { background-color: #0056b3; }
        .message { color: green; font-weight: bold; text-align: center; margin-top: 15px; }
    </style>
</head>
<body>

<div class="dashboard-container">
    <h1>Welcome to the Committee Dashboard, <?= htmlspecialchars($userName) ?></h1>
    <p class="centered">Manage the scholarship application session, review applications, and perform voting actions.</p>

    <?php if ($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <p> </p>

    <!-- Application Session Control -->
    <div class="section">
        <h3>Application Session Control</h3>
        <p><strong>Current Status:</strong> Applications are <?= htmlspecialchars($sessionStatus) ?></p>
        
        <form method="POST" class="form-group">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <button name="toggle_applications">
                <?= $sessionStatus === 'open' ? 'Close Applications Now' : 'Open Applications Now' ?>
            </button>
        </form>
        <form method="POST" class="form-group">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <label for="schedule_time"><?= $sessionStatus === 'open' ? 'Schedule Closing Time:' : 'Schedule Opening Time:' ?></label>
            <input type="datetime-local" name="schedule_time" required>
            <button name="set_schedule">Set Schedule</button>
        </form>
    </div>

    <!-- All Applications -->
    <div class="section">
        <h3>All Applications</h3>
        <table>
            <tr>
                <th>Student Name</th>
                <th>GPA</th>
                <th>Credit Hours</th>
                <th>Status</th>
                <th>Application Date</th>
            </tr>
            <?php if (empty($applications)): ?>
                <tr><td colspan="5">No applications at the moment.</td></tr>
            <?php else: ?>
                <?php foreach ($applications as $application): ?>
                    <tr>
                        <td><?= htmlspecialchars($application['first_name'] . ' ' . $application['last_name']) ?></td>
                        <td><?= htmlspecialchars($application['gpa']) ?></td>
                        <td><?= htmlspecialchars($application['credit_hours']) ?></td>
                        <td><?= htmlspecialchars($application['status']) ?></td>
                        <td><?= htmlspecialchars($application['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </table>
    </div>

    <!-- Voting on Finalists -->
    <div class="section">
        <h3>Voting on Finalists</h3>
        <?php if (!empty($finalists)): ?>
            <p>Select one of the finalists to vote on:</p>
            <?php foreach ($finalists as $finalist): ?>
                <p><strong>Candidate:</strong> <?= htmlspecialchars($finalist['first_name'] . ' ' . $finalist['last_name']) ?> (ID: <?= htmlspecialchars($finalist['id']) ?>)</p>
                <form method="POST">
                    <input type="hidden" name="application_id" value="<?= $finalist['id'] ?>">
                    <button name="vote" value="approve">Approve</button>
                    <button name="vote" value="decline">Decline</button>
                </form>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No voting to conduct at the moment.</p>
        <?php endif; ?>
    </div>

    <!-- Notifications -->
    <div class="section">
        <h3>Notifications</h3>
        <div>
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
