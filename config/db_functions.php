<?php
// db_functions.php

function getUserBadges($user_id) {
    global $db;
    $stmt = $db->prepare("SELECT b.name, b.description, b.image_path, ub.awarded_at 
                          FROM user_badges ub
                          JOIN badges b ON ub.badge_id = b.id
                          WHERE ub.user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getUserSessions($user_id) {
    global $db;
    $active_time = time() - 1800; // Son 30 dakika aktif olanlar
    $stmt = $db->prepare("SELECT * FROM sessions 
                          WHERE user_id = ? AND last_activity > ?");
    $stmt->execute([$user_id, $active_time]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getUserRecentActivities($user_id, $limit = 10) {
    global $db;
    $stmt = $db->prepare("SELECT * FROM user_activities 
                          WHERE user_id = ? 
                          ORDER BY created_at DESC 
                          LIMIT ?");
    $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function logActivity($user_id, $type, $data) {
    global $db;
    $stmt = $db->prepare("INSERT INTO user_activities (user_id, activity_type, activity_data) 
                          VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $type, $data]);
}

function getUserActivityCount($user_id) {
    global $db;
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM user_activities WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch()['count'];
}

function checkActivityBadges($user_id) {
    // Rozet kontrol mantığınız burada olacak
    // Örnek: 10 aktiviteye ulaşanlara rozet verme
    $activity_count = getUserActivityCount($user_id);
    
    if ($activity_count >= 10) {
        awardBadge($user_id, 1); // 1 numaralı rozeti ver
    }
}

function awardBadge($user_id, $badge_id) {
    global $db;
    // Daha önce verilmiş mi kontrol et
    $stmt = $db->prepare("SELECT * FROM user_badges 
                          WHERE user_id = ? AND badge_id = ?");
    $stmt->execute([$user_id, $badge_id]);
    
    if (!$stmt->fetch()) {
        $stmt = $db->prepare("INSERT INTO user_badges (user_id, badge_id, awarded_at) 
                              VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $badge_id, date('Y-m-d H:i:s')]);
    }
}