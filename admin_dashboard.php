<?php
session_start();
require 'db.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $applicationId = $_POST['application_id'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE applications SET status = :status WHERE id = :id");
    if ($stmt->execute([':status' => $status, ':id' => $applicationId])) {
        $message = "Application status updated!";
    } else {
        $message = "An error occurred. Please try again.";
    }
}

$applications = $conn->query("SELECT * FROM applications")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        /* Main styling */
        body { font-family: Arial, sans-serif; background-color: #e9ecef; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .dashboard-container { width: 90%; max-width: 1200px; background-color: #f9f9f9; border-radius: 10px; padding: 20px; box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1); }
        h1, h3 { text-align: center; color: #333; }
        .section { margin-bottom: 20px; padding: 15px; border-radius: 5px; background-color: #ffffff; }
        .section h3 { margin-top: 0; }
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table, th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #007bff; color: white; }
        button { padding: 10px 20px; background-color: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        button:hover { background-color: #0056b3; }
        .stats { display: flex; justify-content: space-around; }
        .stat { background-color: #dfe6f0; padding: 15px; border-radius: 5px; text-align: center; width: 100%; margin: 0 10px; }
    </style>
</head>
<body>

<div class="dashboard-container">
    <!-- Header and Welcome Message -->
    <h1>Welcome, Admin</h1>
    <p style="text-align: center;">Manage applications, users, and system settings.</p>

    <!-- Overview of Applications -->
    <div class="section">
        <h3>Application Overview</h3>
        <div class="stats">
            <div class="stat">
                <p><strong>Total Applications</strong></p>
                <h2>150</h2>
            </div>
            <div class="stat">
                <p><strong>Pending Review</strong></p>
                <h2>30</h2>
            </div>
            <div class="stat">
                <p><strong>Waiting</strong></p>
                <h2>45</h2>
            </div>
            <div class="stat">
                <p><strong>Declined</strong></p>
                <h2>25</h2>
            </div>
            <div class="stat">
                <p><strong>Awarded Scholarships</strong></p>
                <h2>10</h2>
            </div>
        </div>
    </div>

    <!-- Application Management -->
    <div class="section">
        <h3>Application Management</h3>
        <div class="table-container">
            <table>
                <tr>
                    <th>Student Name</th>
                    <th>Student Number</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Gender</th>
                    <th>Date of Birth</th>
                    <th>Status</th>
                    <th>GPA</th>
                    <th>Credit Hours</th>
                    <th>Registrar Verified</th>
                    <th>Actions</th>
                </tr>
                <tr>
                    <td>Emily Davis</td>
                    <td>20231234</td>
                    <td>emily.davis@example.com</td>
                    <td>(123) 456-7890</td>
                    <td>Female</td>
                    <td>2002-08-15</td>
                    <td>Senior</td>
                    <td>3.8</td>
                    <td>18</td>
                    <td>Yes</td>
                    <td><button>View Details</button> <button>Edit Status</button></td>
                </tr>
            </table>
        </div>
    </div>

    <!-- User Management -->
    <div class="section">
        <h3>User Management</h3>
        <div class="table-container">
            <table>
                <tr>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                <tr>
                    <td>committee_john_doe</td>
                    <td>john.doe@committee.com</td>
                    <td>Committee</td>
                    <td>Active</td>
                    <td><button>Edit</button> <button>Deactivate</button></td>
                </tr>
                <tr>
                    <td>michael_jones</td>
                    <td>michael.jones@student.com</td>
                    <td>Student</td>
                    <td>Active</td>
                    <td><button>Edit</button> <button>Deactivate</button></td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Notifications & Alerts -->
    <div class="section">
        <h3>Notifications & Alerts</h3>
        <div class="notifications">
            <p><strong>High Priority:</strong> Application 1024 flagged for review with discrepancies from Registrar data.</p>
            <p><strong>New Application:</strong> Emily Davis submitted a new application.</p>
            <hr>
            <p><strong>Reminder:</strong> Michael Jones' application status needs review.</p>
        </div>
    </div>

    <!-- System Logs and Reports -->
    <div class="section">
        <h3>System Logs & Reports</h3>
        <button>View Application Logs</button>
        <button>Export Data</button>
        <button>View User Activity Logs</button>
    </div>

    <!-- Logout Button -->
    <div class="button-group">
        <a href="logout.php"><button type="button">Logout</button></a>
    </div>
</div>

</body>
</html>
