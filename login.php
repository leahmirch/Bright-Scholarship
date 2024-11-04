<?php
require 'db.php';
session_start();

if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    // Redirect user to their dashboard if already logged in
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin_dashboard.php");
    } elseif ($_SESSION['role'] === 'committee') {
        header("Location: committee_dashboard.php");
    } elseif ($_SESSION['role'] === 'student') {
        header("Location: student_dashboard.php");
    }
    exit();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username = :username";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $message = "Login successful!";
        // Redirect based on role
        if ($user['role'] === 'admin') {
            header("Location: admin_dashboard.php");
        } elseif ($user['role'] === 'committee') {
            header("Location: committee_dashboard.php");
        } elseif ($user['role'] === 'student') {
            header("Location: student_dashboard.php");
        }
        exit();
    } else {
        $message = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <style>
        /* Common styles for body and form container */
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background: url('images/login_register_background.jpg') no-repeat center center fixed;
            background-size: cover;
            position: relative;
        }
        
        /* Dark overlay to make the form more readable */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5); /* Gray overlay */
            z-index: 0;
        }
        
        .form-container {
            position: relative;
            max-width: 400px;
            padding: 20px;
            border-radius: 8px;
            background-color: #fff;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            z-index: 1;
        }
        
        h1 { margin-bottom: 20px; color: #333; }
        
        .form-group { margin-bottom: 15px; text-align: left; }
        
        label { display: block; font-weight: bold; margin-bottom: 5px; }
        
        input[type="text"], input[type="password"], input[type="email"] {
            width: 100%;
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }
        
        button {
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        
        button:hover { background-color: #0056b3; }
        
        .message { font-weight: bold; color: green; margin-top: 10px; }
        
        .link { margin-top: 15px; color: #007bff; font-size: 14px; }
        
        .link a { color: #007bff; text-decoration: none; }
        
        .link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>Login</h1>
        <?php if ($message) { echo "<p class='message'>$message</p>"; } ?>
        <form method="POST">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit">Login</button>
        </form>
        <div class="link">
            <a href="register.php">Register as a Student</a>
        </div>
        <div class="link">
        <a href="index.php">Back to Home</a>
        </div>
    </div>
</body>
</html>
