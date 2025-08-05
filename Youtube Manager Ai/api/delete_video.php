<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    $auth = new GoogleAuth();
    
    if (!$auth->isLoggedIn()) {
        throw new Exception('Giriş yapılmamış');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $videoId = $input['video_id'] ?? '';

    if (empty($videoId)) {
        throw new Exception('Video ID gerekli');
    }

    $user = $auth->getCurrentUser();
    $database = new Database();
    $db = $database->getConnection();

    // Video'nun kullanıcıya ait olduğunu kontrol et
    $stmt = $db->prepare("SELECT * FROM videos WHERE id = :id AND user_id = :user_id");
    $stmt->execute([
        ':id' => $videoId,
        ':user_id' => $user['id']
    ]);
    $video = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$video) {
        throw new Exception('Video bulunamadı veya yetkiniz yok');
    }

    // Video dosyasını sil (eğer varsa)
    if (!empty($video['file_path']) && file_exists($video['file_path'])) {
        unlink($video['file_path']);
    }

    // Veritabanından video ve ilişkili kayıtları sil
    $db->beginTransaction();

    try {
        // Analitik verilerini sil
        $stmt = $db->prepare("DELETE FROM analytics WHERE video_id = :video_id");
        $stmt->execute([':video_id' => $videoId]);

        // Video etiketlerini sil
        $stmt = $db->prepare("DELETE FROM video_tags WHERE video_id = :video_id");
        $stmt->execute([':video_id' => $videoId]);

        // Videoyu sil
        $stmt = $db->prepare("DELETE FROM videos WHERE id = :id");
        $stmt->execute([':id' => $videoId]);

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Video başarıyla silindi'
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        throw new Exception('Silme işlemi başarısız: ' . $e->getMessage());
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 