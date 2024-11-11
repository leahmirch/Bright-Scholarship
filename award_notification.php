<?php
require_once 'db.php';

function notifyRegistrarForTuitionBalance($winnerId, $committeeMemberId, $conn) {
    // Notify registrar office about tuition balance request
    $conn->prepare("INSERT INTO notifications (user_id, notification_type, message, sent_at) 
        VALUES (:user_id, 'registrar', 'Request to Registrars Office: Please provide the tuition balance for awarded student (User ID: $winnerId) for reimbursement.', datetime('now', '+1 second'))")
        ->execute([':user_id' => $committeeMemberId]);

    // Simulate response from registrar with tuition balance
    $tuitionBill = $conn->query("SELECT tuition_bill FROM users WHERE id = $winnerId")->fetchColumn();
    $conn->prepare("INSERT INTO notifications (user_id, notification_type, message, sent_at) 
        VALUES (:user_id, 'system', 'Response from Registrars Office: Tuition balance for student (User ID: $winnerId) is $$tuitionBill.', datetime('now', '+2 seconds'))")
        ->execute([':user_id' => $committeeMemberId]);
}

function requestReimbursementFromAccounting($winnerId, $committeeMemberId, $conn) {
    // Fetch tuition bill for the winner
    $tuitionBill = $conn->query("SELECT tuition_bill FROM users WHERE id = $winnerId")->fetchColumn();

    // Notify accounting to reimburse the awarded student
    $conn->prepare("INSERT INTO notifications (user_id, notification_type, message, sent_at) 
        VALUES (:user_id, 'accounting', 'Request to Accounting Department: Please reimburse awarded student (User ID: $winnerId) with tuition amount of $$tuitionBill.', datetime('now', '+3 seconds'))")
        ->execute([':user_id' => $committeeMemberId]);

    // Simulate response from accounting to the student
    $conn->prepare("INSERT INTO notifications (user_id, notification_type, message, sent_at) 
        VALUES (:user_id, 'award', 'Notification from Accounting: You have been reimbursed with the amount of $$tuitionBill for your tuition.', datetime('now', '+4 seconds'))")
        ->execute([':user_id' => $winnerId]);

    // Send same notification to the committee member
    $conn->prepare("INSERT INTO notifications (user_id, notification_type, message, sent_at) 
        VALUES (:user_id, 'system', 'Notification from Accounting: Student (User ID: $winnerId) has been reimbursed with the amount of $$tuitionBill.', datetime('now', '+4 seconds'))")
        ->execute([':user_id' => $committeeMemberId]);
}

function notifyNonWinners($nonWinners, $conn) {
    // Notify non-winners about the selection outcome only once
    foreach ($nonWinners as $applicant) {
        $conn->prepare("INSERT INTO notifications (user_id, notification_type, message, sent_at) 
            VALUES (:user_id, 'award', 'Thank you for your application. Unfortunately, the scholarship has been awarded to another student.', datetime('now', '+0 second'))")
            ->execute([':user_id' => $applicant['user_id']]);
    }
}

function processAwardNotifications($committeeMemberId, $conn) {
    // Fetch the awarded student
    $winnerStmt = $conn->prepare("SELECT * FROM applications WHERE status = 'awarded' LIMIT 1");
    $winnerStmt->execute();
    $winner = $winnerStmt->fetch(PDO::FETCH_ASSOC);

    if ($winner) {
        $winnerId = $winner['user_id'];

        // Notify registrar for tuition balance and simulate response
        notifyRegistrarForTuitionBalance($winnerId, $committeeMemberId, $conn);

        // Notify accounting to reimburse the student and simulate response
        requestReimbursementFromAccounting($winnerId, $committeeMemberId, $conn);

        // Notify other eligible applicants who were not selected once
        $nonWinnersStmt = $conn->prepare("SELECT * FROM applications WHERE status = 'eligible' AND id != :id");
        $nonWinnersStmt->execute([':id' => $winner['id']]);
        $nonWinners = $nonWinnersStmt->fetchAll(PDO::FETCH_ASSOC);
        notifyNonWinners($nonWinners, $conn);

        return "Winner and departments have been notified. Non-winners have also been notified.";
    } else {
        return "No awarded applications found to notify.";
    }
}

// Handle award notifications if award button is clicked
if (isset($_POST['award_scholarship'])) {
    $conn->beginTransaction();
    try {
        $awardMessage = processAwardNotifications($_SESSION['user_id'], $conn);
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollBack();
        $awardMessage = "Error processing award notifications: " . $e->getMessage();
    }
}
