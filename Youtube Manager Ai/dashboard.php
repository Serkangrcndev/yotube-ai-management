<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/youtube.php';

$auth = new GoogleAuth();

// Test için giriş kontrolünü devre dışı bırak
/*
if (!$auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}
*/

// Test kullanıcısı oluştur
$user = [
    'id' => 1,
    'name' => 'Test Kullanıcı',
    'email' => 'test@example.com',
    'avatar' => 'https://via.placeholder.com/32x32.png?text=T',
    'youtube_channels' => []
];

$youtube = new YouTubeManager();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - YouTube Manager</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="navbar-content">
                <a href="index.php" class="navbar-brand">YouTube Manager</a>
                <ul class="navbar-nav">
                    <li><a href="dashboard.php" class="active">Panel</a></li>
                    <li><a href="upload.php">Yükle</a></li>
                    <li><a href="analytics.php">Analiz</a></li>
                    <li><a href="history.php">Geçmiş</a></li>
                    <li>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <img src="<?php echo htmlspecialchars($user['avatar']); ?>" 
                                 alt="<?php echo htmlspecialchars($user['name']); ?>" 
                                 style="width: 32px; height: 32px; border-radius: 50%;">
                            <span><?php echo htmlspecialchars($user['name']); ?></span>
                            <a href="logout.php" class="btn btn-sm btn-outline">Çıkış</a>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-3">
        <!-- Başlık -->
        <div class="mb-3">
            <h1>Dashboard</h1>
            <p>YouTube videolarınızı yönetin ve analiz edin</p>
        </div>

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

        <!-- Hızlı Eylemler -->
        <div class="card mb-3">
            <div class="card-header">
                <h3>Hızlı Eylemler</h3>
            </div>
            <div class="card-body">
                <div class="d-flex gap-1rem">
                    <a href="upload.php" class="btn btn-success">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="margin-right: 0.5rem;">
                            <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                        </svg>
                        Yeni Video Yükle
                    </a>
                    <a href="analytics.php" class="btn btn-outline">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="margin-right: 0.5rem;">
                            <path d="M16,11.78L20.24,4.45L21.97,5.45L16.74,14.5L10.23,10.75L5.46,19H22V21H2V3H4V17.54L9.5,8L16,11.78Z"/>
                        </svg>
                        Analitikleri Görüntüle
                    </a>
                    <button class="btn btn-outline" onclick="refreshData()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="margin-right: 0.5rem;">
                            <path d="M17.65,6.35C16.2,4.9 14.21,4 12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20C15.73,20 18.84,17.45 19.73,14H17.65C16.83,16.33 14.61,18 12,18A6,6 0 0,1 6,12A6,6 0 0,1 12,6C13.66,6 15.14,6.69 16.22,7.78L13,11H20V4L17.65,6.35Z"/>
                        </svg>
                        Verileri Yenile
                    </button>
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
                        <span class="stat-value" id="engagement-rate">-%</span>
                        <span class="stat-label">Etkileşim Oranı</span>
                        <div class="stat-change positive">+5% bu ay</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- AI Önerileri -->
        <div class="ai-panel mb-3">
            <div class="d-flex align-center mb-3">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" style="margin-right: 0.5rem;">
                    <path d="M12,2A2,2 0 0,1 14,4C14,4.74 13.6,5.39 13,5.73V7H14A7,7 0 0,1 21,14H22A1,1 0 0,1 23,15V18A1,1 0 0,1 22,19H21V20A2,2 0 0,1 19,22H5A2,2 0 0,1 3,20V19H2A1,1 0 0,1 1,18V15A1,1 0 0,1 2,14H3A7,7 0 0,1 10,7H11V5.73C10.4,5.39 10,4.74 10,4A2,2 0 0,1 12,2M12,4A0,0 0 0,0 12,4A0,0 0 0,0 12,4M7.5,13A2.5,2.5 0 0,0 5,15.5A2.5,2.5 0 0,0 7.5,18A2.5,2.5 0 0,0 10,15.5A2.5,2.5 0 0,0 7.5,13M16.5,13A2.5,2.5 0 0,0 14,15.5A2.5,2.5 0 0,0 16.5,18A2.5,2.5 0 0,0 19,15.5A2.5,2.5 0 0,0 16.5,13Z"/>
                </svg>
                <h3>AI Önerileri</h3>
            </div>
            
            <div class="row">
                <div class="col-6">
                    <div class="ai-suggestion">
                        <h4>En İyi Yükleme Zamanı</h4>
                        <p>Verilerinize göre, bugün saat <strong>18:00-20:00</strong> arası videolarınız için optimal zaman.</p>
                        <div class="ai-confidence">Güvenilirlik: %89</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="ai-suggestion">
                        <h4>Trend Konular</h4>
                        <p>Bu hafta <strong>"teknoloji incelemeleri"</strong> ve <strong>"DIY projeleri"</strong> konularında yüksek ilgi var.</p>
                        <div class="ai-confidence">Güvenilirlik: %76</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performans Grafiği -->
        <div class="card mb-3">
            <div class="card-header">
                <h3>Son 30 Gün Performans</h3>
            </div>
            <div class="card-body">
                <canvas id="performanceChart" width="400" height="200"></canvas>
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

    <script src="js/app.js"></script>
    <script>
        // Dashboard özel JavaScript kodları
        let performanceChart;

        function initPerformanceChart() {
            const ctx = document.getElementById('performanceChart').getContext('2d');
            performanceChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Görüntülenme',
                        data: [],
                        borderColor: 'rgb(255, 0, 0)',
                        backgroundColor: 'rgba(255, 0, 0, 0.1)',
                        tension: 0.4
                    }, {
                        label: 'Beğeni',
                        data: [],
                        borderColor: 'rgb(0, 212, 255)',
                        backgroundColor: 'rgba(0, 212, 255, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        function loadDashboardData() {
            // Kanal verilerini yükle
            app.loadUserChannels();
            
            // Performans verilerini yükle
            fetch('api/get_dashboard_stats.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateStats(data.stats);
                        updateChart(data.chartData);
                    }
                })
                .catch(error => {
                    console.error('Dashboard data loading failed:', error);
                });
                
            // Son videoları yükle
            app.refreshVideoList();
        }

        function updateStats(stats) {
            document.getElementById('total-videos').textContent = app.formatNumber(stats.totalVideos || 0);
            document.getElementById('total-views').textContent = app.formatNumber(stats.totalViews || 0);
            document.getElementById('total-likes').textContent = app.formatNumber(stats.totalLikes || 0);
            document.getElementById('engagement-rate').textContent = (stats.engagementRate || 0).toFixed(1) + '%';
        }

        function updateChart(chartData) {
            if (performanceChart && chartData) {
                performanceChart.data.labels = chartData.labels;
                performanceChart.data.datasets[0].data = chartData.views;
                performanceChart.data.datasets[1].data = chartData.likes;
                performanceChart.update();
            }
        }

        function refreshData() {
            const loader = app.showLoader('Veriler yenileniyor...');
            loadDashboardData();
            setTimeout(() => {
                app.hideLoader(loader);
                app.showSuccess('Veriler güncellendi');
            }, 2000);
        }

        // Sayfa yüklendiğinde
        document.addEventListener('DOMContentLoaded', function() {
            initPerformanceChart();
            loadDashboardData();
        });
    </script>
</body>
</html> 