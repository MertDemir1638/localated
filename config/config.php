<?php

session_start();
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Hata raporlama
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');
error_reporting(E_ALL);

// Oturum ayarları
session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();

// Temel yapılandırmalar
define('APP_ROOT', dirname(__DIR__));
define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/mert-workstation');
define('DEFAULT_TIMEZONE', 'Europe/Istanbul');
date_default_timezone_set(DEFAULT_TIMEZONE);

// Güvenlik anahtarları
define('SECRET_KEY', 'your-secret-key-here');
define('CSRF_TOKEN_LIFE', 3600); // 1 saat

// Dosya yükleme ayarları
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);

// Diğer ayarlar
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';