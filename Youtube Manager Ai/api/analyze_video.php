<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/ai.php';

header('Content-Type: application/json');

try {
    $auth = new GoogleAuth();
    
    if (!$auth->isLoggedIn()) {
        throw new Exception('Giriş yapılmamış');
    }

    if (!isset($_FILES['video'])) {
        throw new Exception('Video dosyası bulunamadı');
    }

    $videoFile = $_FILES['video'];
    $channelId = $_POST['channel_id'] ?? '';

    // Dosya kontrolü
    if ($videoFile['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Dosya yükleme hatası');
    }

    // Video analizi
    $ai = new AIVideoAnalyzer();
    $analysis = $ai->analyzeVideo($videoFile['tmp_name'], $videoFile['name']);
    
    // Optimal yükleme zamanlarını hesapla
    $bestTimes = $ai->calculateBestUploadTimes($channelId);
    
    // Trending hashtag'leri al
    $hashtags = $ai->getTrendingHashtags();
    
    echo json_encode([
        'success' => true,
        'title' => $analysis['title'],
        'description' => $analysis['description'],
        'suggestedCategory' => $analysis['category_name'],
        'categoryId' => $analysis['category_id'],
        'titleConfidence' => $analysis['title_confidence'],
        'descriptionConfidence' => $analysis['description_confidence'],
        'categoryConfidence' => $analysis['category_confidence'],
        'bestTimes' => $bestTimes,
        'hashtags' => $hashtags,
        'similarVideos' => $analysis['similar_videos'] ?? []
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 