<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Kullanıcı oturumunu kontrol et
$currentUser = isLoggedIn() ? getUserData($_SESSION['user_id']) : null;

// Tema ayarlarını yönet
$theme = 'system';
if ($currentUser && isset($currentUser['theme'])) {
    $theme = $currentUser['theme'];
} elseif (isset($_COOKIE['theme'])) {
    $theme = $_COOKIE['theme'];
}
?>
<!DOCTYPE html>
<html lang="tr" data-theme="<?php echo $theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akademik Çalışma Yönetim Sistemi</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/themes.css">
</head>
<body>
    <div id="app">
        <div id="loading">Yükleniyor...</div>
    </div>
    
    <script src="assets/js/app.js"></script>
</body>
</html>