<?php
require_once 'config/config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'])) {
        $error = 'Geçersiz CSRF token. Lütfen formu tekrar gönderin.';
    } else {
        $email = sanitizeInput($_POST['email']);
        
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $query = "SELECT id, email FROM users WHERE email = :email AND is_active = TRUE";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() === 1) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Şifre sıfırlama tokenı oluştur
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', time() + 3600); // 1 saat geçerli
                
                // Tokenı veritabanına kaydet
                $query = "UPDATE users SET 
                          password_reset_token = :token, 
                          password_reset_expires = :expires 
                          WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':token', $token);
                $stmt->bindParam(':expires', $expires);
                $stmt->bindParam(':id', $user['id']);
                $stmt->execute();
                
                // Şifre sıfırlama linki oluştur
                $reset_link = BASE_URL . "/reset_password.php?token=$token";
                
                // Email gönder (simüle ediyoruz)
                // Gerçek uygulamada bir email kütüphanesi kullanılmalı
                $subject = "Şifre Sıfırlama Talebi";
                $message = "Merhaba,\n\nŞifrenizi sıfırlamak için aşağıdaki linki kullanabilirsiniz:\n\n$reset_link\n\nBu link 1 saat geçerlidir.\n\nEğer bu talebi siz yapmadıysanız bu emaili dikkate almayın.";
                
                // Aktiviteyi kaydet
                logActivity($user['id'], 'password_reset_request', 'Password reset requested');
                
                $success = 'Şifre sıfırlama linki email adresinize gönderildi.';
            } else {
                $error = 'Bu email adresiyle kayıtlı aktif bir hesap bulunamadı.';
            }
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            $error = 'Sistem hatası. Lütfen daha sonra tekrar deneyin.';
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
    <title>Şifremi Unuttum</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Şifremi Unuttum</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Adresi</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Şifre Sıfırlama Linki Gönder</button>
                            </div>
                        </form>
                        
                        <div class="mt-3 text-center">
                            <a href="login.php">Giriş Yap</a> | 
                            <a href="register.php">Hesap Oluştur</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>