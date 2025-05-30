<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
include '../config.php';

$userName = isset($_SESSION['name']) ? $_SESSION['name'] : '';
$userId = $_SESSION['user_id'];

// Get user's order history (grouped by purchase date)
$orders = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            DATE(t.purchase_date) as order_date,
            COUNT(*) as ticket_count,
            SUM(e.price) as total_amount,
            GROUP_CONCAT(e.title SEPARATOR ', ') as event_titles,
            MIN(t.purchase_date) as purchase_time
        FROM tickets t
        JOIN events e ON t.event_id = e.id
        WHERE t.user_id = ?
        GROUP BY DATE(t.purchase_date)
        ORDER BY t.purchase_date DESC
    ");
    $stmt->execute([$userId]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get detailed tickets for each order
    foreach ($orders as &$order) {
        $stmt = $pdo->prepare("
            SELECT t.*, e.title, e.location, e.date, e.time, e.price, c.name as category_name
            FROM tickets t
            JOIN events e ON t.event_id = e.id
            LEFT JOIN categories c ON e.category_id = c.id
            WHERE t.user_id = ? AND DATE(t.purchase_date) = ?
            ORDER BY t.purchase_date DESC
        ");
        $stmt->execute([$userId, $order['order_date']]);
        $order['tickets'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
}

// Sepetteki toplam ürün sayısı
$cart_count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Siparişlerim - BiletGo</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../styles/index.css">
  <style>
    .page-header {
      background: var(--gradient-1);
      padding: 6rem 0 4rem;
      color: white;
      text-align: center;
    }
    
    .order-card {
      background: var(--card-bg);
      border-radius: 20px;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      margin-bottom: 2rem;
      overflow: hidden;
      transition: all 0.3s ease;
    }
    
    .order-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
    }
    
    .order-header {
      background: var(--gradient-3);
      color: white;
      padding: 1.5rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
    }
    
    .order-info h4 {
      margin: 0;
      font-weight: 700;
    }
    
    .order-summary {
      text-align: right;
    }
    
    .order-total {
      font-size: 1.5rem;
      font-weight: 700;
      margin: 0;
    }
    
    .order-body {
      padding: 0;
    }
    
    .ticket-item {
      padding: 1.5rem;
      border-bottom: 1px solid rgba(0, 0, 0, 0.1);
      transition: all 0.2s ease;
    }
    
    .ticket-item:last-child {
      border-bottom: none;
    }
    
    .ticket-item:hover {
      background: rgba(102, 102, 241, 0.05);
    }
    
    .ticket-details {
      display: grid;
      grid-template-columns: 2fr 1fr 1fr 1fr;
      gap: 1rem;
      align-items: center;
    }
    
    .event-info h5 {
      margin: 0 0 0.5rem 0;
      color: var(--text-dark);
      font-weight: 600;
    }
    
    .event-meta {
      color: var(--text-light);
      font-size: 0.9rem;
    }
    
    .event-meta i {
      margin-right: 0.5rem;
      color: var(--primary-color);
    }
    
    .ticket-price {
      font-size: 1.2rem;
      font-weight: 700;
      color: var(--primary-color);
      text-align: center;
    }
    
    .ticket-status {
      text-align: center;
    }
    
    .status-badge {
      display: inline-block;
      padding: 0.4rem 0.8rem;
      border-radius: 50px;
      font-weight: 600;
      font-size: 0.8rem;
    }
    
    .status-active {
      background: rgba(16, 185, 129, 0.1);
      color: #10b981;
      border: 1px solid rgba(16, 185, 129, 0.2);
    }
    
    .status-expired {
      background: rgba(107, 114, 128, 0.1);
      color: #6b7280;
      border: 1px solid rgba(107, 114, 128, 0.2);
    }
    
    .ticket-actions {
      text-align: center;
    }
    
    .action-btn {
      background: var(--gradient-1);
      border: none;
      color: white;
      padding: 0.5rem 1rem;
      border-radius: 25px;
      font-size: 0.8rem;
      font-weight: 600;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-block;
    }
    
    .action-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(102, 102, 241, 0.4);
      color: white;
    }
    
    .action-btn:disabled {
      background: #6b7280;
      cursor: not-allowed;
      transform: none;
      box-shadow: none;
    }
    
    .empty-state {
      text-align: center;
      padding: 4rem 2rem;
      color: white;
    }
    
    .empty-state i {
      font-size: 4rem;
      margin-bottom: 1rem;
      opacity: 0.5;
    }
    
    .empty-state h3 {
      margin-bottom: 1rem;
      font-weight: 600;
    }
    
    .stats-row {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1rem;
      margin-bottom: 2rem;
    }
    
    .stat-card {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(20px);
      border-radius: 15px;
      padding: 1.5rem;
      text-align: center;
      color: white;
      border: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    .stat-number {
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 0.5rem;
    }
    
    .stat-label {
      opacity: 0.8;
      font-size: 0.9rem;
    }
    
    @media (max-width: 768px) {
      .ticket-details {
        grid-template-columns: 1fr;
        gap: 1rem;
        text-align: center;
      }
      
      .order-header {
        text-align: center;
      }
      
      .order-summary {
        text-align: center;
        margin-top: 1rem;
      }
    }
  </style>
</head>
<body>
  <!-- Refresh Button -->
  <button class="refresh-btn" onclick="location.reload()" title="Sayfayı Yenile">
    <i class="fas fa-sync-alt"></i>
  </button>

  <!-- Animated Background -->
  <div class="particles" id="particles"></div>

  <!-- Navbar -->
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
            <button class="user-dropdown-btn" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
              <div class="user-avatar">
                <i class="fas fa-user"></i>
              </div>
              <span class="user-name">Merhaba, <?php echo htmlspecialchars($userName); ?></span>
              <i class="fas fa-chevron-down ms-2"></i>
            </button>
            <ul class="dropdown-menu user-dropdown-menu" aria-labelledby="userDropdown">
              <li>
                <a class="dropdown-item" href="../users/profile.php">
                  <i class="fas fa-user-circle me-2"></i>
                  Profilim
                </a>
              </li>
              <li>
                <a class="dropdown-item" href="../users/my-tickets.php">
                  <i class="fas fa-ticket-alt me-2"></i>
                  Biletlerim
                </a>
              </li>
              <li>
                <a class="dropdown-item active" href="../users/orders.php">
                  <i class="fas fa-shopping-bag me-2"></i>
                  Siparişlerim
                </a>
              </li>
              <li><hr class="dropdown-divider"></li>
              <li>
                <a class="dropdown-item text-danger" href="../users/logout.php">
                  <i class="fas fa-sign-out-alt me-2"></i>
                  Çıkış Yap
                </a>
              </li>
            </ul>
          </div>
          
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

  <!-- Page Header -->
  <section class="page-header">
    <div class="container">
      <h1><i class="fas fa-shopping-bag me-3"></i>Siparişlerim</h1>
      <p class="lead">Tüm satın alma geçmişiniz ve sipariş detayları</p>
      <small style="opacity: 0.7;">Son güncelleme: <?php echo date('d.m.Y H:i'); ?></small>
    </div>
  </section>

  <!-- Orders Section -->
  <section class="py-5" style="background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(20px); min-height: 60vh;">
    <div class="container">
      <?php if (empty($orders)): ?>
        <div class="empty-state">
          <i class="fas fa-shopping-bag"></i>
          <h3>Henüz siparişiniz bulunmuyor</h3>
          <p>İlk siparişinizi vermek için etkinlikleri keşfedin!</p>
          <a href="../events.php" class="btn btn-light btn-lg">
            <i class="fas fa-calendar me-2"></i>
            Etkinlikleri Keşfet
          </a>
        </div>
      <?php else: ?>
        <!-- Statistics -->
        <div class="stats-row">
          <div class="stat-card">
            <div class="stat-number"><?php echo count($orders); ?></div>
            <div class="stat-label">Toplam Sipariş</div>
          </div>
          <div class="stat-card">
            <div class="stat-number"><?php echo array_sum(array_column($orders, 'ticket_count')); ?></div>
            <div class="stat-label">Toplam Bilet</div>
          </div>
          <div class="stat-card">
            <div class="stat-number">₺<?php echo number_format(array_sum(array_column($orders, 'total_amount')), 2); ?></div>
            <div class="stat-label">Toplam Harcama</div>
          </div>
        </div>

        <!-- Orders List -->
        <?php foreach ($orders as $order): ?>
          <div class="order-card">
            <div class="order-header">
              <div class="order-info">
                <h4>
                  <i class="fas fa-calendar me-2"></i>
                  <?php 
                    $orderDate = new DateTime($order['purchase_time']);
                    echo $orderDate->format('d M Y - H:i'); 
                  ?>
                </h4>
                <small><?php echo $order['ticket_count']; ?> bilet satın alındı</small>
              </div>
              <div class="order-summary">
                <div class="order-total">₺<?php echo number_format($order['total_amount'], 2); ?></div>
                <small>Toplam Tutar</small>
              </div>
            </div>
            
            <div class="order-body">
              <?php if (!empty($order['tickets'])): ?>
                <?php foreach ($order['tickets'] as $ticket): ?>
                  <div class="ticket-item">
                    <div class="ticket-details">
                      <div class="event-info">
                        <h5><?php echo htmlspecialchars($ticket['title']); ?></h5>
                        <div class="event-meta">
                          <div><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($ticket['location']); ?></div>
                          <div><i class="fas fa-clock"></i> <?php echo htmlspecialchars($ticket['date'] . ' - ' . $ticket['time']); ?></div>
                          <div><i class="fas fa-tag"></i> <?php echo htmlspecialchars($ticket['category_name']); ?></div>
                          <?php if (isset($ticket['quantity']) && $ticket['quantity'] > 1): ?>
                            <div><i class="fas fa-ticket-alt"></i> <?php echo $ticket['quantity']; ?> adet</div>
                          <?php endif; ?>
                        </div>
                      </div>
                      <div class="ticket-price">₺<?php echo number_format($ticket['price'], 2); ?></div>
                      <div class="ticket-status">
                        <?php
                          $now = new DateTime();
                          $eventDate = new DateTime($ticket['date'] . ' ' . $ticket['time']);
                          $isActive = $eventDate > $now;
                        ?>
                        <span class="status-badge <?php echo $isActive ? 'status-active' : 'status-expired'; ?>">
                          <?php echo $isActive ? 'Aktif' : 'Geçmiş'; ?>
                        </span>
                      </div>
                      <div class="ticket-actions">
                        <?php if ($isActive): ?>
                          <a href="ticket-details.php?id=<?php echo $ticket['id']; ?>" class="action-btn">Bileti Gör</a>
                        <?php else: ?>
                          <button class="action-btn" disabled>Etkinlik Geçti</button>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="ticket-item text-center">
                  <p class="text-muted">Bu sipariş için bilet bilgileri yüklenemedi.</p>
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>


  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
  <script>
    // Particles.js config
    particlesJS.load('particles', '../scripts/particles.json', function() {
      console.log('Particles.js loaded.');
    });
  </script>
  <script src="../scripts/index.js"></script>
</body>
</html>
