<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

// Veritabanı bağlantısını başlat ve tabloları oluştur
$database = new Database();
$db = $database->getConnection();
$database->createTables();

$auth = new GoogleAuth();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YouTube Manager - AI Destekli Video Yönetimi</title>
    <meta name="description" content="YouTube videolarınızı AI destekli olarak yönetin, analiz edin ve optimize edin">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    
    <!-- CSS -->
    <link rel="stylesheet" href="css/style.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS CDN (for additional utilities) -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Chart.js for analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div id="app">
        <?php if ($auth->isLoggedIn()): ?>
            <!-- Ana Dashboard -->
            <nav class="navbar">
                <div class="container">
                    <div class="navbar-content">
                        <a href="index.php" class="navbar-brand">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                            </svg>
                            YouTube Manager
                        </a>
                        
                        <ul class="navbar-nav">
                            <li><a href="dashboard.php" data-nav="dashboard">Panel</a></li>
                            <li><a href="upload.php" data-nav="upload">Yükle</a></li>
                            <li><a href="analytics.php" data-nav="analytics">Analiz</a></li>
                            <li><a href="history.php" data-nav="history">Geçmiş</a></li>
                            <li>
                                <div class="user-menu">
                                    <?php $user = $auth->getCurrentUser(); ?>
                                    <img src="<?php echo htmlspecialchars($user['avatar']); ?>" 
                                         alt="<?php echo htmlspecialchars($user['name']); ?>" 
                                         class="user-avatar" 
                                         width="32" height="32">
                                    <span><?php echo htmlspecialchars($user['name']); ?></span>
                                    <a href="logout.php" class="btn btn-sm btn-outline">Çıkış</a>
                                </div>
                            </li>
                        </ul>
                        
                        <button class="navbar-toggle" id="mobile-menu-toggle">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M3 12h18M3 6h18M3 18h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </nav>

            <div class="main-container">
                <!-- Sidebar -->
                <aside class="sidebar" id="sidebar">
                    <div class="sidebar-header">
                        <h3>Menü</h3>
                    </div>
                    
                    <ul class="sidebar-menu">
                        <li>
                            <a href="dashboard.php" data-nav="dashboard" class="active">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/>
                                </svg>
                                Dashboard
                            </a>
                        </li>
                        <li>
                            <a href="upload.php" data-nav="upload">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                                </svg>
                                Video Yükle
                            </a>
                        </li>
                        <li>
                            <a href="analytics.php" data-nav="analytics">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M16,11.78L20.24,4.45L21.97,5.45L16.74,14.5L10.23,10.75L5.46,19H22V21H2V3H4V17.54L9.5,8L16,11.78Z"/>
                                </svg>
                                Analitik
                            </a>
                        </li>
                        <li>
                            <a href="history.php" data-nav="history">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M13.5,8H12V13L16.28,15.54L17,14.33L13.5,12.25V8M13,3A9,9 0 0,0 4,12H1L4.96,16.03L9,12H6A7,7 0 0,1 13,5A7,7 0 0,1 20,12A7,7 0 0,1 13,19C11.07,19 9.32,18.21 8.06,16.94L6.64,18.36C8.27,20 10.5,21 13,21A9,9 0 0,0 22,12A9,9 0 0,0 13,3"/>
                                </svg>
                                Video Geçmişi
                            </a>
                        </li>
                        <li>
                            <a href="#" onclick="app.showChannelSettings()">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12,15.5A3.5,3.5 0 0,1 8.5,12A3.5,3.5 0 0,1 12,8.5A3.5,3.5 0 0,1 15.5,12A3.5,3.5 0 0,1 12,15.5M19.43,12.97C19.47,12.65 19.5,12.33 19.5,12C19.5,11.67 19.47,11.34 19.43,11L21.54,9.37C21.73,9.22 21.78,8.95 21.66,8.73L19.66,5.27C19.54,5.05 19.27,4.96 19.05,5.05L16.56,6.05C16.04,5.66 15.5,5.32 14.87,5.07L14.5,2.42C14.46,2.18 14.25,2 14,2H10C9.75,2 9.54,2.18 9.5,2.42L9.13,5.07C8.5,5.32 7.96,5.66 7.44,6.05L4.95,5.05C4.73,4.96 4.46,5.05 4.34,5.27L2.34,8.73C2.22,8.95 2.27,9.22 2.46,9.37L4.57,11C4.53,11.34 4.5,11.67 4.5,12C4.5,12.33 4.53,12.65 4.57,12.97L2.46,14.63C2.27,14.78 2.22,15.05 2.34,15.27L4.34,18.73C4.46,18.95 4.73,19.03 4.95,18.95L7.44,17.94C7.96,18.34 8.5,18.68 9.13,18.93L9.5,21.58C9.54,21.82 9.75,22 10,22H14C14.25,22 14.46,21.82 14.5,21.58L14.87,18.93C15.5,18.68 16.04,18.34 16.56,17.94L19.05,18.95C19.27,19.03 19.54,18.95 19.66,18.73L21.66,15.27C21.78,15.05 21.73,14.78 21.54,14.63L19.43,12.97Z"/>
                                </svg>
                                Ayarlar
                            </a>
                        </li>
                    </ul>
                    
                    <button class="sidebar-toggle" id="sidebar-toggle">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M3,6H21V8H3V6M3,11H21V13H3V11M3,16H21V18H3V16Z"/>
                        </svg>
                    </button>
                </aside>

                <!-- Ana İçerik -->
                <main class="main-content" id="main-content">
                    <div class="content-header">
                        <div class="d-flex justify-between align-center">
                            <div>
                                <h1>Dashboard</h1>
                                <p>YouTube videolarınızı yönetin ve analiz edin</p>
                            </div>
                            <div>
                                <button class="btn btn-success" onclick="window.location.href='upload.php'">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="margin-right: 0.5rem;">
                                        <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                                    </svg>
                                    Yeni Video Yükle
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="content-body">
                        <!-- Kanal Seçici -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h3>Kanal Seçimi</h3>
                            </div>
                            <div class="card-body">
                                <div class="channel-selector" id="channel-selector">
                                    <div class="text-center">
                                        <div class="spinner"></div>
                                        <p>Kanallarınız yükleniyor...</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- İstatistikler -->
                        <div class="row mb-3">
                            <div class="col-3">
                                <div class="card">
                                    <div class="card-body stat-card">
                                        <span class="stat-value" id="total-videos">-</span>
                                        <span class="stat-label">Toplam Video</span>
                                        <div class="stat-change positive">+12% bu ay</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="card">
                                    <div class="card-body stat-card">
                                        <span class="stat-value" id="total-views">-</span>
                                        <span class="stat-label">Toplam Görüntülenme</span>
                                        <div class="stat-change positive">+8% bu ay</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="card">
                                    <div class="card-body stat-card">
                                        <span class="stat-value" id="total-likes">-</span>
                                        <span class="stat-label">Toplam Beğeni</span>
                                        <div class="stat-change positive">+15% bu ay</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="card">
                                    <div class="card-body stat-card">
                                        <span class="stat-value" id="engagement-rate">-</span>
                                        <span class="stat-label">Etkileşim Oranı</span>
                                        <div class="stat-change positive">+5% bu ay</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- AI Önerileri -->
                        <div class="ai-panel" id="ai-recommendations">
                            <div class="ai-title">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12,2A2,2 0 0,1 14,4C14,4.74 13.6,5.39 13,5.73V7H14A7,7 0 0,1 21,14H22A1,1 0 0,1 23,15V18A1,1 0 0,1 22,19H21V20A2,2 0 0,1 19,22H5A2,2 0 0,1 3,20V19H2A1,1 0 0,1 1,18V15A1,1 0 0,1 2,14H3A7,7 0 0,1 10,7H11V5.73C10.4,5.39 10,4.74 10,4A2,2 0 0,1 12,2M12,4A0,0 0 0,0 12,4A0,0 0 0,0 12,4M7.5,13A2.5,2.5 0 0,0 5,15.5A2.5,2.5 0 0,0 7.5,18A2.5,2.5 0 0,0 10,15.5A2.5,2.5 0 0,0 7.5,13M16.5,13A2.5,2.5 0 0,0 14,15.5A2.5,2.5 0 0,0 16.5,18A2.5,2.5 0 0,0 19,15.5A2.5,2.5 0 0,0 16.5,13Z"/>
                                </svg>
                                AI Önerileri
                            </div>
                            <div class="ai-suggestion">
                                <h4>En İyi Yükleme Zamanı</h4>
                                <p>Verilerinize göre, bugün saat 18:00-20:00 arası videolarınız için optimal zaman.</p>
                                <div class="ai-confidence">Güvenilirlik: %89</div>
                            </div>
                            <div class="ai-suggestion">
                                <h4>Trend Konular</h4>
                                <p>Bu hafta "teknoloji incelemeleri" ve "DIY projeleri" konularında yüksek ilgi var.</p>
                                <div class="ai-confidence">Güvenilirlik: %76</div>
                            </div>
                        </div>

                        <!-- Son Videolar -->
                        <div class="card">
                            <div class="card-header">
                                <div class="d-flex justify-between align-center">
                                    <h3>Son Videolarınız</h3>
                                    <a href="history.php" class="btn btn-outline">Tümünü Gör</a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="video-grid" id="recent-videos">
                                    <div class="text-center">
                                        <div class="spinner"></div>
                                        <p>Videolarınız yükleniyor...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>
            </div>

        <?php else: ?>
            <!-- Giriş Ekranı -->
            <div class="login-container">
                <div class="container">
                    <div class="row">
                        <div class="col-6">
                            <div class="login-hero">
                                <h1 class="animate-fade-in-up">YouTube Manager</h1>
                                <h2 class="animate-fade-in-up" style="animation-delay: 0.2s;">AI Destekli Video Yönetimi</h2>
                                <p class="animate-fade-in-up" style="animation-delay: 0.4s;">
                                    Videolarınızı yükleyin, AI ile optimize edin ve YouTube'da daha fazla görüntülenme alın.
                                </p>
                                
                                <div class="feature-list animate-fade-in-up" style="animation-delay: 0.6s;">
                                    <div class="feature-item">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M9,22A1,1 0 0,1 8,21V18H4A2,2 0 0,1 2,16V4C2,2.89 2.9,2 4,2H20A2,2 0 0,1 22,4V16A2,2 0 0,1 20,18H13.9L10.2,21.71C10,21.9 9.75,22 9.5,22V22H9M10,16V19.08L13.08,16H20V4H4V16H10Z"/>
                                        </svg>
                                        <span>AI ile başlık ve açıklama üretimi</span>
                                    </div>
                                    <div class="feature-item">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M16,11.78L20.24,4.45L21.97,5.45L16.74,14.5L10.23,10.75L5.46,19H22V21H2V3H4V17.54L9.5,8L16,11.78Z"/>
                                        </svg>
                                        <span>Detaylı performans analizi</span>
                                    </div>
                                    <div class="feature-item">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M12,20A8,8 0 0,0 20,12A8,8 0 0,0 12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22C6.47,22 2,17.5 2,12A10,10 0 0,1 12,2M12.5,7V12.25L17,14.92L16.25,16.15L11,13V7H12.5Z"/>
                                        </svg>
                                        <span>Optimal yükleme zamanı önerisi</span>
                                    </div>
                                    <div class="feature-item">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                                        </svg>
                                        <span>Otomatik YouTube yükleme</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="login-form-container animate-fade-in-right">
                                <div class="login-form">
                                    <h3>Hemen Başlayın</h3>
                                    <p>Google hesabınızla giriş yapın ve YouTube kanallarınıza erişim sağlayın.</p>
                                    
                                    <a href="<?php echo $auth->getAuthUrl(); ?>" class="btn btn-lg google-login-btn">
                                        <svg width="20" height="20" viewBox="0 0 24 24" style="margin-right: 0.5rem;">
                                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                        </svg>
                                        Google ile Giriş Yap
                                    </a>
                                    
                                    <div class="login-help">
                                        <p><strong>Güvenli ve Hızlı:</strong></p>
                                        <ul>
                                            <li>Verileriniz güvende</li>
                                            <li>Sadece gerekli izinler</li>
                                            <li>Anında erişim</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- JavaScript -->
    <script src="js/app.js"></script>
</body>
</html> 