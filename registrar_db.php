<?php
$registrar_db_file = __DIR__ . '/registrar_db.sqlite';

try {
    $conn = new PDO("sqlite:" . $registrar_db_file);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Registrar_Records table to verify student records
    $conn->exec("CREATE TABLE IF NOT EXISTS registrar_records (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        first_name TEXT NOT NULL,
        last_name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        gpa REAL NOT NULL,
        credit_hours INTEGER NOT NULL,
        age INTEGER NOT NULL,
        status TEXT CHECK(status IN ('Freshman', 'Sophomore', 'Junior', 'Senior')) NOT NULL
    )");

    // Insert Registrar Records
    $conn->exec("INSERT OR IGNORE INTO registrar_records (first_name, last_name, email, gpa, credit_hours, age, status) VALUES 
        ('Michael', 'Jones', 'michael.jones@student.com', 3.6, 15, 20, 'Junior'),
        ('Emily', 'Davis', 'emily.davis@student.com', 3.8, 18, 22, 'Senior'),
        ('Leah', 'Mirch', 'lmirch@umich.edu', 3.62, 15, 21, 'Senior'),
        ('Anna', 'Smith', 'anna.smith@student.com', 3.85, 15, 21, 'Junior'),
        ('Jessica', 'Taylor', 'jessica.taylor@student.com', 3.85, 15, 21, 'Junior'),
        ('Laura', 'Lee', 'laura.lee@student.com', 3.1, 11, 19, 'Sophomore'),
        ('Jack', 'Black', 'jack.black@student.com', 2.9, 10, 18, 'Sophomore')
    ");

} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
