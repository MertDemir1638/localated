<?php
require_once 'config/config.php';

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

// Token doğrulama
if (!empty($token)) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT id FROM users WHERE password_reset_token = :token AND password_reset_expires > NOW()";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        
        if ($stmt->rowCount() !== 1) {
            $error = 'Geçersiz veya süresi dolmuş şifre sıfırlama linki.';
            $token = '';
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        $error = 'Sistem hatası. Lütfen daha sonra tekrar deneyin.';
        $token = '';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($token)) {
    if (!verifyCsrfToken($_POST['csrf_token'])) {
        $error = 'Geçersiz CSRF token. Lütfen formu tekrar gönderin.';
    } else {
        $password = $_POST['password'];
        $password_confirm = $_POST['password_confirm'];
        
        if (empty($password) || empty($password_confirm)) {
            $error = 'Tüm alanları doldurun.';
        } elseif (strlen($password) < 8) {
            $error = 'Şifre en az 8 karakter olmalı.';
        } elseif ($password !== $password_confirm) {
            $error = 'Şifreler eşleşmiyor.';
        } else {
            try {
                // Kullanıcıyı bul
                $query = "SELECT id FROM users WHERE password_reset_token = :token AND password_reset_expires > NOW()";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':token', $token);
                $stmt->execute();
                
                if ($stmt->rowCount() === 1) {
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    $hashed_password = hashPassword($password);
                    
                    // Şifreyi güncelle ve tokenı temizle
                    $query = "UPDATE users SET 
                              password_hash = :password_hash, 
                              password_reset_token = NULL, 
                              password_reset_expires = NULL 
                              WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':password_hash', $hashed_password);
                    $stmt->bindParam(':id', $user['id']);
                    $stmt->execute();
                    
                    // Aktiviteyi kaydet
                    logActivity($user['id'], 'password_reset', 'Password reset successful');
                    
                    $success = 'Şifreniz başarıyla güncellendi. Artık yeni şifrenizle giriş yapabilirsiniz.';
                    $token = '';
                } else {
                    $error = 'Geçersiz veya süresi dolmuş şifre sıfırlama linki.';
                    $token = '';
                }
            } catch (PDOException $e) {
                error_log("Database error: " . $e->getMessage());
                $error = 'Sistem hatası. Lütfen daha sonra tekrar deneyin.';
            }
        }
    }
}

$csrf_token = generateCsrfToken();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Şifre Sıfırlama</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Şifre Sıfırlama</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                            <div class="text-center mt-3">
                                <a href="login.php" class="btn btn-primary">Giriş Yap</a>
                            </div>
                        <?php elseif (!empty($token)): ?>
                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                
                                <div class="mb-3">
                                    <label for="password" class="form-label">Yeni Şifre</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <small class="text-muted">En az 8 karakter</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="password_confirm" class="form-label">Yeni Şifre Tekrar</label>
                                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">Şifremi Sıfırla</button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                Şifre sıfırlama işlemi başlatılmamış veya linkin süresi dolmuş.
                            </div>
                            <div class="text-center mt-3">
                                <a href="forgot_password.php" class="btn btn-primary">Yeni Şifre Sıfırlama Talebi Gönder</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>