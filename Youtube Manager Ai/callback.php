<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

$auth = new GoogleAuth();

if (isset($_GET['code'])) {
    if ($auth->handleCallback($_GET['code'])) {
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Giriş işlemi başarısız oldu.';
    }
} else {
    $error = 'Geçersiz yanıt kodu.';
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş - YouTube Manager</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container" style="margin-top: 100px;">
        <div class="text-center">
            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <a href="index.php" class="btn">Ana Sayfaya Dön</a>
            <?php else: ?>
                <div class="spinner"></div>
                <p>Giriş yapılıyor...</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 