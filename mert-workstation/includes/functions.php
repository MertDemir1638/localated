<?php
function getStudySessions($userId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM study_sessions WHERE user_id = ? ORDER BY start_time DESC");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function startStudySession($userId, $category, $notes) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO study_sessions (user_id, start_time, category, notes) VALUES (?, NOW(), ?, ?)");
    $stmt->execute([$userId, $category, $notes]);
    return $stmt->rowCount() > 0;
}

function getHealthData($userId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM health_data WHERE user_id = ? ORDER BY date DESC");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function getUserBadges($userId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT b.name, b.description, b.image, ub.earned_at 
        FROM user_badges ub
        JOIN badges b ON ub.badge_id = b.id
        WHERE ub.user_id = ?
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function getUserStats($userId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            SUM(duration) AS total_study_time,
            AVG(duration) AS daily_average
        FROM study_sessions 
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $studyStats = $stmt->fetch();
    
    $stmt = $pdo->prepare("
        SELECT SUM(steps) AS weekly_steps 
        FROM health_data 
        WHERE user_id = ? 
        AND date BETWEEN DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND CURDATE()
    ");
    $stmt->execute([$userId]);
    $healthStats = $stmt->fetch();
    
    return [
        'total_study_time' => $studyStats['total_study_time'] ?? 0,
        'daily_average' => $studyStats['daily_average'] ?? 0,
        'weekly_steps' => $healthStats['weekly_steps'] ?? 0
    ];
}