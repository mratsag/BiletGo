<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Include database connection
include '../config.php';

$user_id = $_SESSION['user_id'];
$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $full_name = trim($_POST['full_name']);
    $bio = trim($_POST['bio']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Check if email is already taken by another user
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            throw new Exception('Bu e-posta adresi başka bir kullanıcı tarafından kullanılıyor.');
        }
        
        // Check if username is already taken by another user
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $user_id]);
        if ($stmt->fetch()) {
            throw new Exception('Bu kullanıcı adı başka bir kullanıcı tarafından kullanılıyor.');
        }
        
        // If password change is requested
        if (!empty($current_password) || !empty($new_password)) {
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                throw new Exception('Şifre değiştirmek için tüm şifre alanlarını doldurun.');
            }
            
            if ($new_password !== $confirm_password) {
                throw new Exception('Yeni şifreler eşleşmiyor.');
            }
            
            if (strlen($new_password) < 6) {
                throw new Exception('Yeni şifre en az 6 karakter olmalı.');
            }
            
            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if (!password_verify($current_password, $user['password'])) {
                throw new Exception('Mevcut şifre yanlış.');
            }
            
            // Update with new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, phone = ?, full_name = ?, bio = ?, password = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$username, $email, $phone, $full_name, $bio, $hashed_password, $user_id]);
        } else {
            // Update without password change
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, phone = ?, full_name = ?, bio = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$username, $email, $phone, $full_name, $bio, $user_id]);
        }
        
        // Update session name if changed
        $_SESSION['name'] = $username;
        
        $pdo->commit();
        $message = 'Profiliniz başarıyla güncellendi!';
        $messageType = 'success';
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Fetch user data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception('Kullanıcı bulunamadı.');
    }
} catch (Exception $e) {
    die('Hata: ' . $e->getMessage());
}

