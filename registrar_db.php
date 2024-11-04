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
        age INTEGER NOT NULL
    )");

    // Insert Registrar Records
    $conn->exec("INSERT OR IGNORE INTO registrar_records (first_name, last_name, email, gpa, credit_hours, age) VALUES 
        ('Michael', 'Jones', 'michael.jones@student.com', 3.6, 15, 20),
        ('Emily', 'Davis', 'emily.davis@student.com', 3.8, 18, 22),
        ('Leah', 'Mirch', 'lmirch@umich.edu', 3.62, 15, 21)
    ");

} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
