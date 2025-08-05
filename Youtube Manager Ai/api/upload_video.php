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

    if (!isset($_FILES['video'])) {
        throw new Exception('Video dosyası bulunamadı');
    }

    $user = $auth->getCurrentUser();
    $youtube = new YouTubeManager();
    
    $videoData = [
        'title' => $_POST['title'] ?? '',
        'description' => $_POST['description'] ?? '',
        'category' => $_POST['category'] ?? '22',
        'privacy' => $_POST['privacy'] ?? 'public',
        'channel_id' => $_POST['channel_id'] ?? ''
    ];

    $videoFile = $_FILES['video'];

    // Video dosyasını geçici olarak kaydet
    $uploadDir = '../uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $fileName = uniqid() . '_' . $videoFile['name'];
    $filePath = $uploadDir . $fileName;
    
    if (!move_uploaded_file($videoFile['tmp_name'], $filePath)) {
        throw new Exception('Dosya kaydedilemedi');
    }

    // Veritabanına video kaydını ekle
    $database = new Database();
    $db = $database->getConnection();
    
    $stmt = $db->prepare("
        INSERT INTO videos (user_id, title, description, category_id, file_path, original_filename, file_size, status) 
        VALUES (:user_id, :title, :description, :category_id, :file_path, :original_filename, :file_size, 'processing')
    ");
    
    $stmt->execute([
        ':user_id' => $user['id'],
        ':title' => $videoData['title'],
        ':description' => $videoData['description'],
        ':category_id' => $videoData['category'],
        ':file_path' => $filePath,
        ':original_filename' => $videoFile['name'],
        ':file_size' => $videoFile['size']
    ]);
    
    $videoId = $db->lastInsertId();

    // YouTube'a yükleme işlemini başlat
    $youtubeVideoId = $youtube->uploadVideo($filePath, $videoData, $user['id']);
    
    // YouTube video ID'sini güncelle
    $stmt = $db->prepare("UPDATE videos SET youtube_video_id = :youtube_id, status = 'uploaded' WHERE id = :id");
    $stmt->execute([
        ':youtube_id' => $youtubeVideoId,
        ':id' => $videoId
    ]);

    // Geçici dosyayı sil
    unlink($filePath);

    echo json_encode([
        'success' => true,
        'video_id' => $videoId,
        'youtube_video_id' => $youtubeVideoId,
        'message' => 'Video başarıyla yüklendi'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 