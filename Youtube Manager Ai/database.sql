-- ======================================
-- YouTube Manager Database Schema
-- ======================================

-- Veritabanı oluştur
CREATE DATABASE IF NOT EXISTS youtube_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE youtube_manager;

-- ======================================
-- USERS TABLOSU
-- ======================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    google_id VARCHAR(255) UNIQUE NOT NULL,
    email VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    avatar TEXT,
    youtube_channels JSON,
    access_token TEXT,
    refresh_token TEXT,
    token_expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_google_id (google_id),
    INDEX idx_email (email)
) ENGINE=InnoDB;

-- ======================================
-- VIDEOS TABLOSU
-- ======================================
CREATE TABLE IF NOT EXISTS videos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    youtube_video_id VARCHAR(255),
    channel_id VARCHAR(255),
    title VARCHAR(500) NOT NULL,
    description TEXT,
    category_id INT DEFAULT 22,
    thumbnail TEXT,
    duration INT DEFAULT 0,
    file_path TEXT,
    original_filename VARCHAR(255),
    file_size BIGINT DEFAULT 0,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    publish_date TIMESTAMP NULL,
    view_count BIGINT DEFAULT 0,
    like_count BIGINT DEFAULT 0,
    dislike_count BIGINT DEFAULT 0,
    comment_count BIGINT DEFAULT 0,
    ai_analysis JSON,
    ai_title TEXT,
    ai_description TEXT,
    ai_tags JSON,
    best_upload_times JSON,
    status ENUM('processing', 'analyzed', 'uploading', 'uploaded', 'failed', 'draft') DEFAULT 'processing',
    privacy_status ENUM('public', 'unlisted', 'private') DEFAULT 'public',
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_youtube_video_id (youtube_video_id),
    INDEX idx_channel_id (channel_id),
    INDEX idx_status (status),
    INDEX idx_upload_date (upload_date)
) ENGINE=InnoDB;

-- ======================================
-- ANALYTICS TABLOSU
-- ======================================
CREATE TABLE IF NOT EXISTS analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    video_id INT NOT NULL,
    date DATE NOT NULL,
    views BIGINT DEFAULT 0,
    likes BIGINT DEFAULT 0,
    dislikes BIGINT DEFAULT 0,
    comments BIGINT DEFAULT 0,
    shares BIGINT DEFAULT 0,
    watch_time_minutes BIGINT DEFAULT 0,
    average_view_duration_seconds INT DEFAULT 0,
    click_through_rate DECIMAL(5,4) DEFAULT 0,
    engagement_rate DECIMAL(5,4) DEFAULT 0,
    subscriber_gain INT DEFAULT 0,
    revenue DECIMAL(10,2) DEFAULT 0,
    impressions BIGINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE,
    UNIQUE KEY unique_video_date (video_id, date),
    INDEX idx_video_id (video_id),
    INDEX idx_date (date)
) ENGINE=InnoDB;

-- ======================================
-- CATEGORIES TABLOSU
-- ======================================
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    name_tr VARCHAR(255) NOT NULL,
    youtube_category_id INT UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_youtube_category_id (youtube_category_id)
) ENGINE=InnoDB;

-- ======================================
-- TAGS TABLOSU
-- ======================================
CREATE TABLE IF NOT EXISTS tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    usage_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_usage_count (usage_count)
) ENGINE=InnoDB;

-- ======================================
-- VIDEO_TAGS TABLOSU (Çoka çok ilişki)
-- ======================================
CREATE TABLE IF NOT EXISTS video_tags (
    video_id INT NOT NULL,
    tag_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (video_id, tag_id),
    FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ======================================
-- CHANNELS TABLOSU
-- ======================================
CREATE TABLE IF NOT EXISTS channels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    youtube_channel_id VARCHAR(255) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    thumbnail TEXT,
    subscriber_count BIGINT DEFAULT 0,
    video_count INT DEFAULT 0,
    view_count BIGINT DEFAULT 0,
    country VARCHAR(10),
    language VARCHAR(10),
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_youtube_channel_id (youtube_channel_id)
) ENGINE=InnoDB;

-- ======================================
-- UPLOAD_SESSIONS TABLOSU
-- ======================================
CREATE TABLE IF NOT EXISTS upload_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_id VARCHAR(255) NOT NULL UNIQUE,
    video_id INT,
    upload_url TEXT,
    bytes_uploaded BIGINT DEFAULT 0,
    total_bytes BIGINT DEFAULT 0,
    status ENUM('started', 'uploading', 'processing', 'completed', 'failed') DEFAULT 'started',
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ======================================
-- AI_ANALYSIS_CACHE TABLOSU
-- ======================================
CREATE TABLE IF NOT EXISTS ai_analysis_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    file_hash VARCHAR(64) NOT NULL UNIQUE,
    analysis_result JSON NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_file_hash (file_hash),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB;

