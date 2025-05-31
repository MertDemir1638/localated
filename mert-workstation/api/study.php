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

switch ($action) {
    case 'get':
        $sessions = getStudySessions($userId);
        echo json_encode($sessions);
        break;
        
    case 'start':
        $data = json_decode(file_get_contents('php://input'), true);
        $success = startStudySession($userId, $data['category'], $data['notes']);
        echo json_encode(['success' => $success]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Geçersiz işlem']);
}