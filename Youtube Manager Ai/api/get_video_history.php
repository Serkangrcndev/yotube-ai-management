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

    $user = $auth->getCurrentUser();
    $database = new Database();
    $db = $database->getConnection();

    // Videoları al
    $stmt = $db->prepare("
        SELECT 
            v.*,
            c.name_tr as category_name
        FROM videos v
        LEFT JOIN categories c ON v.category_id = c.youtube_category_id
        WHERE v.user_id = :user_id
        ORDER BY v.upload_date DESC
    ");
    $stmt->execute([':user_id' => $user['id']]);
    $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // İstatistik özeti
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_videos,
            COALESCE(SUM(view_count), 0) as total_views,
            COALESCE(AVG(CASE WHEN view_count > 0 THEN ((like_count + comment_count) / view_count) * 100 ELSE 0 END), 0) as avg_performance,
            COUNT(CASE WHEN upload_date >= DATE_SUB(NOW(), INTERVAL 1 MONTH) THEN 1 END) as this_month_count
        FROM videos 
        WHERE user_id = :user_id
    ");
    $stmt->execute([':user_id' => $user['id']]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'videos' => $videos,
        'stats' => [
            'totalVideos' => (int)$stats['total_videos'],
            'totalViews' => (int)$stats['total_views'],
            'avgPerformance' => round($stats['avg_performance'], 1),
            'thisMonthCount' => (int)$stats['this_month_count']
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 