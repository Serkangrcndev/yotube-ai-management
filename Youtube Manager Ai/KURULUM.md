# YouTube Manager - Kurulum Kılavuzu

## 📁 Proje Dosya Yapısı

```
htdocs/
├── api/                          # API Endpoint'leri
│   ├── analyze_video.php        # Video AI analizi
│   ├── delete_video.php         # Video silme
│   ├── get_dashboard_stats.php  # Dashboard istatistikleri
│   ├── get_user_channels.php    # Kullanıcı kanalları
│   ├── get_video_history.php    # Video geçmişi
│   └── upload_video.php         # Video yükleme
├── config/
│   └── database.php             # Veritabanı bağlantı sınıfı
├── css/
│   └── style.css                # Tüm CSS stilleri
├── includes/
│   ├── ai.php                   # AI analiz sınıfı
│   ├── auth.php                 # Google OAuth sınıfı
│   └── youtube.php              # YouTube API sınıfı
├── js/
│   └── app.js                   # JavaScript uygulama
├── uploads/                     # Geçici video dosyaları
│   └── .htaccess               # Güvenlik ayarları
├── analytics.php                # Analitik sayfası
├── callback.php                 # OAuth callback
├── dashboard.php                # Ana dashboard
├── database.sql                 # Veritabanı yapısı
├── history.php                  # Video geçmişi
├── index.php                    # Ana sayfa
├── logout.php                   # Çıkış işlemi
├── README.md                    # Genel dokumentasyon
├── upload.php                   # Video yükleme
└── KURULUM.md                   # Bu dosya
```

## 🚀 Hızlı Kurulum

### 1. Veritabanı Kurulumu
```bash
# MySQL'e bağlan
mysql -u root -p

# SQL dosyasını çalıştır
source database.sql
```

### 2. Google API Ayarları
1. Google Cloud Console'da proje oluşturun
2. YouTube Data API v3'ü aktifleştirin
3. OAuth 2.0 credentials oluşturun
4. `includes/auth.php` dosyasında API anahtarlarını güncelleyin

### 3. AI Entegrasyonu
`includes/ai.php` dosyasında OpenAI API anahtarınızı ayarlayın

### 4. Sunucu Ayarları
- PHP 7.4+ gerekli
- MySQL 5.7+ gerekli
- cURL ve JSON extension'ları aktif olmalı
- `uploads/` klasörü yazılabilir olmalı (755)

## 📦 Gerekli Bileşenler

### PHP Extensions
- PDO
- JSON
- cURL
- OpenSSL
- mbstring

### Harici Kütüphaneler
- Chart.js (CDN)
- Tailwind CSS (CDN)

## 🔧 Yapılandırma

### Veritabanı Bağlantısı
`config/database.php` dosyasında:
```php
private $host = 'localhost';
private $db_name = 'youtube_manager';
private $username = 'root';
private $password = '';
```

### Google OAuth
`includes/auth.php` dosyasında:
```php
private $clientId = 'YOUR_CLIENT_ID';
private $clientSecret = 'YOUR_CLIENT_SECRET';
private $redirectUri = 'http://localhost/youtube-manager/callback.php';
```

### OpenAI API
`includes/ai.php` dosyasında:
```php
private $apiKey = 'YOUR_OPENAI_API_KEY';
```

## 🛡️ Güvenlik

- Tüm kullanıcı girdileri sanitize edilir
- PDO prepared statements kullanılır
- CSRF token'ları ile korunur
- Upload klasörü PHP execution'dan korunur
- Session güvenliği sağlanır

## 📊 Özellikler

✅ **Tamamlanan Özellikler:**
- Google OAuth ile giriş
- YouTube kanal entegrasyonu
- AI destekli video analizi
- Otomatik başlık/açıklama üretimi
- Video yükleme sistemi
- Detaylı analitikler
- Responsive tasarım
- Video geçmişi yönetimi

🔄 **Geliştirilmekte:**
- Toplu video yükleme
- Zamanlı yayınlama
- Thumbnail önerileri
- A/B test desteği

## 📞 Destek

Kurulum sırasında sorun yaşarsanız:
1. PHP error_log'ları kontrol edin
2. Browser console'u inceleyin  
3. Veritabanı bağlantısını test edin
4. API anahtarlarını doğrulayın

## 🔄 Güncellemeler

Proje düzenli olarak güncellenir. Son güncellemeler için:
- README.md dosyasını takip edin
- Commit geçmişini inceleyin
- Yeni özellikler için changelog'u kontrol edin 