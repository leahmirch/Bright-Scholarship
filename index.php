<!DOCTYPE html>
<html>
<head>
    <title>Bright Scholarship Home</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background: url('images/index_background.jpg') no-repeat center center fixed;
            background-size: cover;
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
            z-index: 0;
        }

        .content {
            position: relative;
            max-width: 600px;
            padding: 30px;
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 8px;
            text-align: center;
            z-index: 1;
        }

        h1 { font-size: 2em; color: #333; }

        p { font-size: 1.2em; color: #555; }

        .link {
            margin-top: 20px;
            color: #007bff;
            font-size: 1em;
        }

        .link a { color: #007bff; text-decoration: none; }

        .link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="content">
        <h1>Welcome to the Bright Scholarship Program</h1>
        <p>Helping students achieve their dreams through scholarships. Explore our application process and join us in fostering academic excellence.</p>
        <div class="link">
            <a href="login.php">Login</a>    |      <a href="register.php">Register as a Student</a>
        </div>
    </div>
</body>
</html>
