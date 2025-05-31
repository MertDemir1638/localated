<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'check':
        echo json_encode([
            'loggedIn' => isLoggedIn(),
            'user' => isLoggedIn() ? getUserData($_SESSION['user_id']) : null
        ]);
        break;
        
    case 'login':
        $data = json_decode(file_get_contents('php://input'), true);
        $result = loginUser($data['email'], $data['password']);
        echo json_encode($result);
        break;
        
    case 'register':
        $data = json_decode(file_get_contents('php://input'), true);
        $result = registerUser($data['username'], $data['email'], $data['password']);
        echo json_encode($result);
        break;
        
    case 'logout':
        $result = logoutUser();
        echo json_encode($result);
        break;
        
    case 'updateTheme':
        if (isLoggedIn()) {
            $data = json_decode(file_get_contents('php://input'), true);
            $result = updateUserTheme($_SESSION['user_id'], $data['theme']);
            echo json_encode($result);
        } else {
            echo json_encode(['success' => false, 'message' => 'Oturum açılmamış']);
        }
        break;
        
    case 'badges':
        if (isLoggedIn()) {
            require_once __DIR__ . '/../includes/functions.php';
            $badges = getUserBadges($_SESSION['user_id']);
            echo json_encode($badges);
        } else {
            echo json_encode([]);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Geçersiz işlem']);
}