<?php
session_start();
require 'db.php';

// Check if user is logged in and is a committee member
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'committee') {
    header("Location: login.php");
    exit();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $applicationId = $_POST['application_id'];
    $vote = $_POST['vote'];

    $stmt = $conn->prepare("INSERT INTO votes (application_id, committee_id, vote) VALUES (:application_id, :committee_id, :vote)");
    if ($stmt->execute([':application_id' => $applicationId, ':committee_id' => $_SESSION['user_id'], ':vote' => $vote])) {
        $message = "Your vote has been recorded!";
    } else {
        $message = "An error occurred. Please try again.";
    }
}

$applications = $conn->query("SELECT * FROM applications WHERE status = 'pending'")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Committee Dashboard</title>
    <style>
        /* Main styling */
        body { font-family: Arial, sans-serif; background-color: #e9ecef; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .dashboard-container { width: 90%; max-width: 1000px; background-color: #f9f9f9; border-radius: 10px; padding: 20px; box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1); }
        h1, h3 { text-align: center; color: #333; }
        .section { margin-bottom: 20px; padding: 15px; border-radius: 5px; background-color: #ffffff; }
        .section h3 { margin-top: 0; }
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table, th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #007bff; color: white; }
        button { padding: 10px 20px; background-color: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        button:hover { background-color: #0056b3; }
        .message { color: green; font-weight: bold; text-align: center; margin-top: 15px; }
        .status-info, .notifications { background-color: #dfe6f0; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>

<div class="dashboard-container">
    <!-- Header and Welcome Message -->
    <h1>Welcome to the Committee Dashboard, John Doe</h1>
    <p style="text-align: center;">Review and vote on scholarship applications.</p>

    <!-- Pending Applications List -->
    <div class="section">
        <h3>Pending Applications</h3>
        <div class="table-container">
            <table>
                <tr>
                    <th>Student Name</th>
                    <th>GPA</th>
                    <th>Credit Hours</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                <tr>
                    <td>Emily Davis</td>
                    <td>3.8</td>
                    <td>18</td>
                    <td>Waiting</td>
                    <td><button>View Details</button></td>
                </tr>
                <tr>
                    <td>Michael Jones</td>
                    <td>3.6</td>
                    <td>15</td>
                    <td>Eligible</td>
                    <td><button>View Details</button></td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Application Voting/Review Section -->
    <div class="section">
        <h3>Application Review and Voting</h3>
        <div class="status-info">
            <p><strong>Student Name:</strong> emily_davis</p>
            <p><strong>First Name:</strong> Emily</p>
            <p><strong>Last Name:</strong> Davis</p>
            <p><strong>GPA:</strong> 3.8</p>
            <p><strong>Credit Hours:</strong> 18</p>
            <p><strong>Age:</strong> 22</p>
            <p><strong>Registrar Verification Status:</strong> Verified</p>
            <p><strong>Application Status:</strong> Waiting</p>
            <p><strong>Registrar Discrepancies:</strong> None</p>
        </div>
        <form>
            <h4>Voting Actions</h4>
            <div style="margin-bottom: 15px;">
                <label for="remarks">Committee Remarks:</label>
                <textarea id="remarks" name="remarks" rows="3" style="width: 100%; padding: 8px; border-radius: 5px; border: 1px solid #ccc;"></textarea>
            </div>
            <div class="button-group">
                <button type="submit">Approve</button>
                <button type="submit">Decline</button>
                <button type="submit">Mark for Further Review</button>
            </div>
        </form>
    </div>

    <!-- Voting History Log -->
    <div class="section">
        <h3>Voting History Log</h3>
        <div class="table-container">
            <table>
                <tr>
                    <th>Application ID</th>
                    <th>Student Name</th>
                    <th>Vote</th>
                    <th>Remarks</th>
                    <th>Timestamp</th>
                </tr>
                <tr>
                    <td>1023</td>
                    <td>Emily Davis</td>
                    <td>Approve</td>
                    <td>Meets all criteria.</td>
                    <td>2023-10-10 14:30</td>
                </tr>
                <tr>
                    <td>1022</td>
                    <td>Michael Jones</td>
                    <td>Decline</td>
                    <td>Does not meet GPA requirement.</td>
                    <td>2023-09-15 09:45</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Notifications -->
    <div class="section">
        <h3>Notifications</h3>
        <div class="notifications">
            <p><strong>10/12/2023:</strong> Application 1023 requires attention.</p>
            <p><strong>10/11/2023:</strong> Application 1022 was declined.</p>
            <hr>
            <p><strong>10/10/2023:</strong> New application from Emily Davis is awaiting review.</p>
        </div>
    </div>

    <!-- Logout Button -->
    <div class="button-group">
        <a href="logout.php"><button type="button">Logout</button></a>
    </div>
</div>

</body>
</html>
