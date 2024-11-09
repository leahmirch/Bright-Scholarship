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
        winner BOOLEAN DEFAULT 0 
    )");

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

    // Insert Users - Admin, Committee, Students
    $conn->exec("INSERT OR IGNORE INTO users (username, email, password, role) VALUES 
        ('admin', 'admin@example.com', '" . password_hash('Password123', PASSWORD_BCRYPT) . "', 'admin'),
        ('committee_john_doe', 'john.doe@committee.com', '" . password_hash('Password123', PASSWORD_BCRYPT) . "', 'committee'),
        ('committee_jane_smith', 'jane.smith@committee.com', '" . password_hash('Password123', PASSWORD_BCRYPT) . "', 'committee'),
        ('michael_jones', 'michael.jones@student.com', '" . password_hash('Password123', PASSWORD_BCRYPT) . "', 'student'),
        ('emily_davis', 'emily.davis@student.com', '" . password_hash('Password123', PASSWORD_BCRYPT) . "', 'student')
    ");

    $conn->exec("INSERT OR IGNORE INTO application_session (status, action_date) 
                SELECT 'open', datetime('now')
                WHERE NOT EXISTS (SELECT 1 FROM application_session)");

} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
