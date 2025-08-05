<?php
class YouTubeManager {
    private $apiKey = 'YOUR_YOUTUBE_API_KEY';
    private $auth;
    private $db;
    
    public function __construct() {
        $this->auth = new GoogleAuth();
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    /**
     * Kullanıcının YouTube kanallarını al
     */
    public function getUserChannels($userId) {
        try {
            $stmt = $this->db->prepare("SELECT youtube_channels FROM users WHERE id = :id");
            $stmt->execute([':id' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && !empty($user['youtube_channels'])) {
                $channels = json_decode($user['youtube_channels'], true);
                
                // Kanal bilgilerini formatla
                $formattedChannels = [];
                foreach ($channels as $channel) {
                    $formattedChannels[] = [
                        'id' => $channel['id'],
                        'title' => $channel['snippet']['title'],
                        'description' => $channel['snippet']['description'],
                        'thumbnail' => $channel['snippet']['thumbnails']['default']['url'] ?? '',
                        'subscriberCount' => $channel['statistics']['subscriberCount'] ?? 0,
                        'videoCount' => $channel['statistics']['videoCount'] ?? 0,
                        'viewCount' => $channel['statistics']['viewCount'] ?? 0
                    ];
                }
                
                return $formattedChannels;
            }
            
            return [];
            
        } catch (Exception $e) {
            error_log('YouTube Channels Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Video YouTube'a yükle
     */
    public function uploadVideo($videoPath, $videoData, $userId) {
        try {
            // Önce video metadata'sını oluştur
            $snippet = [
                'title' => $videoData['title'],
                'description' => $videoData['description'],
                'categoryId' => $videoData['category'],
                'defaultLanguage' => 'tr'
            ];
            
            $status = [
                'privacyStatus' => $videoData['privacy'] ?? 'private'
            ];
            
            $metadata = [
                'snippet' => $snippet,
                'status' => $status
            ];
            
            // Resumable upload başlat
            $uploadUrl = $this->initiateResumableUpload($metadata);
            
            if (!$uploadUrl) {
                throw new Exception('Upload URL alınamadı');
            }
            
            // Video dosyasını yükle
            $videoId = $this->uploadVideoFile($uploadUrl, $videoPath);
            
            if (!$videoId) {
                throw new Exception('Video yükleme başarısız');
            }
            
            // Veritabanını güncelle
            $this->updateVideoInDatabase($userId, $videoId, $videoData);
            
            return $videoId;
            
        } catch (Exception $e) {
            error_log('Video Upload Error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Resumable upload başlat
     */
    private function initiateResumableUpload($metadata) {
        $url = 'https://www.googleapis.com/upload/youtube/v3/videos?uploadType=resumable&part=snippet,status';
        
        $accessToken = $this->auth->getValidAccessToken();
        
        if (!$accessToken) {
            return false;
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($metadata));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
            'X-Upload-Content-Type: video/*'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            // Location header'ından upload URL'sini al
            preg_match('/Location: (.*)/', $response, $matches);
            if (isset($matches[1])) {
                return trim($matches[1]);
            }
        }
        
        return false;
    }
    
    /**
     * Video dosyasını yükle
     */
    private function uploadVideoFile($uploadUrl, $videoPath) {
        $fileSize = filesize($videoPath);
        $chunkSize = 8 * 1024 * 1024; // 8MB chunks
        
        $file = fopen($videoPath, 'rb');
        
        if (!$file) {
            return false;
        }
        
        $uploadedBytes = 0;
        $videoId = null;
        
        while (!feof($file)) {
            $chunk = fread($file, $chunkSize);
            $chunkLength = strlen($chunk);
            
            $rangeStart = $uploadedBytes;
            $rangeEnd = $uploadedBytes + $chunkLength - 1;
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $uploadUrl);
            curl_setopt($ch, CURLOPT_PUT, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $chunk);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Range: bytes ' . $rangeStart . '-' . $rangeEnd . '/' . $fileSize,
                'Content-Length: ' . $chunkLength
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200 || $httpCode === 201) {
                // Upload tamamlandı
                $responseData = json_decode($response, true);
                $videoId = $responseData['id'] ?? null;
                break;
            } elseif ($httpCode === 308) {
                // Devam et
                $uploadedBytes += $chunkLength;
            } else {
                // Hata
                fclose($file);
                return false;
            }
        }
        
        fclose($file);
        return $videoId;
    }
    
    /**
     * Video bilgilerini veritabanında güncelle
     */
    private function updateVideoInDatabase($userId, $youtubeVideoId, $videoData) {
        try {
            $stmt = $this->db->prepare("
                UPDATE videos 
                SET youtube_video_id = :youtube_id, 
                    status = 'uploaded',
                    updated_at = CURRENT_TIMESTAMP
                WHERE user_id = :user_id 
                AND title = :title
                AND status = 'processing'
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            
            $stmt->execute([
                ':youtube_id' => $youtubeVideoId,
                ':user_id' => $userId,
                ':title' => $videoData['title']
            ]);
            
        } catch (PDOException $e) {
            error_log('Database Update Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Video istatistiklerini güncelle
     */
    public function updateVideoStatistics($videoId) {
        try {
            $stmt = $this->db->prepare("SELECT youtube_video_id FROM videos WHERE id = :id");
            $stmt->execute([':id' => $videoId]);
            $video = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$video || empty($video['youtube_video_id'])) {
                return false;
            }
            
            $stats = $this->getVideoStatistics($video['youtube_video_id']);
            
            if ($stats) {
                $stmt = $this->db->prepare("
                    UPDATE videos 
                    SET view_count = :views,
                        like_count = :likes,
                        comment_count = :comments,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id
                ");
                
                $stmt->execute([
                    ':views' => $stats['viewCount'] ?? 0,
                    ':likes' => $stats['likeCount'] ?? 0,
                    ':comments' => $stats['commentCount'] ?? 0,
                    ':id' => $videoId
                ]);
                
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log('Video Statistics Update Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * YouTube API'den video istatistiklerini al
     */
    private function getVideoStatistics($youtubeVideoId) {
        $url = 'https://www.googleapis.com/youtube/v3/videos?' . http_build_query([
            'part' => 'statistics',
            'id' => $youtubeVideoId,
            'key' => $this->apiKey
        ]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            
            if (isset($data['items'][0]['statistics'])) {
                return $data['items'][0]['statistics'];
            }
        }
        
        return false;
    }
    
    /**
     * Kanal istatistiklerini al
     */
    public function getChannelStatistics($channelId) {
        $url = 'https://www.googleapis.com/youtube/v3/channels?' . http_build_query([
            'part' => 'statistics',
            'id' => $channelId,
            'key' => $this->apiKey
        ]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            
            if (isset($data['items'][0]['statistics'])) {
                return $data['items'][0]['statistics'];
            }
        }
        
        return false;
    }
    
    /**
     * Video analitiklerini al (YouTube Analytics API)
     */
    public function getVideoAnalytics($videoId, $startDate, $endDate) {
        $url = 'https://youtubeanalytics.googleapis.com/v2/reports?' . http_build_query([
            'ids' => 'channel==MINE',
            'startDate' => $startDate,
            'endDate' => $endDate,
            'metrics' => 'views,likes,comments,shares,estimatedMinutesWatched,averageViewDuration',
            'dimensions' => 'video',
            'filters' => 'video==' . $videoId
        ]);
        
        return $this->auth->makeApiRequest($url);
    }
    
    /**
     * En iyi yükleme zamanlarını hesapla
     */
    public function calculateBestUploadTimes($channelId, $days = 30) {
        try {
            // Geçmiş videolarin performansına bakarak en iyi zamanları hesapla
            $stmt = $this->db->prepare("
                SELECT 
                    HOUR(upload_date) as hour,
                    DAYOFWEEK(upload_date) as day_of_week,
                    AVG(view_count) as avg_views,
                    COUNT(*) as video_count
                FROM videos 
                WHERE channel_id = :channel_id 
                AND upload_date >= DATE_SUB(NOW(), INTERVAL :days DAY)
                AND status = 'uploaded'
                AND view_count > 0
                GROUP BY HOUR(upload_date), DAYOFWEEK(upload_date)
                HAVING video_count >= 2
                ORDER BY avg_views DESC
                LIMIT 5
            ");
            
            $stmt->execute([
                ':channel_id' => $channelId,
                ':days' => $days
            ]);
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $bestTimes = [];
            foreach ($results as $result) {
                $bestTimes[] = (int)$result['hour'];
            }
            
            // Eğer yeterli veri yoksa genel optimal zamanları döndür
            if (empty($bestTimes)) {
                $bestTimes = [18, 19, 20, 21, 14]; // Genel optimal saatler
            }
            
            return array_unique($bestTimes);
            
        } catch (Exception $e) {
            error_log('Best Upload Times Error: ' . $e->getMessage());
            return [18, 19, 20]; // Varsayılan değerler
        }
    }
    
    /**
     * Trend konuları analiz et
     */
    public function getTrendingTopics($region = 'TR') {
        $url = 'https://www.googleapis.com/youtube/v3/videos?' . http_build_query([
            'part' => 'snippet',
            'chart' => 'mostPopular',
            'regionCode' => $region,
            'maxResults' => 50,
            'key' => $this->apiKey
        ]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            
            $topics = [];
            if (isset($data['items'])) {
                foreach ($data['items'] as $video) {
                    if (isset($video['snippet']['tags'])) {
                        $topics = array_merge($topics, $video['snippet']['tags']);
                    }
                }
            }
            
            // En sık kullanılan tag'leri döndür
            $topicCounts = array_count_values($topics);
            arsort($topicCounts);
            
            return array_slice(array_keys($topicCounts), 0, 20);
        }
        
        return [];
    }
    
    /**
     * Video thumbnail'ını güncelle
     */
    public function updateVideoThumbnail($youtubeVideoId, $thumbnailPath) {
        $url = 'https://www.googleapis.com/upload/youtube/v3/thumbnails/set?videoId=' . $youtubeVideoId;
        
        $accessToken = $this->auth->getValidAccessToken();
        
        if (!$accessToken) {
            return false;
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'image' => new CURLFile($thumbnailPath, 'image/jpeg', 'thumbnail.jpg')
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode === 200;
    }
    
    /**
     * Tüm video istatistiklerini toplu güncelle
     */
    public function batchUpdateStatistics($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, youtube_video_id 
                FROM videos 
                WHERE user_id = :user_id 
                AND youtube_video_id IS NOT NULL 
                AND status = 'uploaded'
                ORDER BY upload_date DESC
                LIMIT 50
            ");
            $stmt->execute([':user_id' => $userId]);
            $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $updated = 0;
            foreach ($videos as $video) {
                if ($this->updateVideoStatistics($video['id'])) {
                    $updated++;
                }
                
                // Rate limiting için kısa bekle
                usleep(100000); // 0.1 saniye
            }
            
            return $updated;
            
        } catch (Exception $e) {
            error_log('Batch Update Error: ' . $e->getMessage());
            return 0;
        }
    }
}
?> 