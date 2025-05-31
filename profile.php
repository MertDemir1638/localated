<?php
require_once 'config/config.php';

if (!isLoggedIn()) {
    redirect('/login.php');
}

$user = getCurrentUser();
$error = '';
$success = '';

// Profil bilgilerini güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (!verifyCsrfToken($_POST['csrf_token'])) {
        $error = 'Geçersiz CSRF token. Lütfen formu tekrar gönderin.';
    } else {
        $first_name = sanitizeInput($_POST['first_name']);
        $last_name = sanitizeInput($_POST['last_name']);
        $bio = sanitizeInput($_POST['bio']);
        
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $query = "UPDATE users SET 
                      first_name = :first_name, 
                      last_name = :last_name, 
                      bio = :bio, 
                      updated_at = NOW() 
                      WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':first_name', $first_name);
            $stmt->bindParam(':last_name', $last_name);
            $stmt->bindParam(':bio', $bio);
            $stmt->bindParam(':id', $user['id']);
            $stmt->execute();
            
            // Aktiviteyi kaydet
            logActivity($user['id'], 'profile_update', 'Profile information updated');
            
            $success = 'Profil bilgileriniz başarıyla güncellendi.';
            $user = getCurrentUser(); // Yenile
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            $error = 'Profil güncelleme sırasında bir hata oluştu.';
        }
    }
}

// Avatar yükleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_avatar'])) {
    if (!verifyCsrfToken($_POST['csrf_token'])) {
        $error = 'Geçersiz CSRF token. Lütfen formu tekrar gönderin.';
    } elseif (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['avatar'];
        
        // Dosya kontrolü
        if ($file['size'] > MAX_FILE_SIZE) {
            $error = 'Dosya boyutu maksimum 5MB olabilir.';
        } elseif (!in_array($file['type'], ALLOWED_FILE_TYPES)) {
            $error = 'Sadece JPG, PNG veya GIF formatında dosya yükleyebilirsiniz.';
        } else {
            // Dosya uzantısını belirle
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'avatar_' . $user['id'] . '_' . time() . '.' . $ext;
            $upload_path = 'uploads/avatars/' . $filename;
            
            // Yükleme dizini kontrolü
            if (!is_dir('uploads/avatars')) {
                mkdir('uploads/avatars', 0755, true);
            }
            
            // Dosyayı yükle
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                try {
                    $database = new Database();
                    $db = $database->getConnection();
                    
                    // Eski avatarı sil
                    if (!empty($user['avatar_path']) && file_exists($user['avatar_path'])) {
                        unlink($user['avatar_path']);
                    }
                    
                    // Yeni avatarı kaydet
                    $query = "UPDATE users SET avatar_path = :avatar_path WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':avatar_path', $upload_path);
                    $stmt->bindParam(':id', $user['id']);
                    $stmt->execute();
                    
                    // Aktiviteyi kaydet
                    logActivity($user['id'], 'avatar_upload', 'Avatar updated');
                    
                    $success = 'Profil fotoğrafınız başarıyla güncellendi.';
                    $user = getCurrentUser(); // Yenile
                } catch (PDOException $e) {
                    error_log("Database error: " . $e->getMessage());
                    $error = 'Profil fotoğrafı güncelleme sırasında bir hata oluştu.';
                }
            } else {
                $error = 'Dosya yüklenirken bir hata oluştu.';
            }
        }
    } else {
        $error = 'Lütfen geçerli bir dosya seçin.';
    }
}

// Şifre değiştirme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (!verifyCsrfToken($_POST['csrf_token'])) {
        $error = 'Geçersiz CSRF token. Lütfen formu tekrar gönderin.';
    } else {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $new_password_confirm = $_POST['new_password_confirm'];
        
        if (empty($current_password) || empty($new_password) || empty($new_password_confirm)) {
            $error = 'Tüm alanları doldurun.';
        } elseif (strlen($new_password) < 8) {
            $error = 'Yeni şifre en az 8 karakter olmalı.';
        } elseif ($new_password !== $new_password_confirm) {
            $error = 'Yeni şifreler eşleşmiyor.';
        } else {
            try {
                $database = new Database();
                $db = $database->getConnection();
                
                // Mevcut şifreyi doğrula
                $query = "SELECT password_hash FROM users WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $user['id']);
                $stmt->execute();
                
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (verifyPassword($current_password, $result['password_hash'])) {
                    // Yeni şifreyi hashle ve kaydet
                    $hashed_password = hashPassword($new_password);
                    
                    $query = "UPDATE users SET password_hash = :password_hash WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':password_hash', $hashed_password);
                    $stmt->bindParam(':id', $user['id']);
                    $stmt->execute();
                    
                    // Aktiviteyi kaydet
                    logActivity($user['id'], 'password_change', 'Password changed');
                    
                    $success = 'Şifreniz başarıyla güncellendi.';
                } else {
                    $error = 'Mevcut şifreniz yanlış.';
                }
            } catch (PDOException $e) {
                error_log("Database error: " . $e->getMessage());
                $error = 'Şifre güncelleme sırasında bir hata oluştu.';
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
    <title>Profil Yönetimi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .avatar {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Profil Yönetimi</h1>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>Profil Fotoğrafı</h5>
                            </div>
                            <div class="card-body text-center">
                                <?php if (!empty($user['avatar_path'])): ?>
                                    <img src="<?php echo htmlspecialchars($user['avatar_path']); ?>" class="avatar mb-3" alt="Profil Fotoğrafı">
                                <?php else: ?>
                                    <div class="avatar mb-3 bg-secondary d-flex align-items-center justify-content-center text-white">
                                        <span class="h4"><?php echo substr($user['username'], 0, 1); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    
                                    <div class="mb-3">
                                        <input type="file" class="form-control" name="avatar" accept="image/*">
                                    </div>
                                    <button type="submit" name="upload_avatar" class="btn btn-primary">Fotoğrafı Güncelle</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>Profil Bilgileri</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="username" class="form-label">Kullanıcı Adı</label>
                                            <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="first_name" class="form-label">Ad</label>
                                            <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="last_name" class="form-label">Soyad</label>
                                            <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="bio" class="form-label">Hakkımda</label>
                                        <textarea class="form-control" id="bio" name="bio" rows="3"><?php echo htmlspecialchars($user['bio']); ?></textarea>
                                    </div>
                                    
                                    <button type="submit" name="update_profile" class="btn btn-primary">Bilgileri Güncelle</button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header">
                                <h5>Şifre Değiştir</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Mevcut Şifre</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">Yeni Şifre</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                                        <small class="text-muted">En az 8 karakter</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="new_password_confirm" class="form-label">Yeni Şifre Tekrar</label>
                                        <input type="password" class="form-control" id="new_password_confirm" name="new_password_confirm" required>
                                    </div>
                                    
                                    <button type="submit" name="change_password" class="btn btn-primary">Şifreyi Değiştir</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>