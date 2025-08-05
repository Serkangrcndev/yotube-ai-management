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

    // Genel istatistikler
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_videos,
            COALESCE(SUM(view_count), 0) as total_views,
            COALESCE(SUM(like_count), 0) as total_likes,
            COALESCE(AVG(CASE WHEN view_count > 0 THEN ((like_count + comment_count) / view_count) * 100 ELSE 0 END), 0) as engagement_rate
        FROM videos 
        WHERE user_id = :user_id AND status = 'uploaded'
    ");
    $stmt->execute([':user_id' => $user['id']]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Son 30 gün için grafik verisi
    $stmt = $db->prepare("
        SELECT 
            DATE(upload_date) as date,
            COUNT(*) as video_count,
            COALESCE(SUM(view_count), 0) as views,
            COALESCE(SUM(like_count), 0) as likes
        FROM videos 
        WHERE user_id = :user_id 
        AND upload_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        AND status = 'uploaded'
        GROUP BY DATE(upload_date)
        ORDER BY date ASC
    ");
    $stmt->execute([':user_id' => $user['id']]);
    $chartData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Grafik verilerini formatla
    $labels = [];
    $views = [];
    $likes = [];

    foreach ($chartData as $row) {
        $labels[] = date('d/m', strtotime($row['date']));
        $views[] = (int)$row['views'];
        $likes[] = (int)$row['likes'];
    }

    echo json_encode([
        'success' => true,
        'stats' => [
            'totalVideos' => (int)$stats['total_videos'],
            'totalViews' => (int)$stats['total_views'],
            'totalLikes' => (int)$stats['total_likes'],
            'engagementRate' => round($stats['engagement_rate'], 2)
        ],
        'chartData' => [
            'labels' => $labels,
            'views' => $views,
            'likes' => $likes
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 