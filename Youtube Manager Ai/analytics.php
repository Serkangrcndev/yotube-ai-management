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
    <title>Analitik - YouTube Manager</title>
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
                    <li><a href="dashboard.php">Panel</a></li>
                    <li><a href="upload.php">Yükle</a></li>
                    <li><a href="analytics.php" class="active">Analiz</a></li>
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
        <!-- Başlık ve Filtreler -->
        <div class="d-flex justify-between align-center mb-3">
            <div>
                <h1>Analitik</h1>
                <p>Videolarınızın performansını detaylı şekilde analiz edin</p>
            </div>
            <div class="analytics-filters">
                <select class="form-control" id="time-range">
                    <option value="7">Son 7 Gün</option>
                    <option value="30" selected>Son 30 Gün</option>
                    <option value="90">Son 90 Gün</option>
                    <option value="365">Son 1 Yıl</option>
                </select>
                <button class="btn btn-outline" onclick="exportAnalytics()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="margin-right: 0.5rem;">
                        <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                    </svg>
                    Rapor İndir
                </button>
            </div>
        </div>

        <!-- Özet İstatistikler -->
        <div class="row mb-3">
            <div class="col-3">
                <div class="card">
                    <div class="card-body stat-card">
                        <span class="stat-value" id="total-views">-</span>
                        <span class="stat-label">Toplam Görüntülenme</span>
                        <div class="stat-change" id="views-change">-</div>
                    </div>
                </div>
            </div>
            <div class="col-3">
                <div class="card">
                    <div class="card-body stat-card">
                        <span class="stat-value" id="total-watchtime">-</span>
                        <span class="stat-label">İzlenme Süresi</span>
                        <div class="stat-change" id="watchtime-change">-</div>
                    </div>
                </div>
            </div>
            <div class="col-3">
                <div class="card">
                    <div class="card-body stat-card">
                        <span class="stat-value" id="avg-engagement">-%</span>
                        <span class="stat-label">Ortalama Etkileşim</span>
                        <div class="stat-change" id="engagement-change">-</div>
                    </div>
                </div>
            </div>
            <div class="col-3">
                <div class="card">
                    <div class="card-body stat-card">
                        <span class="stat-value" id="subscriber-growth">-</span>
                        <span class="stat-label">Abone Artışı</span>
                        <div class="stat-change" id="subscriber-change">-</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ana Performans Grafiği -->
        <div class="card mb-3">
            <div class="card-header">
                <div class="d-flex justify-between align-center">
                    <h3>Performans Trendi</h3>
                    <div class="chart-controls">
                        <select class="form-control" id="metric-selector">
                            <option value="views">Görüntülenme</option>
                            <option value="watchtime">İzlenme Süresi</option>
                            <option value="engagement">Etkileşim</option>
                            <option value="subscribers">Abone</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <canvas id="performanceTrendChart" height="100"></canvas>
            </div>
        </div>

        <!-- Video Performans Analizi -->
        <div class="row mb-3">
            <div class="col-6">
                <div class="card">
                    <div class="card-header">
                        <h3>En İyi Performans Gösteren Videolar</h3>
                    </div>
                    <div class="card-body">
                        <canvas id="topVideosChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card">
                    <div class="card-header">
                        <h3>Kategori Bazlı Performans</h3>
                    </div>
                    <div class="card-body">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Zaman Analizi -->
        <div class="row mb-3">
            <div class="col-6">
                <div class="card">
                    <div class="card-header">
                        <h3>Saatlik İzleyici Aktivitesi</h3>
                    </div>
                    <div class="card-body">
                        <canvas id="hourlyActivityChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card">
                    <div class="card-header">
                        <h3>Haftalık Trend</h3>
                    </div>
                    <div class="card-body">
                        <canvas id="weeklyTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detaylı Video Listesi -->
        <div class="card mb-3">
            <div class="card-header">
                <div class="d-flex justify-between align-center">
                    <h3>Video Performans Detayları</h3>
                    <div class="table-controls">
                        <input type="text" class="form-control" placeholder="Video ara..." id="video-search">
                        <select class="form-control" id="sort-by">
                            <option value="views">Görüntülenme</option>
                            <option value="likes">Beğeni</option>
                            <option value="comments">Yorum</option>
                            <option value="date">Tarih</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table" id="video-performance-table">
                        <thead>
                            <tr>
                                <th>Video</th>
                                <th>Yayın Tarihi</th>
                                <th>Görüntülenme</th>
                                <th>İzlenme Süresi</th>
                                <th>Beğeni</th>
                                <th>Yorum</th>
                                <th>Etkileşim Oranı</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody id="video-table-body">
                            <tr>
                                <td colspan="8" class="text-center">
                                    <div class="spinner"></div>
                                    <p>Veriler yükleniyor...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- AI Önerileri -->
        <div class="ai-panel">
            <div class="d-flex align-center mb-3">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" style="margin-right: 0.5rem;">
                    <path d="M12,2A2,2 0 0,1 14,4C14,4.74 13.6,5.39 13,5.73V7H14A7,7 0 0,1 21,14H22A1,1 0 0,1 23,15V18A1,1 0 0,1 22,19H21V20A2,2 0 0,1 19,22H5A2,2 0 0,1 3,20V19H2A1,1 0 0,1 1,18V15A1,1 0 0,1 2,14H3A7,7 0 0,1 10,7H11V5.73C10.4,5.39 10,4.74 10,4A2,2 0 0,1 12,2M12,4A0,0 0 0,0 12,4A0,0 0 0,0 12,4M7.5,13A2.5,2.5 0 0,0 5,15.5A2.5,2.5 0 0,0 7.5,18A2.5,2.5 0 0,0 10,15.5A2.5,2.5 0 0,0 7.5,13M16.5,13A2.5,2.5 0 0,0 14,15.5A2.5,2.5 0 0,0 16.5,18A2.5,2.5 0 0,0 19,15.5A2.5,2.5 0 0,0 16.5,13Z"/>
                </svg>
                <h3>AI Performans Önerileri</h3>
            </div>
            
            <div class="row">
                <div class="col-4">
                    <div class="ai-suggestion">
                        <h4>İçerik Önerisi</h4>
                        <p id="content-suggestion">Verileriniz analiz ediliyor...</p>
                        <div class="ai-confidence" id="content-confidence">-</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="ai-suggestion">
                        <h4>Optimizasyon Önerisi</h4>
                        <p id="optimization-suggestion">Verileriniz analiz ediliyor...</p>
                        <div class="ai-confidence" id="optimization-confidence">-</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="ai-suggestion">
                        <h4>Timing Önerisi</h4>
                        <p id="timing-suggestion">Verileriniz analiz ediliyor...</p>
                        <div class="ai-confidence" id="timing-confidence">-</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script>
        // Analytics sayfa JavaScript'i
        let charts = {};
        let analyticsData = {};

        document.addEventListener('DOMContentLoaded', function() {
            initializeCharts();
            loadAnalyticsData();
            
            // Event listeners
            document.getElementById('time-range').addEventListener('change', loadAnalyticsData);
            document.getElementById('metric-selector').addEventListener('change', updatePerformanceChart);
            document.getElementById('video-search').addEventListener('input', filterVideoTable);
            document.getElementById('sort-by').addEventListener('change', sortVideoTable);
        });

        function initializeCharts() {
            // Performance Trend Chart
            const performanceCtx = document.getElementById('performanceTrendChart').getContext('2d');
            charts.performance = new Chart(performanceCtx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Görüntülenme',
                        data: [],
                        borderColor: 'rgb(255, 0, 0)',
                        backgroundColor: 'rgba(255, 0, 0, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });

            // Top Videos Chart
            const topVideosCtx = document.getElementById('topVideosChart').getContext('2d');
            charts.topVideos = new Chart(topVideosCtx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Görüntülenme',
                        data: [],
                        backgroundColor: 'rgba(255, 0, 0, 0.8)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });

            // Category Chart
            const categoryCtx = document.getElementById('categoryChart').getContext('2d');
            charts.category = new Chart(categoryCtx, {
                type: 'doughnut',
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: [
                            '#FF0000', '#00D4FF', '#00C851', '#FFB74D', 
                            '#FF5252', '#9C27B0', '#FF9800', '#4CAF50'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });

            // Hourly Activity Chart
            const hourlyCtx = document.getElementById('hourlyActivityChart').getContext('2d');
            charts.hourly = new Chart(hourlyCtx, {
                type: 'bar',
                data: {
                    labels: Array.from({length: 24}, (_, i) => i + ':00'),
                    datasets: [{
                        label: 'İzleyici Aktivitesi',
                        data: [],
                        backgroundColor: 'rgba(0, 212, 255, 0.8)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });

            // Weekly Trend Chart
            const weeklyCtx = document.getElementById('weeklyTrendChart').getContext('2d');
            charts.weekly = new Chart(weeklyCtx, {
                type: 'line',
                data: {
                    labels: ['Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'],
                    datasets: [{
                        label: 'Ortalama Görüntülenme',
                        data: [],
                        borderColor: 'rgb(0, 200, 81)',
                        backgroundColor: 'rgba(0, 200, 81, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }

        function loadAnalyticsData() {
            const timeRange = document.getElementById('time-range').value;
            const loader = app.showLoader('Analitik veriler yükleniyor...');

            fetch(`api/get_analytics.php?range=${timeRange}`)
                .then(response => response.json())
                .then(data => {
                    app.hideLoader(loader);
                    
                    if (data.success) {
                        analyticsData = data;
                        updateAllCharts(data);
                        updateStats(data.stats);
                        updateVideoTable(data.videos);
                        updateAISuggestions(data.aiSuggestions);
                    } else {
                        app.showError('Analitik veriler yüklenemedi: ' + data.error);
                    }
                })
                .catch(error => {
                    app.hideLoader(loader);
                    app.showError('Veri yükleme hatası: ' + error.message);
                });
        }

        function updateAllCharts(data) {
            // Performance trend
            charts.performance.data.labels = data.chartData.labels;
            charts.performance.data.datasets[0].data = data.chartData.views;
            charts.performance.update();

            // Top videos
            charts.topVideos.data.labels = data.topVideos.map(v => v.title.substring(0, 20) + '...');
            charts.topVideos.data.datasets[0].data = data.topVideos.map(v => v.views);
            charts.topVideos.update();

            // Category distribution
            charts.category.data.labels = data.categoryData.map(c => c.name);
            charts.category.data.datasets[0].data = data.categoryData.map(c => c.count);
            charts.category.update();

            // Hourly activity
            charts.hourly.data.datasets[0].data = data.hourlyActivity;
            charts.hourly.update();

            // Weekly trend
            charts.weekly.data.datasets[0].data = data.weeklyTrend;
            charts.weekly.update();
        }

        function updatePerformanceChart() {
            const metric = document.getElementById('metric-selector').value;
            
            if (analyticsData.chartData) {
                charts.performance.data.datasets[0].data = analyticsData.chartData[metric] || [];
                charts.performance.data.datasets[0].label = getMetricLabel(metric);
                charts.performance.update();
            }
        }

        function getMetricLabel(metric) {
            const labels = {
                'views': 'Görüntülenme',
                'watchtime': 'İzlenme Süresi',
                'engagement': 'Etkileşim',
                'subscribers': 'Abone'
            };
            return labels[metric] || metric;
        }

        function updateStats(stats) {
            document.getElementById('total-views').textContent = app.formatNumber(stats.totalViews || 0);
            document.getElementById('total-watchtime').textContent = formatWatchTime(stats.totalWatchtime || 0);
            document.getElementById('avg-engagement').textContent = (stats.avgEngagement || 0).toFixed(1) + '%';
            document.getElementById('subscriber-growth').textContent = app.formatNumber(stats.subscriberGrowth || 0);

            // Change indicators
            updateChangeIndicator('views-change', stats.viewsChange);
            updateChangeIndicator('watchtime-change', stats.watchtimeChange);
            updateChangeIndicator('engagement-change', stats.engagementChange);
            updateChangeIndicator('subscriber-change', stats.subscriberChange);
        }

        function updateChangeIndicator(elementId, change) {
            const element = document.getElementById(elementId);
            const isPositive = change >= 0;
            
            element.textContent = (isPositive ? '+' : '') + change.toFixed(1) + '%';
            element.className = 'stat-change ' + (isPositive ? 'positive' : 'negative');
        }

        function formatWatchTime(minutes) {
            if (minutes < 60) {
                return minutes.toFixed(0) + ' dk';
            } else if (minutes < 1440) {
                return (minutes / 60).toFixed(1) + ' sa';
            } else {
                return (minutes / 1440).toFixed(1) + ' gün';
            }
        }

        function updateVideoTable(videos) {
            const tbody = document.getElementById('video-table-body');
            
            if (!videos || videos.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center">Video bulunamadı</td></tr>';
                return;
            }

            tbody.innerHTML = videos.map(video => `
                <tr>
                    <td>
                        <div class="video-table-item">
                            <img src="${video.thumbnail || 'images/default-thumb.jpg'}" 
                                 alt="${video.title}" 
                                 style="width: 60px; height: 40px; object-fit: cover; border-radius: 4px;">
                            <div style="margin-left: 10px;">
                                <div class="video-title" style="font-weight: 500; font-size: 0.875rem;">
                                    ${video.title.length > 50 ? video.title.substring(0, 50) + '...' : video.title}
                                </div>
                                <div style="font-size: 0.75rem; color: var(--text-secondary);">
                                    ${formatDuration(video.duration)}
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>${app.formatDate(video.upload_date)}</td>
                    <td>${app.formatNumber(video.view_count)}</td>
                    <td>${formatWatchTime(video.watch_time || 0)}</td>
                    <td>${app.formatNumber(video.like_count)}</td>
                    <td>${app.formatNumber(video.comment_count)}</td>
                    <td>${calculateEngagementRate(video).toFixed(1)}%</td>
                    <td>
                        <button class="btn btn-sm btn-outline" onclick="viewVideoDetails('${video.id}')">
                            Detay
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        function formatDuration(seconds) {
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const secs = seconds % 60;
            
            if (hours > 0) {
                return `${hours}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
            }
            return `${minutes}:${secs.toString().padStart(2, '0')}`;
        }

        function calculateEngagementRate(video) {
            if (video.view_count === 0) return 0;
            return ((video.like_count + video.comment_count) / video.view_count) * 100;
        }

        function updateAISuggestions(suggestions) {
            if (suggestions) {
                document.getElementById('content-suggestion').textContent = suggestions.content || 'Öneri bulunamadı';
                document.getElementById('content-confidence').textContent = 'Güvenilirlik: ' + (suggestions.contentConfidence || 0) + '%';
                
                document.getElementById('optimization-suggestion').textContent = suggestions.optimization || 'Öneri bulunamadı';
                document.getElementById('optimization-confidence').textContent = 'Güvenilirlik: ' + (suggestions.optimizationConfidence || 0) + '%';
                
                document.getElementById('timing-suggestion').textContent = suggestions.timing || 'Öneri bulunamadı';
                document.getElementById('timing-confidence').textContent = 'Güvenilirlik: ' + (suggestions.timingConfidence || 0) + '%';
            }
        }

        function filterVideoTable() {
            const searchTerm = document.getElementById('video-search').value.toLowerCase();
            const rows = document.querySelectorAll('#video-table-body tr');
            
            rows.forEach(row => {
                const title = row.cells[0]?.textContent.toLowerCase() || '';
                row.style.display = title.includes(searchTerm) ? '' : 'none';
            });
        }

        function sortVideoTable() {
            const sortBy = document.getElementById('sort-by').value;
            // Implement sorting logic based on selected criteria
            // This would require re-fetching and re-rendering the table
            loadAnalyticsData();
        }

        function viewVideoDetails(videoId) {
            // Open video details modal or navigate to detail page
            app.showInfo('Video detay sayfası geliştirme aşamasında');
        }

        function exportAnalytics() {
            const timeRange = document.getElementById('time-range').value;
            window.open(`api/export_analytics.php?range=${timeRange}&format=excel`, '_blank');
        }
    </script>
</body>
</html> 