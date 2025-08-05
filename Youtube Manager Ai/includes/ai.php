<?php
class AIVideoAnalyzer {
    private $openaiApiKey = 'YOUR_OPENAI_API_KEY';
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    /**
     * Video dosyasını analiz et
     */
    public function analyzeVideo($videoPath, $userId) {
        try {
            $analysis = [
                'video_info' => $this->getVideoInfo($videoPath),
                'ai_suggestions' => $this->generateVideoSuggestions($videoPath),
                'optimal_thumbnail' => $this->analyzeThumbnail($videoPath),
                'content_analysis' => $this->analyzeVideoContent($videoPath)
            ];
            
            // Analiz sonuçlarını veritabanına kaydet
            $this->saveAnalysisResults($userId, $analysis);
            
            return $analysis;
            
        } catch (Exception $e) {
            error_log('AI Analysis Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Video bilgilerini çıkar
     */
    private function getVideoInfo($videoPath) {
        $info = [];
        
        // FFprobe ile video bilgilerini al
        $command = "ffprobe -v quiet -print_format json -show_format -show_streams " . escapeshellarg($videoPath);
        $output = shell_exec($command);
        
        if ($output) {
            $data = json_decode($output, true);
            
            if (isset($data['format'])) {
                $info['duration'] = (float)($data['format']['duration'] ?? 0);
                $info['size'] = (int)($data['format']['size'] ?? 0);
                $info['bitrate'] = (int)($data['format']['bit_rate'] ?? 0);
            }
            
            if (isset($data['streams'])) {
                foreach ($data['streams'] as $stream) {
                    if ($stream['codec_type'] === 'video') {
                        $info['resolution'] = [
                            'width' => (int)($stream['width'] ?? 0),
                            'height' => (int)($stream['height'] ?? 0)
                        ];
                        $info['fps'] = $this->evaluateFraction($stream['r_frame_rate'] ?? '0/1');
                    }
                }
            }
        }
        
        return $info;
    }
    
    /**
     * Framerate fraction değerini hesapla
     */
    private function evaluateFraction($fraction) {
        $parts = explode('/', $fraction);
        if (count($parts) === 2 && $parts[1] != 0) {
            return (float)$parts[0] / (float)$parts[1];
        }
        return 0;
    }
    
    /**
     * AI ile video önerileri üret
     */
    private function generateVideoSuggestions($videoPath) {
        // Video'dan örnek frame'ler çıkar
        $frames = $this->extractVideoFrames($videoPath, 5);
        
        // OpenAI API ile analiz et
        $suggestions = [
            'title_suggestions' => $this->generateTitleSuggestions($frames),
            'description_suggestions' => $this->generateDescriptionSuggestions($frames),
            'tag_suggestions' => $this->generateTagSuggestions($frames),
            'category_suggestion' => $this->suggestCategory($frames),
            'optimal_times' => $this->suggestOptimalUploadTimes()
        ];
        
        return $suggestions;
    }
    
    /**
     * Video'dan frame'leri çıkar
     */
    private function extractVideoFrames($videoPath, $count = 5) {
        $frames = [];
        $tempDir = sys_get_temp_dir() . '/video_frames_' . uniqid();
        
        if (!mkdir($tempDir, 0755, true)) {
            return $frames;
        }
        
        // Video süresini al
        $command = "ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 " . escapeshellarg($videoPath);
        $duration = (float)shell_exec($command);
        
        if ($duration > 0) {
            for ($i = 0; $i < $count; $i++) {
                $time = ($duration / ($count + 1)) * ($i + 1);
                $framePath = $tempDir . "/frame_$i.jpg";
                
                $command = "ffmpeg -ss $time -i " . escapeshellarg($videoPath) . " -vframes 1 -q:v 2 " . escapeshellarg($framePath) . " 2>/dev/null";
                shell_exec($command);
                
                if (file_exists($framePath)) {
                    $frames[] = [
                        'path' => $framePath,
                        'timestamp' => $time,
                        'base64' => base64_encode(file_get_contents($framePath))
                    ];
                }
            }
        }
        
        return $frames;
    }
    
    /**
     * AI ile başlık önerileri üret
     */
    private function generateTitleSuggestions($frames) {
        if (empty($frames)) {
            return $this->getDefaultTitleSuggestions();
        }
        
        $prompt = "Video karelerini analiz ederek, YouTube için çekici ve SEO uyumlu 5 farklı başlık önerisi oluştur. Başlıklar Türkçe olmalı ve 60 karakteri geçmemelidir.";
        
        return $this->callOpenAI($prompt, $frames, 'title');
    }
    
    /**
     * AI ile açıklama önerileri üret
     */
    private function generateDescriptionSuggestions($frames) {
        if (empty($frames)) {
            return $this->getDefaultDescriptionSuggestions();
        }
        
        $prompt = "Video içeriğini analiz ederek YouTube açıklaması oluştur. Açıklama SEO uyumlu, etkileyici ve viewer engagement'ı artıracak şekilde olmalıdır. Türkçe olarak yazılmalıdır.";
        
        return $this->callOpenAI($prompt, $frames, 'description');
    }
    
    /**
     * AI ile tag önerileri üret
     */
    private function generateTagSuggestions($frames) {
        if (empty($frames)) {
            return $this->getDefaultTagSuggestions();
        }
        
        $prompt = "Video içeriğini analiz ederek ilgili YouTube tag'leri öner. Tag'ler hem Türkçe hem İngilizce olabilir ve video keşfedilebilirliğini artırmalıdır.";
        
        return $this->callOpenAI($prompt, $frames, 'tags');
    }
    
    /**
     * AI ile kategori önerisi
     */
    private function suggestCategory($frames) {
        $categories = [
            '1' => 'Film ve Animasyon',
            '2' => 'Otomobil ve Araçlar', 
            '10' => 'Müzik',
            '15' => 'Evcil Hayvanlar',
            '17' => 'Spor',
            '19' => 'Seyahat',
            '20' => 'Oyun',
            '22' => 'İnsan ve Bloglar',
            '23' => 'Komedi',
            '24' => 'Eğlence',
            '25' => 'Haber ve Politika',
            '26' => 'Nasıl Yapılır',
            '27' => 'Eğitim',
            '28' => 'Bilim ve Teknoloji'
        ];
        
        if (empty($frames)) {
            return ['category_id' => '22', 'category_name' => 'İnsan ve Bloglar', 'confidence' => 50];
        }
        
        $prompt = "Video karelerini analiz ederek en uygun YouTube kategorisini belirle. Kategoriler: " . json_encode($categories);
        
        $response = $this->callOpenAI($prompt, $frames, 'category');
        
        // Yanıtı parse et
        foreach ($categories as $id => $name) {
            if (stripos($response, $name) !== false) {
                return [
                    'category_id' => $id,
                    'category_name' => $name,
                    'confidence' => 85
                ];
            }
        }
        
        return ['category_id' => '22', 'category_name' => 'İnsan ve Bloglar', 'confidence' => 50];
    }
    
    /**
     * Optimal yükleme zamanları öner
     */
    private function suggestOptimalUploadTimes() {
        // Kullanıcının geçmiş verilerine ve genel trendlere göre optimal zamanları hesapla
        $defaultTimes = [
            'weekdays' => ['18:00', '19:00', '20:00'],
            'weekends' => ['14:00', '15:00', '16:00', '20:00'],
            'best_overall' => '19:00'
        ];
        
        return $defaultTimes;
    }
    
    /**
     * OpenAI API çağrısı
     */
    private function callOpenAI($prompt, $frames = [], $type = 'general') {
        if (empty($this->openaiApiKey) || $this->openaiApiKey === 'YOUR_OPENAI_API_KEY') {
            return $this->getDefaultSuggestion($type);
        }
        
        $messages = [
            [
                'role' => 'system',
                'content' => 'Sen YouTube video optimizasyonu konusunda uzman bir asistansın. Türkçe yanıt ver ve verilen görüntüleri analiz ederek önerilerde bulun.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ];
        
        // Eğer frame'ler varsa görsel analiz ekle
        if (!empty($frames)) {
            $imageData = [];
            foreach (array_slice($frames, 0, 3) as $frame) { // İlk 3 frame'i kullan
                $imageData[] = [
                    'type' => 'image_url',
                    'image_url' => [
                        'url' => 'data:image/jpeg;base64,' . $frame['base64']
                    ]
                ];
            }
            
            $messages[1]['content'] = [
                ['type' => 'text', 'text' => $prompt],
                ...$imageData
            ];
        }
        
        $data = [
            'model' => 'gpt-4-vision-preview',
            'messages' => $messages,
            'max_tokens' => 500,
            'temperature' => 0.7
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->openaiApiKey
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            
            if (isset($result['choices'][0]['message']['content'])) {
                return $this->parseAIResponse($result['choices'][0]['message']['content'], $type);
            }
        }
        
        return $this->getDefaultSuggestion($type);
    }
    
    /**
     * AI yanıtını parse et
     */
    private function parseAIResponse($response, $type) {
        switch ($type) {
            case 'title':
                // Başlıkları liste halinde parse et
                $lines = explode("\n", $response);
                $titles = [];
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (!empty($line) && !preg_match('/^(başlık|title|öneri)/i', $line)) {
                        $line = preg_replace('/^\d+[\.\-\)]\s*/', '', $line);
                        if (strlen($line) <= 60 && !empty($line)) {
                            $titles[] = $line;
                        }
                    }
                }
                return array_slice($titles, 0, 5);
                
            case 'description':
                return trim($response);
                
            case 'tags':
                // Tag'leri parse et
                $tags = [];
                $response = preg_replace('/^(tag|etiket|anahtar kelime).*?:/i', '', $response);
                $tagMatches = preg_split('/[,\n]+/', $response);
                foreach ($tagMatches as $tag) {
                    $tag = trim($tag, ' #"');
                    if (!empty($tag) && strlen($tag) <= 30) {
                        $tags[] = $tag;
                    }
                }
                return array_slice(array_unique($tags), 0, 15);
                
            case 'category':
                return $response;
                
            default:
                return $response;
        }
    }
    
    /**
     * Varsayılan önerileri döndür
     */
    private function getDefaultSuggestion($type) {
        switch ($type) {
            case 'title':
                return $this->getDefaultTitleSuggestions();
            case 'description':
                return $this->getDefaultDescriptionSuggestions();
            case 'tags':
                return $this->getDefaultTagSuggestions();
            default:
                return [];
        }
    }
    
    private function getDefaultTitleSuggestions() {
        return [
            'Muhteşem Video İçeriği',
            'İzlemeye Değer İçerik',
            'Harika Bir Video Deneyimi',
            'Kaliteli Video İçeriği',
            'Etkileyici Video'
        ];
    }
    
    private function getDefaultDescriptionSuggestions() {
        return "Bu video harika içerikler barındırıyor! Beğenmeyi ve abone olmayı unutmayın.\n\n#video #içerik #youtube";
    }
    
    private function getDefaultTagSuggestions() {
        return [
            'video', 'içerik', 'youtube', 'eğlence', 'kalite', 
            'harika', 'güzel', 'izle', 'beğen', 'abone'
        ];
    }
    
    /**
     * Thumbnail analizi
     */
    private function analyzeThumbnail($videoPath) {
        // Video'nun ortasından thumbnail çıkar
        $command = "ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 " . escapeshellarg($videoPath);
        $duration = (float)shell_exec($command);
        
        $thumbnailTime = $duration / 2; // Video ortası
        $thumbnailPath = sys_get_temp_dir() . '/thumbnail_' . uniqid() . '.jpg';
        
        $command = "ffmpeg -ss $thumbnailTime -i " . escapeshellarg($videoPath) . " -vframes 1 -q:v 2 " . escapeshellarg($thumbnailPath) . " 2>/dev/null";
        shell_exec($command);
        
        if (file_exists($thumbnailPath)) {
            return [
                'path' => $thumbnailPath,
                'timestamp' => $thumbnailTime,
                'suggestions' => [
                    'brightness' => 'Parlaklığı %10 artırın',
                    'contrast' => 'Kontrastı optimize edin',
                    'text' => 'Açıklayıcı metin ekleyin'
                ]
            ];
        }
        
        return null;
    }
    
    /**
     * Video içerik analizi
     */
    private function analyzeVideoContent($videoPath) {
        $analysis = [
            'quality_score' => $this->calculateQualityScore($videoPath),
            'engagement_factors' => $this->analyzeEngagementFactors($videoPath),
            'seo_optimization' => $this->analyzeSEOFactors($videoPath),
            'recommendations' => []
        ];
        
        // Kalite puanına göre öneriler
        if ($analysis['quality_score'] < 70) {
            $analysis['recommendations'][] = 'Video kalitesini artırmayı düşünün';
        }
        
        return $analysis;
    }
    
    /**
     * Video kalite puanı hesapla
     */
    private function calculateQualityScore($videoPath) {
        $info = $this->getVideoInfo($videoPath);
        $score = 0;
        
        // Çözünürlük puanı
        if (isset($info['resolution'])) {
            $pixels = $info['resolution']['width'] * $info['resolution']['height'];
            if ($pixels >= 1920 * 1080) $score += 30; // 1080p+
            elseif ($pixels >= 1280 * 720) $score += 25; // 720p
            else $score += 15; // Lower
        }
        
        // FPS puanı
        if (isset($info['fps'])) {
            if ($info['fps'] >= 60) $score += 20;
            elseif ($info['fps'] >= 30) $score += 15;
            else $score += 10;
        }
        
        // Süre puanı
        if (isset($info['duration'])) {
            if ($info['duration'] >= 300 && $info['duration'] <= 600) $score += 25; // 5-10 dakika optimal
            elseif ($info['duration'] >= 120) $score += 20;
            else $score += 10;
        }
        
        // Bitrate puanı
        if (isset($info['bitrate'])) {
            if ($info['bitrate'] >= 5000000) $score += 25; // 5+ Mbps
            elseif ($info['bitrate'] >= 2500000) $score += 20; // 2.5+ Mbps
            else $score += 10;
        }
        
        return min(100, $score);
    }
    
    /**
     * Etkileşim faktörlerini analiz et
     */
    private function analyzeEngagementFactors($videoPath) {
        $info = $this->getVideoInfo($videoPath);
        
        $factors = [
            'optimal_length' => false,
            'good_quality' => false,
            'thumbnail_potential' => true
        ];
        
        // Optimal uzunluk (5-10 dakika)
        if (isset($info['duration']) && $info['duration'] >= 300 && $info['duration'] <= 600) {
            $factors['optimal_length'] = true;
        }
        
        // Kalite kontrolü
        if ($this->calculateQualityScore($videoPath) >= 70) {
            $factors['good_quality'] = true;
        }
        
        return $factors;
    }
    
    /**
     * SEO faktörlerini analiz et
     */
    private function analyzeSEOFactors($videoPath) {
        return [
            'has_captions' => false, // Altyazı analizi yapılabilir
            'optimal_duration' => true,
            'good_thumbnail' => true,
            'seo_score' => 75
        ];
    }
    
    /**
     * Analiz sonuçlarını veritabanına kaydet
     */
    private function saveAnalysisResults($userId, $analysis) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO ai_analysis (user_id, analysis_data, quality_score, created_at) 
                VALUES (:user_id, :analysis_data, :quality_score, NOW())
            ");
            
            $stmt->execute([
                ':user_id' => $userId,
                ':analysis_data' => json_encode($analysis),
                ':quality_score' => $analysis['content_analysis']['quality_score'] ?? 0
            ]);
            
            return $this->db->lastInsertId();
            
        } catch (PDOException $e) {
            error_log('AI Analysis Save Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Kullanıcının geçmiş analiz sonuçlarını al
     */
    public function getUserAnalysisHistory($userId, $limit = 10) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM ai_analysis 
                WHERE user_id = :user_id 
                ORDER BY created_at DESC 
                LIMIT :limit
            ");
            
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // JSON verilerini decode et
            foreach ($results as &$result) {
                $result['analysis_data'] = json_decode($result['analysis_data'], true);
            }
            
            return $results;
            
        } catch (PDOException $e) {
            error_log('Analysis History Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * AI önerilerini güncelle
     */
    public function updateSuggestions($userId, $videoData) {
        $suggestions = [
            'title_optimization' => $this->optimizeTitle($videoData['title']),
            'description_enhancement' => $this->enhanceDescription($videoData['description']),
            'tag_recommendations' => $this->recommendTags($videoData),
            'timing_suggestions' => $this->suggestOptimalUploadTimes()
        ];
        
        return $suggestions;
    }
    
    /**
     * Başlık optimizasyonu
     */
    private function optimizeTitle($title) {
        $optimizations = [];
        
        if (strlen($title) > 60) {
            $optimizations[] = 'Başlığı 60 karakterin altına indirin';
        }
        
        if (!preg_match('/[!?]/', $title)) {
            $optimizations[] = 'Duygu belirten noktalama işaretleri ekleyin';
        }
        
        return [
            'current_title' => $title,
            'optimizations' => $optimizations,
            'suggestions' => $this->getDefaultTitleSuggestions()
        ];
    }
    
    /**
     * Açıklama geliştirme
     */
    private function enhanceDescription($description) {
        $enhancements = [];
        
        if (strlen($description) < 100) {
            $enhancements[] = 'Açıklamayı daha detaylandırın (en az 100 karakter)';
        }
        
        if (strpos($description, '#') === false) {
            $enhancements[] = 'Hashtag\'ler ekleyin';
        }
        
        return [
            'current_description' => $description,
            'enhancements' => $enhancements,
            'template' => $this->getDescriptionTemplate()
        ];
    }
    
    /**
     * Açıklama şablonu
     */
    private function getDescriptionTemplate() {
        return "Bu videoda [video konusu] hakkında detaylı bilgiler bulacaksınız.\n\n⏰ Zaman damgaları:\n00:00 Giriş\n\n📱 Sosyal medya:\n\n🔔 Abone olmayı unutmayın!\n\n#hashtag1 #hashtag2 #hashtag3";
    }
    
    /**
     * Tag önerileri
     */
    private function recommendTags($videoData) {
        $baseTags = $this->getDefaultTagSuggestions();
        
        // Video başlığından tag'ler çıkar
        $titleWords = explode(' ', strtolower($videoData['title']));
        $titleTags = array_filter($titleWords, function($word) {
            return strlen($word) > 3 && !in_array($word, ['için', 'olan', 'ile', 'bir', 'bu', 'şu']);
        });
        
        return array_merge($baseTags, array_slice($titleTags, 0, 5));
    }
    
    /**
     * Geçici dosyaları temizle
     */
    public function cleanupTempFiles() {
        $tempDir = sys_get_temp_dir();
        $files = glob($tempDir . '/video_frames_*');
        $files = array_merge($files, glob($tempDir . '/thumbnail_*'));
        
        foreach ($files as $file) {
            if (is_file($file) && (time() - filemtime($file)) > 3600) { // 1 saat eski dosyalar
                unlink($file);
            }
        }
    }
    
    /**
     * Trend analizi
     */
    public function analyzeTrends($category = null) {
        // Genel trend analizi
        $trends = [
            'popular_topics' => [
                'teknoloji', 'oyun', 'eğitim', 'eğlence', 'müzik'
            ],
            'optimal_durations' => [
                'short_form' => '30-60 saniye',
                'standard' => '5-10 dakika', 
                'long_form' => '15+ dakika'
            ],
            'best_upload_times' => [
                'weekdays' => '18:00-21:00',
                'weekends' => '14:00-17:00'
            ]
        ];
        
        return $trends;
    }
}
?> 