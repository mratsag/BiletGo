<?php
session_start();
require_once '../config.php';

// Admin kontrolü
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('HTTP/1.0 403 Forbidden');
    exit();
}

// Event ID kontrolü
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('HTTP/1.0 404 Not Found');
    exit();
}

try {
    // Etkinlik görselini çek
    $stmt = $pdo->prepare("SELECT photo FROM events WHERE id = ? AND photo IS NOT NULL");
    $stmt->execute([$_GET['id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && $result['photo']) {
        // Görsel tipini belirle
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($result['photo']);
        
        // Cache headers
        header('Cache-Control: private, max-age=86400');
        header('Pragma: cache');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT');
        
        // Content headers
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . strlen($result['photo']));
        
        // Görseli çıktıla
        echo $result['photo'];
    } else {
        // Varsayılan placeholder görsel
        header('Content-Type: image/svg+xml');
        echo '<svg width="400" height="300" xmlns="http://www.w3.org/2000/svg">
                <rect width="400" height="300" fill="#e2e8f0"/>
                <text x="50%" y="50%" text-anchor="middle" dy=".3em" fill="#94a3b8" font-family="Arial" font-size="20">
                    Görsel Yok
                </text>
              </svg>';
    }
} catch (PDOException $e) {
    header('HTTP/1.0 500 Internal Server Error');
    exit();
}
?>