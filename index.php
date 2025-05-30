<?php
session_start();

// Debug için - gerçek uygulamada kaldırın
error_log("Session data: " . print_r($_SESSION, true));

// Veritabanı bağlantısını dahil et
include 'config.php';

// Eğer oturumda kullanıcı bilgisi varsa, kullanıcı adını al
$userName = isset($_SESSION['name']) ? $_SESSION['name'] : '';

// Eğer kullanıcı adı boşsa ve kullanıcı ID'si varsa, veritabanından tekrar çekelim
if (empty($userName) && isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $userName = $user['username'];
            $_SESSION['name'] = $userName; // Session'ı güncelle
        }
    } catch (PDOException $e) {
        // Hata durumunda sessizce devam et
        error_log("Database error: " . $e->getMessage());
    }
}

// Etkinlikleri veritabanından çek
$events = [];
try {
    $stmt = $pdo->prepare("
        SELECT e.id, e.title, e.description, e.location, e.date, e.time, e.price,
               CASE WHEN e.photo IS NOT NULL THEN 1 ELSE 0 END as has_photo,
               c.name as category_name 
        FROM events e 
        LEFT JOIN categories c ON e.category_id = c.id 
        WHERE e.date >= CURDATE() 
        ORDER BY e.date ASC 
        LIMIT 9
    ");
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
}

// Sepet işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    $event_id = intval($_POST['event_id']);
    if (isset($_SESSION['cart'][$event_id])) {
        $_SESSION['cart'][$event_id]++;
    } else {
        $_SESSION['cart'][$event_id] = 1;
    }
    
    // AJAX response
    if (isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'cart_count' => array_sum($_SESSION['cart'])]);
        exit;
    }
}

// Sepetteki toplam ürün sayısı
$cart_count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BiletGo - Modern Etkinlik Platformu</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles/index.css">
</head>
<body>
  <!-- Animated Background -->
  <div class="particles" id="particles"></div>

  <!-- Modern Navbar -->
  <nav class="navbar navbar-expand-lg fixed-top">
    <div class="container">
      <a class="navbar-brand" href="#">
        <i class="fas fa-ticket-alt me-2"></i>BiletGo
      </a>
      
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav me-auto">
          <li class="nav-item">
            <a class="nav-link active" href="#">Ana Sayfa</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="events.php">Etkinlikler</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#upcoming">Yaklaşan</a>
          </li>
        </ul>
        
        <div class="d-flex align-items-center">
          <?php if (!empty($userName)): ?>
            <!-- User Dropdown -->
            <div class="dropdown me-3">
              <button class="user-dropdown-btn" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="user-avatar">
                  <i class="fas fa-user"></i>
                </div>
                <span class="user-name">Merhaba, <?php echo htmlspecialchars($userName); ?></span>
                <i class="fas fa-chevron-down ms-2"></i>
              </button>
              <ul class="dropdown-menu user-dropdown-menu" aria-labelledby="userDropdown">
                <li>
                  <a class="dropdown-item" href="users/profile.php">
                    <i class="fas fa-user-circle me-2"></i>
                    Profilim
                  </a>
                </li>
                <li>
                  <a class="dropdown-item" href="users/my-tickets.php">
                    <i class="fas fa-ticket-alt me-2"></i>
                    Biletlerim
                  </a>
                </li>
                <li>
                  <a class="dropdown-item" href="users/orders.php">
                    <i class="fas fa-shopping-bag me-2"></i>
                    Siparişlerim
                  </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                  <a class="dropdown-item text-danger" href="users/logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i>
                    Çıkış Yap
                  </a>
                </li>
              </ul>
            </div>
          <?php else: ?>
            <!-- Login/Register buttons for non-logged users -->
            <div class="d-flex align-items-center me-3">
              <a href="auth/login.php" class="btn btn-outline-light btn-sm me-2">
                <i class="fas fa-sign-in-alt me-1"></i>
                Giriş Yap
              </a>
              <a href="auth/register.php" class="btn btn-light btn-sm">
                <i class="fas fa-user-plus me-1"></i>
                Kayıt Ol
              </a>
            </div>
          <?php endif; ?>
          
          <a class="cart-btn" href="cart.php" style="text-decoration: none;">
            <i class="fas fa-shopping-cart me-1"></i>
            Sepet
            <?php if ($cart_count > 0): ?>
              <span class="cart-badge"><?php echo $cart_count; ?></span>
            <?php endif; ?>
          </a>
        </div>
      </div>
    </div>
  </nav>

  <!-- Hero Section -->
  <section class="hero-section">
    <div class="container">
      <h1 class="hero-title">Unutulmaz Anıları Yaşa</h1>
      <p class="hero-subtitle">
        En sevdiğin sanatçıların konserlerini kaçırma! 
        Binlerce etkinlik arasından seç, güvenli ödeme yap ve anında biletini al.
      </p>
    </div>
  </section>

  <!-- Events Section -->
  <section class="events-section" id="upcoming">
    <div class="container">
      <h2 class="section-title">
        <i class="fas fa-fire me-2"></i>Yaklaşan Etkinlikler
      </h2>
      
      <?php if (empty($events)): ?>
        <div class="empty-state">
          <i class="fas fa-calendar-times"></i>
          <h3>Henüz etkinlik bulunmuyor</h3>
          <p>Yakında harika etkinlikler eklenecek!</p>
        </div>
      <?php else: ?>
        <div class="row g-4">
          <?php foreach ($events as $event): ?>
            <div class="col-lg-4 col-md-6">
              <div class="event-card">
                <div class="event-image">
                  <?php if ($event['has_photo']): ?>
                    <img src="display_image.php?id=<?php echo $event['id']; ?>" 
                         alt="<?php echo htmlspecialchars($event['title']); ?>" 
                         class="img-fluid"
                         style="width: 100%; height: 100%; object-fit: cover;">
                  <?php else: ?>
                    <i class="fas fa-music"></i>
                  <?php endif; ?>
                </div>
                <div class="event-content">
                  <?php if ($event['category_name']): ?>
                    <span class="event-category"><?php echo htmlspecialchars($event['category_name']); ?></span>
                  <?php endif; ?>
                  
                  <h3 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                  
                  <div class="event-info">
                    <div class="mb-2">
                      <i class="fas fa-map-marker-alt me-2"></i>
                      <?php echo htmlspecialchars($event['location'] ?: 'Konum belirtilmemiş'); ?>
                    </div>
                    <?php if ($event['description']): ?>
                      <p class="mb-0"><?php echo htmlspecialchars(substr($event['description'], 0, 100)) . '...'; ?></p>
                    <?php endif; ?>
                  </div>
                  
                  <div class="event-meta">
                    <div class="event-date">
                      <i class="fas fa-calendar me-1"></i>
                      <?php 
                        $date = new DateTime($event['date'] . ' ' . $event['time']);
                        echo $date->format('d M Y, H:i'); 
                      ?>
                    </div>
                    <div class="event-price">
                      ₺<?php echo number_format($event['price'], 2); ?>
                    </div>
                  </div>
                  
                  <form class="add-to-cart-form" data-event-id="<?php echo $event['id']; ?>">
                    <button type="submit" class="buy-btn">
                      <i class="fas fa-shopping-cart me-2"></i>
                      Sepete Ekle
                    </button>
                  </form>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="scripts/index.js"></script>
</body>
</html>