<?php
require 'db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = 'student';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d]{10,}$/', $password)) {
        $message = "Password must be at least 10 characters, including 1 lowercase, 1 uppercase, and 1 number.";
    } else {
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $sql = "INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, :role)";
        $stmt = $conn->prepare($sql);

        if ($stmt->execute([':username' => $username, ':email' => $email, ':password' => $passwordHash, ':role' => $role])) {
            $message = "Registration successful! Please login.";
        } else {
            $message = "Registration failed. Username or email may already be taken.";
        }
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
        <h1>Register</h1>
        <?php if ($message) { echo "<p class='message'>$message</p>"; } ?>
        <form method="POST">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit">Register</button>
        </form>
        <div class="link">
            <p>Already have an account? <a href="login.php">Login here</a></p>
            <p><a href="index.php">Back to Home</a></p>
        </div>
    </div>
</body>
</html>
