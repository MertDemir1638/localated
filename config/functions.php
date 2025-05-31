<?php
// Güvenli şifre oluşturma
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

// Şifre doğrulama
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// CSRF token oluşturma
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token']) || time() > $_SESSION['csrf_token_expire']) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_expire'] = time() + CSRF_TOKEN_LIFE;
    }
    return $_SESSION['csrf_token'];
}

// CSRF token doğrulama
function verifyCsrfToken($token) {
    if (!empty($_SESSION['csrf_token']) && 
        hash_equals($_SESSION['csrf_token'], $token) && 
        time() < $_SESSION['csrf_token_expire']) {
        return true;
    }
    return false;
}

// XSS koruması
function sanitizeInput($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Yönlendirme fonksiyonu
function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit();
}

// Hata mesajı gösterimi
function displayError($message) {
    $_SESSION['error_message'] = $message;
    redirect('/error.php');
}

// Başarı mesajı gösterimi
function displaySuccess($message) {
    $_SESSION['success_message'] = $message;
}

// Kullanıcı oturumunu başlatma
function startUserSession($user) {
    // Eski oturum verilerini temizle
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['logged_in'] = true;
    $_SESSION['last_activity'] = time();
    
    // IP ve tarayıcı bilgisi ile oturumu doğrula
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
}

// Oturum doğrulama
function validateSession() {
    if (!isset($_SESSION['logged_in']) || 
        $_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR'] || 
        $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
        session_destroy();
        return false;
    }
    return true;
}

// Kullanıcı giriş yapmış mı kontrolü
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && validateSession();
}

// Kullanıcı bilgilerini alma
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM users WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $_SESSION['user_id']);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
// db_functions.php (config.php'ye dahil edin veya ayrı require edin)

function getUserBadges($user_id) {
    global $db; // Veritabanı bağlantısı
    $stmt = $db->prepare("SELECT * FROM user_badges WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

function getUserSessions($user_id) {
    global $db;
    $stmt = $db->prepare("SELECT * FROM sessions WHERE user_id = ? AND last_activity > ?");
    $stmt->execute([$user_id, time() - 3600]); // Son 1 saat aktif olanlar
    return $stmt->fetchAll();
}

// Diğer fonksiyonlar için benzer şablon kullanın