// Get user statistics
try {
    // Count total orders
    $stmt = $pdo->prepare("SELECT COUNT(*) as order_count FROM orders WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $order_count = $stmt->fetch()['order_count'];
    
    // Count total tickets
    $stmt = $pdo->prepare("SELECT SUM(quantity) as ticket_count FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.user_id = ?");
    $stmt->execute([$user_id]);
    $ticket_count = $stmt->fetch()['ticket_count'] ?: 0;
    
    // Get recent orders
    $stmt = $pdo->prepare("
        SELECT o.*, COUNT(oi.id) as item_count 
        FROM orders o 
        LEFT JOIN order_items oi ON o.id = oi.order_id 
        WHERE o.user_id = ? 
        GROUP BY o.id 
        ORDER BY o.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $order_count = 0;
    $ticket_count = 0;
    $recent_orders = [];
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilim - BiletGo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles/index.css">
    <link rel="stylesheet" href="../styles/profile.css">
</head>
<body>
    <!-- Animated Background -->
    <div class="particles" id="particles"></div>

    <!-- Modern Navbar -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-ticket-alt me-2"></i>BiletGo
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">Ana Sayfa</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../events.php">Etkinlikler</a>
                    </li>
                </ul>
                
                <div class="d-flex align-items-center">
                    <div class="dropdown me-3">
                        <button class="user-dropdown-btn" type="button" id="userDropdown" data-bs-toggle="dropdown">
                            <div class="user-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <span class="user-name"><?php echo htmlspecialchars($user['username']); ?></span>
                            <i class="fas fa-chevron-down ms-2"></i>
                        </button>
                        <ul class="dropdown-menu user-dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-circle me-2"></i>Profilim</a></li>
                            <li><a class="dropdown-item" href="my-tickets.php"><i class="fas fa-ticket-alt me-2"></i>Biletlerim</a></li>
                            <li><a class="dropdown-item" href="orders.php"><i class="fas fa-shopping-bag me-2"></i>Siparişlerim</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Çıkış Yap</a></li>
                        </ul>
                    </div>
                    
                    <a class="cart-btn" href="../cart.php" style="text-decoration: none;">
                        <i class="fas fa-shopping-cart me-1"></i>
                        Sepet
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Profile Section -->
    <section class="profile-section">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="profile-card">
                        <!-- Profile Header -->
                        <div class="profile-header">
                            <div class="profile-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <h1 class="profile-name"><?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?></h1>
                            <p class="profile-email"><?php echo htmlspecialchars($user['email']); ?></p>
                        </div>

                        <!-- Statistics -->
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-shopping-bag"></i>
                                </div>
                                <div class="stat-number"><?php echo $order_count; ?></div>
                                <div class="stat-label">Toplam Sipariş</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-ticket-alt"></i>
                                </div>
                                <div class="stat-number"><?php echo $ticket_count; ?></div>
                                <div class="stat-label">Satın Alınan Bilet</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div class="stat-number">
                                    <?php 
                                    $member_since = new DateTime($user['created_at']);
                                    echo $member_since->format('M Y');
                                    ?>
                                </div>
                                <div class="stat-label">Üyelik Tarihi</div>
                            </div>
                        </div>

                        <!-- Alert Messages -->
                        <?php if ($message): ?>
                            <div class="alert alert-custom alert-<?php echo $messageType; ?>">
                                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-lg-8">
                                <!-- Profile Update Form -->
                                <div class="form-section">
                                    <h3 class="section-title">
                                        <i class="fas fa-user-edit me-2"></i>
                                        Profil Bilgilerini Güncelle
                                    </h3>
                                    
                                    <form method="POST">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Kullanıcı Adı</label>
                                                <input type="text" class="form-control" name="username" 
                                                       value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">E-posta</label>
                                                <input type="email" class="form-control" name="email" 
                                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Ad Soyad</label>
                                                <input type="text" class="form-control" name="full_name" 
                                                       value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Telefon</label>
                                                <input type="tel" class="form-control" name="phone" 
                                                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Hakkımda</label>
                                            <textarea class="form-control" name="bio" rows="3" 
                                                      placeholder="Kendiniz hakkında birkaç kelime..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                                        </div>
                                        
                                        <hr class="my-4">
                                        
                                        <h5 class="mb-3">
                                            <i class="fas fa-lock me-2"></i>
                                            Şifre Değiştir (Opsiyonel)
                                        </h5>
                                        
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Mevcut Şifre</label>
                                                <input type="password" class="form-control" name="current_password">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Yeni Şifre</label>
                                                <input type="password" class="form-control" name="new_password">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Yeni Şifre Tekrar</label>
                                                <input type="password" class="form-control" name="confirm_password">
                                            </div>
                                        </div>
                                        
                                        <div class="text-end">
                                            <button type="submit" class="btn-update">
                                                <i class="fas fa-save me-2"></i>
                                                Profili Güncelle
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            
                            <div class="col-lg-4">
                                <!-- Recent Orders -->
                                <div class="recent-orders">
                                    <h3 class="section-title">
                                        <i class="fas fa-history me-2"></i>
                                        Son Siparişler
                                    </h3>
                                    
                                    <?php if (empty($recent_orders)): ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-shopping-bag text-muted" style="font-size: 3rem; opacity: 0.3;"></i>
                                            <p class="text-muted mt-2">Henüz sipariş yok</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($recent_orders as $order): ?>
                                            <div class="order-item">
                                                <div class="order-info">
                                                    <h6>Sipariş #<?php echo $order['id']; ?></h6>
                                                    <small>
                                                        <?php 
                                                        $order_date = new DateTime($order['created_at']);
                                                        echo $order_date->format('d M Y'); 
                                                        ?>
                                                        • <?php echo $order['item_count']; ?> ürün
                                                    </small>
                                                </div>
                                                <div>
                                                    <div class="order-status status-<?php echo strtolower($order['status'] ?? 'pending'); ?>">
                                                        <?php echo ucfirst($order['status'] ?? 'Beklemede'); ?>
                                                    </div>
                                                    <small class="text-muted d-block mt-1">
                                                        ₺<?php echo number_format($order['total_amount'], 2); ?>
                                                    </small>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                        
                                        <div class="text-center mt-3">
                                            <a href="orders.php" class="btn btn-outline-primary btn-sm">
                                                Tüm Siparişleri Görüntüle
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../scripts/index.js"></script>
</body>
</html>