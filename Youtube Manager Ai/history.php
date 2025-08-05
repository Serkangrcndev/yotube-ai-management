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
    <title>Video Geçmişi - YouTube Manager</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="navbar-content">
                <a href="index.php" class="navbar-brand">YouTube Manager</a>
                <ul class="navbar-nav">
                    <li><a href="dashboard.php">Panel</a></li>
                    <li><a href="upload.php">Yükle</a></li>
                    <li><a href="analytics.php">Analiz</a></li>
                    <li><a href="history.php" class="active">Geçmiş</a></li>
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
                <h1>Video Geçmişi</h1>
                <p>Yüklediğiniz tüm videoları görüntüleyin ve yönetin</p>
            </div>
            <div class="history-controls">
                <input type="text" class="form-control" placeholder="Video ara..." id="search-input">
                <select class="form-control" id="filter-status">
                    <option value="">Tüm Durumlar</option>
                    <option value="uploaded">Yüklenmiş</option>
                    <option value="processing">İşleniyor</option>
                    <option value="failed">Başarısız</option>
                </select>
                <select class="form-control" id="filter-category">
                    <option value="">Tüm Kategoriler</option>
                    <option value="1">Film ve Animasyon</option>
                    <option value="10">Müzik</option>
                    <option value="20">Oyun</option>
                    <option value="22">İnsan ve Bloglar</option>
                    <option value="24">Eğlence</option>
                    <option value="27">Eğitim</option>
                    <option value="28">Bilim ve Teknoloji</option>
                </select>
                <select class="form-control" id="sort-by">
                    <option value="date_desc">En Yeni</option>
                    <option value="date_asc">En Eski</option>
                    <option value="views_desc">En Çok İzlenen</option>
                    <option value="likes_desc">En Çok Beğenilen</option>
                    <option value="title_asc">Başlık A-Z</option>
                </select>
            </div>
        </div>

        <!-- İstatistik Özeti -->
        <div class="row mb-3">
            <div class="col-3">
                <div class="card">
                    <div class="card-body stat-card">
                        <span class="stat-value" id="total-video-count">-</span>
                        <span class="stat-label">Toplam Video</span>
                    </div>
                </div>
            </div>
            <div class="col-3">
                <div class="card">
                    <div class="card-body stat-card">
                        <span class="stat-value" id="total-views-sum">-</span>
                        <span class="stat-label">Toplam Görüntülenme</span>
                    </div>
                </div>
            </div>
            <div class="col-3">
                <div class="card">
                    <div class="card-body stat-card">
                        <span class="stat-value" id="avg-performance">-%</span>
                        <span class="stat-label">Ortalama Performans</span>
                    </div>
                </div>
            </div>
            <div class="col-3">
                <div class="card">
                    <div class="card-body stat-card">
                        <span class="stat-value" id="this-month-count">-</span>
                        <span class="stat-label">Bu Ay Yüklenen</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Görünüm Değiştirici -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-between align-center">
                    <div class="view-modes">
                        <button class="btn btn-outline active" id="grid-view-btn" onclick="switchView('grid')">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M3,3H11V11H3V3M13,3H21V11H13V3M3,13H11V21H3V13M13,13H21V21H13V13Z"/>
                            </svg>
                            Kart Görünümü
                        </button>
                        <button class="btn btn-outline" id="list-view-btn" onclick="switchView('list')">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M3,5H21V7H3V5M3,11H21V13H3V11M3,17H21V19H3V17Z"/>
                            </svg>
                            Liste Görünümü
                        </button>
                    </div>
                    <div class="results-info">
                        <span id="results-count">-</span> video gösteriliyor
                    </div>
                </div>
            </div>
        </div>

        <!-- Video Grid (Kart Görünümü) -->
        <div id="grid-view" class="video-grid" style="display: block;">
            <div class="text-center">
                <div class="spinner"></div>
                <p>Videolarınız yükleniyor...</p>
            </div>
        </div>

        <!-- Video List (Liste Görünümü) -->
        <div id="list-view" class="card" style="display: none;">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table" id="video-list-table">
                        <thead>
                            <tr>
                                <th>Video</th>
                                <th>Kategori</th>
                                <th>Yayın Tarihi</th>
                                <th>Durum</th>
                                <th>Görüntülenme</th>
                                <th>Beğeni</th>
                                <th>Yorum</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody id="video-list-tbody">
                            <!-- Dinamik olarak doldurulacak -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-between align-center">
                    <div class="pagination-info">
                        <span id="pagination-text">-</span>
                    </div>
                    <div class="pagination" id="pagination-controls">
                        <!-- Dinamik olarak doldurulacak -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Video Detay Modal -->
    <div class="modal" id="video-detail-modal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h3 class="modal-title">Video Detayları</h3>
                <button class="modal-close" onclick="app.hideModal()">&times;</button>
            </div>
            <div class="modal-body" id="video-detail-content">
                <!-- Dinamik olarak doldurulacak -->
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="app.hideModal()">Kapat</button>
                <button class="btn btn-success" onclick="openYouTubeVideo()">YouTube'da Aç</button>
            </div>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script>
        // History sayfa JavaScript'i
        let allVideos = [];
        let filteredVideos = [];
        let currentView = 'grid';
        let currentPage = 1;
        let videosPerPage = 20;
        let selectedVideo = null;

        document.addEventListener('DOMContentLoaded', function() {
            loadVideoHistory();
            bindEventListeners();
        });

        function bindEventListeners() {
            document.getElementById('search-input').addEventListener('input', filterVideos);
            document.getElementById('filter-status').addEventListener('change', filterVideos);
            document.getElementById('filter-category').addEventListener('change', filterVideos);
            document.getElementById('sort-by').addEventListener('change', sortAndDisplay);
        }

        function loadVideoHistory() {
            const loader = app.showLoader('Video geçmişi yükleniyor...');

            fetch('api/get_video_history.php')
                .then(response => response.json())
                .then(data => {
                    app.hideLoader(loader);
                    
                    if (data.success) {
                        allVideos = data.videos;
                        filteredVideos = [...allVideos];
                        updateStats(data.stats);
                        displayVideos();
                    } else {
                        app.showError('Video geçmişi yüklenemedi: ' + data.error);
                    }
                })
                .catch(error => {
                    app.hideLoader(loader);
                    app.showError('Veri yükleme hatası: ' + error.message);
                });
        }

        function updateStats(stats) {
            document.getElementById('total-video-count').textContent = app.formatNumber(stats.totalVideos || 0);
            document.getElementById('total-views-sum').textContent = app.formatNumber(stats.totalViews || 0);
            document.getElementById('avg-performance').textContent = (stats.avgPerformance || 0).toFixed(1) + '%';
            document.getElementById('this-month-count').textContent = app.formatNumber(stats.thisMonthCount || 0);
        }

        function filterVideos() {
            const searchTerm = document.getElementById('search-input').value.toLowerCase();
            const statusFilter = document.getElementById('filter-status').value;
            const categoryFilter = document.getElementById('filter-category').value;

            filteredVideos = allVideos.filter(video => {
                const matchesSearch = video.title.toLowerCase().includes(searchTerm) ||
                                    video.description.toLowerCase().includes(searchTerm);
                const matchesStatus = !statusFilter || video.status === statusFilter;
                const matchesCategory = !categoryFilter || video.category_id == categoryFilter;

                return matchesSearch && matchesStatus && matchesCategory;
            });

            currentPage = 1;
            sortAndDisplay();
        }

        function sortAndDisplay() {
            const sortBy = document.getElementById('sort-by').value;
            
            filteredVideos.sort((a, b) => {
                switch (sortBy) {
                    case 'date_desc':
                        return new Date(b.upload_date) - new Date(a.upload_date);
                    case 'date_asc':
                        return new Date(a.upload_date) - new Date(b.upload_date);
                    case 'views_desc':
                        return b.view_count - a.view_count;
                    case 'likes_desc':
                        return b.like_count - a.like_count;
                    case 'title_asc':
                        return a.title.localeCompare(b.title);
                    default:
                        return 0;
                }
            });

            displayVideos();
        }

        function displayVideos() {
            updateResultsCount();
            
            if (currentView === 'grid') {
                displayGridView();
            } else {
                displayListView();
            }
            
            updatePagination();
        }

        function displayGridView() {
            const container = document.getElementById('grid-view');
            const startIndex = (currentPage - 1) * videosPerPage;
            const endIndex = startIndex + videosPerPage;
            const videosToShow = filteredVideos.slice(startIndex, endIndex);

            if (videosToShow.length === 0) {
                container.innerHTML = `
                    <div class="text-center">
                        <p>Hiç video bulunamadı</p>
                        <a href="upload.php" class="btn btn-success">İlk Videoyu Yükle</a>
                    </div>
                `;
                return;
            }

            container.innerHTML = videosToShow.map(video => `
                <div class="video-card" onclick="showVideoDetail('${video.id}')">
                    <div class="video-thumbnail">
                        <img src="${video.thumbnail || 'images/default-thumb.jpg'}" 
                             alt="${video.title}">
                        <div class="video-duration">${formatDuration(video.duration)}</div>
                        <div class="video-status status-${video.status}">${getStatusText(video.status)}</div>
                    </div>
                    <div class="video-info">
                        <div class="video-title">${video.title}</div>
                        <div class="video-meta">
                            <div class="video-stats">
                                <span>${app.formatNumber(video.view_count)} görüntülenme</span>
                                <span>${app.formatNumber(video.like_count)} beğeni</span>
                            </div>
                            <div class="video-date">${app.formatDate(video.upload_date)}</div>
                        </div>
                        <div class="video-category">${getCategoryName(video.category_id)}</div>
                        <div class="video-actions" onclick="event.stopPropagation()">
                            <button class="btn btn-sm btn-outline" onclick="editVideo('${video.id}')">Düzenle</button>
                            <button class="btn btn-sm btn-outline" onclick="viewAnalytics('${video.id}')">Analiz</button>
                            <button class="btn btn-sm btn-outline" onclick="deleteVideo('${video.id}')">Sil</button>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function displayListView() {
            const tbody = document.getElementById('video-list-tbody');
            const startIndex = (currentPage - 1) * videosPerPage;
            const endIndex = startIndex + videosPerPage;
            const videosToShow = filteredVideos.slice(startIndex, endIndex);

            if (videosToShow.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center">Hiç video bulunamadı</td></tr>';
                return;
            }

            tbody.innerHTML = videosToShow.map(video => `
                <tr onclick="showVideoDetail('${video.id}')" style="cursor: pointer;">
                    <td>
                        <div class="video-list-item">
                            <img src="${video.thumbnail || 'images/default-thumb.jpg'}" 
                                 alt="${video.title}" 
                                 style="width: 80px; height: 60px; object-fit: cover; border-radius: 4px;">
                            <div style="margin-left: 10px;">
                                <div class="video-title" style="font-weight: 500;">
                                    ${video.title.length > 60 ? video.title.substring(0, 60) + '...' : video.title}
                                </div>
                                <div style="font-size: 0.75rem; color: var(--text-secondary);">
                                    ${formatDuration(video.duration)}
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>${getCategoryName(video.category_id)}</td>
                    <td>${app.formatDate(video.upload_date)}</td>
                    <td><span class="video-status status-${video.status}">${getStatusText(video.status)}</span></td>
                    <td>${app.formatNumber(video.view_count)}</td>
                    <td>${app.formatNumber(video.like_count)}</td>
                    <td>${app.formatNumber(video.comment_count)}</td>
                    <td onclick="event.stopPropagation()">
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline" onclick="editVideo('${video.id}')">Düzenle</button>
                            <button class="btn btn-sm btn-outline" onclick="viewAnalytics('${video.id}')">Analiz</button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        function switchView(view) {
            currentView = view;
            
            // Update buttons
            document.getElementById('grid-view-btn').classList.toggle('active', view === 'grid');
            document.getElementById('list-view-btn').classList.toggle('active', view === 'list');
            
            // Show/hide views
            document.getElementById('grid-view').style.display = view === 'grid' ? 'block' : 'none';
            document.getElementById('list-view').style.display = view === 'list' ? 'block' : 'none';
            
            displayVideos();
        }

        function updateResultsCount() {
            document.getElementById('results-count').textContent = app.formatNumber(filteredVideos.length);
        }

        function updatePagination() {
            const totalPages = Math.ceil(filteredVideos.length / videosPerPage);
            const startIndex = (currentPage - 1) * videosPerPage + 1;
            const endIndex = Math.min(currentPage * videosPerPage, filteredVideos.length);
            
            document.getElementById('pagination-text').textContent = 
                `${startIndex}-${endIndex} / ${filteredVideos.length} video`;

            const paginationContainer = document.getElementById('pagination-controls');
            
            if (totalPages <= 1) {
                paginationContainer.innerHTML = '';
                return;
            }

            let paginationHTML = '';
            
            // Previous button
            if (currentPage > 1) {
                paginationHTML += `<button class="btn btn-outline" onclick="changePage(${currentPage - 1})">Önceki</button>`;
            }

            // Page numbers
            const startPage = Math.max(1, currentPage - 2);
            const endPage = Math.min(totalPages, currentPage + 2);

            for (let i = startPage; i <= endPage; i++) {
                paginationHTML += `
                    <button class="btn ${i === currentPage ? 'btn-primary' : 'btn-outline'}" 
                            onclick="changePage(${i})">${i}</button>
                `;
            }

            // Next button
            if (currentPage < totalPages) {
                paginationHTML += `<button class="btn btn-outline" onclick="changePage(${currentPage + 1})">Sonraki</button>`;
            }

            paginationContainer.innerHTML = paginationHTML;
        }

        function changePage(page) {
            currentPage = page;
            displayVideos();
        }

        function showVideoDetail(videoId) {
            const video = allVideos.find(v => v.id === videoId);
            if (!video) return;

            selectedVideo = video;
            
            const content = `
                <div class="video-detail-content">
                    <div class="row">
                        <div class="col-6">
                            <img src="${video.thumbnail || 'images/default-thumb.jpg'}" 
                                 alt="${video.title}" 
                                 style="width: 100%; border-radius: 8px;">
                        </div>
                        <div class="col-6">
                            <h4>${video.title}</h4>
                            <p><strong>Kategori:</strong> ${getCategoryName(video.category_id)}</p>
                            <p><strong>Yayın Tarihi:</strong> ${app.formatDate(video.upload_date)}</p>
                            <p><strong>Süre:</strong> ${formatDuration(video.duration)}</p>
                            <p><strong>Durum:</strong> <span class="video-status status-${video.status}">${getStatusText(video.status)}</span></p>
                            
                            <div class="video-stats-detail">
                                <div class="stat-item">
                                    <span class="stat-value">${app.formatNumber(video.view_count)}</span>
                                    <span class="stat-label">Görüntülenme</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-value">${app.formatNumber(video.like_count)}</span>
                                    <span class="stat-label">Beğeni</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-value">${app.formatNumber(video.comment_count)}</span>
                                    <span class="stat-label">Yorum</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-value">${calculateEngagementRate(video).toFixed(1)}%</span>
                                    <span class="stat-label">Etkileşim</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="video-description">
                        <h5>Açıklama</h5>
                        <p>${video.description || 'Açıklama bulunmuyor'}</p>
                    </div>
                    
                    ${video.ai_analysis ? `
                        <div class="ai-analysis-summary">
                            <h5>AI Analiz Özeti</h5>
                            <div class="ai-analysis-content">
                                <!-- AI analiz sonuçları buraya -->
                            </div>
                        </div>
                    ` : ''}
                </div>
            `;

            document.getElementById('video-detail-content').innerHTML = content;
            document.getElementById('video-detail-modal').classList.add('show');
        }

        function openYouTubeVideo() {
            if (selectedVideo && selectedVideo.youtube_video_id) {
                window.open(`https://www.youtube.com/watch?v=${selectedVideo.youtube_video_id}`, '_blank');
            }
        }

        function editVideo(videoId) {
            app.showInfo('Video düzenleme özelliği geliştirme aşamasında');
        }

        function viewAnalytics(videoId) {
            window.location.href = `analytics.php?video=${videoId}`;
        }

        function deleteVideo(videoId) {
            if (confirm('Bu videoyu silmek istediğinizden emin misiniz?')) {
                const loader = app.showLoader('Video siliniyor...');
                
                fetch('api/delete_video.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ video_id: videoId })
                })
                .then(response => response.json())
                .then(data => {
                    app.hideLoader(loader);
                    
                    if (data.success) {
                        app.showSuccess('Video başarıyla silindi');
                        loadVideoHistory(); // Refresh list
                    } else {
                        app.showError('Video silinemedi: ' + data.error);
                    }
                })
                .catch(error => {
                    app.hideLoader(loader);
                    app.showError('Silme işlemi başarısız: ' + error.message);
                });
            }
        }

        // Helper functions
        function formatDuration(seconds) {
            if (!seconds) return '0:00';
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const secs = seconds % 60;
            
            if (hours > 0) {
                return `${hours}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
            }
            return `${minutes}:${secs.toString().padStart(2, '0')}`;
        }

        function getStatusText(status) {
            const statusTexts = {
                'uploaded': 'Yüklenmiş',
                'processing': 'İşleniyor',
                'failed': 'Başarısız'
            };
            return statusTexts[status] || status;
        }

        function getCategoryName(categoryId) {
            const categories = {
                '1': 'Film ve Animasyon',
                '2': 'Otomobil ve Araçlar',
                '10': 'Müzik',
                '15': 'Evcil Hayvanlar',
                '17': 'Spor',
                '19': 'Seyahat',
                '20': 'Oyun',
                '22': 'İnsan ve Bloglar',
                '23': 'Komedi',
                '24': 'Eğlence',
                '25': 'Haber ve Politika',
                '26': 'Nasıl Yapılır',
                '27': 'Eğitim',
                '28': 'Bilim ve Teknoloji'
            };
            return categories[categoryId] || 'Bilinmiyor';
        }

        function calculateEngagementRate(video) {
            if (video.view_count === 0) return 0;
            return ((video.like_count + video.comment_count) / video.view_count) * 100;
        }
    </script>
</body>
</html> 