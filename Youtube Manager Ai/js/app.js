/**
 * YouTube Manager - Ana JavaScript Dosyası
 * Tüm JavaScript kodları burada toplanmıştır
 */

class YouTubeManager {
    constructor() {
        this.init();
        this.bindEvents();
        this.currentVideo = null;
        this.uploadQueue = [];
        this.selectedChannel = null;
    }

    init() {
        console.log('YouTube Manager başlatılıyor...');
        this.initializeTheme();
        this.initializeComponents();
        this.checkAuth();
    }

    // Tema Yönetimi
    initializeTheme() {
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
    }

    toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
    }

    // Event Listeners
    bindEvents() {
        // Dosya yükleme
        document.addEventListener('DragEvent', this.handleDrag.bind(this));
        document.addEventListener('change', this.handleFileSelect.bind(this));
        
        // Form gönderimi
        document.addEventListener('submit', this.handleFormSubmit.bind(this));
        
        // Modal yönetimi
        document.addEventListener('click', this.handleModalClick.bind(this));
        
        // Navigasyon
        document.addEventListener('click', this.handleNavigation.bind(this));
        
        // Klavye kısayolları
        document.addEventListener('keydown', this.handleKeyboard.bind(this));
    }

    // Kimlik Doğrulama Kontrolü
    checkAuth() {
        fetch('includes/check_auth.php')
            .then(response => response.json())
            .then(data => {
                if (data.authenticated) {
                    this.showDashboard();
                    this.loadUserChannels();
                } else {
                    this.showLoginScreen();
                }
            })
            .catch(error => {
                console.error('Auth check failed:', error);
                this.showError('Kimlik doğrulama kontrolü başarısız');
            });
    }

    // Google OAuth Giriş
    initiateGoogleLogin() {
        const loader = this.showLoader('Google hesabına yönlendiriliyor...');
        
        fetch('includes/get_auth_url.php')
            .then(response => response.json())
            .then(data => {
                this.hideLoader(loader);
                if (data.auth_url) {
                    window.location.href = data.auth_url;
                } else {
                    this.showError('Giriş URL\'i alınamadı');
                }
            })
            .catch(error => {
                this.hideLoader(loader);
                this.showError('Giriş işlemi başarısız: ' + error.message);
            });
    }

    // Kullanıcı Kanallarını Yükle
    loadUserChannels() {
        fetch('includes/get_channels.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.displayChannels(data.channels);
                } else {
                    this.showError('Kanallar yüklenemedi');
                }
            })
            .catch(error => {
                this.showError('Kanal yükleme hatası: ' + error.message);
            });
    }

    // Kanalları Göster
    displayChannels(channels) {
        const container = document.getElementById('channel-selector');
        if (!container) return;

        if (channels.length === 1) {
            this.selectChannel(channels[0]);
            return;
        }

        container.innerHTML = channels.map(channel => `
            <div class="channel-card" data-channel-id="${channel.id}">
                <img src="${channel.snippet.thumbnails.default.url}" 
                     alt="${channel.snippet.title}" 
                     class="channel-avatar">
                <div class="channel-name">${channel.snippet.title}</div>
                <div class="channel-stats">
                    ${this.formatNumber(channel.statistics?.subscriberCount || 0)} abone
                </div>
            </div>
        `).join('');

        // Kanal seçimi event listener'ı
        container.addEventListener('click', (e) => {
            const channelCard = e.target.closest('.channel-card');
            if (channelCard) {
                const channelId = channelCard.dataset.channelId;
                const channel = channels.find(c => c.id === channelId);
                this.selectChannel(channel);
            }
        });
    }

    // Kanal Seçimi
    selectChannel(channel) {
        this.selectedChannel = channel;
        
        // UI güncellemesi
        document.querySelectorAll('.channel-card').forEach(card => {
            card.classList.remove('selected');
        });
        
        const selectedCard = document.querySelector(`[data-channel-id="${channel.id}"]`);
        if (selectedCard) {
            selectedCard.classList.add('selected');
        }

        // Dashboard güncelle
        this.updateDashboard();
        
        // Success mesajı
        this.showSuccess(`${channel.snippet.title} kanalı seçildi`);
    }

    // Dosya Sürükle-Bırak İşlemleri
    handleDrag(e) {
        if (!e.target.closest('.upload-area')) return;
        
        e.preventDefault();
        e.stopPropagation();

        const uploadArea = e.target.closest('.upload-area');
        
        if (e.type === 'dragenter' || e.type === 'dragover') {
            uploadArea.classList.add('drag-over');
        } else if (e.type === 'dragleave' || e.type === 'drop') {
            uploadArea.classList.remove('drag-over');
        }

        if (e.type === 'drop') {
            const files = Array.from(e.dataTransfer.files);
            this.handleFiles(files);
        }
    }

    // Dosya Seçimi
    handleFileSelect(e) {
        if (e.target.type === 'file') {
            const files = Array.from(e.target.files);
            this.handleFiles(files);
        }
    }

    // Dosya İşleme
    handleFiles(files) {
        const videoFiles = files.filter(file => file.type.startsWith('video/'));
        
        if (videoFiles.length === 0) {
            this.showError('Lütfen geçerli video dosyaları seçin');
            return;
        }

        videoFiles.forEach(file => this.addToUploadQueue(file));
    }

    // Yükleme Kuyruğuna Ekle
    addToUploadQueue(file) {
        const videoId = this.generateId();
        
        const videoItem = {
            id: videoId,
            file: file,
            name: file.name,
            size: file.size,
            status: 'pending',
            progress: 0,
            aiAnalysis: null,
            suggestedTitle: '',
            suggestedDescription: '',
            category: 'entertainment',
            bestTimes: []
        };

        this.uploadQueue.push(videoItem);
        this.renderUploadQueue();
        this.startVideoAnalysis(videoItem);
    }

    // Video Analizi Başlat
    async startVideoAnalysis(videoItem) {
        videoItem.status = 'analyzing';
        this.updateUploadItem(videoItem);

        const formData = new FormData();
        formData.append('video', videoItem.file);
        formData.append('video_id', videoItem.id);

        try {
            const response = await fetch('includes/analyze_video.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            
            if (result.success) {
                videoItem.aiAnalysis = result.analysis;
                videoItem.suggestedTitle = result.title;
                videoItem.suggestedDescription = result.description;
                videoItem.bestTimes = result.best_times;
                videoItem.status = 'ready';
                
                this.updateUploadItem(videoItem);
                this.showAIAnalysis(videoItem);
            } else {
                videoItem.status = 'error';
                this.updateUploadItem(videoItem);
                this.showError('Video analizi başarısız: ' + result.error);
            }
        } catch (error) {
            videoItem.status = 'error';
            this.updateUploadItem(videoItem);
            this.showError('Analiz hatası: ' + error.message);
        }
    }

    // AI Analizi Göster
    showAIAnalysis(videoItem) {
        const modal = this.createModal('AI Analiz Sonuçları', `
            <div class="ai-analysis-results">
                <div class="form-group">
                    <label class="form-label">AI Önerilen Başlık:</label>
                    <input type="text" class="form-control" 
                           value="${videoItem.suggestedTitle}" 
                           data-field="title" data-video-id="${videoItem.id}">
                </div>
                
                <div class="form-group">
                    <label class="form-label">AI Önerilen Açıklama:</label>
                    <textarea class="form-control" rows="6" 
                              data-field="description" data-video-id="${videoItem.id}">${videoItem.suggestedDescription}</textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Kategori:</label>
                    <select class="form-control" data-field="category" data-video-id="${videoItem.id}">
                        ${this.getCategoryOptions(videoItem.category)}
                    </select>
                </div>
                
                <div class="best-times-widget">
                    <h4>En İyi Yükleme Saatleri:</h4>
                    <div class="best-times">
                        ${videoItem.bestTimes.map(time => `
                            <div class="time-slot optimal">${time}:00</div>
                        `).join('')}
                    </div>
                </div>
            </div>
        `, [
            { text: 'İptal', class: 'btn-secondary', action: 'close' },
            { text: 'Yükle', class: 'btn btn-success', action: () => this.uploadVideo(videoItem) }
        ]);

        this.showModal(modal);
    }

    // Video Yükleme
    async uploadVideo(videoItem) {
        if (!this.selectedChannel) {
            this.showError('Lütfen önce bir kanal seçin');
            return;
        }

        videoItem.status = 'uploading';
        this.updateUploadItem(videoItem);
        this.hideModal();

        const formData = new FormData();
        formData.append('video', videoItem.file);
        formData.append('title', this.getVideoField(videoItem.id, 'title'));
        formData.append('description', this.getVideoField(videoItem.id, 'description'));
        formData.append('category', this.getVideoField(videoItem.id, 'category'));
        formData.append('channel_id', this.selectedChannel.id);

        try {
            const xhr = new XMLHttpRequest();
            
            // Progress tracking
            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    const progress = (e.loaded / e.total) * 100;
                    videoItem.progress = progress;
                    this.updateUploadItem(videoItem);
                }
            });

            xhr.onload = () => {
                const response = JSON.parse(xhr.responseText);
                
                if (response.success) {
                    videoItem.status = 'completed';
                    videoItem.youtubeId = response.video_id;
                    this.updateUploadItem(videoItem);
                    this.showSuccess('Video başarıyla yüklendi!');
                    this.refreshVideoList();
                } else {
                    videoItem.status = 'error';
                    this.updateUploadItem(videoItem);
                    this.showError('Yükleme başarısız: ' + response.error);
                }
            };

            xhr.onerror = () => {
                videoItem.status = 'error';
                this.updateUploadItem(videoItem);
                this.showError('Yükleme sırasında hata oluştu');
            };

            xhr.open('POST', 'includes/upload_video.php');
            xhr.send(formData);

        } catch (error) {
            videoItem.status = 'error';
            this.updateUploadItem(videoItem);
            this.showError('Yükleme hatası: ' + error.message);
        }
    }

    // Yükleme Kuyruğunu Render Et
    renderUploadQueue() {
        const container = document.getElementById('upload-queue');
        if (!container) return;

        container.innerHTML = this.uploadQueue.map(item => `
            <div class="upload-item" data-video-id="${item.id}">
                <div class="upload-info">
                    <div class="video-name">${item.name}</div>
                    <div class="video-size">${this.formatFileSize(item.size)}</div>
                    <div class="video-status status-${item.status}">${this.getStatusText(item.status)}</div>
                </div>
                <div class="upload-progress">
                    <div class="progress">
                        <div class="progress-bar" style="width: ${item.progress}%"></div>
                    </div>
                </div>
                <div class="upload-actions">
                    <button class="btn btn-sm btn-secondary" onclick="app.removeFromQueue('${item.id}')">
                        Kaldır
                    </button>
                </div>
            </div>
        `).join('');
    }

    // Yükleme Öğesini Güncelle
    updateUploadItem(videoItem) {
        const element = document.querySelector(`[data-video-id="${videoItem.id}"]`);
        if (!element) return;

        const statusElement = element.querySelector('.video-status');
        const progressBar = element.querySelector('.progress-bar');

        if (statusElement) {
            statusElement.textContent = this.getStatusText(videoItem.status);
            statusElement.className = `video-status status-${videoItem.status}`;
        }

        if (progressBar) {
            progressBar.style.width = `${videoItem.progress}%`;
        }
    }

    // Video Listesini Yenile
    refreshVideoList() {
        fetch('includes/get_user_videos.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.displayVideoList(data.videos);
                }
            })
            .catch(error => {
                console.error('Video list refresh failed:', error);
            });
    }

    // Video Listesini Göster
    displayVideoList(videos) {
        const container = document.getElementById('video-list');
        if (!container) return;

        container.innerHTML = videos.map(video => `
            <div class="video-card">
                <div class="video-thumbnail">
                    <img src="${video.thumbnail || 'images/default-thumb.jpg'}" alt="${video.title}">
                    <div class="video-duration">${this.formatDuration(video.duration)}</div>
                </div>
                <div class="video-info">
                    <div class="video-title">${video.title}</div>
                    <div class="video-meta">
                        <div class="video-stats">
                            <span>${this.formatNumber(video.view_count)} görüntülenme</span>
                            <span>${this.formatNumber(video.like_count)} beğeni</span>
                        </div>
                        <div class="video-date">${this.formatDate(video.upload_date)}</div>
                    </div>
                </div>
            </div>
        `).join('');
    }

    // Modal Yönetimi
    createModal(title, content, buttons = []) {
        const modalId = 'modal-' + this.generateId();
        
        const modal = document.createElement('div');
        modal.className = 'modal';
        modal.id = modalId;
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">${title}</h3>
                    <button class="modal-close" data-action="close">&times;</button>
                </div>
                <div class="modal-body">${content}</div>
                <div class="modal-footer">
                    ${buttons.map(btn => `
                        <button class="${btn.class}" data-action="${btn.action}">${btn.text}</button>
                    `).join('')}
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        return modal;
    }

    showModal(modal) {
        setTimeout(() => modal.classList.add('show'), 10);
    }

    hideModal() {
        const modal = document.querySelector('.modal.show');
        if (modal) {
            modal.classList.remove('show');
            setTimeout(() => modal.remove(), 300);
        }
    }

    // Bildirim Sistemi
    showNotification(message, type = 'info', duration = 3000) {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            min-width: 300px;
            animation: slideInRight 0.3s ease;
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, duration);
    }

    showSuccess(message) {
        this.showNotification(message, 'success');
    }

    showError(message) {
        this.showNotification(message, 'error');
    }

    showInfo(message) {
        this.showNotification(message, 'info');
    }

    // Loading Spinner
    showLoader(message = 'Yükleniyor...') {
        const loader = document.createElement('div');
        loader.className = 'loading-overlay';
        loader.innerHTML = `
            <div style="text-align: center;">
                <div class="spinner"></div>
                <div style="margin-top: 1rem;">${message}</div>
            </div>
        `;
        document.body.appendChild(loader);
        return loader;
    }

    hideLoader(loader) {
        if (loader && loader.parentNode) {
            loader.parentNode.removeChild(loader);
        }
    }

    // Yardımcı Fonksiyonlar
    generateId() {
        return Math.random().toString(36).substr(2, 9);
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    formatNumber(num) {
        if (num >= 1000000) {
            return (num / 1000000).toFixed(1) + 'M';
        } else if (num >= 1000) {
            return (num / 1000).toFixed(1) + 'K';
        }
        return num.toString();
    }

    formatDuration(seconds) {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;
        
        if (hours > 0) {
            return `${hours}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }
        return `${minutes}:${secs.toString().padStart(2, '0')}`;
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('tr-TR');
    }

    getStatusText(status) {
        const statusTexts = {
            'pending': 'Bekliyor',
            'analyzing': 'Analiz ediliyor',
            'ready': 'Hazır',
            'uploading': 'Yükleniyor',
            'completed': 'Tamamlandı',
            'error': 'Hata'
        };
        return statusTexts[status] || status;
    }

    getCategoryOptions(selected = '') {
        const categories = [
            { value: 'entertainment', text: 'Eğlence' },
            { value: 'education', text: 'Eğitim' },
            { value: 'music', text: 'Müzik' },
            { value: 'gaming', text: 'Oyun' },
            { value: 'tech', text: 'Teknoloji' },
            { value: 'sports', text: 'Spor' },
            { value: 'news', text: 'Haber' },
            { value: 'comedy', text: 'Komedi' }
        ];

        return categories.map(cat => 
            `<option value="${cat.value}" ${cat.value === selected ? 'selected' : ''}>${cat.text}</option>`
        ).join('');
    }

    getVideoField(videoId, field) {
        const element = document.querySelector(`[data-video-id="${videoId}"] [data-field="${field}"]`);
        return element ? element.value : '';
    }

    removeFromQueue(videoId) {
        this.uploadQueue = this.uploadQueue.filter(item => item.id !== videoId);
        this.renderUploadQueue();
    }

    // Dashboard güncellemeleri
    updateDashboard() {
        if (this.selectedChannel) {
            document.getElementById('selected-channel-name').textContent = this.selectedChannel.snippet.title;
            this.loadChannelAnalytics();
        }
    }

    loadChannelAnalytics() {
        // Kanal analitiklerini yükle
        fetch(`includes/get_analytics.php?channel_id=${this.selectedChannel.id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.displayAnalytics(data.analytics);
                }
            })
            .catch(error => {
                console.error('Analytics loading failed:', error);
            });
    }

    displayAnalytics(analytics) {
        // Analytics gösterimi
        const container = document.getElementById('analytics-container');
        if (container && analytics) {
            container.innerHTML = `
                <div class="row">
                    <div class="col-3">
                        <div class="stat-card">
                            <span class="stat-value">${this.formatNumber(analytics.total_views)}</span>
                            <span class="stat-label">Toplam Görüntülenme</span>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stat-card">
                            <span class="stat-value">${this.formatNumber(analytics.total_videos)}</span>
                            <span class="stat-label">Toplam Video</span>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stat-card">
                            <span class="stat-value">${this.formatNumber(analytics.total_likes)}</span>
                            <span class="stat-label">Toplam Beğeni</span>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stat-card">
                            <span class="stat-value">${analytics.avg_engagement}%</span>
                            <span class="stat-label">Ortalama Etkileşim</span>
                        </div>
                    </div>
                </div>
            `;
        }
    }

    // Form işlemleri
    handleFormSubmit(e) {
        if (e.target.classList.contains('ajax-form')) {
            e.preventDefault();
            this.submitForm(e.target);
        }
    }

    async submitForm(form) {
        const formData = new FormData(form);
        const loader = this.showLoader('İşleniyor...');

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            this.hideLoader(loader);

            if (result.success) {
                this.showSuccess(result.message || 'İşlem başarılı');
                if (result.redirect) {
                    window.location.href = result.redirect;
                }
            } else {
                this.showError(result.error || 'İşlem başarısız');
            }
        } catch (error) {
            this.hideLoader(loader);
            this.showError('Bir hata oluştu: ' + error.message);
        }
    }

    // Modal click handling
    handleModalClick(e) {
        if (e.target.classList.contains('modal')) {
            this.hideModal();
        } else if (e.target.dataset.action === 'close') {
            this.hideModal();
        } else if (e.target.dataset.action && typeof e.target.dataset.action === 'function') {
            e.target.dataset.action();
        }
    }

    // Navigation handling
    handleNavigation(e) {
        if (e.target.dataset.nav) {
            e.preventDefault();
            this.navigate(e.target.dataset.nav);
        }
    }

    navigate(page) {
        // SPA navigation
        window.history.pushState(null, '', `?page=${page}`);
        this.loadPage(page);
    }

    loadPage(page) {
        fetch(`pages/${page}.php`)
            .then(response => response.text())
            .then(html => {
                document.getElementById('main-content').innerHTML = html;
                this.initializeComponents();
            })
            .catch(error => {
                this.showError('Sayfa yüklenemedi: ' + error.message);
            });
    }

    // Klavye kısayolları
    handleKeyboard(e) {
        // Ctrl+U: Upload
        if (e.ctrlKey && e.key === 'u') {
            e.preventDefault();
            document.getElementById('file-input')?.click();
        }
        
        // Escape: Modal kapat
        if (e.key === 'Escape') {
            this.hideModal();
        }
    }

    // Component initialization
    initializeComponents() {
        // Drag & drop areas
        document.querySelectorAll('.upload-area').forEach(area => {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                area.addEventListener(eventName, this.handleDrag.bind(this), false);
            });
        });

        // File inputs
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', this.handleFileSelect.bind(this));
        });

        // Theme toggle
        const themeToggle = document.getElementById('theme-toggle');
        if (themeToggle) {
            themeToggle.addEventListener('click', this.toggleTheme.bind(this));
        }

        // Search functionality
        const searchInput = document.getElementById('search-input');
        if (searchInput) {
            searchInput.addEventListener('input', this.handleSearch.bind(this));
        }
    }

    handleSearch(e) {
        const query = e.target.value.toLowerCase();
        const videos = document.querySelectorAll('.video-card');
        
        videos.forEach(video => {
            const title = video.querySelector('.video-title').textContent.toLowerCase();
            video.style.display = title.includes(query) ? 'block' : 'none';
        });
    }

    // Login/Logout screens
    showLoginScreen() {
        document.getElementById('app-content').innerHTML = `
            <div class="login-screen">
                <div class="container">
                    <div class="text-center">
                        <h1>YouTube Manager</h1>
                        <p>YouTube videolarınızı AI destekli olarak yönetin</p>
                        <button class="btn btn-lg" onclick="app.initiateGoogleLogin()">
                            Google ile Giriş Yap
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    showDashboard() {
        // Dashboard HTML'ini yükle
        fetch('dashboard.php')
            .then(response => response.text())
            .then(html => {
                document.getElementById('app-content').innerHTML = html;
                this.initializeComponents();
            });
    }
}

// Global app instance
let app;

// DOM Ready
document.addEventListener('DOMContentLoaded', () => {
    app = new YouTubeManager();
});

// Global helper functions
function selectChannel(channelId) {
    app.selectChannel(channelId);
}

function uploadVideo(videoId) {
    app.uploadVideo(videoId);
}

function removeFromQueue(videoId) {
    app.removeFromQueue(videoId);
} 