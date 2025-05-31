<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Oturum açılmamış']);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

if ($action === 'get') {
    $healthData = getHealthData($userId);
    echo json_encode($healthData);
} 
else if ($action === 'add') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // DÜZELTME: Buradaki parantez hatası düzeltildi
    if (!isset($data['steps'])) { // Parantez kapatma eklendi
        echo json_encode(['success' => false, 'message' => 'Adım verisi eksik']);
        exit;
    }
    
    global $pdo;
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("INSERT INTO health_data (user_id, date, steps, blood_sugar) 
                           VALUES (?, ?, ?, ?)
                           ON DUPLICATE KEY UPDATE steps = ?, blood_sugar = ?");
    $success = $stmt->execute([
        $userId,
        $today,
        $data['steps'],
        $data['blood_sugar'] ?? null,
        $data['steps'],
        $data['blood_sugar'] ?? null
    ]);
    
    echo json_encode(['success' => $success]);
} 
else {
    echo json_encode(['success' => false, 'message' => 'Geçersiz işlem']);
}