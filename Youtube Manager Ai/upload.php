<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/youtube.php';
require_once 'includes/ai.php';

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
$ai = new AIVideoAnalyzer();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Yükle - YouTube Manager</title>
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
                    <li><a href="upload.php" class="active">Yükle</a></li>
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
            <h1>Video Yükle</h1>
            <p>Videolarınızı AI desteği ile optimize ederek YouTube'a yükleyin</p>
        </div>

        <!-- Adım İndikkatörü -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="upload-steps">
                    <div class="step active" data-step="1">
                        <div class="step-number">1</div>
                        <div class="step-title">Video Seç</div>
                    </div>
                    <div class="step" data-step="2">
                        <div class="step-number">2</div>
                        <div class="step-title">AI Analizi</div>
                    </div>
                    <div class="step" data-step="3">
                        <div class="step-number">3</div>
                        <div class="step-title">Optimize Et</div>
                    </div>
                    <div class="step" data-step="4">
                        <div class="step-number">4</div>
                        <div class="step-title">Yükle</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kanal Seçimi -->
        <div class="card mb-3" id="channel-selection">
            <div class="card-header">
                <h3>Hedef Kanal Seçin</h3>
            </div>
            <div class="card-body">
                <div class="channel-selector" id="upload-channel-selector">
                    <div class="text-center">
                        <div class="spinner"></div>
                        <p>Kanallarınız yükleniyor...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Video Yükleme Alanı -->
        <div class="card mb-3" id="upload-section">
            <div class="card-header">
                <h3>Video Dosyasını Seçin</h3>
            </div>
            <div class="card-body">
                <div class="upload-area" id="upload-area">
                    <div class="upload-content">
                        <svg class="upload-icon" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                        </svg>
                        <div class="upload-text">Videoyu buraya sürükleyin</div>
                        <div class="upload-hint">veya dosya seçmek için tıklayın</div>
                        <div class="upload-formats">
                            Desteklenen formatlar: MP4, MOV, AVI, WMV (Maksimum 2GB)
                        </div>
                    </div>
                    <input type="file" id="video-file-input" accept="video/*" style="display: none;">
                </div>
            </div>
        </div>

        <!-- Yükleme Kuyruğu -->
        <div class="card mb-3" id="upload-queue-section" style="display: none;">
            <div class="card-header">
                <h3>Yükleme Kuyruğu</h3>
            </div>
            <div class="card-body">
                <div id="upload-queue">
                    <!-- Dinamik olarak doldurulacak -->
                </div>
            </div>
        </div>

        <!-- AI Analiz Sonuçları -->
        <div class="card mb-3" id="ai-analysis-section" style="display: none;">
            <div class="card-header">
                <div class="d-flex align-center">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" style="margin-right: 0.5rem;">
                        <path d="M12,2A2,2 0 0,1 14,4C14,4.74 13.6,5.39 13,5.73V7H14A7,7 0 0,1 21,14H22A1,1 0 0,1 23,15V18A1,1 0 0,1 22,19H21V20A2,2 0 0,1 19,22H5A2,2 0 0,1 3,20V19H2A1,1 0 0,1 1,18V15A1,1 0 0,1 2,14H3A7,7 0 0,1 10,7H11V5.73C10.4,5.39 10,4.74 10,4A2,2 0 0,1 12,2M12,4A0,0 0 0,0 12,4A0,0 0 0,0 12,4M7.5,13A2.5,2.5 0 0,0 5,15.5A2.5,2.5 0 0,0 7.5,18A2.5,2.5 0 0,0 10,15.5A2.5,2.5 0 0,0 7.5,13M16.5,13A2.5,2.5 0 0,0 14,15.5A2.5,2.5 0 0,0 16.5,18A2.5,2.5 0 0,0 19,15.5A2.5,2.5 0 0,0 16.5,13Z"/>
                    </svg>
                    <h3>AI Analiz Sonuçları</h3>
                </div>
            </div>
            <div class="card-body" id="ai-analysis-content">
                <!-- Dinamik olarak doldurulacak -->
            </div>
        </div>

        <!-- Video Optimizasyon Formu -->
        <div class="card mb-3" id="optimization-section" style="display: none;">
            <div class="card-header">
                <h3>Video Detaylarını Optimize Edin</h3>
            </div>
            <div class="card-body">
                <form id="video-optimization-form">
                    <div class="row">
                        <div class="col-8">
                            <div class="form-group">
                                <label class="form-label">Video Başlığı</label>
                                <input type="text" class="form-control" id="video-title" 
                                       placeholder="AI önerilen başlık burada görünecek" maxlength="100">
                                <small class="form-text">Maksimum 100 karakter. SEO dostu olmalı.</small>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Video Açıklaması</label>
                                <textarea class="form-control" id="video-description" rows="8" 
                                          placeholder="AI önerilen açıklama burada görünecek"></textarea>
                                <small class="form-text">Hashtag'ler ve linkler dahil edilebilir.</small>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Kategori</label>
                                <select class="form-control" id="video-category">
                                    <option value="">Kategori seçin</option>
                                    <option value="1">Film ve Animasyon</option>
                                    <option value="2">Otomobil ve Araçlar</option>
                                    <option value="10">Müzik</option>
                                    <option value="15">Evcil Hayvanlar ve Hayvanlar</option>
                                    <option value="17">Spor</option>
                                    <option value="19">Seyahat ve Etkinlikler</option>
                                    <option value="20">Oyun</option>
                                    <option value="22">İnsan ve Bloglar</option>
                                    <option value="23">Komedi</option>
                                    <option value="24">Eğlence</option>
                                    <option value="25">Haber ve Politika</option>
                                    <option value="26">Nasıl Yapılır ve Stil</option>
                                    <option value="27">Eğitim</option>
                                    <option value="28">Bilim ve Teknoloji</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Görünürlük</label>
                                <select class="form-control" id="video-privacy">
                                    <option value="public">Herkese Açık</option>
                                    <option value="unlisted">Listelenmemiş</option>
                                    <option value="private">Özel</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-4">
                            <!-- AI Önerileri Sidebar -->
                            <div class="ai-suggestions-sidebar">
                                <h4>AI Önerileri</h4>
                                
                                <div class="suggestion-box">
                                    <h5>En İyi Yükleme Zamanları</h5>
                                    <div class="best-times" id="suggested-times">
                                        <!-- Dinamik olarak doldurulacak -->
                                    </div>
                                </div>

                                <div class="suggestion-box">
                                    <h5>Trending Hashtag'ler</h5>
                                    <div class="hashtag-suggestions" id="suggested-hashtags">
                                        <!-- Dinamik olarak doldurulacak -->
                                    </div>
                                </div>

                                <div class="suggestion-box">
                                    <h5>Benzer Başarılı Videolar</h5>
                                    <div class="similar-videos" id="similar-videos">
                                        <!-- Dinamik olarak doldurulacak -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="goToPreviousStep()">
                            Geri
                        </button>
                        <button type="submit" class="btn btn-success">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="margin-right: 0.5rem;">
                                <path d="M9,16V10H5L12,3L19,10H15V16H9M5,20V18H19V20H5Z"/>
                            </svg>
                            YouTube'a Yükle
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Yükleme İlerlemesi -->
        <div class="card" id="upload-progress-section" style="display: none;">
            <div class="card-header">
                <h3>Yükleniyor...</h3>
            </div>
            <div class="card-body">
                <div class="upload-progress-details">
                    <div class="progress-info">
                        <span id="upload-status">Video yükleniyor...</span>
                        <span id="upload-percentage">0%</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar progress-animated" id="main-progress-bar" style="width: 0%"></div>
                    </div>
                    <div class="progress-steps">
                        <small id="current-step-text">Dosya yükleniyor...</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script>
        // Upload sayfası özel JavaScript
        let currentStep = 1;
        let selectedChannel = null;
        let currentVideoData = null;

        // Sayfa yüklendiğinde
        document.addEventListener('DOMContentLoaded', function() {
            loadChannelsForUpload();
            initializeUploadArea();
        });

        function loadChannelsForUpload() {
            app.loadUserChannels().then(() => {
                // Kanal seçim event'ini ayarla
                setupChannelSelection();
            });
        }

        function setupChannelSelection() {
            const channelCards = document.querySelectorAll('#upload-channel-selector .channel-card');
            channelCards.forEach(card => {
                card.addEventListener('click', function() {
                    // Önceki seçimi kaldır
                    channelCards.forEach(c => c.classList.remove('selected'));
                    
                    // Yeni seçimi ayarla
                    this.classList.add('selected');
                    selectedChannel = this.dataset.channelId;
                    
                    app.showSuccess('Kanal seçildi: ' + this.querySelector('.channel-name').textContent);
                });
            });
        }

        function initializeUploadArea() {
            const uploadArea = document.getElementById('upload-area');
            const fileInput = document.getElementById('video-file-input');

            // Click to upload
            uploadArea.addEventListener('click', () => fileInput.click());

            // File selection
            fileInput.addEventListener('change', function(e) {
                if (e.target.files.length > 0) {
                    handleFileSelection(e.target.files[0]);
                }
            });

            // Drag and drop
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                uploadArea.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            uploadArea.addEventListener('dragenter', highlight);
            uploadArea.addEventListener('dragover', highlight);
            uploadArea.addEventListener('dragleave', unhighlight);
            uploadArea.addEventListener('drop', unhighlight);

            function highlight() {
                uploadArea.classList.add('drag-over');
            }

            function unhighlight() {
                uploadArea.classList.remove('drag-over');
            }

            uploadArea.addEventListener('drop', function(e) {
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    handleFileSelection(files[0]);
                }
            });
        }

        function handleFileSelection(file) {
            if (!selectedChannel) {
                app.showError('Lütfen önce bir kanal seçin');
                return;
            }

            if (!file.type.startsWith('video/')) {
                app.showError('Lütfen geçerli bir video dosyası seçin');
                return;
            }

            if (file.size > 2 * 1024 * 1024 * 1024) { // 2GB limit
                app.showError('Dosya boyutu 2GB\'ı geçemez');
                return;
            }

            currentVideoData = {
                file: file,
                name: file.name,
                size: file.size,
                channelId: selectedChannel
            };

            // Adım 2'ye geç: AI Analizi
            goToStep(2);
            startAIAnalysis(file);
        }

        function startAIAnalysis(file) {
            showAIAnalysisSection();
            
            const formData = new FormData();
            formData.append('video', file);
            formData.append('channel_id', selectedChannel);

            fetch('api/analyze_video.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayAIAnalysis(data);
                    currentVideoData.aiAnalysis = data;
                    goToStep(3);
                } else {
                    app.showError('AI analizi başarısız: ' + data.error);
                }
            })
            .catch(error => {
                app.showError('Analiz sırasında hata oluştu: ' + error.message);
            });
        }

        function showAIAnalysisSection() {
            document.getElementById('ai-analysis-section').style.display = 'block';
            document.getElementById('ai-analysis-content').innerHTML = `
                <div class="text-center">
                    <div class="spinner"></div>
                    <p>Video AI tarafından analiz ediliyor...</p>
                    <small>Bu işlem birkaç dakika sürebilir</small>
                </div>
            `;
        }

        function displayAIAnalysis(data) {
            const analysisContent = document.getElementById('ai-analysis-content');
            
            analysisContent.innerHTML = `
                <div class="ai-analysis-results">
                    <div class="row">
                        <div class="col-6">
                            <div class="analysis-item">
                                <h4>Önerilen Başlık</h4>
                                <p class="ai-suggestion-text">${data.title}</p>
                                <div class="confidence-score">Güvenilirlik: ${data.titleConfidence}%</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="analysis-item">
                                <h4>En İyi Kategori</h4>
                                <p class="ai-suggestion-text">${data.suggestedCategory}</p>
                                <div class="confidence-score">Güvenilirlik: ${data.categoryConfidence}%</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="analysis-item">
                        <h4>Önerilen Açıklama</h4>
                        <div class="description-preview">
                            ${data.description.substring(0, 200)}...
                        </div>
                        <div class="confidence-score">Güvenilirlik: ${data.descriptionConfidence}%</div>
                    </div>
                    
                    <div class="analysis-item">
                        <h4>En İyi Yükleme Zamanları</h4>
                        <div class="best-times">
                            ${data.bestTimes.map(time => `<span class="time-slot optimal">${time}:00</span>`).join('')}
                        </div>
                    </div>
                </div>
            `;

            // Form alanlarını doldur
            document.getElementById('video-title').value = data.title;
            document.getElementById('video-description').value = data.description;
            document.getElementById('video-category').value = data.categoryId;
            
            // Sidebar önerilerini güncelle
            updateSidebarSuggestions(data);
        }

        function updateSidebarSuggestions(data) {
            // En iyi zamanlar
            document.getElementById('suggested-times').innerHTML = 
                data.bestTimes.map(time => `<div class="time-suggestion">${time}:00</div>`).join('');

            // Hashtag önerileri
            document.getElementById('suggested-hashtags').innerHTML = 
                data.hashtags.map(tag => `<span class="hashtag-suggestion">#${tag}</span>`).join('');

            // Benzer videolar
            if (data.similarVideos) {
                document.getElementById('similar-videos').innerHTML = 
                    data.similarVideos.map(video => `
                        <div class="similar-video-item">
                            <div class="similar-video-title">${video.title}</div>
                            <div class="similar-video-stats">${app.formatNumber(video.views)} görüntülenme</div>
                        </div>
                    `).join('');
            }
        }

        function goToStep(step) {
            currentStep = step;
            
            // Step indicators güncelle
            document.querySelectorAll('.step').forEach((stepEl, index) => {
                stepEl.classList.toggle('active', index + 1 <= step);
                stepEl.classList.toggle('completed', index + 1 < step);
            });

            // Sections'ları göster/gizle
            switch(step) {
                case 1:
                    showSection('channel-selection');
                    showSection('upload-section');
                    hideSection('ai-analysis-section');
                    hideSection('optimization-section');
                    hideSection('upload-progress-section');
                    break;
                case 2:
                    hideSection('upload-section');
                    showSection('ai-analysis-section');
                    hideSection('optimization-section');
                    break;
                case 3:
                    showSection('optimization-section');
                    break;
                case 4:
                    hideSection('optimization-section');
                    showSection('upload-progress-section');
                    break;
            }
        }

        function showSection(sectionId) {
            document.getElementById(sectionId).style.display = 'block';
        }

        function hideSection(sectionId) {
            document.getElementById(sectionId).style.display = 'none';
        }

        function goToPreviousStep() {
            if (currentStep > 1) {
                goToStep(currentStep - 1);
            }
        }

        // Form gönderimi
        document.getElementById('video-optimization-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!currentVideoData) {
                app.showError('Video verisi bulunamadı');
                return;
            }

            goToStep(4);
            uploadVideoToYouTube();
        });

        function uploadVideoToYouTube() {
            const formData = new FormData();
            formData.append('video', currentVideoData.file);
            formData.append('title', document.getElementById('video-title').value);
            formData.append('description', document.getElementById('video-description').value);
            formData.append('category', document.getElementById('video-category').value);
            formData.append('privacy', document.getElementById('video-privacy').value);
            formData.append('channel_id', selectedChannel);

            const xhr = new XMLHttpRequest();

            // Progress tracking
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    updateUploadProgress(percentComplete, 'Video yükleniyor...');
                }
            });

            xhr.onload = function() {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        updateUploadProgress(100, 'Video başarıyla yüklendi!');
                        app.showSuccess('Video YouTube\'a başarıyla yüklendi!');
                        
                        setTimeout(() => {
                            window.location.href = 'dashboard.php';
                        }, 2000);
                    } else {
                        app.showError('Yükleme başarısız: ' + response.error);
                    }
                } catch (error) {
                    app.showError('Yanıt işlenirken hata oluştu');
                }
            };

            xhr.onerror = function() {
                app.showError('Yükleme sırasında hata oluştu');
            };

            xhr.open('POST', 'api/upload_video.php');
            xhr.send(formData);
        }

        function updateUploadProgress(percentage, statusText) {
            document.getElementById('upload-percentage').textContent = Math.round(percentage) + '%';
            document.getElementById('upload-status').textContent = statusText;
            document.getElementById('main-progress-bar').style.width = percentage + '%';
            document.getElementById('current-step-text').textContent = statusText;
        }
    </script>
</body>
</html> 