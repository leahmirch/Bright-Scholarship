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
        role TEXT CHECK(role IN ('student', 'committee', 'admin')) NOT NULL
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

    // Verification_Log table
    $conn->exec("CREATE TABLE IF NOT EXISTS verification_log (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        application_id INTEGER NOT NULL,
        verified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        gpa_verified BOOLEAN NOT NULL,
        credit_hours_verified BOOLEAN NOT NULL,
        age_verified BOOLEAN NOT NULL,
        discrepancy_found BOOLEAN NOT NULL,
        discrepancy_details TEXT,
        FOREIGN KEY(application_id) REFERENCES applications(id) ON DELETE CASCADE
    )");

    // Eligibility_Checks table
    $conn->exec("CREATE TABLE IF NOT EXISTS eligibility_checks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        application_id INTEGER NOT NULL,
        gpa_eligible BOOLEAN NOT NULL,
        credit_hours_eligible BOOLEAN NOT NULL,
        age_eligible BOOLEAN NOT NULL,
        full_time BOOLEAN NOT NULL,
        core_courses BOOLEAN NOT NULL,
        overall_eligible BOOLEAN NOT NULL,
        FOREIGN KEY(application_id) REFERENCES applications(id) ON DELETE CASCADE
    )");

    // Votes table
    $conn->exec("CREATE TABLE IF NOT EXISTS votes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        application_id INTEGER NOT NULL,
        committee_member_id INTEGER NOT NULL,
        vote_casted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        vote INTEGER CHECK(vote IN (0, 1)) NOT NULL,
        FOREIGN KEY(application_id) REFERENCES applications(id) ON DELETE CASCADE,
        FOREIGN KEY(committee_member_id) REFERENCES users(id) ON DELETE CASCADE
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

    // Award table
    $conn->exec("CREATE TABLE IF NOT EXISTS award (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        application_id INTEGER NOT NULL,
        awarded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        payment_status TEXT CHECK(payment_status IN ('pending', 'completed')) NOT NULL,
        amount REAL NOT NULL,
        FOREIGN KEY(application_id) REFERENCES applications(id) ON DELETE CASCADE
    )");

    // Insert Users - Admin, Committee, Students
    $conn->exec("INSERT OR IGNORE INTO users (username, email, password, role) VALUES 
        ('admin', 'admin@example.com', '" . password_hash('Password123', PASSWORD_BCRYPT) . "', 'admin'),
        ('committee_john_doe', 'john.doe@committee.com', '" . password_hash('Password123', PASSWORD_BCRYPT) . "', 'committee'),
        ('committee_jane_smith', 'jane.smith@committee.com', '" . password_hash('Password123', PASSWORD_BCRYPT) . "', 'committee'),
        ('michael_jones', 'michael.jones@student.com', '" . password_hash('Password123', PASSWORD_BCRYPT) . "', 'student'),
        ('emily_davis', 'emily.davis@student.com', '" . password_hash('Password123', PASSWORD_BCRYPT) . "', 'student')
    ");

} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>