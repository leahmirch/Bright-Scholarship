<?php
session_start();
require 'db.php';

// Ensure user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

// Connect to registrar database to verify records
$registrar_db_file = __DIR__ . '/registrar_db.sqlite';
$registrarConn = new PDO("sqlite:" . $registrar_db_file);
$registrarConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$userId = $_SESSION['user_id'];

// Retrieve the student's latest application details if it exists
$stmt = $conn->prepare("SELECT * FROM applications WHERE user_id = :user_id ORDER BY id DESC LIMIT 1");
$stmt->execute([':user_id' => $userId]);
$application = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if the student already applied (only one application allowed)
$hasApplied = $application ? true : false;

// Status Messages
$statusMessages = [
    'pending' => "Your application has been successfully submitted and is awaiting verification.",
    'verified' => "Your application has passed verification and is now eligible for further review.",
    'discrepant' => "Your application was found to have discrepancies and was declined.",
    'eligible' => "Your application meets eligibility criteria and will proceed to the next review stage.",
    'ineligible' => "Your application does not meet the eligibility criteria.",
    'awarded' => "Congratulations! You have been awarded the Bright Scholarship.",
    'declined' => "Your application was not selected for the scholarship this time."
];

// Retrieve discrepancy details if application status is discrepant
$discrepancyDetails = '';
if ($application && $application['status'] === 'discrepant') {
    $logStmt = $conn->prepare("SELECT remarks FROM application_status_log WHERE application_id = :application_id ORDER BY changed_at DESC LIMIT 1");
    $logStmt->execute([':application_id' => $application['id']]);
    $log = $logStmt->fetch(PDO::FETCH_ASSOC);
    $discrepancyDetails = $log ? $log['remarks'] : '';
}

// Handle form submission directly within this file
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$hasApplied) {
    $firstName = $_POST['first_name'];
    $lastName = $_POST['last_name'];
    $gpa = $_POST['gpa'];
    $creditHours = $_POST['credit_hours'];
    $age = $_POST['age'];

    // Insert application with initial status of "pending"
    $stmt = $conn->prepare("INSERT INTO applications (user_id, first_name, last_name, gpa, credit_hours, age, status) VALUES (:user_id, :first_name, :last_name, :gpa, :credit_hours, :age, 'pending')");
    if ($stmt->execute([':user_id' => $userId, ':first_name' => $firstName, ':last_name' => $lastName, ':gpa' => $gpa, ':credit_hours' => $creditHours, ':age' => $age])) {
        $applicationId = $conn->lastInsertId();
        $message = "Application submitted successfully!";

        // Trigger Python script to verify application
        $command = escapeshellcmd("python3 verify_application.py $applicationId");
        $output = shell_exec($command);
        echo "<pre>$output</pre>";  // Display the Python script output for debugging

        // Refresh the application data after verification
        $stmt = $conn->prepare("SELECT * FROM applications WHERE id = :application_id");
        $stmt->execute([':application_id' => $applicationId]);
        $application = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $message = "An error occurred. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <style>
        /* Main styling */
        body { font-family: Arial, sans-serif; background-color: #e9ecef; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .dashboard-container { width: 90%; max-width: 800px; background-color: #f9f9f9; border-radius: 10px; padding: 20px; box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1); }
        h1, h3 { text-align: center; color: #333; }
        .section { margin-bottom: 20px; padding: 15px; border-radius: 5px; background-color: #ffffff; }
        .section h3 { margin-top: 0; }
        .form-group { margin-bottom: 15px; }
        label { font-weight: bold; }
        input[type="text"], input[type="number"] { width: 100%; padding: 10px; margin: 5px 0; box-sizing: border-box; border-radius: 5px; border: 1px solid #ccc; }
        .button-group { display: flex; justify-content: space-between; margin-top: 20px; }
        button { padding: 10px 20px; background-color: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        button:hover { background-color: #0056b3; }
        .message { color: green; font-weight: bold; text-align: center; margin-top: 15px; }
        .status-info { background-color: #dfe6f0; padding: 15px; border-radius: 5px; color: #444; }
        .status-info p, .application-log p, .notifications p { margin: 5px 0; }
        .error { color: red; }
    </style>
</head>
<body>

<div class="dashboard-container">
    <!-- Header and Welcome Message -->
    <h1>Welcome to the Scholarship Dashboard, Emily Davis</h1>
    <p style="text-align: center;">Manage your scholarship application and view your application status here.</p>

    <!-- Application Status Display -->
    <div class="section">
        <h3>Latest Application Status</h3>
        <div class="status-info">
            <p><strong>Status:</strong> Waiting Submission</p>
            <p><strong>Discrepancies:</strong> Not Applicable</p>
        </div>
    </div>

    <!-- Application Submission Form -->
    <div class="section">
        <h3>Submit a New Application</h3>
        <form method="POST">
            <div class="form-group">
                <label for="first_name">First Name:</label>
                <input type="text" name="first_name" required>
            </div>
            <div class="form-group">
                <label for="last_name">Last Name:</label>
                <input type="text" name="last_name" required>
            </div>
            <div class="form-group">
                <label for="gpa">GPA:</label>
                <input type="number" step="0.01" name="gpa" required>
            </div>
            <div class="form-group">
                <label for="credit_hours">Credit Hours:</label>
                <input type="number" name="credit_hours" required>
            </div>
            <div class="form-group">
                <label for="age">Age:</label>
                <input type="number" name="age" required>
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
            <p><strong>Application Date:</strong> 2023-10-01</p>
            <p><strong>Status:</strong> Awarded</p>
            <p><strong>Notes:</strong> Congratulations! You have been awarded the Bright Scholarship.</p>
            <hr>
            <p><strong>Application Date:</strong> 2022-09-15</p>
            <p><strong>Status:</strong> Declined</p>
            <p><strong>Notes:</strong> Your application did not meet the eligibility criteria.</p>
        </div>
    </div>

    <!-- Notifications -->
    <div class="section">
        <h3>Notifications</h3>
        <div class="notifications">
            <p><strong>10/05/2023:</strong> Your application has been reviewed and is currently in the "Waiting" status.</p>
            <p><strong>10/01/2023:</strong> You have successfully submitted your application.</p>
            <hr>
            <p><strong>09/15/2022:</strong> Your application was declined.</p>
        </div>
    </div>

    <!-- Logout Button -->
    <div class="button-group">
        <a href="logout.php"><button type="button">Logout</button></a>
    </div>
</div>

</body>
</html>
