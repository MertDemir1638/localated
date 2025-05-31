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
$stats = getUserStats($userId);
echo json_encode($stats);