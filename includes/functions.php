<?php
// Kullanıcı rozetlerini alma
function getUserBadges($user_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT b.*, ub.awarded_at 
              FROM badges b 
              JOIN user_badges ub ON b.id = ub.badge_id 
              WHERE ub.user_id = :user_id 
              ORDER BY ub.awarded_at DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Kullanıcı oturumlarını alma
function getUserSessions($user_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM user_sessions 
              WHERE user_id = :user_id AND is_active = TRUE AND expires_at > NOW() 
              ORDER BY created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Kullanıcı aktivitelerini kaydetme
function logActivity($user_id, $activity_type, $activity_data = '') {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "INSERT INTO user_activities (user_id, activity_type, activity_data) 
              VALUES (:user_id, :activity_type, :activity_data)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':activity_type', $activity_type);
    $stmt->bindParam(':activity_data', $activity_data);
    $stmt->execute();
    
    return $db->lastInsertId();
}

// Kullanıcının son aktivitelerini alma
function getUserRecentActivities($user_id, $limit = 10) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM user_activities 
              WHERE user_id = :user_id 
              ORDER BY created_at DESC 
              LIMIT :limit";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Kullanıcının toplam aktivite sayısı
function getUserActivityCount($user_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT COUNT(*) as count FROM user_activities WHERE user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'];
}

// Rozet verme fonksiyonu
function awardBadge($user_id, $criteria) {
    $database = new Database();
    $db = $database->getConnection();
    
    // Kriterlere göre rozet bul
    $query = "SELECT id FROM badges WHERE criteria = :criteria";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':criteria', $criteria);
    $stmt->execute();
    
    $badge = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($badge) {
        // Kullanıcı bu rozete zaten sahip mi kontrol et
        $query = "SELECT 1 FROM user_badges WHERE user_id = :user_id AND badge_id = :badge_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':badge_id', $badge['id']);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            // Rozeti ver
            $query = "INSERT INTO user_badges (user_id, badge_id) VALUES (:user_id, :badge_id)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':badge_id', $badge['id']);
            $stmt->execute();
            
            // Aktiviteyi kaydet
            logActivity($user_id, 'earned_badge', 'Badge ID: ' . $badge['id']);
            
            return true;
        }
    }
    
    return false;
}

// Giriş rozetlerini kontrol etme
function checkLoginBadges($user_id) {
    // Son 7 gün boyunca giriş yapmış mı kontrol et
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT COUNT(DISTINCT DATE(created_at)) as login_days 
              FROM user_activities 
              WHERE user_id = :user_id AND activity_type = 'login' 
              AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['login_days'] >= 7) {
        awardBadge($user_id, 'login_7_days');
    }
    
    // Son 30 gün boyunca giriş yapmış mı kontrol et
    $query = "SELECT COUNT(DISTINCT DATE(created_at)) as login_days 
              FROM user_activities 
              WHERE user_id = :user_id AND activity_type = 'login' 
              AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['login_days'] >= 30) {
        awardBadge($user_id, 'login_30_days');
    }
}

// Aktivite rozetlerini kontrol etme
function checkActivityBadges($user_id) {
    // Topluluk lideri rozeti (10 yorum)
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT COUNT(*) as comment_count 
              FROM user_activities 
              WHERE user_id = :user_id AND activity_type = 'comment'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['comment_count'] >= 10) {
        awardBadge($user_id, '10_comments');
    }
}

// Tarih formatlama
function formatDate($date, $show_time = false) {
    if (empty($date)) return '';
    
    $timestamp = strtotime($date);
    $format = $show_time ? 'd.m.Y H:i' : 'd.m.Y';
    return date($format, $timestamp);
}

// Aktivite tipini okunabilir hale getirme
function getActivityTypeName($type) {
    $types = [
        'login' => 'Giriş Yapıldı',
        'logout' => 'Çıkış Yapıldı',
        'view_dashboard' => 'Dashboard Görüntülendi',
        'earned_badge' => 'Rozet Kazanıldı',
        'profile_update' => 'Profil Güncellendi',
        'comment' => 'Yorum Yapıldı'
    ];
    
    return $types[$type] ?? $type;
}

// Cihaz adını belirleme
function getDeviceName($user_agent) {
    if (strpos($user_agent, 'Mobile') !== false) {
        return 'Mobil Cihaz';
    } elseif (strpos($user_agent, 'Tablet') !== false) {
        return 'Tablet';
    } elseif (strpos($user_agent, 'Windows') !== false) {
        return 'Windows Bilgisayar';
    } elseif (strpos($user_agent, 'Macintosh') !== false) {
        return 'Mac Bilgisayar';
    } elseif (strpos($user_agent, 'Linux') !== false) {
        return 'Linux Bilgisayar';
    } else {
        return 'Bilinmeyen Cihaz';
    }
}