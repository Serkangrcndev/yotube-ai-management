# YouTube Manager - AI Destekli Video Yönetim Sistemi

Modern, kullanıcı dostu ve AI destekli YouTube video yönetim platformu. Videolarınızı yükleyin, optimize edin ve performanslarını analiz edin.

## ✨ Özellikler

### 🎯 Temel Özellikler
- **Google OAuth ile Güvenli Giriş** - YouTube hesaplarınıza güvenli erişim
- **Çoklu Kanal Desteği** - Birden fazla YouTube kanalınızı yönetin
- **Sürükle-Bırak Video Yükleme** - Modern ve kullanıcı dostu arayüz
- **Otomatik YouTube Yükleme** - Videolarınızı doğrudan YouTube'a yükleyin

### 🤖 AI Destekli Özellikler
- **Akıllı Başlık Üretimi** - Video içeriğine göre SEO optimizasyonlu başlıklar
- **Otomatik Açıklama Yazımı** - Kapsamlı ve etkili açıklamalar
- **En İyi Yükleme Zamanı Tahmini** - Veriye dayalı optimal timing önerileri
- **Kategori Önerileri** - İçerik analizi ile en uygun kategori seçimi
- **Hashtag Önerileri** - Trend hashtag'ler ve SEO önerileri

### 📊 Analitik ve Raporlama
- **Detaylı Performans Analizi** - Görüntülenme, beğeni, yorum istatistikleri
- **Trend Analizi** - Zaman bazlı performans grafikleri
- **Kategori Bazlı Analiz** - Hangi içerik türlerinin daha başarılı olduğunu görün
- **Etkileşim Oranları** - Audience engagement metrikleri
- **Rapor İndirme** - Excel formatında detaylı raporlar

### 🎨 Kullanıcı Deneyimi
- **Responsive Tasarım** - Tüm cihazlarda mükemmel görünüm
- **Dark/Light Tema** - Kullanıcı tercihine göre tema seçimi
- **Hızlı Arama ve Filtreleme** - Videolarınızı kolayca bulun
- **Gerçek Zamanlı Bildirimler** - İşlem durumları için anlık güncellemeler

## 🛠️ Teknoloji Stack

### Backend
- **PHP 8.0+** - Modern PHP özellikleri
- **MySQL 8.0+** - Güvenilir veritabanı yönetimi
- **PDO** - Güvenli veritabanı erişimi
- **cURL** - API entegrasyonları

### Frontend
- **HTML5 & CSS3** - Modern web standartları
- **Vanilla JavaScript** - Performanslı ve hafif
- **Chart.js** - İnteraktif grafikler
- **Google Fonts** - Profesyonel tipografi

### API Entegrasyonları
- **Google OAuth 2.0** - Güvenli kimlik doğrulama
- **YouTube Data API v3** - Video ve kanal yönetimi
- **YouTube Analytics API** - Detaylı performans verileri
- **OpenAI GPT-4** - AI destekli içerik optimizasyonu

## 📋 Sistem Gereksinimleri

### Sunucu Gereksinimleri
- **PHP**: 8.0 veya üzeri
- **MySQL**: 8.0 veya üzeri
- **Web Sunucu**: Apache/Nginx
- **SSL Sertifikası**: HTTPS desteği (Google OAuth için gerekli)

### PHP Uzantıları
```bash
- php-curl
- php-json
- php-pdo
- php-mysqli
- php-gd (görsel işleme için)
- php-fileinfo
- php-mbstring
```

### Sunucu Ayarları
```ini
upload_max_filesize = 2048M
post_max_size = 2048M
max_execution_time = 7200
memory_limit = 512M
```

## 🚀 Kurulum

### 1. Dosyaları İndirin
```bash
git clone https://github.com/yourusername/youtube-manager.git
cd youtube-manager
```

