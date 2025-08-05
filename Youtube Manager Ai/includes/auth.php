<?php
require_once 'config/database.php';

class GoogleAuth {
    private $clientId = '' ;
    private $clientSecret = '';
    private $redirectUri = 'http://localhost/youtube-manager/callback.php';
    private $scope = 'https://www.googleapis.com/auth/youtube https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email';
    
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    /**
     * Google OAuth URL'sini oluştur
     */
    public function getAuthUrl() {
        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => $this->scope,
            'response_type' => 'code',
            'access_type' => 'offline',
            'prompt' => 'consent'
        ];
        
        return 'https://accounts.google.com/o/oauth2/auth?' . http_build_query($params);
    }
    
    /**
     * OAuth callback'i işle
     */
    public function handleCallback($code) {
        try {
            // Access token al
            $tokenData = $this->getAccessToken($code);
            
            if (!$tokenData) {
                return false;
            }
            
            // Kullanıcı bilgilerini al
            $userInfo = $this->getUserInfo($tokenData['access_token']);
            
            if (!$userInfo) {
                return false;
            }
            
            // YouTube kanallarını al
            $channels = $this->getYouTubeChannels($tokenData['access_token']);
            
            // Kullanıcıyı veritabanına kaydet/güncelle
            $userId = $this->saveOrUpdateUser($userInfo, $tokenData, $channels);
            
            if ($userId) {
                // Session'a kaydet
                $_SESSION['user_id'] = $userId;
                $_SESSION['access_token'] = $tokenData['access_token'];
                $_SESSION['refresh_token'] = $tokenData['refresh_token'] ?? null;
                $_SESSION['token_expires'] = time() + ($tokenData['expires_in'] ?? 3600);
                
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log('OAuth Callback Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Authorization code ile access token al
     */
    private function getAccessToken($code) {
        $data = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'grant_type' => 'authorization_code',
            'code' => $code
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            return json_decode($response, true);
        }
        
        return false;
    }
    
    /**
     * Access token ile kullanıcı bilgilerini al
     */
    private function getUserInfo($accessToken) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://www.googleapis.com/oauth2/v2/userinfo');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            return json_decode($response, true);
        }
        
        return false;
    }
    
    /**
     * YouTube kanallarını al
     */
    private function getYouTubeChannels($accessToken) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://www.googleapis.com/youtube/v3/channels?part=snippet,statistics&mine=true');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            return $data['items'] ?? [];
        }
        
        return [];
    }
    
    /**
     * Kullanıcıyı veritabanına kaydet veya güncelle
     */
    private function saveOrUpdateUser($userInfo, $tokenData, $channels) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO users (google_id, email, name, avatar, youtube_channels, access_token, refresh_token, token_expires_at) 
                VALUES (:google_id, :email, :name, :avatar, :channels, :access_token, :refresh_token, :expires_at)
                ON DUPLICATE KEY UPDATE 
                    email = VALUES(email),
                    name = VALUES(name),
                    avatar = VALUES(avatar),
                    youtube_channels = VALUES(youtube_channels),
                    access_token = VALUES(access_token),
                    refresh_token = VALUES(refresh_token),
                    token_expires_at = VALUES(token_expires_at),
                    updated_at = CURRENT_TIMESTAMP
            ");
            
            $expiresAt = date('Y-m-d H:i:s', time() + ($tokenData['expires_in'] ?? 3600));
            
            $stmt->execute([
                ':google_id' => $userInfo['id'],
                ':email' => $userInfo['email'],
                ':name' => $userInfo['name'],
                ':avatar' => $userInfo['picture'] ?? '',
                ':channels' => json_encode($channels),
                ':access_token' => $tokenData['access_token'],
                ':refresh_token' => $tokenData['refresh_token'] ?? null,
                ':expires_at' => $expiresAt
            ]);
            
            // User ID'yi al
            $stmt = $this->db->prepare("SELECT id FROM users WHERE google_id = :google_id");
            $stmt->execute([':google_id' => $userInfo['id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $user['id'] ?? false;
            
        } catch (PDOException $e) {
            error_log('Database Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Kullanıcının giriş yapıp yapmadığını kontrol et
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Mevcut kullanıcının bilgilerini al
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id");
            $stmt->execute([':id' => $_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // YouTube kanallarını decode et
                $user['youtube_channels'] = json_decode($user['youtube_channels'] ?? '[]', true);
                return $user;
            }
            
            return false;
            
        } catch (PDOException $e) {
            error_log('Database Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Access token'ı yenile
     */
    public function refreshAccessToken() {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $user = $this->getCurrentUser();
        
        if (!$user || empty($user['refresh_token'])) {
            return false;
        }
        
        $data = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $user['refresh_token'],
            'grant_type' => 'refresh_token'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $tokenData = json_decode($response, true);
            
            // Yeni token'ı veritabanına kaydet
            $stmt = $this->db->prepare("
                UPDATE users 
                SET access_token = :access_token, 
                    token_expires_at = :expires_at,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            
            $expiresAt = date('Y-m-d H:i:s', time() + ($tokenData['expires_in'] ?? 3600));
            
            $stmt->execute([
                ':access_token' => $tokenData['access_token'],
                ':expires_at' => $expiresAt,
                ':id' => $user['id']
            ]);
            
            // Session'ı güncelle
            $_SESSION['access_token'] = $tokenData['access_token'];
            $_SESSION['token_expires'] = time() + ($tokenData['expires_in'] ?? 3600);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Geçerli access token al (gerekirse yenile)
     */
    public function getValidAccessToken() {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        // Token süresi kontrol et
        if (isset($_SESSION['token_expires']) && $_SESSION['token_expires'] < (time() + 300)) {
            // Token 5 dakika içinde expire olacaksa yenile
            if (!$this->refreshAccessToken()) {
                return false;
            }
        }
        
        return $_SESSION['access_token'] ?? false;
    }
    
    /**
     * Kullanıcıyı çıkış yaptır
     */
    public function logout() {
        // Session'ı temizle
        unset($_SESSION['user_id']);
        unset($_SESSION['access_token']);
        unset($_SESSION['refresh_token']);
        unset($_SESSION['token_expires']);
        
        // Session'ı yok et
        session_destroy();
        
        // Ana sayfaya yönlendir
        header('Location: index.php');
        exit;
    }
    
    /**
     * API isteği yap
     */
    public function makeApiRequest($url, $method = 'GET', $data = null) {
        $accessToken = $this->getValidAccessToken();
        
        if (!$accessToken) {
            return false;
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return json_decode($response, true);
        }
        
        return false;
    }
}
?> 