<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

try {
    $auth = new GoogleAuth();
    
    if (!$auth->isLoggedIn()) {
        throw new Exception('Giriş yapılmamış');
    }

    $user = $auth->getCurrentUser();
    $database = new Database();
    $db = $database->getConnection();

    $timeRange = $_GET['range'] ?? '30';
    $format = $_GET['format'] ?? 'excel';
    $timeRange = (int)$timeRange;

    // Verileri al
    $stmt = $db->prepare("
        SELECT 
            v.title as 'Video Başlığı',
            v.upload_date as 'Yayın Tarihi',
            v.view_count as 'Görüntülenme',
            v.like_count as 'Beğeni',
            v.comment_count as 'Yorum',
            CASE 
                WHEN v.view_count > 0 THEN 
                    ROUND(((v.like_count + v.comment_count) / v.view_count) * 100, 2)
                ELSE 0 
            END as 'Etkileşim Oranı (%)',
            c.name_tr as 'Kategori',
            v.duration as 'Süre (saniye)'
        FROM videos v
        LEFT JOIN categories c ON v.category_id = c.youtube_category_id
        WHERE v.user_id = :user_id 
        AND v.upload_date >= DATE_SUB(NOW(), INTERVAL :range DAY)
        AND v.status = 'uploaded'
        ORDER BY v.view_count DESC
    ");
    $stmt->execute([':user_id' => $user['id'], ':range' => $timeRange]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($format === 'excel') {
        // Excel formatında export
        $filename = 'youtube_analytics_' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // UTF-8 BOM ekle (Excel'de Türkçe karakterler için)
        echo "\xEF\xBB\xBF";

        $output = fopen('php://output', 'w');

        // Başlıkları yaz
        if (!empty($data)) {
            fputcsv($output, array_keys($data[0]), ';');
            
            // Verileri yaz
            foreach ($data as $row) {
                // Tarihi formatla
                if (isset($row['Yayın Tarihi'])) {
                    $row['Yayın Tarihi'] = date('d.m.Y H:i', strtotime($row['Yayın Tarihi']));
                }
                
                // Süreyi formatla
                if (isset($row['Süre (saniye)'])) {
                    $seconds = $row['Süre (saniye)'];
                    $hours = floor($seconds / 3600);
                    $minutes = floor(($seconds % 3600) / 60);
                    $secs = $seconds % 60;
                    
                    if ($hours > 0) {
                        $row['Süre (saniye)'] = sprintf('%d:%02d:%02d', $hours, $minutes, $secs);
                    } else {
                        $row['Süre (saniye)'] = sprintf('%d:%02d', $minutes, $secs);
                    }
                }
                
                fputcsv($output, $row, ';');
            }
        }

        fclose($output);
        
    } else {
        // JSON formatında export
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="youtube_analytics_' . date('Y-m-d') . '.json"');
        
        echo json_encode([
            'export_date' => date('Y-m-d H:i:s'),
            'time_range_days' => $timeRange,
            'total_videos' => count($data),
            'data' => $data
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 