<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include '../config.php';

$userName = isset($_SESSION['name']) ? $_SESSION['name'] : '';
$userId = $_SESSION['user_id'];

// Get user's purchased tickets
$tickets = [];
try {
    $stmt = $pdo->prepare("
        SELECT t.*, e.title, e.description, e.location, e.date, e.time, e.price, c.name as category_name
        FROM tickets t
        JOIN events e ON t.event_id = e.id
        LEFT JOIN categories c ON e.category_id = c.id
        WHERE t.user_id = ?
        ORDER BY t.purchase_date DESC
    ");
    $stmt->execute([$userId]);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
  <title>Biletlerim - Biletix</title>
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
    
    .ticket-card {
      background: var(--card-bg);
      border-radius: 20px;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      margin-bottom: 2rem;
      overflow: hidden;
      transition: all 0.3s ease;
    }
    
    .ticket-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
    }
    
    .ticket-header {
      background: var(--gradient-2);
      color: white;
      padding: 1.5rem;
      position: relative;
    }
    
    .ticket-header::after {
      content: '';
      position: absolute;
      bottom: -10px;
      left: 50%;
      transform: translateX(-50%);
      width: 0;
      height: 0;
      border-left: 10px solid transparent;
      border-right: 10px solid transparent;
      border-top: 10px solid rgba(240, 147, 251, 0.8);
    }
    
    .ticket-body {
      padding: 2rem;
    }
    
    .ticket-id {
      background: rgba(255, 255, 255, 0.2);
      padding: 0.3rem 0.8rem;
      border-radius: 50px;
      font-size: 0.8rem;
      font-weight: 600;
      display: inline-block;
      margin-bottom: 0.5rem;
    }
    
    .event-title {
      font-size: 1.5rem;
      font-weight: 700;
      margin-bottom: 0.5rem;
    }
    
    .ticket-info {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1rem;
      margin: 1.5rem 0;
    }
    
    .info-item {
      display: flex;
      align-items: center;
      color: var(--text-light);
    }
    
    .info-item i {
      margin-right: 0.5rem;
      color: var(--primary-color);
      width: 20px;
    }
    
    .ticket-status {
      display: inline-block;
      padding: 0.5rem 1rem;
      border-radius: 50px;
      font-weight: 600;
      font-size: 0.9rem;
    }
    
    .status-active {
      background: rgba(16, 185, 129, 0.1);
      color: #10b981;
      border: 1px solid rgba(16, 185, 129, 0.2);
    }
    
    .status-used {
      background: rgba(107, 114, 128, 0.1);
      color: #6b7280;
      border: 1px solid rgba(107, 114, 128, 0.2);
    }
    
    .status-expired {
      background: rgba(239, 68, 68, 0.1);
      color: #ef4444;
      border: 1px solid rgba(239, 68, 68, 0.2);
    }
    
    .download-btn {
      background: var(--gradient-1);
      border: none;
      color: white;
      padding: 0.8rem 1.5rem;
      border-radius: 50px;
      font-weight: 600;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-block;
    }
    
    .download-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(102, 102, 241, 0.4);
      color: white;
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
    
    .empty-state .btn {
      margin-top: 1rem;
    }
  </style>
</head>
<body>
  <!-- Animated Background -->
  <div class="particles" id="particles"></div>

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg fixed-top">
    <div class="container">
      <a class="navbar-brand" href="../index.php">
        <i class="fas fa-ticket-alt me-2"></i>Biletix
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
                <a class="dropdown-item active" href="../users/my-tickets.php">
                  <i class="fas fa-ticket-alt me-2"></i>
                  Biletlerim
                </a>
              </li>
              <li>
                  <a class="dropdown-item" href="../users/orders.php">
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
          
          <a class="cart-btn" href="../cart.php" style="text-decoration: none;">
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
      <h1><i class="fas fa-ticket-alt me-3"></i>Biletlerim</h1>
      <p class="lead">Satın aldığınız tüm etkinlik biletleri</p>
    </div>
  </section>

  <!-- Tickets Section -->
  <section class="py-5" style="background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(20px); min-height: 60vh;">
    <div class="container">
      <?php if (empty($tickets)): ?>
        <div class="empty-state">
          <i class="fas fa-ticket-alt"></i>
          <h3>Henüz biletiniz bulunmuyor</h3>
          <p>Harika etkinlikleri kaçırmayın! Hemen bir bilet satın alın.</p>
          <a href="events.php" class="btn btn-light btn-lg">
            <i class="fas fa-calendar me-2"></i>
            Etkinlikleri Keşfet
          </a>
        </div>
      <?php else: ?>
        <div class="row">
          <?php foreach ($tickets as $ticket): ?>
            <div class="col-lg-6 col-xl-4">
              <div class="ticket-card">
                <div class="ticket-header">
                  <div class="ticket-id">Bilet #<?php echo str_pad($ticket['id'], 6, '0', STR_PAD_LEFT); ?></div>
                  <h3 class="event-title"><?php echo htmlspecialchars($ticket['title']); ?></h3>
                  <?php if ($ticket['category_name']): ?>
                    <small><i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($ticket['category_name']); ?></small>
                  <?php endif; ?>
                </div>
                
                <div class="ticket-body">
                  <div class="ticket-info">
                    <div class="info-item">
                      <i class="fas fa-map-marker-alt"></i>
                      <span><?php echo htmlspecialchars($ticket['location'] ?: 'Konum belirtilmemiş'); ?></span>
                    </div>
                    <div class="info-item">
                      <i class="fas fa-calendar"></i>
                      <span>
                        <?php 
                          $eventDate = new DateTime($ticket['date'] . ' ' . $ticket['time']);
                          echo $eventDate->format('d M Y, H:i'); 
                        ?>
                      </span>
                    </div>
                    <div class="info-item">
                      <i class="fas fa-money-bill-wave"></i>
                      <span>₺<?php echo number_format($ticket['price'], 2); ?></span>
                    </div>
                    <div class="info-item">
                      <i class="fas fa-shopping-cart"></i>
                      <span>
                        <?php 
                          $purchaseDate = new DateTime($ticket['purchase_date']);
                          echo $purchaseDate->format('d M Y'); 
                        ?>
                      </span>
                    </div>
                  </div>
                  
                  <?php
                    $now = new DateTime();
                    $eventDateTime = new DateTime($ticket['date'] . ' ' . $ticket['time']);
                    $status = $eventDateTime > $now ? 'active' : 'expired';
                  ?>
                  
                  <div class="d-flex justify-content-between align-items-center">
                    <span class="ticket-status status-<?php echo $status; ?>">
                      <?php echo $status === 'active' ? 'Aktif' : 'Geçmiş'; ?>
                    </span>
                    
                    <?php if ($status === 'active'): ?>
                      <a href="download-ticket.php?id=<?php echo $ticket['id']; ?>" class="download-btn">
                        <i class="fas fa-download me-2"></i>
                        İndir
                      </a>
                    <?php endif; ?>
                  </div>
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
  <script src="../scripts/index.js"></script>
</body>
</html>