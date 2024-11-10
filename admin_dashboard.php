<?php
session_start();
require 'db.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['csrf_token']) && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        
        // Handle application status updates
        if (isset($_POST['update_application_status'])) {
            try {
                $applicationId = $_POST['application_id'];
                $newStatus = $_POST['status'];
                
                $conn->beginTransaction();
                
                // Update application status
                $stmt = $conn->prepare("UPDATE applications SET status = :status WHERE id = :id");
                $stmt->execute([':status' => $newStatus, ':id' => $applicationId]);
                
                // Log the status change
                $stmt = $conn->prepare("INSERT INTO application_status_log (application_id, status, remarks) VALUES (:app_id, :status, :remarks)");
                $stmt->execute([
                    ':app_id' => $applicationId,
                    ':status' => $newStatus,
                    ':remarks' => "Status updated by admin"
                ]);
                
                $conn->commit();
                $message = "Application status updated successfully!";
            } catch (Exception $e) {
                $conn->rollBack();
                $message = "Error updating application status: " . $e->getMessage();
            }
        }
        
        // Handle user management
        if (isset($_POST['update_user'])) {
            try {
                $userId = $_POST['user_id'];
                $newRole = $_POST['role'];
                
                $stmt = $conn->prepare("UPDATE users SET role = :role WHERE id = :id");
                $stmt->execute([
                    ':role' => $newRole,
                    ':id' => $userId
                ]);
                
                $message = "User updated successfully!";
            } catch (Exception $e) {
                $message = "Error updating user: " . $e->getMessage();
            }
        }

        // Handle exports
        if (isset($_POST['export_applications'])) {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="applications_export_'.date('Y-m-d').'.csv"');
            
            $output = fopen('php://output', 'w');
            fputcsv($output, ['ID', 'Student Name', 'Email', 'GPA', 'Credit Hours', 'Age', 'Status', 'Created At']);
            
            foreach ($applications as $app) {
                fputcsv($output, [
                    $app['id'],
                    $app['first_name'] . ' ' . $app['last_name'],
                    $app['email'],
                    $app['gpa'],
                    $app['credit_hours'],
                    $app['age'],
                    $app['status'],
                    $app['created_at']
                ]);
            }
            fclose($output);
            exit();
        }
        
        if (isset($_POST['export_users'])) {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="users_export_'.date('Y-m-d').'.csv"');
            
            $output = fopen('php://output', 'w');
            fputcsv($output, ['ID', 'Username', 'Email', 'Role', 'Applications Count']);
            
            foreach ($users as $user) {
                fputcsv($output, [
                    $user['id'],
                    $user['username'],
                    $user['email'],
                    $user['role'],
                    $user['application_count']
                ]);
            }
            fclose($output);
            exit();
        }

        if (isset($_POST['export_applications'])) {
            try {
                $filename = __DIR__ . '/applications_export_' . date('Y-m-d') . '.csv';
                
                $output = fopen($filename, 'w');
                if ($output === false) {
                    throw new Exception("Unable to create file");
                }
                
                // Write headers
                fputcsv($output, ['ID', 'Student Name', 'Email', 'GPA', 'Credit Hours', 'Age', 'Status', 'Created At']);
                
                // Fetch all applications with user email
                $stmt = $conn->query("
                    SELECT a.*, u.email 
                    FROM applications a 
                    LEFT JOIN users u ON a.user_id = u.id 
                    ORDER BY a.created_at DESC
                ");
                
                while ($app = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    fputcsv($output, [
                        $app['id'],
                        $app['first_name'] . ' ' . $app['last_name'],
                        $app['email'],
                        $app['gpa'],
                        $app['credit_hours'],
                        $app['age'],
                        $app['status'],
                        $app['created_at']
                    ]);
                }
                
                fclose($output);
                $message = "Applications exported successfully to " . basename($filename);
                
            } catch (Exception $e) {
                $message = "Error exporting applications: " . $e->getMessage();
            }
        }
        
        if (isset($_POST['export_users'])) {
            try {
                $filename = __DIR__ . '/users_export_' . date('Y-m-d') . '.csv';
                
                $output = fopen($filename, 'w');
                if ($output === false) {
                    throw new Exception("Unable to create file");
                }
                
                // Write headers
                fputcsv($output, ['ID', 'Username', 'Email', 'Role', 'Applications Count', 'Winner Status']);
                
                // Fetch all users with application count
                $stmt = $conn->query("
                    SELECT u.*, 
                           COUNT(DISTINCT a.id) as application_count
                    FROM users u 
                    LEFT JOIN applications a ON u.id = a.user_id 
                    GROUP BY u.id 
                    ORDER BY u.username
                ");
                
                while ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    fputcsv($output, [
                        $user['id'],
                        $user['username'],
                        $user['email'],
                        $user['role'],
                        $user['application_count'],
                        $user['winner'] ? 'Yes' : 'No'
                    ]);
                }
                
                fclose($output);
                $message = "Users exported successfully to " . basename($filename);
                
            } catch (Exception $e) {
                $message = "Error exporting users: " . $e->getMessage();
            }
        }
    }
}

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Fetch application statistics
$stats = [
    'total' => $conn->query("SELECT COUNT(*) FROM applications")->fetchColumn(),
    'pending' => $conn->query("SELECT COUNT(*) FROM applications WHERE status = 'pending'")->fetchColumn(),
    'eligible' => $conn->query("SELECT COUNT(*) FROM applications WHERE status = 'eligible'")->fetchColumn(),
    'ineligible' => $conn->query("SELECT COUNT(*) FROM applications WHERE status = 'ineligible'")->fetchColumn(),
    'awarded' => $conn->query("SELECT COUNT(*) FROM applications WHERE status = 'awarded'")->fetchColumn()
];

