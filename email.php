<?php
function send_email($to, $subject, $message) {
    $headers = "From: Bright Scholarship <lmirch@umich.edu>\r\n";
    $headers .= "Reply-To: lmirch@umich.edu\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";

    if (mail($to, $subject, $message, $headers)) {
        echo "Email sent successfully to $to\n";
    } else {
        echo "Failed to send email to $to\n";
    }
}

function notify_student($user_id, $status, $remarks = '') {
    global $conn;
    $stmt = $conn->prepare("SELECT email, username FROM users WHERE id = :user_id AND role = 'student'");
    $stmt->execute([':user_id' => $user_id]);
    $user = $stmt->fetch();

    if ($user) {
        $to = $user['email'];
        $username = $user['username'];
        $subject = "Bright Scholarship Application Status Update";
        $message = "";

        switch ($status) {
            case 'submitted':
                $message = "<p>Dear $username,</p><p>Your application has been successfully submitted and is under review.</p>";
                break;
            case 'verified':
                $message = "<p>Dear $username,</p><p>Your application has passed verification and is now eligible for further review.</p>";
                break;
            case 'discrepant':
                $message = "<p>Dear $username,</p><p>Unfortunately, there was an issue with your application due to: $remarks. Your application has been marked as declined.</p>";
                break;
            case 'eligible':
                $message = "<p>Dear $username,</p><p>Your application meets eligibility criteria and will proceed to the next review stage.</p>";
                break;
            case 'ineligible':
                $message = "<p>Dear $username,</p><p>Your application does not meet the eligibility criteria due to: $remarks.</p>";
                break;
            case 'awarded':
                $message = "<p>Dear $username,</p><p>Congratulations! You have been awarded the Bright Scholarship. Details will follow soon.</p>";
                break;
            case 'declined':
                $message = "<p>Dear $username,</p><p>Your application was not selected for the scholarship this time. We encourage you to apply in the future.</p>";
                break;
            default:
                $message = "<p>Dear $username,</p><p>Your application status has been updated to: $status.</p>";
                break;
        }

        send_email($to, $subject, $message);
    }
}
?>