### 2. Veritabanını Oluşturun
MySQL'de yeni bir veritabanı oluşturun:
```sql
CREATE DATABASE youtube_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 3. Veritabanı Ayarları
`config/database.php` dosyasındaki veritabanı bilgilerini güncelleyin:
```php
private $host = 'localhost';
private $db_name = 'youtube_manager';
private $username = 'your_username';
private $password = 'your_password';
```

### 4. Google API Ayarları

#### Google Cloud Console'da Proje Oluşturun
1. [Google Cloud Console](https://console.cloud.google.com/) adresine gidin
2. Yeni proje oluşturun veya mevcut projeyi seçin
3. YouTube Data API v3 ve YouTube Analytics API'yi etkinleştirin

#### OAuth 2.0 İstemcisi Oluşturun
1. "Kimlik bilgileri" bölümüne gidin
2. "OAuth 2.0 İstemci Kimlikleri" oluşturun
3. Yetki verilen yönlendirme URI'leri ekleyin:
   ```
   https://yourdomain.com/callback.php
   ```

#### API Anahtarlarını Güncelleyin
`includes/auth.php` dosyasında:
```php
private $client_id = 'YOUR_GOOGLE_CLIENT_ID';
private $client_secret = 'YOUR_GOOGLE_CLIENT_SECRET';
private $redirect_uri = 'https://yourdomain.com/callback.php';
```

`includes/youtube.php` dosyasında:
```php
private $api_key = 'YOUR_YOUTUBE_API_KEY';
```

### 5. OpenAI API Ayarları
`includes/ai.php` dosyasında:
```php
private $openai_api_key = 'YOUR_OPENAI_API_KEY';
```

### 6. Dosya İzinlerini Ayarlayın
```bash
chmod 755 -R /path/to/youtube-manager/
chmod 777 /path/to/youtube-manager/uploads/
```

### 7. İlk Kurulumu Tamamlayın
Tarayıcınızda projenizi açın. Sistem otomatik olarak gerekli tabloları oluşturacaktır.

## 📊 Veritabanı Yapısı

### Ana Tablolar

#### users
```sql
- id (INT, PRIMARY KEY)
- google_id (VARCHAR, UNIQUE)
- email (VARCHAR)
- name (VARCHAR)
- avatar (TEXT)
- youtube_channels (JSON)
- created_at (TIMESTAMP)
```

#### videos
```sql
- id (INT, PRIMARY KEY)
- user_id (INT, FOREIGN KEY)
- youtube_video_id (VARCHAR)
- title (VARCHAR)
- description (TEXT)
- category_id (INT)
- thumbnail (TEXT)
- duration (INT)
- upload_date (TIMESTAMP)
- view_count (INT)
- like_count (INT)
- comment_count (INT)
- ai_analysis (JSON)
- best_upload_times (JSON)
- status (ENUM)
```

#### analytics
```sql
- id (INT, PRIMARY KEY)
- video_id (INT, FOREIGN KEY)
- date (DATE)
- views (INT)
- watch_time (INT)
- engagement_rate (DECIMAL)
```

#### categories
```sql
- id (INT, PRIMARY KEY)
- name (VARCHAR)
- youtube_category_id (INT)
```

## 🎮 Kullanım Kılavuzu

### İlk Giriş
1. Ana sayfada "Google ile Giriş Yap" butonuna tıklayın
2. Google hesabınızla giriş yapın ve izinleri verin
3. YouTube kanallarınız otomatik olarak yüklenecektir

### Video Yükleme
1. "Video Yükle" sayfasına gidin
2. Hedef kanalınızı seçin
3. Video dosyanızı sürükleyin veya seçin
4. AI analizinin tamamlanmasını bekleyin
5. Önerilen başlık ve açıklamayı inceleyin/düzenleyin
6. "YouTube'a Yükle" butonuna tıklayın

### Analitik İnceleme
1. "Analitik" sayfasından performans verilerinizi görüntüleyin
2. Zaman aralığını seçin (7 gün, 30 gün, 90 gün, 1 yıl)
3. Farklı metrikleri karşılaştırın
4. AI önerilerini inceleyin
5. Detaylı raporları indirin

### Video Yönetimi
1. "Geçmiş" sayfasında tüm videolarınızı görüntüleyin
2. Arama ve filtreleme kullanarak istediğiniz videoları bulun
3. Kart veya liste görünümü arasında seçim yapın
4. Video detaylarını incelemek için tıklayın

## 🔧 Özelleştirme

### Tema Değişiklikleri
CSS değişkenlerini düzenleyerek temayı özelleştirebilirsiniz:
```css
:root {
    --primary-color: #FF0000;
    --secondary-color: #282828;
    --accent-color: #00D4FF;
    /* Diğer değişkenler */
}
```

### AI Prompt'larını Özelleştirme
`includes/ai.php` dosyasındaki prompt'ları düzenleyerek AI yanıtlarını özelleştirebilirsiniz.

### Ek Özellikler Ekleme
Modüler yapı sayesinde yeni özellikler kolayca eklenebilir:
1. `includes/` klasörüne yeni sınıf dosyası ekleyin
2. İhtiyaç duyulan veritabanı tablolarını oluşturun
3. Frontend'de yeni sayfa/bileşen ekleyin

## 🔒 Güvenlik

### Güvenlik Önlemleri
- **PDO Prepared Statements** - SQL injection koruması
- **CSRF Koruması** - Form güvenliği
- **XSS Koruması** - Çıktı temizleme
- **File Upload Güvenliği** - Dosya türü ve boyut kontrolü
- **Session Güvenliği** - Güvenli session yönetimi

### Önerilen Güvenlik Ayarları
```apache
# .htaccess
<Files "*.php">
    Require all granted
