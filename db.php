<?php
$db_file = __DIR__ . '/bright_scholarship_db.sqlite';

try {
    $conn = new PDO("sqlite:" . $db_file);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Users table
    $conn->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            email TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            role TEXT CHECK(role IN ('student', 'committee', 'admin')) NOT NULL,
            winner BOOLEAN DEFAULT 0,
            gender TEXT CHECK(gender IN ('male', 'female', 'other')),
            class_year TEXT CHECK(class_year IN ('freshman', 'sophomore', 'junior', 'senior'))
        )
    ");

    // Student Records table
    $conn->exec("CREATE TABLE IF NOT EXISTS student_records (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            current_semester_gpa REAL NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    // Applications table
    $conn->exec("CREATE TABLE IF NOT EXISTS applications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        first_name TEXT NOT NULL,
        last_name TEXT NOT NULL,
        gpa REAL NOT NULL,
        credit_hours INTEGER NOT NULL,
        age INTEGER NOT NULL,
        status TEXT CHECK(status IN ('pending', 'verified', 'discrepant', 'eligible', 'ineligible', 'awarded', 'declined')) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // Application_Status_Log table
    $conn->exec("CREATE TABLE IF NOT EXISTS application_status_log (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        application_id INTEGER NOT NULL,
        status TEXT NOT NULL,
        changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        remarks TEXT,
        FOREIGN KEY(application_id) REFERENCES applications(id) ON DELETE CASCADE
    )");

        // Application_Session table
        $conn->exec("CREATE TABLE IF NOT EXISTS application_session (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            status TEXT CHECK(status IN ('open', 'closed')) NOT NULL,
            action_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            closing_time TIMESTAMP
        )");

    // Notifications table
    $conn->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        notification_type TEXT NOT NULL,
        message TEXT NOT NULL,
        sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        read BOOLEAN DEFAULT 0,
        FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // voting_candidates table
    $conn->exec("CREATE TABLE IF NOT EXISTS voting_candidates (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        application_id INTEGER NOT NULL,
        voting_status TEXT CHECK(voting_status IN ('active', 'completed', 'cancelled')) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY(application_id) REFERENCES applications(id) ON DELETE CASCADE
    )");

    






    // Insert sample users
    $conn->exec("INSERT OR IGNORE INTO users (username, email, password, role, gender, class_year) VALUES 
        ('admin', 'admin@example.com', '" . password_hash('Password123', PASSWORD_BCRYPT) . "', 'admin', NULL, NULL),
        ('committee_john_doe', 'john.doe@committee.com', '" . password_hash('Password123', PASSWORD_BCRYPT) . "', 'committee', NULL, NULL),
        ('committee_jane_smith', 'jane.smith@committee.com', '" . password_hash('Password123', PASSWORD_BCRYPT) . "', 'committee', NULL, NULL),
        ('michael_jones', 'michael.jones@student.com', '" . password_hash('Password123', PASSWORD_BCRYPT) . "', 'student', 'male', 'junior'),
        ('emily_davis', 'emily.davis@student.com', '" . password_hash('Password123', PASSWORD_BCRYPT) . "', 'student', 'female', 'senior'),
        ('anna_smith', 'anna.smith@student.com', '" . password_hash('Password123', PASSWORD_BCRYPT) . "', 'student', 'female', 'junior'),
        ('jessica_taylor', 'jessica.taylor@student.com', '" . password_hash('Password123', PASSWORD_BCRYPT) . "', 'student', 'female', 'junior'),
        ('jack_black', 'jack.black@student.com', '" . password_hash('Password123', PASSWORD_BCRYPT) . "', 'student', 'male', 'freshman'),
        ('laura_lee', 'laura.lee@student.com', '" . password_hash('Password123', PASSWORD_BCRYPT) . "', 'student', 'female', 'sophomore')
    ");

    // Insert student records
    $conn->exec("INSERT OR IGNORE INTO student_records (user_id, current_semester_gpa) VALUES 
        ((SELECT id FROM users WHERE username = 'michael_jones'), 3.6),
        ((SELECT id FROM users WHERE username = 'emily_davis'), 3.8),
        ((SELECT id FROM users WHERE username = 'anna_smith'), 3.85),
        ((SELECT id FROM users WHERE username = 'jessica_taylor'), 3.85),
        ((SELECT id FROM users WHERE username = 'jack_black'), 2.9),
        ((SELECT id FROM users WHERE username = 'laura_lee'), 3.1)
    ");

    // Insert applications
    $applications = [
        ['username' => 'michael_jones', 'first_name' => 'Michael', 'last_name' => 'Jones', 'gpa' => 3.6, 'credit_hours' => 15, 'age' => 20, 'status' => 'eligible'],
        ['username' => 'emily_davis', 'first_name' => 'Emily', 'last_name' => 'Davis', 'gpa' => 3.8, 'credit_hours' => 18, 'age' => 22, 'status' => 'eligible'],
        ['username' => 'anna_smith', 'first_name' => 'Anna', 'last_name' => 'Smith', 'gpa' => 3.85, 'credit_hours' => 15, 'age' => 21, 'status' => 'eligible'],
        ['username' => 'jessica_taylor', 'first_name' => 'Jessica', 'last_name' => 'Taylor', 'gpa' => 3.85, 'credit_hours' => 15, 'age' => 21, 'status' => 'eligible'],
        ['username' => 'jack_black', 'first_name' => 'Jack', 'last_name' => 'Black', 'gpa' => 2.9, 'credit_hours' => 10, 'age' => 18, 'status' => 'ineligible'],
        ['username' => 'laura_lee', 'first_name' => 'Laura', 'last_name' => 'Lee', 'gpa' => 3.1, 'credit_hours' => 11, 'age' => 19, 'status' => 'ineligible']
    ];

    foreach ($applications as $app) {
        $user_id = $conn->query("SELECT id FROM users WHERE username = '{$app['username']}'")->fetchColumn();
        if ($user_id) {
            $app_id = $conn->query("SELECT id FROM applications WHERE user_id = $user_id")->fetchColumn();
            if (!$app_id) {
                $conn->exec("INSERT INTO applications (user_id, first_name, last_name, gpa, credit_hours, age, status) VALUES 
                    ($user_id, '{$app['first_name']}', '{$app['last_name']}', {$app['gpa']}, {$app['credit_hours']}, {$app['age']}, '{$app['status']}')
                ");
                $app_id = $conn->lastInsertId();

                // Add application status log entries
                $conn->exec("INSERT INTO application_status_log (application_id, status, remarks) VALUES 
                    ($app_id, '{$app['status']}', 'Initial application status.')
                ");
                
                // Add notifications for each status change
                $conn->exec("INSERT INTO notifications (user_id, notification_type, message) VALUES 
                    ($user_id, 'submission', 'Your application has been submitted and is currently {$app['status']}.')
                ");
            }
        }
    }

    // Ensure the application session is open
    $conn->exec("INSERT OR IGNORE INTO application_session (status, action_date) 
                SELECT 'open', datetime('now')
                WHERE NOT EXISTS (SELECT 1 FROM application_session)");

} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