// Fetch all applications with user information
$applications = $conn->query("
    SELECT a.*, u.email
    FROM applications a 
    LEFT JOIN users u ON a.user_id = u.id 
    ORDER BY a.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all users
$users = $conn->query("
    SELECT u.*, COUNT(a.id) as application_count 
    FROM users u 
    LEFT JOIN applications a ON u.id = a.user_id 
    GROUP BY u.id 
    ORDER BY u.username
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch recent notifications with user information
$notifications = $conn->query("
    SELECT n.*, u.username, u.email 
    FROM notifications n
    LEFT JOIN users u ON n.user_id = u.id 
    ORDER BY n.sent_at DESC 
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch recent application status changes
$recentChanges = $conn->query("
    SELECT asl.*, a.first_name, a.last_name 
    FROM application_status_log asl 
    JOIN applications a ON asl.application_id = a.id 
    ORDER BY asl.changed_at DESC 
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #e9ecef; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .dashboard-container { width: 90%; max-width: 1200px; background-color: #f9f9f9; border-radius: 10px; padding: 20px; box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1); }
        h1, h3 { text-align: center; color: #333; }
        .section { margin-bottom: 20px; padding: 15px; border-radius: 5px; background-color: #ffffff; }
        .section h3 { margin-top: 0; }
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table, th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #007bff; color: white; }
        button { padding: 10px 20px; background-color: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; margin: 2px; }
        button:hover { background-color: #0056b3; }
        .stats { display: flex; justify-content: space-around; flex-wrap: wrap; }
        .stat { background-color: #dfe6f0; padding: 15px; border-radius: 5px; text-align: center; min-width: 150px; margin: 5px; flex: 1; }
        .message { color: green; font-weight: bold; text-align: center; margin: 10px 0; }
        .error { color: red; }
        .notifications { padding: 10px; }
        .notifications p { margin: 5px 0; padding: 8px; border-bottom: 1px solid #eee; }
        .button-group { display: flex; justify-content: center; gap: 10px; margin-top: 15px; }
        select, input[type="text"] { padding: 8px; border-radius: 4px; border: 1px solid #ddd; }
    </style>
</head>
<body>

<div class="dashboard-container">
    <h1>Welcome, Admin</h1>
    <p style="text-align: center;">Manage applications and users</p>

    <?php if ($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <!-- Overview of Applications -->
    <div class="section">
        <h3>Application Overview</h3>
        <div class="stats">
            <div class="stat">
                <p><strong>Total Applications</strong></p>
                <h2><?= $stats['total'] ?></h2>
            </div>
            <div class="stat">
                <p><strong>Pending Review</strong></p>
                <h2><?= $stats['pending'] ?></h2>
            </div>
            <div class="stat">
                <p><strong>Eligible</strong></p>
                <h2><?= $stats['eligible'] ?></h2>
            </div>
            <div class="stat">
                <p><strong>Ineligible</strong></p>
                <h2><?= $stats['ineligible'] ?></h2>
            </div>
            <div class="stat">
                <p><strong>Awarded</strong></p>
                <h2><?= $stats['awarded'] ?></h2>
            </div>
        </div>
    </div>

    <!-- Export and Report Options -->
    <div class="section">
        <h3>Reports and Data Export</h3>
        <div class="button-group">
            <form method="POST" style="display: inline;">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <button type="submit" name="export_applications">Export Applications to CSV</button>
                <button type="submit" name="export_users">Export Users to CSV</button>
            </form>
        </div>
        <?php if ($message): ?>
            <p class="export-message" style="text-align: center; margin-top: 10px;">
                <?= htmlspecialchars($message) ?>
            </p>
        <?php endif; ?>
    </div>

    <!-- Application Management -->
    <div class="section">
        <h3>Application Management</h3>
        <div class="table-container">
            <table>
                <tr>
                    <th>Student Name</th>
                    <th>Email</th>
                    <th>GPA</th>
                    <th>Credit Hours</th>
                    <th>Age</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
                <?php if (empty($applications)): ?>
                    <tr><td colspan="8">No applications found.</td></tr>
                <?php else: ?>
                    <?php foreach ($applications as $app): ?>
                        <tr>
                            <td><?= htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) ?></td>
                            <td><?= htmlspecialchars($app['email']) ?></td>
                            <td><?= htmlspecialchars($app['gpa']) ?></td>
                            <td><?= htmlspecialchars($app['credit_hours']) ?></td>
                            <td><?= htmlspecialchars($app['age']) ?></td>
                            <td><?= htmlspecialchars($app['status']) ?></td>
                            <td><?= htmlspecialchars($app['created_at']) ?></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
                                    <select name="status">
                                        <option value="pending" <?= $app['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="eligible" <?= $app['status'] == 'eligible' ? 'selected' : '' ?>>Eligible</option>
                                        <option value="ineligible" <?= $app['status'] == 'ineligible' ? 'selected' : '' ?>>Ineligible</option>
                                        <option value="awarded" <?= $app['status'] == 'awarded' ? 'selected' : '' ?>>Awarded</option>
                                    </select>
                                    <button type="submit" name="update_application_status">Update</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
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
                    <th>Applications</th>
                    <th>Actions</th>
                </tr>
                <?php if (empty($users)): ?>
                    <tr><td colspan="5">No users found.</td></tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <select name="role">
                                        <option value="student" <?= $user['role'] == 'student' ? 'selected' : '' ?>>Student</option>
                                        <option value="committee" <?= $user['role'] == 'committee' ? 'selected' : '' ?>>Committee</option>
                                        <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                                    </select>
                                    <button type="submit" name="update_user">Update Role</button>
                                </form>
                            </td>
                            <td><?= htmlspecialchars($user['application_count']) ?></td>
                            <td>
                                <button onclick="editUser(<?= $user['id'] ?>)">Edit</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <!-- Notifications -->
    <div class="section">
        <h3>Notifications</h3>
        <div class="notifications">
            <?php if (empty($notifications)): ?>
                <p>No notifications found.</p>
            <?php else: ?>
                <?php foreach ($notifications as $notification): ?>
                    <p>
                        <strong><?= htmlspecialchars($notification['notification_type']) ?>:</strong>
                        <?= htmlspecialchars($notification['message']) ?>
                        <small>(User: <?= htmlspecialchars($notification['username']) ?> | <?= htmlspecialchars($notification['sent_at']) ?>)</small>
                    </p>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Logout Button -->
    <div class="button-group">
        <a href="logout.php"><button type="button">Logout</button></a>
    </div>
</div>

<script>
function editUser(userId) {
    console.log('Editing user:', userId);
}
</script>

</body>
</html>