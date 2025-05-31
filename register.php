<?php
require_once 'config/config.php';

// Rozet verme fonksiyonu
function awardBadge($user_id, $criteria) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Kriter ile eşleşen rozeti bul
        $query = "SELECT id FROM badges WHERE criteria = :criteria";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':criteria', $criteria);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $badge_id = $row['id'];
            
            // Kullanıcıya rozeti ver
            $query = "INSERT INTO user_badges (user_id, badge_id) VALUES (:user_id, :badge_id)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':badge_id', $badge_id);
            $stmt->execute();
        }
    } catch (PDOException $e) {
        error_log("Rozet verme hatası: " . $e->getMessage());
    }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF koruması
    if (!verifyCsrfToken($_POST['csrf_token'])) {
        $error = 'Geçersiz CSRF token. Lütfen formu tekrar gönderin.';
    } else {
        $username = sanitizeInput($_POST['username']);
        $email = sanitizeInput($_POST['email']);
        $password = $_POST['password'];
        $password_confirm = $_POST['password_confirm'];
        
        // Validasyon
        if (empty($username) || empty($email) || empty($password)) {
            $error = 'Tüm alanları doldurun.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Geçersiz email formatı.';
        } elseif (strlen($password) < 8) {
            $error = 'Şifre en az 8 karakter olmalı.';
        } elseif ($password !== $password_confirm) {
            $error = 'Şifreler eşleşmiyor.';
        } else {
            try {
                $database = new Database();
                $db = $database->getConnection();
                
                // Kullanıcı adı ve email kontrolü
                $query = "SELECT id FROM users WHERE username = :username OR email = :email";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':email', $email);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $error = 'Bu kullanıcı adı veya email zaten kullanımda.';
                } else {
                    // Kullanıcıyı kaydet
                    $verification_token = bin2hex(random_bytes(32));
                    $hashed_password = hashPassword($password);
                    
                    $query = "INSERT INTO users (username, email, password_hash, verification_token, created_at, updated_at) 
                              VALUES (:username, :email, :password_hash, :verification_token, NOW(), NOW())";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':username', $username);
                    $stmt->bindParam(':email', $email);
                    $stmt->bindParam(':password_hash', $hashed_password);
                    $stmt->bindParam(':verification_token', $verification_token);
                    
                    if ($stmt->execute()) {
                        // Yeni kullanıcıya "Yeni Başlayan" rozeti ver
                        $user_id = $db->lastInsertId();
                        awardBadge($user_id, 'register');
                        
                        // Doğrulama emaili gönder (simüle ediyoruz)
                        $verification_link = BASE_URL . "/verify.php?token=$verification_token";
                        // Burada gerçek bir email gönderim fonksiyonu kullanılmalı
                        
                        $success = 'Kayıt başarılı! Lütfen emailinizi kontrol edin ve hesabınızı doğrulayın.';
                    } else {
                        $error = 'Kayıt sırasında bir hata oluştu. Lütfen tekrar deneyin.';
                    }
                }
            } catch (PDOException $e) {
                error_log("Database error: " . $e->getMessage());
                $error = 'Veritabanı hatası. Lütfen daha sonra tekrar deneyin.';
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
    <title>Kayıt Ol</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .card-header {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
        }
        .btn-primary {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            border: none;
        }
        .password-strength {
            height: 5px;
            margin-top: 5px;
            border-radius: 2px;
            transition: width 0.3s;
        }
        .strength-0 { width: 20%; background-color: #dc3545; }
        .strength-1 { width: 40%; background-color: #fd7e14; }
        .strength-2 { width: 60%; background-color: #ffc107; }
        .strength-3 { width: 80%; background-color: #28a745; }
        .strength-4 { width: 100%; background-color: #20c997; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Mert Workstation'a Kayıt Ol</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" id="registrationForm">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">Kullanıcı Adı</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                                <small class="text-muted">Sadece harf, rakam ve alt çizgi kullanabilirsiniz</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Adresi</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Şifre</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="password-strength" id="passwordStrength"></div>
                                <small class="text-muted">En az 8 karakter, bir büyük harf, bir küçük harf ve bir rakam içermelidir</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password_confirm" class="form-label">Şifre Tekrar</label>
                                <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                                <div class="text-danger mt-1" id="passwordMatchError"></div>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="terms" required>
                                <label class="form-check-label" for="terms">
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Kullanım Koşulları</a>'nı okudum ve kabul ediyorum
                                </label>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">Kayıt Ol</button>
                            </div>
                        </form>
                        
                        <div class="mt-3 text-center">
                            <p>Zaten hesabınız var mı? <a href="login.php">Giriş yapın</a></p>
                            <p>veya</p>
                            <button class="btn btn-outline-dark">
                                <i class="fab fa-google me-2"></i> Google ile kayıt ol
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Kullanım Koşulları Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="termsModalLabel">Kullanım Koşulları</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h5>1. Kullanım Şartları</h5>
                    <p>Bu platformu kullanarak aşağıdaki şartları kabul etmiş sayılırsınız...</p>
                    
                    <h5>2. Hesap Güvenliği</h5>
                    <p>Hesap bilgilerinizin güvenliğinden siz sorumlusunuz...</p>
                    
                    <h5>3. İçerik Politikası</h5>
                    <p>Platformda paylaştığınız tüm içeriklerden siz sorumlusunuz...</p>
                    
                    <h5>4. Gizlilik</h5>
                    <p>Kişisel verileriniz gizlilik politikamıza uygun şekilde korunur...</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script>
        // Şifre gücü kontrolü
        const passwordInput = document.getElementById('password');
        const passwordStrength = document.getElementById('passwordStrength');
        const passwordConfirm = document.getElementById('password_confirm');
        const passwordMatchError = document.getElementById('passwordMatchError');
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            // Uzunluk kontrolü
            if (password.length >= 8) strength++;
            
            // Büyük harf kontrolü
            if (/[A-Z]/.test(password)) strength++;
            
            // Küçük harf kontrolü
            if (/[a-z]/.test(password)) strength++;
            
            // Rakam kontrolü
            if (/[0-9]/.test(password)) strength++;
            
            // Özel karakter kontrolü
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            // Güç seviyesini güncelle
            passwordStrength.className = 'password-strength strength-' + Math.min(strength, 4);
        });
        
        // Şifre eşleşme kontrolü
        passwordConfirm.addEventListener('input', function() {
            if (passwordInput.value !== this.value) {
                passwordMatchError.textContent = 'Şifreler eşleşmiyor';
            } else {
                passwordMatchError.textContent = '';
            }
        });
        
        // Form gönderiminde ek kontrol
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            if (passwordInput.value !== passwordConfirm.value) {
                e.preventDefault();
                passwordMatchError.textContent = 'Şifreler eşleşmiyor. Lütfen kontrol edin.';
                passwordConfirm.focus();
            }
        });
    </script>
</body>
</html>