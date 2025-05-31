<?php
require_once 'db.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUserData($userId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

function loginUser($email, $password) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        return ['success' => true, 'user' => $user];
    }
    
    return ['success' => false, 'message' => 'Geçersiz e-posta veya şifre'];
}

function registerUser($username, $email, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    
    if ($stmt->fetchColumn() > 0) {
        return ['success' => false, 'message' => 'Kullanıcı adı veya e-posta zaten kullanılıyor'];
    }
    
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->execute([$username, $email, $hashedPassword]);
    
    $userId = $pdo->lastInsertId();
    
    if ($userId) {
        $stmt = $pdo->prepare("INSERT INTO user_profiles (user_id) VALUES (?)");
        $stmt->execute([$userId]);
        
        $_SESSION['user_id'] = $userId;
        return ['success' => true, 'user' => getUserData($userId)];
    }
    
    return ['success' => false, 'message' => 'Kayıt sırasında bir hata oluştu'];
}

function logoutUser() {
    session_unset();
    session_destroy();
    return ['success' => true];
}

function updateUserTheme($userId, $theme) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE users SET theme = ? WHERE id = ?");
    $stmt->execute([$theme, $userId]);
    return ['success' => true];
}