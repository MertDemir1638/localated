<?php
require_once 'config/config.php';

// Belirli bir oturumu sonlandırma
if (isset($_GET['session_id'])) {
    if (isLoggedIn()) {
        $session_id = $_GET['session_id'];
        
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "UPDATE user_sessions SET is_active = FALSE WHERE id = :session_id AND user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':session_id', $session_id);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        
        // Aktiviteyi kaydet
        logActivity($_SESSION['user_id'], 'logout', 'Session terminated: ' . $session_id);
        
        // Eğer mevcut oturum sonlandırılıyorsa tamamen çıkış yap
        if ($session_id === session_id()) {
            session_destroy();
            redirect('/login.php');
        } else {
            redirect('/dashboard.php');
        }
    } else {
        redirect('/login.php');
    }
} else {
    // Tüm oturumlardan çıkış yap
    if (isLoggedIn()) {
        // Aktiviteyi kaydet
        logActivity($_SESSION['user_id'], 'logout', 'User logged out');
        
        // Tüm oturumları sonlandır
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "UPDATE user_sessions SET is_active = FALSE WHERE user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        
        // Çerezleri temizle
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
        }
        
        // Oturumu sonlandır
        session_destroy();
    }
    
    redirect('/login.php');
}