-- ======================================
-- KATEGORI VERİLERİNİ EKLE
-- ======================================
INSERT IGNORE INTO categories (id, name, name_tr, youtube_category_id, description) VALUES 
(1, 'Film & Animation', 'Film ve Animasyon', 1, 'Film, animasyon ve sinema içerikleri'),
(2, 'Autos & Vehicles', 'Otomobil ve Araçlar', 2, 'Otomobil, motorsiklet ve araç incelemeleri'),
(3, 'Music', 'Müzik', 10, 'Müzik videoları, konserler ve müzik içerikleri'),
(4, 'Pets & Animals', 'Evcil Hayvanlar ve Hayvanlar', 15, 'Hayvan videoları ve evcil hayvan bakımı'),
(5, 'Sports', 'Spor', 17, 'Spor maçları, antrenmanlar ve spor haberleri'),
(6, 'Travel & Events', 'Seyahat ve Etkinlikler', 19, 'Seyahat vlogları ve etkinlik kayıtları'),
(7, 'Gaming', 'Oyun', 20, 'Video oyunu içerikleri ve canlı yayınlar'),
(8, 'People & Blogs', 'İnsan ve Bloglar', 22, 'Kişisel vloglar ve yaşam tarzı içerikleri'),
(9, 'Comedy', 'Komedi', 23, 'Komedi videoları ve eğlenceli içerikler'),
(10, 'Entertainment', 'Eğlence', 24, 'Genel eğlence içerikleri'),
(11, 'News & Politics', 'Haber ve Politika', 25, 'Haber, güncel olaylar ve politik içerikler'),
(12, 'Howto & Style', 'Nasıl Yapılır ve Stil', 26, 'Öğretici videolar ve stil tavsiyeleri'),
(13, 'Education', 'Eğitim', 27, 'Eğitici içerikler ve online dersler'),
(14, 'Science & Technology', 'Bilim ve Teknoloji', 28, 'Bilim, teknoloji ve inovasyon içerikleri');

-- ======================================
-- YAYGIN ETİKETLERİ EKLE
-- ======================================
INSERT IGNORE INTO tags (name, usage_count) VALUES 
('youtube', 0),
('viral', 0),
('trend', 0),
('eğlence', 0),
('komedi', 0),
('müzik', 0),
('oyun', 0),
('eğitim', 0),
('teknoloji', 0),
('bilim', 0),
('spor', 0),
('seyahat', 0),
('vlog', 0),
('tutorial', 0),
('inceleme', 0),
('haberler', 0),
('güncel', 0),
('hayvan', 0),
('çocuk', 0),
('aile', 0);

-- ======================================
-- GÖRÜNÜMLER (VIEWS) OLUŞTUR
-- ======================================

-- Video istatistikleri özet görünümü
CREATE VIEW video_stats_summary AS
SELECT 
    v.id,
    v.title,
    v.youtube_video_id,
    v.channel_id,
    v.upload_date,
    v.status,
    v.view_count,
    v.like_count,
    v.comment_count,
    CASE 
        WHEN v.view_count > 0 THEN 
            ROUND(((v.like_count + v.comment_count) / v.view_count) * 100, 2)
        ELSE 0 
    END as engagement_rate,
    c.name_tr as category_name,
    u.name as user_name
FROM videos v
LEFT JOIN categories c ON v.category_id = c.youtube_category_id
LEFT JOIN users u ON v.user_id = u.id;

-- Günlük analitik özet görünümü
CREATE VIEW daily_analytics_summary AS
SELECT 
    DATE(a.date) as analytics_date,
    COUNT(DISTINCT a.video_id) as video_count,
    SUM(a.views) as total_views,
    SUM(a.likes) as total_likes,
    SUM(a.comments) as total_comments,
    AVG(a.engagement_rate) as avg_engagement_rate,
    SUM(a.watch_time_minutes) as total_watch_time
FROM analytics a
GROUP BY DATE(a.date)
ORDER BY analytics_date DESC;

-- Kullanıcı başına video istatistikleri
CREATE VIEW user_video_stats AS
SELECT 
    u.id as user_id,
    u.name as user_name,
    u.email,
    COUNT(v.id) as total_videos,
    COUNT(CASE WHEN v.status = 'uploaded' THEN 1 END) as uploaded_videos,
    COUNT(CASE WHEN v.status = 'failed' THEN 1 END) as failed_videos,
    SUM(v.view_count) as total_views,
    SUM(v.like_count) as total_likes,
    SUM(v.comment_count) as total_comments,
    AVG(CASE 
        WHEN v.view_count > 0 THEN 
            ((v.like_count + v.comment_count) / v.view_count) * 100
        ELSE 0 
    END) as avg_engagement_rate
FROM users u
LEFT JOIN videos v ON u.id = v.user_id
GROUP BY u.id, u.name, u.email;

-- ======================================
-- TEMİZLİK PROCEDÜRLERI
-- ======================================

-- Eski AI analiz cache'ini temizle
CREATE EVENT IF NOT EXISTS cleanup_ai_cache
ON SCHEDULE EVERY 1 DAY
DO
  DELETE FROM ai_analysis_cache WHERE expires_at < NOW();

-- Eski upload session'larını temizle
CREATE EVENT IF NOT EXISTS cleanup_old_sessions
ON SCHEDULE EVERY 1 HOUR
DO
  DELETE FROM upload_sessions 
  WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR) 
  AND status IN ('started', 'failed');

-- ======================================
-- İNDEKSLER VE OPTİMİZASYON
-- ======================================

-- Performans için ek indeksler
CREATE INDEX idx_videos_user_status ON videos(user_id, status);
CREATE INDEX idx_videos_upload_date_desc ON videos(upload_date DESC);
CREATE INDEX idx_analytics_video_date ON analytics(video_id, date DESC);
CREATE INDEX idx_videos_view_count_desc ON videos(view_count DESC);

-- ======================================
-- SAMPLE DATA (İsteğe bağlı test verisi)
-- ======================================

-- Test kullanıcısı ekle (sadece development için)
-- INSERT INTO users (google_id, email, name, avatar) VALUES 
-- ('test_google_id', 'test@example.com', 'Test Kullanıcı', 'https://example.com/avatar.jpg');

-- ======================================
-- VERITABANI YAPISI TAMAMLANDI
-- ====================================== 