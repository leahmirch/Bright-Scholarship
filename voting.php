<?php
require 'db.php';

// Determine winner based on committee votes
function determineWinnerFromVotes($conn) {
    $votesStmt = $conn->prepare("
        SELECT candidate_id, COUNT(committee_member_id) AS vote_count
        FROM committee_votes
        WHERE voting_round = (SELECT MAX(voting_round) FROM committee_votes)
        GROUP BY candidate_id
        ORDER BY vote_count DESC, candidate_id ASC
        LIMIT 1
    ");
    $votesStmt->execute();
    return $votesStmt->fetch(PDO::FETCH_ASSOC);
}

// Function to retrieve top candidates based on cumulative GPA
function getTopCandidates($conn) {
    $stmt = $conn->prepare("
        SELECT a.*, u.gender, u.class_year, sr.current_semester_gpa
        FROM applications a
        JOIN users u ON a.user_id = u.id
        LEFT JOIN student_records sr ON a.user_id = sr.user_id
        WHERE a.status = 'eligible'
        ORDER BY a.gpa DESC
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Ensure `determineFinalists` function returns unique candidates only
function determineFinalists($candidates) {
    $topGpa = $candidates[0]['gpa'];
    $topCandidates = array_unique(array_filter($candidates, fn($c) => $c['gpa'] == $topGpa), SORT_REGULAR);

    if (count($topCandidates) == 1) {
        return [$topCandidates[0]];
    }

    $maxSemesterGpa = max(array_column($topCandidates, 'current_semester_gpa'));
    $semesterTopCandidates = array_unique(array_filter($topCandidates, fn($c) => $c['current_semester_gpa'] == $maxSemesterGpa), SORT_REGULAR);

    if (count($semesterTopCandidates) == 1) {
        return [$semesterTopCandidates[0]];
    }

    $juniors = array_unique(array_filter($semesterTopCandidates, fn($c) => strtolower($c['class_year']) == 'junior'), SORT_REGULAR);
    if (count($juniors) >= 1) {
        return $juniors;
    }

    $females = array_unique(array_filter($semesterTopCandidates, fn($c) => strtolower($c['gender']) == 'female'), SORT_REGULAR);
    if (count($females) >= 1) {
        return $females;
    }

    usort($semesterTopCandidates, fn($a, $b) => $a['age'] - $b['age']);
    return array_slice($semesterTopCandidates, 0, 2);
}

// Function to initiate voting for finalists
function initiateVoting($conn, $finalists) {
    // Clear only 'active' records before initiating new voting round
    $conn->exec("DELETE FROM voting_candidates WHERE voting_status = 'active'");

    foreach ($finalists as $finalist) {
        // Use INSERT OR IGNORE to prevent duplicates in case of re-run
        $conn->prepare("
            INSERT OR IGNORE INTO voting_candidates (user_id, application_id, voting_status) 
            VALUES (:user_id, :application_id, 'active')
        ")->execute([
            ':user_id' => $finalist['user_id'], 
            ':application_id' => $finalist['id']
        ]);
    }
}
