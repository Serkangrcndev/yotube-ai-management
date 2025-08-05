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

    $user = $auth->getCurrentUser();
    $database = new Database();
    $db = $database->getConnection();
    $ai = new AIVideoAnalyzer();

    $timeRange = $_GET['range'] ?? '30';
    $timeRange = (int)$timeRange;

    // Genel istatistikler
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_videos,
            COALESCE(SUM(view_count), 0) as total_views,
            COALESCE(SUM(like_count), 0) as total_likes,
            COALESCE(SUM(comment_count), 0) as total_comments,
            COALESCE(AVG(CASE WHEN view_count > 0 THEN ((like_count + comment_count) / view_count) * 100 ELSE 0 END), 0) as avg_engagement
        FROM videos 
        WHERE user_id = :user_id 
        AND upload_date >= DATE_SUB(NOW(), INTERVAL :range DAY)
        AND status = 'uploaded'
    ");
    $stmt->execute([':user_id' => $user['id'], ':range' => $timeRange]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Önceki dönem karşılaştırması için
    $stmt = $db->prepare("
        SELECT 
            COALESCE(SUM(view_count), 0) as prev_views,
            COALESCE(AVG(CASE WHEN view_count > 0 THEN ((like_count + comment_count) / view_count) * 100 ELSE 0 END), 0) as prev_engagement
        FROM videos 
        WHERE user_id = :user_id 
        AND upload_date >= DATE_SUB(NOW(), INTERVAL :range2 DAY)
        AND upload_date < DATE_SUB(NOW(), INTERVAL :range DAY)
        AND status = 'uploaded'
    ");
    $stmt->execute([':user_id' => $user['id'], ':range' => $timeRange, ':range2' => $timeRange * 2]);
    $prevStats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Değişim oranlarını hesapla
    $viewsChange = $prevStats['prev_views'] > 0 ? 
        (($stats['total_views'] - $prevStats['prev_views']) / $prevStats['prev_views']) * 100 : 0;
    $engagementChange = $prevStats['prev_engagement'] > 0 ? 
        (($stats['avg_engagement'] - $prevStats['prev_engagement']) / $prevStats['prev_engagement']) * 100 : 0;

    // Grafik verisi - günlük bazda
    $stmt = $db->prepare("
        SELECT 
            DATE(upload_date) as date,
            COALESCE(SUM(view_count), 0) as views,
            COALESCE(SUM(like_count), 0) as likes,
            COALESCE(SUM(comment_count), 0) as comments,
            COALESCE(AVG(CASE WHEN view_count > 0 THEN ((like_count + comment_count) / view_count) * 100 ELSE 0 END), 0) as engagement
        FROM videos 
        WHERE user_id = :user_id 
        AND upload_date >= DATE_SUB(NOW(), INTERVAL :range DAY)
        AND status = 'uploaded'
        GROUP BY DATE(upload_date)
        ORDER BY date ASC
    ");
    $stmt->execute([':user_id' => $user['id'], ':range' => $timeRange]);
    $chartData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // En iyi performans gösteren videolar
    $stmt = $db->prepare("
        SELECT id, title, view_count as views, upload_date, thumbnail
        FROM videos 
        WHERE user_id = :user_id 
        AND upload_date >= DATE_SUB(NOW(), INTERVAL :range DAY)
        AND status = 'uploaded'
        ORDER BY view_count DESC
        LIMIT 10
    ");
    $stmt->execute([':user_id' => $user['id'], ':range' => $timeRange]);
    $topVideos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Kategori bazlı analiz
    $stmt = $db->prepare("
        SELECT 
            c.name_tr as name,
            COUNT(v.id) as count,
            COALESCE(SUM(v.view_count), 0) as total_views
        FROM videos v
        LEFT JOIN categories c ON v.category_id = c.youtube_category_id
        WHERE v.user_id = :user_id 
        AND v.upload_date >= DATE_SUB(NOW(), INTERVAL :range DAY)
        AND v.status = 'uploaded'
        GROUP BY c.id, c.name_tr
        ORDER BY count DESC
    ");
    $stmt->execute([':user_id' => $user['id'], ':range' => $timeRange]);
    $categoryData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Saatlik aktivite (simülasyon - gerçek veriler YouTube Analytics API'den alınır)
    $hourlyActivity = array_fill(0, 24, 0);
    for ($i = 0; $i < 24; $i++) {
        $hourlyActivity[$i] = rand(100, 1000); // Gerçek implementasyonda API'den alınacak
    }

    // Haftalık trend (simülasyon)
    $weeklyTrend = [
        rand(800, 1200), // Pazartesi
        rand(900, 1300), // Salı
        rand(1000, 1400), // Çarşamba
        rand(1100, 1500), // Perşembe
        rand(1200, 1600), // Cuma
        rand(1000, 1400), // Cumartesi
        rand(900, 1300)  // Pazar
    ];

    // Detaylı video listesi
    $stmt = $db->prepare("
        SELECT 
            v.*,
            c.name_tr as category_name
        FROM videos v
        LEFT JOIN categories c ON v.category_id = c.youtube_category_id
        WHERE v.user_id = :user_id 
        AND v.upload_date >= DATE_SUB(NOW(), INTERVAL :range DAY)
        AND v.status = 'uploaded'
        ORDER BY v.view_count DESC
    ");
    $stmt->execute([':user_id' => $user['id'], ':range' => $timeRange]);
    $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // AI önerileri
    $aiSuggestions = $ai->generatePerformanceInsights($user['id'], $timeRange);

    // Grafik verilerini formatla
    $labels = [];
    $views = [];
    $likes = [];
    $comments = [];
    $engagement = [];

    foreach ($chartData as $row) {
        $labels[] = date('d/m', strtotime($row['date']));
        $views[] = (int)$row['views'];
        $likes[] = (int)$row['likes'];
        $comments[] = (int)$row['comments'];
        $engagement[] = round($row['engagement'], 2);
    }

    echo json_encode([
        'success' => true,
        'stats' => [
            'totalViews' => (int)$stats['total_views'],
            'totalWatchtime' => rand(5000, 15000), // Simülasyon - gerçekte YouTube Analytics'ten
            'avgEngagement' => round($stats['avg_engagement'], 2),
            'subscriberGrowth' => rand(10, 100), // Simülasyon
            'viewsChange' => round($viewsChange, 1),
            'watchtimeChange' => rand(-5, 15), // Simülasyon
            'engagementChange' => round($engagementChange, 1),
            'subscriberChange' => rand(-2, 8) // Simülasyon
        ],
        'chartData' => [
            'labels' => $labels,
            'views' => $views,
            'watchtime' => array_map(function($v) { return $v * rand(2, 8); }, $views), // Simülasyon
            'engagement' => $engagement,
            'subscribers' => array_map(function($v) { return intval($v / 100); }, $views) // Simülasyon
        ],
        'topVideos' => $topVideos,
        'categoryData' => $categoryData,
        'hourlyActivity' => $hourlyActivity,
        'weeklyTrend' => $weeklyTrend,
        'videos' => $videos,
        'aiSuggestions' => $aiSuggestions
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 