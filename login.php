<?php
require_once 'config/config.php';

if (isLoggedIn()) {
    redirect('/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'])) {
        $error = 'Geçersiz CSRF token. Lütfen formu tekrar gönderin.';
    } else {
        $username = sanitizeInput($_POST['username']);
        $password = $_POST['password'];
        $remember = isset($_POST['remember']) ? true : false;
        
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $query = "SELECT * FROM users WHERE username = :username OR email = :username";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            if ($stmt->rowCount() === 1) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (verifyPassword($password, $user['password_hash'])) {
                    if ($user['is_active']) {
                        if ($user['is_verified']) {
                            startUserSession($user);
                            
                            $query = "UPDATE users SET last_login = NOW() WHERE id = :id";
                            $stmt = $db->prepare($query);
                            $stmt->bindParam(':id', $user['id']);
                            $stmt->execute();
                            
                            if ($remember) {
                                $token = bin2hex(random_bytes(32));
                                $expires = time() + 30 * 24 * 60 * 60;
                                setcookie('remember_token', $token, $expires, '/', '', true, true);
                                
                                $query = "UPDATE users SET remember_token = :token, remember_token_expires = :expires WHERE id = :id";
                                $stmt = $db->prepare($query);
                                $stmt->bindParam(':token', $token);
                                $stmt->bindParam(':expires', date('Y-m-d H:i:s', $expires));
                                $stmt->bindParam(':id', $user['id']);
                                $stmt->execute();
                            }
                            
                            // Removed: logActivity($user['id'], 'login', 'User logged in');
                            // Removed: checkLoginBadges($user['id']);
                            
                            redirect('/dashboard.php');
                        } else {
                            $error = 'Hesabınızı doğrulamadınız. Lütfen emailinizi kontrol edin.';
                        }
                    } else {
                        $error = 'Hesabınız askıya alınmış. Lütfen yöneticiyle iletişime geçin.';
                    }
                } else {
                    $error = 'Geçersiz kullanıcı adı veya şifre.';
                }
            } else {
                $error = 'Geçersiz kullanıcı adı veya şifre.';
            }
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            $error = 'Sistem hatası. Lütfen daha sonra tekrar deneyin.';
        }
    }
}

$csrf_token = generateCsrfToken();
?>

<!-- HTML kodu aynı kalır -->