<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/youtube.php';

header('Content-Type: application/json');

try {
    $auth = new GoogleAuth();
    
    if (!$auth->isLoggedIn()) {
        throw new Exception('Giriş yapılmamış');
    }

    $user = $auth->getCurrentUser();
    $youtube = new YouTubeManager();
    
    // Kullanıcının YouTube kanallarını al
    $channels = $youtube->getUserChannels($user['id']);
    
    echo json_encode([
        'success' => true,
        'channels' => $channels
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 