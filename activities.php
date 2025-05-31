<?php
require_once 'config/config.php';
require_once 'helpers.php';

// Veritabanı fonksiyonlarını içe aktar
require_once 'db_functions.php';

if (!isLoggedIn()) {
    redirect('/login.php');
}

$user = getCurrentUser();

// getUserActivities fonksiyonunu tanımla
function getUserActivities($user_id, $limit = 10) {
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

$badges = getUserBadges($user['id']);
$sessions = getUserSessions($user['id']);
$activities = getUserActivities($user['id']); // Tanımladığımız fonksiyonu kullan

// Kullanıcı aktivitesini kaydet
logActivity($user['id'], 'view_dashboard', 'User viewed dashboard');

// Rozet kontrolü
checkActivityBadges($user['id']);

?>