<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'youtube_manager';
    private $username = 'root';
    private $password = '';
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                                $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Bağlantı hatası: " . $exception->getMessage();
        }
        return $this->conn;
    }

    public function createTables() {
        $queries = [
            "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                google_id VARCHAR(255) UNIQUE,
                email VARCHAR(255),
                name VARCHAR(255),
                avatar TEXT,
                youtube_channels JSON,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            
            "CREATE TABLE IF NOT EXISTS videos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                youtube_video_id VARCHAR(255),
                title VARCHAR(500),
                description TEXT,
                category_id INT,
                thumbnail TEXT,
                duration INT,
                upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                view_count INT DEFAULT 0,
                like_count INT DEFAULT 0,
                comment_count INT DEFAULT 0,
                ai_analysis JSON,
                best_upload_times JSON,
                status ENUM('uploaded', 'processing', 'failed') DEFAULT 'processing',
                FOREIGN KEY (user_id) REFERENCES users(id)
            )",
            
            "CREATE TABLE IF NOT EXISTS analytics (
                id INT AUTO_INCREMENT PRIMARY KEY,
                video_id INT,
                date DATE,
                views INT DEFAULT 0,
                watch_time INT DEFAULT 0,
                engagement_rate DECIMAL(5,2) DEFAULT 0,
                FOREIGN KEY (video_id) REFERENCES videos(id)
            )",
            
            "CREATE TABLE IF NOT EXISTS categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255),
                youtube_category_id INT
            )",
            
            "INSERT IGNORE INTO categories (id, name, youtube_category_id) VALUES 
            (1, 'Film ve Animasyon', 1),
            (2, 'Otomobil ve Araçlar', 2),
            (3, 'Müzik', 10),
            (4, 'Evcil Hayvanlar ve Hayvanlar', 15),
            (5, 'Spor', 17),
            (6, 'Seyahat ve Etkinlikler', 19),
            (7, 'Oyun', 20),
            (8, 'İnsan ve Bloglar', 22),
            (9, 'Komedi', 23),
            (10, 'Eğlence', 24),
            (11, 'Haber ve Politika', 25),
            (12, 'Nasıl Yapılır ve Stil', 26),
            (13, 'Eğitim', 27),
            (14, 'Bilim ve Teknoloji', 28)"
        ];

        foreach ($queries as $query) {
            try {
                $this->conn->exec($query);
            } catch(PDOException $e) {
                echo "Tablo oluşturma hatası: " . $e->getMessage();
            }
        }
    }
}
?> 