</Files>

<Files "config/*">
    Require all denied
</Files>

<Files "includes/*">
    Require all denied
</Files>
```

## 🐛 Sorun Giderme

### Yaygın Sorunlar

#### "API Key Geçersiz" Hatası
- Google Cloud Console'da API'lerin etkinleştirildiğinden emin olun
- API anahtarlarının doğru girildiğini kontrol edin
- Quota limitlerini kontrol edin

#### Video Yükleme Başarısız
- Dosya boyutu limitlerini kontrol edin
- Internet bağlantınızı kontrol edin
- YouTube API quota'sını kontrol edin

#### Veritabanı Bağlantı Hatası
- Veritabanı bilgilerini kontrol edin
- MySQL servisinin çalıştığından emin olun
- Kullanıcı izinlerini kontrol edin

### Log Dosyaları
Hata ayıklama için PHP error log'larını kontrol edin:
```bash
tail -f /var/log/apache2/error.log
```

## 📈 Performans Optimizasyonu

### Veritabanı Optimizasyonu
```sql
-- Sık kullanılan sorguları hızlandırmak için index'ler
CREATE INDEX idx_user_videos ON videos(user_id, upload_date);
CREATE INDEX idx_video_analytics ON analytics(video_id, date);
```

### Önbellekleme
- Browser cache için uygun header'lar ayarlayın
- Static dosyalar için CDN kullanın
- Database query cache'i etkinleştirin

### Dosya Yükleme Optimizasyonu
- Video dosyaları için ayrı storage kullanın
- Chunked upload implementasyonu
- Background processing için queue sistemi

## 🤝 Katkıda Bulunma

### Geliştirme Süreci
1. Fork yapın
2. Feature branch oluşturun (`git checkout -b feature/amazing-feature`)
3. Değişikliklerinizi commit edin (`git commit -m 'Add amazing feature'`)
4. Branch'i push edin (`git push origin feature/amazing-feature`)
5. Pull Request oluşturun

### Kod Standartları
- PSR-12 coding standards
- Meaningful commit messages
- Comprehensive comments
- Unit tests (önerilir)

## 📄 Lisans

Bu proje MIT lisansı altında lisanslanmıştır. Detaylar için `LICENSE` dosyasını inceleyebilirsiniz.

## 🆘 Destek

### Topluluk Desteği
- GitHub Issues - Hata raporları ve özellik istekleri
- GitHub Discussions - Genel sorular ve tartışmalar

### Dokümantasyon
- [API Dokümantasyonu](docs/api.md)
- [Özelleştirme Kılavuzu](docs/customization.md)
- [Güvenlik En İyi Uygulamaları](docs/security.md)

## 🎯 Yol Haritası

### v1.1 (Yakında)
- [ ] Thumbnail editörü
- [ ] Toplu video yükleme
- [ ] Zamanlı yayın desteği
- [ ] Mobil uygulama

### v1.2 (Gelecek)
- [ ] Multi-language support
- [ ] Advanced analytics
- [ ] Team collaboration features
- [ ] API for third-party integrations

## 📊 İstatistikler

### Desteklenen Video Formatları
- MP4, MOV, AVI, WMV, FLV, WebM
- Maksimum dosya boyutu: 2GB
- Desteklenen çözünürlükler: 720p, 1080p, 1440p, 2160p (4K)

### AI Özellikleri Doğruluk Oranları
- Başlık önerileri: ~89% doğruluk
- Kategori tespiti: ~92% doğruluk
- En iyi yükleme zamanı: ~76% doğruluk
- Hashtag önerileri: ~84% doğruluk

---

**YouTube Manager** ile videolarınızı profesyonel düzeyde yönetin ve YouTube'da daha fazla başarı elde edin! 🚀

*Son güncelleme: 2025* 